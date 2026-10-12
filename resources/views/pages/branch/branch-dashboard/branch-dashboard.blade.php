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
    public $revenuePeriod = 'today';

    public ?int $tenantId = null;
    public ?int $branchId = null;

    public $periodRevenue = 0;
    public $revenueComparison = [];
    public $chartData = [];
    public $activeOrders = 0;
    public $lowStockCount = 0;
    public $outOfStockCount = 0;
    public $averageRating = 0;
    public $recentOrders = [];
    public $topProducts = [];
    public $recentFeedback = [];
    public $staffCount = 0;
    public $branch;

    public function mount()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $this->tenantId = $user->tenant_id;
        $this->branchId = $user->branch_id;

        if (!$this->branchId) {
            abort(403, 'No branch assigned to this user.');
        }

        $this->branch = Branch::where('tenant_id', $this->tenantId)
            ->where('id', $this->branchId)
            ->first();

        if (!$this->branch) {
            abort(404, 'Branch not found.');
        }

        $this->loadAllData();
    }

    public function setRevenuePeriod($period)
    {
        $this->revenuePeriod = $period;
        $this->loadRevenueData();
    }

    public function loadAllData()
    {
        $this->loadRevenueData();
        $this->loadStatsData();
        $this->loadOrdersData();
        $this->loadTopProducts();
        $this->loadFeedbackData();
        $this->loadStaffCount();
    }

    public function loadRevenueData()
    {
        $this->periodRevenue     = $this->getRevenueByPeriod($this->revenuePeriod);
        $this->revenueComparison = $this->getRevenueComparison($this->revenuePeriod);
        $this->chartData         = $this->getChartBreakdown($this->revenuePeriod);
    }

    public function loadStatsData()
    {
        $this->activeOrders = $this->baseOrders()
            ->whereIn('status', ['pending', 'brewing', 'preparing', 'ready'])
            ->count();

        $this->lowStockCount = Inventory::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('quantity', '<', 5)
            ->where('quantity', '>', 0)
            ->count();

        $this->outOfStockCount = Inventory::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('quantity', '=', 0)
            ->count();

        $this->averageRating = number_format(
            Review::where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->avg('rating') ?? 0,
            1
        );
    }

    public function loadOrdersData()
    {
        $this->recentOrders = $this->baseOrders()
            ->latest()
            ->take(8)
            ->get();
    }

    /**
     * Top products for THIS BRANCH.
     *
     * Each branch owns its product catalog, so only this branch's products
     * are joined to its completed order items.
     */
    public function loadTopProducts()
    {
        $this->topProducts = Product::where('products.tenant_id', $this->tenantId)
            ->where('products.branch_id', $this->branchId)
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->where('orders.branch_id', '=', $this->branchId)
                    ->where('orders.status', '=', 'completed');
            })
            ->where('products.is_active', true)
            ->select(
                'products.id',
                'products.name',
                'products.price',
                'products.tenant_id',
                'products.branch_id',
                'products.category_id',
                'products.cost',
                'products.sku',
                'products.image',
                'products.is_active',
                'products.created_at',
                'products.updated_at',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as orders_count')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.price',
                'products.tenant_id',
                'products.branch_id',
                'products.category_id',
                'products.cost',
                'products.sku',
                'products.image',
                'products.is_active',
                'products.created_at',
                'products.updated_at'
            )
            ->orderByDesc('orders_count')
            ->take(5)
            ->get()
            ->map(function ($product) {
                $inventory = Inventory::where('tenant_id', $this->tenantId)
                    ->where('branch_id', $this->branchId)
                    ->where('product_id', $product->id)
                    ->first();
                $product->stock = $inventory->quantity ?? 0;
                return $product;
            });
    }

    public function loadFeedbackData()
    {
        $this->recentFeedback = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->with('user')
            ->where('is_visible', true)
            ->latest()
            ->take(4)
            ->get();
    }

    public function loadStaffCount()
    {
        $this->staffCount = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->count();
    }

    private function baseOrders()
    {
        return Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId);
    }

    private function getRevenueByPeriod($period)
    {
        $completed = fn () => (clone $this->baseOrders())->where('status', 'completed');

        return match ($period) {
            'today' => $completed()->whereDate('created_at', today())->sum('total_amount'),
            'week'  => $completed()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount'),
            'month' => $completed()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount'),
            'year'  => $completed()->whereYear('created_at', now()->year)->sum('total_amount'),
            default => 0,
        };
    }

    private function getRevenueComparison($period)
    {
        $completed = fn () => (clone $this->baseOrders())->where('status', 'completed');

        [$current, $previous] = match ($period) {
            'today' => [
                $completed()->whereDate('created_at', today())->sum('total_amount'),
                $completed()->whereDate('created_at', today()->subDay())->sum('total_amount'),
            ],
            'week' => [
                $completed()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount'),
                $completed()->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->sum('total_amount'),
            ],
            'month' => [
                $completed()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount'),
                $completed()->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->sum('total_amount'),
            ],
            'year' => [
                $completed()->whereYear('created_at', now()->year)->sum('total_amount'),
                $completed()->whereYear('created_at', now()->subYear()->year)->sum('total_amount'),
            ],
            default => [0, 0],
        };

        $percentage = $previous > 0
            ? (($current - $previous) / $previous) * 100
            : ($current > 0 ? 100 : 0);

        return [
            'current'    => $current,
            'previous'   => $previous,
            'percentage' => round($percentage, 1),
        ];
    }

    private function getChartBreakdown(string $period): array
    {
        return match ($period) {
            'today' => $this->getHourlyBreakdown(),
            'week'  => $this->getDailyBreakdown(7),
            'month' => $this->getDailyBreakdown(now()->daysInMonth),
            'year'  => $this->getMonthlyBreakdown(),
            default => $this->getDailyBreakdown(7),
        };
    }

    private function getHourlyBreakdown(): array
    {
        $rows = (clone $this->baseOrders())
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as h, SUM(total_amount) as revenue')
            ->groupBy('h')
            ->pluck('revenue', 'h');

        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $out[] = [
                'day'     => sprintf('%02d:00', $h),
                'date'    => sprintf('%02d:00', $h),
                'revenue' => (float) ($rows[$h] ?? 0),
            ];
        }
        return $out;
    }

    private function getDailyBreakdown(int $days): array
    {
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = (clone $this->baseOrders())
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('total_amount');

            $out[] = [
                'day'     => $date->format('D'),
                'date'    => $date->format('M d'),
                'revenue' => (float) $revenue,
            ];
        }
        return $out;
    }

    private function getMonthlyBreakdown(): array
    {
        $rows = (clone $this->baseOrders())
            ->where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as m, SUM(total_amount) as revenue')
            ->groupBy('m')
            ->pluck('revenue', 'm');

        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $label = \Carbon\Carbon::create(null, $m, 1)->format('M');
            $out[] = [
                'day'     => $label,
                'date'    => $label . ' ' . now()->year,
                'revenue' => (float) ($rows[$m] ?? 0),
            ];
        }
        return $out;
    }

    public function render()
    {
        return view('pages.branch.branch-dashboard.branch-dashboard', [
            'branch'            => $this->branch,
            'periodRevenue'     => $this->periodRevenue,
            'revenueComparison' => $this->revenueComparison,
            'revenuePeriod'     => $this->revenuePeriod,
            'chartData'         => $this->chartData,
            'activeOrders'      => $this->activeOrders,
            'lowStockCount'     => $this->lowStockCount,
            'outOfStockCount'   => $this->outOfStockCount,
            'averageRating'     => $this->averageRating,
            'recentOrders'      => $this->recentOrders,
            'topProducts'       => $this->topProducts,
            'recentFeedback'    => $this->recentFeedback,
            'staffCount'        => $this->staffCount,
        ]);
    }
};
?>

