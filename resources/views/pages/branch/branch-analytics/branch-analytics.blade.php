<?php

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.branch')] class extends Component
{
    public $branchId;
    public $tenantId;
    public $period = 'monthly';

    public function mount()
    {
        $user = Auth::user();
        $this->tenantId = $user->tenant_id;
        $this->branchId = $user->branch_id;
    }

    public function setPeriod($period)
    {
        $this->period = $period;
    }

    public function render()
    {
        // ⚡ Compute range once
        [$start, $end] = $this->getDateRange();

        // ⚡ Base completed-orders query — reused everywhere
        $completedBase = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end]);

        // ⚡ One query for revenue + count
        $completedStats = (clone $completedBase)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as orders_count')
            ->first();

        $totalRevenue  = (float) ($completedStats->revenue ?? 0);
        $totalOrders   = (int)   ($completedStats->orders_count ?? 0);
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // ⚡ One query for all status counts
        $statusCounts = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $pendingOrders   = (int) ($statusCounts['pending']   ?? 0);
        $preparingOrders = (int) ($statusCounts['preparing'] ?? 0);
        $readyOrders     = (int) ($statusCounts['ready']     ?? 0);

        // ⚡ One query for rating + review count
        $reviewStats = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->selectRaw('COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as cnt')
            ->first();

        $avgRating   = (float) ($reviewStats->avg_rating ?? 0);
        $reviewCount = (int)   ($reviewStats->cnt ?? 0);

        // ⚡ Single staff count
        $staffCount = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->count();

        // ⚡ Revenue timeline — HOURLY for daily, DAILY for week/month, MONTHLY for year
        $revenueTimeline = $this->buildRevenueTimeline($start, $end);

        // ⚡ Orders by category (unchanged)
        $ordersByCategory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.tenant_id', $this->tenantId)
            ->where('orders.branch_id', $this->branchId)
            ->where('products.tenant_id', $this->tenantId)
            ->where('categories.tenant_id', $this->tenantId)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select('categories.cat_name as name', DB::raw('SUM(order_items.quantity) as total'))
            ->groupBy('categories.cat_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Aggregate this branch's own catalog against its completed orders.
        $branchProductSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $this->tenantId)
            ->where('orders.branch_id', $this->branchId)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'order_items.product_id',
                DB::raw('SUM(order_items.quantity) as orders_count'),
                DB::raw('SUM(order_items.subtotal) as revenue_total')
            )
            ->groupBy('order_items.product_id');

        $topProductsRaw = Product::query()
            ->where('products.tenant_id', $this->tenantId)
            ->where('products.branch_id', $this->branchId)
            ->leftJoinSub($branchProductSales, 'branch_product_sales', function ($join) {
                $join->on('products.id', '=', 'branch_product_sales.product_id');
            })
            ->select(
                'products.*',
                DB::raw('COALESCE(branch_product_sales.orders_count, 0) as orders_count'),
                DB::raw('COALESCE(branch_product_sales.revenue_total, 0) as revenue_total')
            )
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();

        $productIds = $topProductsRaw->pluck('id');
        $stocks = Inventory::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('product_id', $productIds)
            ->pluck('quantity', 'product_id');

        $topProducts = $topProductsRaw->map(function ($product) use ($stocks) {
            $product->stock = $stocks[$product->id] ?? 0;
            return $product;
        });

        $branch = Branch::where('tenant_id', $this->tenantId)
            ->where('id', $this->branchId)
            ->first();

        return view('pages.branch.branch-analytics.branch-analytics', [
            'branch'           => $branch,
            'totalRevenue'     => $totalRevenue,
            'totalOrders'      => $totalOrders,
            'avgOrderValue'    => $avgOrderValue,
            'pendingOrders'    => $pendingOrders,
            'preparingOrders'  => $preparingOrders,
            'readyOrders'      => $readyOrders,
            'avgRating'        => $avgRating,
            'reviewCount'      => $reviewCount,
            'staffCount'       => $staffCount,
            'revenueTimeline'  => $revenueTimeline,
            'ordersByCategory' => $ordersByCategory,
            'topProducts'      => $topProducts,
            'period'           => $this->period,
        ]);
    }

    /**
     * ⚡ Builds a timeline of revenue + order counts.
     * - daily  → 24 hourly buckets (00:00 … 23:00)
     * - weekly → N daily buckets in the week
     * - monthly→ N daily buckets in the month
     * - yearly → 12 monthly buckets
     */
    private function buildRevenueTimeline($start, $end)
    {
        return match ($this->period) {
            'daily'   => $this->buildHourlyTimeline($start, $end),
            'yearly'  => $this->buildMonthlyTimeline($start, $end),
            default   => $this->buildDailyTimeline($start, $end),
        };
    }

    /**
     * ⚡ Hourly breakdown for "Today" — 24 buckets.
     */
    private function buildHourlyTimeline($start, $end)
    {
        $rows = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('HOUR(created_at) as bucket, SUM(total_amount) as total, COUNT(id) as orders_count')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $timeline = collect();
        for ($h = 0; $h < 24; $h++) {
            $row = $rows->get($h);
            $timeline->push((object) [
                'bucket'       => $h,
                'label'        => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00',
                'short_label'  => str_pad($h, 2, '0', STR_PAD_LEFT),
                'total'        => (float) ($row->total ?? 0),
                'orders_count' => (int)   ($row->orders_count ?? 0),
            ]);
        }

        return $timeline;
    }

    /**
     * ⚡ Daily breakdown — one bucket per day in the range.
     */
    private function buildDailyTimeline($start, $end)
    {
        $rows = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as bucket, SUM(total_amount) as total, COUNT(id) as orders_count')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $timeline = collect();
        $cursor = $start->copy()->startOfDay();
        $last   = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $key = $cursor->format('Y-m-d');
            $row = $rows->get($key);
            $timeline->push((object) [
                'bucket'       => $key,
                'label'        => $cursor->format('M d'),
                'short_label'  => $cursor->format('d'),
                'total'        => (float) ($row->total ?? 0),
                'orders_count' => (int)   ($row->orders_count ?? 0),
            ]);
            $cursor->addDay();
        }

        return $timeline;
    }

    /**
     * ⚡ Monthly breakdown for "Yearly" — 12 buckets.
     */
    private function buildMonthlyTimeline($start, $end)
    {
        $rows = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('MONTH(created_at) as bucket, SUM(total_amount) as total, COUNT(id) as orders_count')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');

        $timeline = collect();
        for ($m = 1; $m <= 12; $m++) {
            $row = $rows->get($m);
            $label = \Carbon\Carbon::create(null, $m, 1)->format('M');
            $timeline->push((object) [
                'bucket'       => $m,
                'label'        => $label,
                'short_label'  => $label,
                'total'        => (float) ($row->total ?? 0),
                'orders_count' => (int)   ($row->orders_count ?? 0),
            ]);
        }

        return $timeline;
    }

    /**
     * ⚡ Returns [start, end].
     */
    private function getDateRange(): array
    {
        $end = now();

        $start = match ($this->period) {
            'daily'   => now()->startOfDay(),
            'weekly'  => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            'yearly'  => now()->startOfYear(),
            default   => now()->startOfMonth(),
        };

        return [$start, $end];
    }
}
?>