<div class="relative min-h-screen transition-colors duration-300 font-sans overflow-x-hidden"
     style="background-color: var(--bg-primary); color: var(--text-primary);">

    {{-- ========================================================= --}}
    {{-- THEME + RESPONSIVE VARIABLES --}}
    {{-- ========================================================= --}}
    @once
    <style>
        :root {
            --bg-primary: #f5f0e8;
            --bg-secondary: #faf6ef;
            --bg-elevated: #ffffff;
            --bg-inner: #f5f0e8;
            --text-primary: #1a3a3a;
            --text-muted: #6b7f7a;
            --accent: #c8965f;
            --accent-warm: #d4a574;
            --accent-bright: #b8874a;
            --forest: #1a3a3a;
            --border-soft: rgba(26, 58, 58, 0.10);
            --ok: #16a34a;
            --warn: #d97706;
            --bad: #dc2626;
            --info: #2563eb;
            --violet: #7c3aed;
            --cyan: #0891b2;
        }
        .dark {
            --bg-primary: #0a1f1a;
            --bg-secondary: #0f2922;
            --bg-elevated: #14332c;
            --bg-inner: #0a1f1a;
            --text-primary: #f0ebe0;
            --text-muted: #8a9a94;
            --accent: #d4a574;
            --accent-warm: #e0b585;
            --accent-bright: #c8965f;
            --forest: #f0ebe0;
            --border-soft: rgba(212, 165, 116, 0.12);
            --ok: #4ade80;
            --warn: #fbbf24;
            --bad: #f87171;
            --info: #60a5fa;
            --violet: #a78bfa;
            --cyan: #22d3ee;
        }

        .branch-theme .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .branch-theme .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
        .branch-theme .revenue-banner {
            background-image: linear-gradient(135deg,
                color-mix(in srgb, var(--accent) 95%, white 5%) 0%,
                color-mix(in srgb, var(--accent-bright) 90%, black 10%) 55%,
                color-mix(in srgb, var(--forest) 85%, black 15%) 100%);
        }
        .dark .branch-theme .revenue-banner {
            background-image: linear-gradient(135deg,
                color-mix(in srgb, var(--accent) 90%, black 10%) 0%,
                color-mix(in srgb, var(--accent-bright) 85%, black 15%) 55%,
                color-mix(in srgb, var(--bg-secondary) 80%, black 20%) 100%);
        }
        .branch-theme .icon-chip {
            background-color: color-mix(in srgb, var(--accent) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
            color: var(--accent);
        }
        .branch-theme .pill-accent {
            background-color: color-mix(in srgb, var(--accent) 12%, transparent);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
        }
        .branch-theme .pill-ok {
            background-color: color-mix(in srgb, var(--ok) 12%, transparent);
            color: var(--ok);
            border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);
        }
        .branch-theme .pill-warn {
            background-color: color-mix(in srgb, var(--warn) 12%, transparent);
            color: var(--warn);
            border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);
        }
        .branch-theme .pill-bad {
            background-color: color-mix(in srgb, var(--bad) 12%, transparent);
            color: var(--bad);
            border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);
        }
        .branch-theme .pill-info {
            background-color: color-mix(in srgb, var(--info) 12%, transparent);
            color: var(--info);
            border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);
        }
        .branch-theme .pill-violet {
            background-color: color-mix(in srgb, var(--violet) 12%, transparent);
            color: var(--violet);
            border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent);
        }
        .branch-theme .divider { border-color: var(--border-soft); }
        .branch-theme .text-muted { color: var(--text-muted); }
        .branch-theme .text-body  { color: var(--text-primary); }

        /* ---- Responsive x-axis for chart ---- */
        .branch-theme .x-axis {
            display: flex;
            justify-content: space-between;
            padding-left: 2.5rem;
            padding-right: 2.5rem;
            margin-top: 0.25rem;
        }
        .branch-theme .x-axis > span {
            flex: 1 1 0;
            min-width: 0;
            text-align: center;
            font-size: 9px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 480px) {
            .branch-theme .x-axis { padding-left: 1.25rem; padding-right: 1.25rem; }
            .branch-theme .x-axis > span { font-size: 8px; }
        }

        /* ---- Responsive period pills ---- */
        .branch-theme .period-pills {
            display: inline-flex;
            padding: 4px;
            border-radius: 0.75rem;
            background-color: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(6px);
            max-width: 100%;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .branch-theme .period-pills::-webkit-scrollbar { display: none; }
        .branch-theme .period-pills > button {
            padding: 4px 12px;
            font-size: 11px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 700;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        @media (max-width: 480px) {
            .branch-theme .period-pills > button {
                padding: 5px 10px;
                font-size: 10px;
            }
        }

        /* ---- Responsive KPI values ---- */
        .branch-theme .kpi-value {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        @media (min-width: 640px) {
            .branch-theme .kpi-value { font-size: 1.5rem; line-height: 2rem; }
        }

        /* ---- Prevent long tables from breaking layout ---- */
        .branch-theme .scroll-x {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* ---- Truncate helpers ---- */
        .branch-theme .truncate-1 {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ---- Tooltip tap support (mobile) ---- */
        @media (hover: none) {
            .branch-theme .touch-tip {
                opacity: 0;
                transition: opacity 0.2s;
            }
            .branch-theme .touch-tip:active { opacity: 1; }
        }
    </style>
    @endonce

    {{-- Auto-refresh 30s --}}
    <div wire:poll.30s="$refresh" class="hidden"></div>

    {{-- Ambient Glow --}}
    <div class="fixed inset-0 pointer-events-none opacity-40 dark:opacity-25"
         style="background:
             radial-gradient(circle at 10% 15%, color-mix(in srgb, var(--accent) 22%, transparent), transparent 42%),
             radial-gradient(circle at 90% 15%, color-mix(in srgb, var(--ok) 18%, transparent), transparent 42%);
         ">
    </div>

    <main class="branch-theme relative z-10 p-2.5 sm:p-4 md:p-5 lg:p-6 max-w-7xl mx-auto space-y-2.5 sm:space-y-3.5">

        {{-- ========================================================= --}}
        {{-- TOP HUD --}}
        {{-- ========================================================= --}}
        <div class="card p-3 sm:p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <div class="icon-chip w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-store text-xs sm:text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-base sm:text-lg md:text-xl font-black tracking-tight text-body truncate">
                            {{ $branch->name }}
                        </h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold pill-accent flex-shrink-0">
                            {{ $branch->code ?? 'NODE-01' }}
                        </span>
                    </div>
                    <p class="text-[11px] sm:text-xs font-mono truncate text-muted">
                        Manager: {{ explode(' ', auth()->user()->name)[0] }} •
                        <span class="font-bold" style="color: var(--ok);">Live Stream Active</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                <div class="px-2.5 py-1 rounded-xl inner text-[11px] font-mono text-muted flex items-center gap-1.5">
                    <i class="fa-regular fa-clock" style="color: var(--accent);"></i>
                    <span id="system-time">--:--</span>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- REVENUE BANNER (responsive) --}}
        {{-- ========================================================= --}}
        <div class="revenue-banner p-3.5 sm:p-5 rounded-2xl text-white shadow-md relative overflow-hidden">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                {{-- Amount block --}}
                <div class="min-w-0">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-white/85 font-bold flex items-center gap-1">
                        <i class="fa-solid fa-vault"></i> Branch Revenue Velocity
                    </span>
                    <div class="flex items-baseline gap-2 mt-1 flex-wrap">
                        <span class="text-2xl xs:text-3xl sm:text-4xl font-black font-mono tracking-tight text-white leading-none">
                            ₱{{ number_format($periodRevenue, 2) }}
                        </span>
                        @if($revenueComparison['percentage'] != 0)
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-lg bg-black/25 text-white flex items-center gap-1">
                                <i class="fa-solid {{ $revenueComparison['percentage'] >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"
                                   style="color: {{ $revenueComparison['percentage'] >= 0 ? 'var(--ok)' : 'var(--bad)' }};"></i>
                                {{ abs($revenueComparison['percentage']) }}% vs prev
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Period pills --}}
                <div class="period-pills self-start sm:self-auto w-full sm:w-auto">
                    @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                        <button wire:click="setRevenuePeriod('{{ $key }}')"
                                class="{{ $revenuePeriod === $key
                                            ? 'bg-white shadow-sm'
                                            : 'text-white/80 hover:text-white hover:bg-white/10' }}"
                                @if($revenuePeriod === $key) style="color: var(--accent-bright);" @endif>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- 4 KPI CARDS --}}
        {{-- ========================================================= --}}
        <div class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3.5">

            {{-- Active Orders --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--warn);">
                        <i class="fa-solid fa-hourglass-half"></i> Pipeline
                    </span>
                    <span class="w-2 h-2 rounded-full {{ $activeOrders > 0 ? 'animate-pulse' : '' }}"
                          style="background-color: {{ $activeOrders > 0 ? 'var(--warn)' : 'var(--text-muted)' }};"></span>
                </div>
                <div class="my-1.5 font-black font-mono text-body kpi-value">
                    {{ $activeOrders }} <span class="text-xs font-normal text-muted">in queue</span>
                </div>
                <div class="text-[10px] font-mono text-muted pt-1 border-t divider flex items-center justify-between gap-2">
                    <span class="truncate-1">Pending, Brewing &amp; Ready</span>
                    <a href="{{ route('branch.analytics') }}" class="hover:underline font-bold flex-shrink-0" style="color: var(--accent);">View →</a>
                </div>
            </div>

            {{-- Stock Health --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="text-[10px] font-bold uppercase tracking-wider"
                          style="color: {{ $lowStockCount > 0 ? 'var(--bad)' : 'var(--ok)' }};">
                        <i class="fa-solid fa-boxes-stacked"></i> Stock
                    </span>
                    @if($lowStockCount > 0)
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold pill-bad">ALERT</span>
                    @endif
                </div>
                <div class="my-1.5 font-black font-mono kpi-value"
                     style="color: {{ $lowStockCount > 0 ? 'var(--bad)' : 'var(--ok)' }};">
                    {{ $lowStockCount }} <span class="text-xs font-normal text-muted">low stock</span>
                </div>
                <div class="text-[10px] font-mono text-muted pt-1 border-t divider flex items-center justify-between gap-2">
                    <span class="truncate-1">{{ $outOfStockCount > 0 ? $outOfStockCount . ' zero stock' : 'Inventory optimal' }}</span>
                    <a href="{{ route('branch.inventory') }}" class="hover:underline font-bold flex-shrink-0" style="color: var(--accent);">Restock →</a>
                </div>
            </div>

            {{-- Rating --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--accent);">
                        <i class="fa-solid fa-star"></i> Rating
                    </span>
                    <span class="text-[10px] font-bold font-mono" style="color: var(--accent);">{{ $averageRating }} ⭐</span>
                </div>
                <div class="my-1.5 flex items-center gap-1.5 font-black font-mono kpi-value" style="color: var(--accent);">
                    {{ $averageRating }}
                    <div class="flex gap-0.5">
                        @for($i=1; $i<=5; $i++)
                            <i class="fa-solid fa-star text-[9px] sm:text-[10px]"
                               style="color: {{ $i <= round($averageRating) ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 18%, transparent)' }};"></i>
                        @endfor
                    </div>
                </div>
                <div class="text-[10px] font-mono text-muted pt-1 border-t divider flex items-center justify-between gap-2">
                    <span class="truncate-1">Customer feedback</span>
                    <a href="{{ route('branch.rating') }}" class="hover:underline font-bold flex-shrink-0" style="color: var(--accent);">Reviews →</a>
                </div>
            </div>

            {{-- Crew --}}
            <div class="card p-3.5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs font-mono">
                    <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--violet);">
                        <i class="fa-solid fa-user-group"></i> Crew
                    </span>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold pill-violet">{{ $staffCount }} ROSTER</span>
                </div>
                <div class="my-1.5 font-black font-mono kpi-value" style="color: var(--violet);">
                    {{ $staffCount }} <span class="text-xs font-normal text-muted">staff</span>
                </div>
                <div class="text-[10px] font-mono pt-1 border-t divider flex items-center justify-between gap-2">
                    <span class="text-muted truncate-1">Roster active</span>
                    <a href="{{ route('branch.staff') }}" class="hover:underline font-bold flex-shrink-0" style="color: var(--accent);">Manage →</a>
                </div>
            </div>

        </div>

        {{-- ========================================================= --}}
        {{-- CHART + TOP PRODUCTS --}}
        {{-- ========================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">

            {{-- REACTIVE LINE CHART (7 cols) --}}
            <div class="lg:col-span-7 card p-3.5 sm:p-4 shadow-sm flex flex-col">

                @php
                    $chartTitles = [
                        'today' => 'Today · Hourly Revenue',
                        'week'  => 'Last 7 Days · Daily Revenue',
                        'month' => now()->format('F Y') . ' · Daily Revenue',
                        'year'  => now()->year . ' · Monthly Revenue',
                    ];
                    $chartPeakLabel = [
                        'today' => 'Peak Hour',
                        'week'  => 'Peak Day',
                        'month' => 'Peak Day',
                        'year'  => 'Peak Month',
                    ];
                    $chartPeriodLabel = [
                        'today' => 'Performance: 24h Cycle',
                        'week'  => 'Performance: 7-Day Cycle',
                        'month' => 'Performance: ' . now()->format('F Y'),
                        'year'  => 'Performance: ' . now()->year,
                    ];
                    $labelEvery = match($revenuePeriod) {
                        'today' => 3,
                        'month' => 5,
                        default => 1,
                    };

                    $chartData = $chartData ?? [];
                    $maxRev = !empty($chartData) ? (max(array_column($chartData, 'revenue')) ?: 1) : 1;

                    $chartW = 700;
                    $chartH = 200;
                    $padL = 40; $padR = 20; $padT = 20; $padB = 30;
                    $innerW = $chartW - $padL - $padR;
                    $innerH = $chartH - $padT - $padB;

                    $pts = [];
                    $count = count($chartData);

                    foreach ($chartData as $i => $day) {
                        $x = $padL + ($count > 1 ? ($i / ($count - 1)) * $innerW : $innerW / 2);
                        $y = $padT + $innerH - (($day['revenue'] / $maxRev) * $innerH);
                        $pts[] = ['x' => $x, 'y' => $y, 'day' => $day];
                    }

                    $buildPath = function ($pts) {
                        if (count($pts) < 2) return '';
                        $d = 'M ' . $pts[0]['x'] . ' ' . $pts[0]['y'];
                        for ($i = 0; $i < count($pts) - 1; $i++) {
                            $p0 = $pts[max(0, $i - 1)];
                            $p1 = $pts[$i];
                            $p2 = $pts[$i + 1];
                            $p3 = $pts[min(count($pts) - 1, $i + 2)];
                            $cp1x = $p1['x'] + ($p2['x'] - $p0['x']) / 6;
                            $cp1y = $p1['y'] + ($p2['y'] - $p0['y']) / 6;
                            $cp2x = $p2['x'] - ($p3['x'] - $p1['x']) / 6;
                            $cp2y = $p2['y'] - ($p3['y'] - $p1['y']) / 6;
                            $d .= ' C ' . $cp1x . ' ' . $cp1y . ', ' . $cp2x . ' ' . $cp2y . ', ' . $p2['x'] . ' ' . $p2['y'];
                        }
                        return $d;
                    };

                    $linePath = $buildPath($pts);
                    $areaPath = !empty($pts)
                        ? $linePath . ' L ' . end($pts)['x'] . ' ' . ($padT + $innerH) . ' L ' . $pts[0]['x'] . ' ' . ($padT + $innerH) . ' Z'
                        : '';
                @endphp

                <div class="flex items-start sm:items-center justify-between pb-2.5 mb-3 border-b divider gap-2 flex-wrap">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: var(--accent);"></div>
                        <h3 class="text-xs sm:text-sm font-bold text-body truncate">{{ $chartTitles[$revenuePeriod] ?? 'Revenue Trend' }}</h3>
                    </div>
                    <span class="text-[10px] font-mono text-muted whitespace-nowrap">
                        {{ $chartPeakLabel[$revenuePeriod] ?? 'Peak' }}:
                        ₱{{ number_format($maxRev, 2) }}
                    </span>
                </div>

                @if(empty($chartData))
                    <div class="flex-grow flex items-center justify-center py-10 sm:py-12">
                        <div class="text-center text-xs font-mono text-muted px-4">
                            <i class="fa-solid fa-chart-line text-2xl mb-2 opacity-30 block"></i>
                            No revenue data for this period.
                        </div>
                    </div>
                @else
                    <div class="relative flex-grow">
                        <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}"
                             class="w-full h-40 xs:h-44 sm:h-52"
                             preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="weeklyGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%"   stop-color="var(--accent)" stop-opacity="0.42"/>
                                    <stop offset="100%" stop-color="var(--accent)" stop-opacity="0"/>
                                </linearGradient>
                            </defs>

                            @for($g = 0; $g <= 4; $g++)
                                @php $y = $padT + ($g / 4) * $innerH; @endphp
                                <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $chartW - $padR }}" y2="{{ $y }}"
                                      stroke="currentColor" stroke-opacity="0.10" stroke-dasharray="3 4"
                                      class="text-body"/>
                            @endfor

                            <path d="{{ $areaPath }}" fill="url(#weeklyGradient)"/>
                            <path d="{{ $linePath }}" fill="none" stroke="var(--accent)" stroke-width="3"
                                  stroke-linecap="round" stroke-linejoin="round"/>

                            @foreach($pts as $p)
                                @php $isPeak = $p['day']['revenue'] == $maxRev && $maxRev > 0; @endphp
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}"
                                        r="{{ $isPeak ? 6 : 4 }}"
                                        fill="{{ $isPeak ? 'var(--accent)' : 'var(--bg-elevated)' }}"
                                        stroke="var(--accent)" stroke-width="2.5"/>
                                @if($isPeak)
                                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="10"
                                            fill="var(--accent)" fill-opacity="0.2"/>
                                @endif
                            @endforeach
                        </svg>

                        {{-- X-axis labels (thinned for dense periods) --}}
                        <div class="x-axis">
                            @foreach($pts as $i => $p)
                                <span>{{ $i % $labelEvery === 0 ? ($p['day']['day'] ?? '') : '' }}</span>
                            @endforeach
                        </div>

                        {{-- Hover / tap tooltips --}}
                        <div class="absolute inset-0 hidden sm:flex pointer-events-none"
                             style="padding-left: 2.5rem; padding-right: 2.5rem;">
                            @foreach($pts as $p)
                                <div class="flex-1 relative group pointer-events-auto">
                                    <div class="absolute inset-y-0 left-1/2 w-px bg-transparent group-hover:bg-[color-mix(in_srgb,var(--accent)_30%,transparent)] transition-colors"></div>
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none absolute left-1/2 -translate-x-1/2 -top-1 z-20 whitespace-nowrap">
                                        <div class="px-2.5 py-1 rounded-lg font-mono text-[10px] shadow-xl"
                                             style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid color-mix(in srgb, var(--accent) 50%, transparent);">
                                            <div class="font-bold" style="color: var(--accent);">₱{{ number_format($p['day']['revenue'], 2) }}</div>
                                            <div class="text-[8px] opacity-70">{{ $p['day']['date'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-2 border-t divider text-[10px] font-mono text-muted flex items-center justify-between gap-2 flex-wrap">
                    <span class="truncate">{{ $chartPeriodLabel[$revenuePeriod] ?? 'Performance' }}</span>
                    <span class="font-bold flex items-center gap-1 flex-shrink-0" style="color: var(--ok);">
                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background-color: var(--ok);"></span>
                        Synchronized
                    </span>
                </div>
            </div>

            {{-- TOP PRODUCTS (5 cols) --}}
            <div class="lg:col-span-5 card p-3.5 sm:p-4 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between pb-2.5 mb-3 border-b divider gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="fa-solid fa-trophy flex-shrink-0" style="color: var(--accent);"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-body truncate">Top Demand Items</h3>
                    </div>
                    <span class="text-[10px] font-mono text-muted flex-shrink-0">By Orders</span>
                </div>

                <div class="space-y-2 flex-grow">
                    @forelse($topProducts as $index => $product)
                        @php
                            $maxOrders = $topProducts->first()->orders_count ?: 1;
                            $barPct = ($product->orders_count / $maxOrders) * 100;
                        @endphp
                        <div class="p-2 inner">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    @php
                                        $rankStyle = match($index) {
                                            0 => 'background-color: var(--accent); color: #1a1a1a;',
                                            1 => 'background-color: color-mix(in srgb, var(--text-primary) 30%, transparent); color: var(--text-primary);',
                                            2 => 'background-color: var(--accent-bright); color: #fff;',
                                            default => 'background-color: color-mix(in srgb, var(--text-primary) 10%, transparent); color: var(--text-muted);',
                                        };
                                    @endphp
                                    <span class="w-5 h-5 rounded text-[10px] font-mono font-black flex items-center justify-center flex-shrink-0"
                                          style="{{ $rankStyle }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-body text-xs truncate">{{ $product->name }}</div>
                                        <div class="text-[9px] font-mono text-muted">Stock: {{ $product->stock ?? 0 }} units</div>
                                    </div>
                                </div>
                                <span class="font-mono font-bold ml-2 text-[11px] flex-shrink-0" style="color: var(--accent);">
                                    {{ $product->orders_count }} sold
                                </span>
                            </div>
                            <div class="w-full rounded-full h-1 mt-1.5 overflow-hidden"
                                 style="background-color: color-mix(in srgb, var(--text-primary) 10%, transparent);">
                                <div class="h-full rounded-full transition-all duration-500"
                                     style="width: {{ $barPct }}%; background-image: linear-gradient(90deg, var(--accent), var(--accent-bright));"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-xs font-mono text-muted">
                            <i class="fa-solid fa-trophy text-xl mb-2 opacity-30 block"></i>
                            No product sales yet.
                        </div>
                    @endforelse
                </div>

                <div class="pt-2 border-t divider text-[10px] font-mono text-muted flex items-center justify-between">
                    <span>Rank Matrix</span>
                    <a href="{{ route('branch.product') }}" class="hover:underline font-bold" style="color: var(--accent);">Catalog →</a>
                </div>
            </div>

        </div>

        {{-- ========================================================= --}}
        {{-- RECENT ORDERS + RECENT FEEDBACK --}}
        {{-- ========================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">

            {{-- RECENT ORDERS (8 cols) --}}
            <div class="lg:col-span-8 card p-3.5 sm:p-4 shadow-sm">
                <div class="flex items-center justify-between pb-2.5 mb-3 border-b divider gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="fa-solid fa-receipt flex-shrink-0" style="color: var(--accent);"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-body truncate">Recent Transactions</h3>
                    </div>
                    <span class="text-[10px] font-mono text-muted flex-shrink-0">Live Feed</span>
                </div>

                <div class="scroll-x -mx-1">
                    <table class="w-full text-left text-xs min-w-[420px]">
                        <thead class="inner text-muted font-mono text-[10px] uppercase tracking-wider border-b divider">
                            <tr>
                                <th class="px-3 py-2">Order #</th>
                                <th class="px-3 py-2">Time</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr class="transition" style="border-bottom: 1px solid var(--border-soft);"
                                    onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--accent) 5%, transparent)'"
                                    onmouseout="this.style.backgroundColor='transparent'">
                                    <td class="px-3 py-2 font-mono font-bold text-xs whitespace-nowrap" style="color: var(--accent);">
                                        {{ $order->order_number ?? '#' . $order->id }}
                                    </td>
                                    <td class="px-3 py-2 text-muted font-mono text-[11px] whitespace-nowrap">
                                        {{ $order->created_at->format('h:i A') }}
                                    </td>
                                    <td class="px-3 py-2 font-mono font-bold text-body text-xs whitespace-nowrap">
                                        ₱{{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @php
                                            $statusPill = match($order->status) {
                                                'completed' => 'pill-ok',
                                                'preparing' => 'pill-info',
                                                'ready'     => 'pill-ok',
                                                'pending'   => 'pill-warn',
                                                'cancelled' => 'pill-bad',
                                                default     => 'pill-accent',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold border {{ $statusPill }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-10 text-center">
                                        <div class="w-12 h-12 mx-auto rounded-2xl inner flex items-center justify-center mb-2">
                                            <i class="fa-solid fa-receipt text-lg text-muted"></i>
                                        </div>
                                        <p class="text-xs font-mono text-muted">No transactions recorded yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- RECENT FEEDBACK (4 cols) --}}
            <div class="lg:col-span-4 card p-3.5 sm:p-4 shadow-sm flex flex-col">
                <div class="flex items-center justify-between pb-2.5 mb-3 border-b divider gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <i class="fa-solid fa-comment-dots flex-shrink-0" style="color: var(--accent);"></i>
                        <h3 class="text-xs sm:text-sm font-bold text-body truncate">Recent Feedback</h3>
                    </div>
                    <a href="{{ route('branch.rating') }}" class="text-[10px] font-mono hover:underline font-bold flex-shrink-0" style="color: var(--accent);">
                        All →
                    </a>
                </div>

                <div class="space-y-2 flex-grow overflow-y-auto max-h-72 sm:max-h-80">
                    @forelse($recentFeedback as $feedback)
                        @php
                            $tint = $feedback->rating >= 4 ? 'var(--ok)' : ($feedback->rating == 3 ? 'var(--warn)' : 'var(--bad)');
                        @endphp
                        <div class="p-2.5 inner" style="border-left: 4px solid {{ $tint }}; border-radius: 0.75rem;">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="text-[10px] font-bold text-body truncate">
                                    {{ $feedback->user->name ?? 'Guest' }}
                                </span>
                                <div class="flex gap-0.5 flex-shrink-0">
                                    @for($i=1; $i<=5; $i++)
                                        <i class="fa-solid fa-star text-[8px]"
                                           style="color: {{ $i <= $feedback->rating ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 18%, transparent)' }};"></i>
                                    @endfor
                                </div>
                            </div>
                            <p class="text-[10px] font-mono italic line-clamp-2 text-muted">
                                "{{ $feedback->comment }}"
                            </p>
                            <div class="text-[9px] font-mono text-muted mt-1">
                                {{ $feedback->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-12 h-12 mx-auto rounded-2xl inner flex items-center justify-center mb-2">
                                <i class="fa-solid fa-comment-slash text-lg text-muted"></i>
                            </div>
                            <p class="text-xs font-mono text-muted">No feedback yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </main>
</div>

<script>
    function updateSystemClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const el = document.getElementById('system-time');
        if (el) el.textContent = timeStr;
    }
    setInterval(updateSystemClock, 1000);
    updateSystemClock();
</script>