<div class="analytics-root relative min-h-screen font-sans transition-colors duration-300"
     style="background-color: var(--bg-primary); color: var(--text-primary);">

    {{-- Theme styles (now INSIDE the root element) --}}
    <style>
        .analytics-root {
            --bg-primary: #f5f0e8;
            --bg-secondary: #faf6ef;
            --bg-elevated: #ffffff;
            --bg-inner: #f5f0e8;

            --text-primary: #1a3a3a;
            --text-secondary: #1a3a3a;
            --text-muted: #6b7f7a;

            --accent: #c8965f;
            --accent-warm: #d4a574;
            --accent-bright: #b8874a;
            --forest: #1a3a3a;
            --sage: #7a9a8a;

            --border-soft: rgba(26, 58, 58, 0.10);
            --border-medium: rgba(26, 58, 58, 0.20);

            --ok: #16a34a;
            --warn: #d97706;
            --bad: #dc2626;
            --info: #2563eb;
            --violet: #7c3aed;

            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .dark .analytics-root {
            --bg-primary: #0a1f1a;
            --bg-secondary: #0f2922;
            --bg-elevated: #14332c;
            --bg-inner: #0a1f1a;

            --text-primary: #f0ebe0;
            --text-secondary: #d1c8b8;
            --text-muted: #8a9a94;

            --accent: #d4a574;
            --accent-warm: #e0b585;
            --accent-bright: #c8965f;
            --forest: #f0ebe0;
            --sage: #7a9a8a;

            --border-soft: rgba(212, 165, 116, 0.12);
            --border-medium: rgba(212, 165, 116, 0.25);

            --ok: #4ade80;
            --warn: #fbbf24;
            --bad: #f87171;
            --info: #60a5fa;
            --violet: #a78bfa;
        }

        .analytics-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .analytics-root .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
    </style>

    <main class="relative z-10 p-3 sm:p-4 md:p-5 max-w-7xl mx-auto space-y-3.5">

        {{-- HEADER --}}
        <div class="card p-3 sm:p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa-solid fa-chart-pie text-sm"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black tracking-tight" style="color: var(--text-primary);">
                            Analytics Hub
                        </h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold"
                              style="background-color: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span>
                            LIVE
                        </span>
                    </div>
                    <p class="text-xs font-mono truncate" style="color: var(--text-muted);">
                        {{ $branch->name ?? 'Branch Node' }} • {{ now()->format('M d, Y') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between sm:justify-end gap-2 flex-wrap">
                <div class="inline-flex p-0.5 rounded-xl"
                     style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                    @foreach(['daily' => 'Day', 'weekly' => 'Week', 'monthly' => 'Month', 'yearly' => 'Year'] as $key => $label)
                        <button wire:click="setPeriod('{{ $key }}')"
                                class="px-2.5 py-1 text-[11px] font-bold font-mono uppercase rounded-lg transition-all"
                                style="{{ $period === $key
                                    ? 'background-color: var(--forest); color: var(--bg-primary);'
                                    : 'color: var(--text-muted);' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- KPI MATRIX --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">
            {{-- REVENUE --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="flex items-center gap-1.5 font-bold uppercase text-[10px] tracking-wider" style="color: var(--accent);">
                        <i class="fa-solid fa-wallet"></i> Revenue
                    </span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded font-bold uppercase"
                          style="background-color: var(--bg-inner); color: var(--text-muted);">{{ $period }}</span>
                </div>
                <div class="my-2 text-xl sm:text-2xl font-black font-mono tracking-tight" style="color: var(--text-primary);">
                    <span style="color: var(--accent);">₱</span>{{ number_format($totalRevenue ?? 0, 2) }}
                </div>
                <div class="text-[10px] font-mono pt-1.5 flex items-center justify-between"
                     style="color: var(--text-muted); border-top: 1px solid var(--border-soft);">
                    <span>Avg / Order:</span>
                    <strong style="color: var(--text-primary);">₱{{ number_format($avgOrderValue ?? 0, 2) }}</strong>
                </div>
            </div>

            {{-- ORDERS --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="flex items-center gap-1.5 font-bold uppercase text-[10px] tracking-wider" style="color: var(--accent);">
                        <i class="fa-solid fa-box-open"></i> Completed
                    </span>
                    <span class="text-[10px] font-bold" style="color: var(--accent);">{{ $totalOrders }} Total</span>
                </div>
                <div class="my-2 text-xl sm:text-2xl font-black font-mono tracking-tight" style="color: var(--text-primary);">
                    {{ number_format($totalOrders ?? 0) }}
                    <span class="text-xs font-normal" style="color: var(--text-muted);">orders</span>
                </div>
                <div class="text-[10px] font-mono pt-1.5 flex items-center justify-between"
                     style="border-top: 1px solid var(--border-soft);">
                    <span class="font-bold" style="color: var(--warn);">Pending: {{ $pendingOrders }}</span>
                    <span class="font-bold" style="color: var(--info);">Prep: {{ $preparingOrders }}</span>
                    <span class="font-bold" style="color: var(--ok);">Ready: {{ $readyOrders }}</span>
                </div>
            </div>

            {{-- STAFF --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="flex items-center gap-1.5 font-bold uppercase text-[10px] tracking-wider" style="color: var(--accent);">
                        <i class="fa-solid fa-users"></i> Staff & Ops
                    </span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded font-bold"
                          style="background-color: color-mix(in srgb, var(--violet) 15%, transparent); color: var(--violet);">
                        {{ $staffCount }} Active
                    </span>
                </div>
                <div class="my-2 text-xl sm:text-2xl font-black font-mono tracking-tight" style="color: var(--text-primary);">
                    ₱{{ number_format($avgOrderValue ?? 0, 2) }}
                </div>
                <div class="text-[10px] font-mono pt-1.5 flex items-center justify-between"
                     style="color: var(--text-muted); border-top: 1px solid var(--border-soft);">
                    <span>Active Queue:</span>
                    <strong style="color: var(--text-primary);">{{ $pendingOrders + $preparingOrders + $readyOrders }} orders</strong>
                </div>
            </div>

            {{-- RATING --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="flex items-center gap-1.5 font-bold uppercase text-[10px] tracking-wider" style="color: var(--accent);">
                        <i class="fa-solid fa-star"></i> Feedback Score
                    </span>
                    <span class="text-[10px] font-bold" style="color: var(--text-muted);">{{ $reviewCount }} Reviews</span>
                </div>
                <div class="my-2 flex items-center gap-2">
                    <div class="text-xl sm:text-2xl font-black font-mono tracking-tight" style="color: var(--accent);">
                        {{ number_format($avgRating ?? 0, 1) }}
                    </div>
                    <div class="flex text-xs">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fa-solid fa-star text-[11px]"
                               style="color: {{ $i <= round($avgRating ?? 0) ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 20%, transparent)' }};"></i>
                        @endfor
                    </div>
                </div>
                <div class="text-[10px] font-mono pt-1.5 flex items-center justify-between"
                     style="color: var(--text-muted); border-top: 1px solid var(--border-soft);">
                    <span>Quality Index:</span>
                    <span class="font-bold" style="color: var(--ok);">Standard Node</span>
                </div>
            </div>
        </div>

        {{-- CHARTS --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">

            {{-- LINE CHART --}}
            <div class="lg:col-span-7 card p-4 shadow-sm flex flex-col">
                @php
                    $chartTitles = [
                        'daily'   => 'Today · Hourly Revenue',
                        'weekly'  => 'This Week · Daily Revenue',
                        'monthly' => now()->format('F Y') . ' · Daily Revenue',
                        'yearly'  => now()->year . ' · Monthly Revenue',
                    ];
                @endphp
                <div class="flex items-center justify-between pb-2.5 mb-3" style="border-bottom: 1px solid var(--border-soft);">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full" style="background-color: var(--accent);"></div>
                        <h3 class="text-sm font-bold" style="color: var(--text-primary);">
                            {{ $chartTitles[$period] ?? 'Revenue & Orders Trend' }}
                        </h3>
                    </div>
                    <span class="text-[11px] font-mono font-bold" style="color: var(--text-muted);">
                        Total: ₱{{ number_format($totalRevenue ?? 0, 2) }}
                    </span>
                </div>

                @if($revenueTimeline->count() > 0 && $revenueTimeline->sum('orders_count') > 0)
                    @php
                        $maxRevenue = $revenueTimeline->max('total') ?: 1;
                        $maxOrders  = $revenueTimeline->max('orders_count') ?: 1;

                        $chartW = 700; $chartH = 200;
                        $padL = 40; $padR = 40; $padT = 20; $padB = 30;
                        $innerW = $chartW - $padL - $padR;
                        $innerH = $chartH - $padT - $padB;

                        $points = []; $orderPoints = [];
                        $count = $revenueTimeline->count();

                        foreach ($revenueTimeline as $i => $slot) {
                            $x = $count > 1
                                ? $padL + ($i / ($count - 1)) * $innerW
                                : $padL + $innerW / 2;
                            $points[] = [
                                'x' => $x,
                                'y' => $padT + $innerH - (($slot->total / $maxRevenue) * $innerH),
                                'data' => $slot,
                            ];
                            $orderPoints[] = [
                                'x' => $x,
                                'y' => $padT + $innerH - (($slot->orders_count / $maxOrders) * $innerH),
                            ];
                        }

                        $buildPath = function ($pts) {
                            $n = count($pts);
                            if ($n < 2) return '';
                            $d = 'M ' . $pts[0]['x'] . ' ' . $pts[0]['y'];
                            for ($i = 0; $i < $n - 1; $i++) {
                                $p0 = $pts[max(0, $i - 1)];
                                $p1 = $pts[$i];
                                $p2 = $pts[$i + 1];
                                $p3 = $pts[min($n - 1, $i + 2)];
                                $cp1x = $p1['x'] + ($p2['x'] - $p0['x']) / 6;
                                $cp1y = $p1['y'] + ($p2['y'] - $p0['y']) / 6;
                                $cp2x = $p2['x'] - ($p3['x'] - $p1['x']) / 6;
                                $cp2y = $p2['y'] - ($p3['y'] - $p1['y']) / 6;
                                $d .= ' C ' . $cp1x . ' ' . $cp1y . ', ' . $cp2x . ' ' . $cp2y . ', ' . $p2['x'] . ' ' . $p2['y'];
                            }
                            return $d;
                        };

                        $revPath  = $buildPath($points);
                        $ordPath  = $buildPath($orderPoints);
                        $revArea  = $revPath
                                  . ' L ' . end($points)['x'] . ' ' . ($padT + $innerH)
                                  . ' L ' . $points[0]['x'] . ' ' . ($padT + $innerH) . ' Z';

                        $gridLines = array_map(
                            fn ($g) => $padT + ($g / 4) * $innerH,
                            range(0, 4)
                        );

                        // ⚡ Label thinning for dense timelines
                        $labelEvery = match($period) {
                            'daily'   => 3,   // 24 hours → every 3rd (00, 03, 06, …)
                            'monthly' => 5,   // up to 31 days → every 5th
                            default   => 1,
                        };
                    @endphp

                    <div class="relative -mx-1">
                        <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="w-full h-52" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="revenueGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="var(--accent)" stop-opacity="0.35"/>
                                    <stop offset="100%" stop-color="var(--accent)" stop-opacity="0"/>
                                </linearGradient>
                            </defs>

                            @foreach($gridLines as $y)
                                <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $chartW - $padR }}" y2="{{ $y }}"
                                      stroke="var(--text-primary)" stroke-opacity="0.10" stroke-dasharray="3 4"/>
                            @endforeach

                            <path d="{{ $revArea }}" fill="url(#revenueGradient)"/>
                            <path d="{{ $revPath }}" fill="none" stroke="var(--accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="{{ $ordPath }}" fill="none" stroke="var(--text-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>

                            @foreach($points as $p)
                                @php $isPeak = $p['data']->total == $maxRevenue && $maxRevenue > 0; @endphp
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="{{ $isPeak ? 6 : 4 }}"
                                        fill="{{ $isPeak ? 'var(--accent)' : 'var(--bg-elevated)' }}"
                                        stroke="var(--accent)" stroke-width="2.5"/>
                            @endforeach

                            @foreach($orderPoints as $p)
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3"
                                        fill="var(--text-primary)" stroke="var(--bg-elevated)" stroke-width="1.5"/>
                            @endforeach
                        </svg>

                        <div class="flex justify-between px-10 mt-1">
                            @foreach($points as $i => $p)
                                <span class="text-[9px] font-mono" style="color: var(--text-muted);">
                                    {{ $i % $labelEvery === 0 ? $p['data']->short_label : '' }}
                                </span>
                            @endforeach
                        </div>

                        <div class="absolute inset-0 flex px-10 pointer-events-none">
                            @foreach($points as $p)
                                <div class="flex-1 relative group pointer-events-auto">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none absolute left-1/2 -translate-x-1/2 -top-2 z-20 whitespace-nowrap">
                                        <div class="px-2.5 py-1.5 rounded-lg font-mono text-[10px]"
                                             style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid var(--border-soft);">
                                            <div class="font-bold" style="color: var(--accent-warm);">₱{{ number_format($p['data']->total, 2) }}</div>
                                            <div class="opacity-70">{{ $p['data']->orders_count }} orders · {{ $p['data']->label }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-4 mt-2 pt-2" style="border-top: 1px solid var(--border-soft);">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-0.5 rounded-full" style="background-color: var(--accent);"></span>
                            <span class="text-[10px] font-mono" style="color: var(--text-muted);">Revenue (₱)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-0.5 rounded-full" style="background-color: var(--text-primary);"></span>
                            <span class="text-[10px] font-mono" style="color: var(--text-muted);">Orders (#)</span>
                        </div>
                    </div>
                @else
                    <div class="h-52 flex flex-col items-center justify-center font-mono text-xs rounded-xl"
                         style="color: var(--text-muted); border: 1px dashed var(--border-soft);">
                        <i class="fa-solid fa-chart-line text-2xl mb-2 opacity-40"></i>
                        No revenue recorded for this period
                    </div>
                @endif
            </div>

            {{-- DONUT --}}
            <div class="lg:col-span-5 card p-4 shadow-sm flex flex-col">
                <div class="flex items-center justify-between pb-2.5 mb-3" style="border-bottom: 1px solid var(--border-soft);">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full" style="background-color: var(--accent);"></div>
                        <h3 class="text-sm font-bold" style="color: var(--text-primary);">Orders by Category</h3>
                    </div>
                    <span class="text-[10px] font-mono font-bold uppercase" style="color: var(--text-muted);">{{ $period }}</span>
                </div>

                @if($ordersByCategory->count() > 0)
                    @php
                        $catTotal = $ordersByCategory->sum('total');
                        $colors = ['#c8965f', '#1a3a3a', '#a87a4a', '#8b6a45', '#6b5138'];
                        $circumference = 2 * M_PI * 60;
                        $offset = 0;
                        $segments = [];
                        foreach ($ordersByCategory as $i => $cat) {
                            $pct = $catTotal > 0 ? ($cat->total / $catTotal) : 0;
                            $dash = $pct * $circumference;
                            $segments[] = [
                                'name'   => $cat->name,
                                'total'  => $cat->total,
                                'pct'    => round($pct * 100, 1),
                                'color'  => $colors[$i % count($colors)],
                                'dash'   => $dash,
                                'offset' => -$offset,
                            ];
                            $offset += $dash;
                        }
                    @endphp

                    <div class="flex flex-col sm:flex-row items-center gap-4 flex-grow">
                        <div class="relative flex-shrink-0">
                            <svg viewBox="0 0 160 160" class="w-40 h-40">
                                <circle cx="80" cy="80" r="60" fill="none" stroke="var(--border-soft)" stroke-width="18"/>
                                @foreach($segments as $seg)
                                    <circle cx="80" cy="80" r="60" fill="none"
                                            stroke="{{ $seg['color'] }}"
                                            stroke-width="18"
                                            stroke-dasharray="{{ $seg['dash'] }} {{ $circumference }}"
                                            stroke-dashoffset="{{ $seg['offset'] }}"
                                            transform="rotate(-90 80 80)">
                                        <title>{{ $seg['name'] }}: {{ $seg['total'] }} ({{ $seg['pct'] }}%)</title>
                                    </circle>
                                @endforeach
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-xl font-black font-mono" style="color: var(--text-primary);">{{ number_format($catTotal) }}</span>
                                <span class="text-[9px] font-mono uppercase tracking-wider" style="color: var(--text-muted);">Total Orders</span>
                            </div>
                        </div>

                        <div class="flex-grow w-full space-y-2">
                            @foreach($segments as $seg)
                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background: {{ $seg['color'] }};"></span>
                                        <span class="font-bold truncate" style="color: var(--text-primary);">{{ $seg['name'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="font-mono text-[10px]" style="color: var(--text-muted);">{{ $seg['total'] }} orders</span>
                                        <span class="font-mono text-[10px] font-bold w-9 text-right" style="color: var(--text-primary);">{{ $seg['pct'] }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 p-2.5 rounded-xl flex items-center gap-2.5"
                         style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background-color: color-mix(in srgb, var(--accent) 20%, transparent);">
                            <i class="fa-solid fa-crown text-sm" style="color: var(--accent);"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-mono uppercase tracking-wider" style="color: var(--text-muted);">Top Category</p>
                            <p class="text-xs font-bold truncate" style="color: var(--text-primary);">
                                {{ $segments[0]['name'] }} is the most ordered
                            </p>
                        </div>
                    </div>
                @else
                    <div class="h-52 flex flex-col items-center justify-center font-mono text-xs rounded-xl"
                         style="color: var(--text-muted); border: 1px dashed var(--border-soft);">
                        <i class="fa-solid fa-chart-pie text-2xl mb-2 opacity-40"></i>
                        No category data available
                    </div>
                @endif
            </div>
        </div>

        {{-- TOP PRODUCTS --}}
        <div class="card p-4 shadow-sm">
            <div class="flex items-center justify-between pb-2.5 mb-3" style="border-bottom: 1px solid var(--border-soft);">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full" style="background-color: var(--accent);"></div>
                    <h3 class="text-sm font-bold" style="color: var(--text-primary);">Top 5 Products</h3>
                </div>
                <span class="text-[10px] font-mono font-bold" style="color: var(--text-muted);">By Orders</span>
            </div>

            @if($topProducts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                    @foreach($topProducts as $rank => $product)
                        @php
                            $maxOrders = $topProducts->first()->orders_count ?: 1;
                            $barPercent = ($product->orders_count / $maxOrders) * 100;
                            $rankNum = $rank + 1;
                        @endphp
                        <div class="inner p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="w-6 h-6 rounded-lg text-[11px] font-mono font-black flex items-center justify-center"
                                      style="{{ $rankNum === 1
                                          ? 'background-color: var(--accent); color: var(--bg-primary);'
                                          : ($rankNum === 2
                                              ? 'background-color: color-mix(in srgb, var(--text-primary) 20%, transparent); color: var(--text-primary);'
                                              : ($rankNum === 3
                                                  ? 'background-color: var(--accent-bright); color: var(--bg-primary);'
                                                  : 'background-color: color-mix(in srgb, var(--text-primary) 10%, transparent); color: var(--text-muted);')) }}">
                                    {{ $rankNum }}
                                </span>
                                <span class="font-mono text-[10px] font-bold" style="color: var(--accent);">{{ $product->orders_count }} sold</span>
                            </div>
                            <p class="text-xs font-bold truncate mb-2" style="color: var(--text-primary);" title="{{ $product->name }}">
                                {{ $product->name }}
                            </p>
                            <div class="w-full rounded-full h-1.5 mb-2 overflow-hidden"
                                 style="background-color: color-mix(in srgb, var(--text-primary) 10%, transparent);">
                                <div class="h-full rounded-full transition-all duration-500"
                                     style="width: {{ $barPercent }}%; background-color: var(--accent);"></div>
                            </div>
                            <div class="flex justify-between items-center text-[9px] font-mono" style="color: var(--text-muted);">
                                <span>Stock: <strong style="color: {{ ($product->stock ?? 0) <= 5 ? 'var(--bad)' : 'var(--ok)' }};">{{ $product->stock ?? 0 }}</strong></span>
                                <span class="font-bold" style="color: var(--text-primary);">₱{{ number_format($product->revenue_total ?? 0, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 font-mono text-xs" style="color: var(--text-muted);">
                    <i class="fa-solid fa-box-open text-2xl mb-2 opacity-40 block"></i>
                    No product sales recorded.
                </div>
            @endif
        </div>

    </main>
</div>
