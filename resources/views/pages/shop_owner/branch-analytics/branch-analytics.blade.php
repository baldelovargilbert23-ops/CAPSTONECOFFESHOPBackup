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

new #[Layout('layouts.shop_owner')] class extends Component
{
    public $period = 'month';
    public $viewMode = 'overview';
    public $searchBranch = '';
    public $selectedBranchId = null;

    public function setPeriod($period) { $this->period = $period; }
    public function setViewMode($mode) { $this->viewMode = $mode; }
    public function selectBranch($branchId) { $this->selectedBranchId = $branchId; }
    public function clearBranchSelection() { $this->selectedBranchId = null; }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;
        $currentUserId = Auth::id();

        $branches = Branch::where('tenant_id', $tenantId)
            ->when($this->searchBranch, fn($q) => $q->where('name', 'like', '%' . $this->searchBranch . '%'))
            ->withCount(['users as staff_count' => function ($q) use ($currentUserId) {
                $q->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                  ->where('id', '!=', $currentUserId);
            }])
            ->get();

        $branchStats = [];
        $totalRevenue = 0;
        $totalOrders = 0;
        $totalProducts = 0;
        $totalStaff = 0;

        foreach ($branches as $branch) {
            $stats = $this->getBranchStats($branch->id, $currentUserId);
            $branchStats[$branch->id] = $stats;
            $totalRevenue += $stats['revenue'];
            $totalOrders += $stats['orders'];
            $totalProducts += $stats['products'];
            $totalStaff += $stats['staff'];
        }

        $topProducts = Product::where('products.tenant_id', $tenantId)
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', fn($join) => $join->on('order_items.order_id', '=', 'orders.id')->where('orders.tenant_id', '=', $tenantId)->where('orders.status', '=', 'completed'))
            ->select('products.*', DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold'), DB::raw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue'))
            ->groupBy('products.id', 'products.name', 'products.price', 'products.tenant_id', 'products.branch_id', 'products.category_id', 'products.cost', 'products.sku', 'products.image', 'products.is_active', 'products.created_at', 'products.updated_at')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();

        $recentReviews = Review::where('tenant_id', $tenantId)
            ->with(['user', 'branch'])
            ->where('is_visible', true)
            ->latest()
            ->take(10)
            ->get();

        $revenueChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dailyRevenue = Order::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
            $revenueChart[] = ['day' => $date->format('D'), 'revenue' => $dailyRevenue];
        }

        $branchComparison = [];
        foreach ($branches as $branch) {
            $stats = $branchStats[$branch->id] ?? [];
            $branchComparison[] = [
                'name' => $branch->name,
                'revenue' => $stats['revenue'] ?? 0,
                'orders' => $stats['orders'] ?? 0,
                'rating' => $stats['rating'] ?? 0,
            ];
        }

        $orderStatusDistribution = [
            'pending' => Order::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'preparing' => Order::where('tenant_id', $tenantId)->where('status', 'preparing')->count(),
            'ready' => Order::where('tenant_id', $tenantId)->where('status', 'ready')->count(),
            'completed' => Order::where('tenant_id', $tenantId)->where('status', 'completed')->count(),
            'cancelled' => Order::where('tenant_id', $tenantId)->where('status', 'cancelled')->count(),
        ];

        $branchRatings = [];
        foreach ($branches as $branch) {
            $branchRatings[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'rating' => number_format(Review::where('tenant_id', $tenantId)->where('branch_id', $branch->id)->avg('rating') ?? 0, 1),
                'count' => Review::where('tenant_id', $tenantId)->where('branch_id', $branch->id)->count(),
            ];
        }

        $selectedBranch = null;
        $selectedBranchStats = null;
        $branchOrders = [];
        $branchReviews = [];

        if ($this->selectedBranchId) {
            $selectedBranch = $branches->firstWhere('id', $this->selectedBranchId);
            $selectedBranchStats = $branchStats[$this->selectedBranchId] ?? null;

            $branchOrders = Order::where('tenant_id', $tenantId)
                ->where('branch_id', $this->selectedBranchId)
                ->with(['items.product'])
                ->latest()
                ->take(20)
                ->get();

            $branchReviews = Review::where('tenant_id', $tenantId)
                ->where('branch_id', $this->selectedBranchId)
                ->with('user')
                ->latest()
                ->take(10)
                ->get();
        }

        return view('pages.shop_owner.⚡branch-analytics.branch-analytics', [
            'branches' => $branches,
            'branchStats' => $branchStats,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalProducts' => $totalProducts,
            'totalStaff' => $totalStaff,
            'topProducts' => $topProducts,
            'recentReviews' => $recentReviews,
            'branchRatings' => $branchRatings,
            'selectedBranch' => $selectedBranch,
            'selectedBranchStats' => $selectedBranchStats,
            'branchOrders' => $branchOrders,
            'branchReviews' => $branchReviews,
            'revenueChart' => $revenueChart,
            'branchComparison' => $branchComparison,
            'orderStatusDistribution' => $orderStatusDistribution,
        ]);
    }

    private function getBranchStats($branchId, $currentUserId)
    {
        $tenantId = Auth::user()->tenant_id;

        $revenue = Order::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->when($this->period === 'today', fn($q) => $q->whereDate('created_at', today()))
            ->when($this->period === 'week', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($this->period === 'month', fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
            ->when($this->period === 'year', fn($q) => $q->whereYear('created_at', now()->year))
            ->sum('total_amount');

        $orders = Order::where('tenant_id', $tenantId)->where('branch_id', $branchId)->count();
        $completedOrders = Order::where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('status', 'completed')->count();
        $pendingOrders = Order::where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('status', 'pending')->count();

        $rating = Review::where('tenant_id', $tenantId)->where('branch_id', $branchId)->avg('rating') ?? 0;
        $ratingCount = Review::where('tenant_id', $tenantId)->where('branch_id', $branchId)->count();

        $products = Inventory::where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('quantity', '>', 0)->count();
        $lowStock = Inventory::where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('quantity', '<', 5)->where('quantity', '>', 0)->count();
        $outOfStock = Inventory::where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('quantity', '=', 0)->count();

        $staff = User::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->where('id', '!=', $currentUserId)
            ->count();

        $dailySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $sales = Order::where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
            $dailySales[] = ['day' => $date->format('D'), 'date' => $date->format('M d'), 'sales' => $sales];
        }

        return [
            'revenue' => $revenue,
            'orders' => $orders,
            'completed_orders' => $completedOrders,
            'pending_orders' => $pendingOrders,
            'rating' => number_format($rating, 1),
            'rating_count' => $ratingCount,
            'products' => $products,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'staff' => $staff,
            'daily_sales' => $dailySales,
        ];
    }
}
?>

<div class="owner-root">
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
        }
        .owner-root { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .owner-root .card {
            background-color: var(--bg-elevated) !important;
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .owner-root input {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .owner-root input::placeholder { color: var(--text-muted); }
        .owner-root input:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--accent) 30%, transparent); border-radius: 10px; }
    </style>
    @endonce

    <main class="relative min-h-screen font-sans p-4 sm:p-6 space-y-6 transition-colors duration-300"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <div class="max-w-7xl mx-auto space-y-6">

            {{-- Header --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-chart-line" style="color: var(--accent);"></i> Branch Analytics
                    </h1>
                    <p class="text-sm" style="color: var(--text-muted);">Comprehensive performance insights across all branches</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                        <button wire:click="setPeriod('{{ $key }}')"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition"
                                style="{{ $period === $key
                                    ? 'background-color: var(--accent); color: var(--bg-primary);'
                                    : 'background-color: var(--bg-inner); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- View Mode Tabs --}}
            <div class="card p-1 w-fit shadow-sm flex flex-wrap gap-1">
                @foreach(['overview' => 'Overview', 'details' => 'Branch Details', 'ratings' => 'Ratings & Reviews'] as $key => $label)
                    <button wire:click="setViewMode('{{ $key }}')"
                            class="px-4 py-2 text-xs font-bold rounded-lg transition"
                            style="{{ $viewMode === $key
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'color: var(--text-muted);' }}">
                        <i class="fa {{ $key === 'overview' ? 'fa-th-large' : ($key === 'details' ? 'fa-store' : 'fa-star') }} mr-1"></i> {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Search --}}
            <div class="card p-4 shadow-sm">
                <div class="relative">
                    <i class="fa fa-search absolute left-4 top-1/2 -translate-y-1/2" style="color: var(--text-muted);"></i>
                    <input wire:model.live.debounce.300ms="searchBranch"
                           type="text"
                           placeholder="Search branches..."
                           class="w-full text-sm rounded-xl pl-10 pr-4 py-2.5 outline-none transition">
                </div>
            </div>

            {{-- ==================== OVERVIEW MODE ==================== --}}
            @if($viewMode === 'overview')

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="card p-5 shadow-sm">
                        <p class="text-xs uppercase font-bold" style="color: var(--text-muted);">Total Revenue</p>
                        <p class="text-2xl font-black" style="color: var(--accent);">₱{{ number_format($totalRevenue, 2) }}</p>
                        <p class="text-[10px] mt-1" style="color: var(--text-muted);">{{ ucfirst($period) }}</p>
                    </div>
                    <div class="card p-5 shadow-sm">
                        <p class="text-xs uppercase font-bold" style="color: var(--text-muted);">Total Orders</p>
                        <p class="text-2xl font-black" style="color: var(--info);">{{ $totalOrders }}</p>
                        <p class="text-[10px] mt-1" style="color: var(--text-muted);">Across all branches</p>
                    </div>
                    <div class="card p-5 shadow-sm">
                        <p class="text-xs uppercase font-bold" style="color: var(--text-muted);">Products in Stock</p>
                        <p class="text-2xl font-black" style="color: var(--ok);">{{ $totalProducts }}</p>
                        <p class="text-[10px] mt-1" style="color: var(--text-muted);">Available inventory</p>
                    </div>
                    <div class="card p-5 shadow-sm">
                        <p class="text-xs uppercase font-bold" style="color: var(--text-muted);">Total Staff</p>
                        <p class="text-2xl font-black" style="color: var(--violet);">{{ $totalStaff }}</p>
                        <p class="text-[10px] mt-1" style="color: var(--text-muted);">All branches combined</p>
                    </div>
                </div>

                {{-- Revenue Chart — ✅ GUARANTEED RENDER --}}
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h3 class="font-black" style="color: var(--text-primary);">
                            <i class="fa fa-chart-area mr-2" style="color: var(--accent);"></i> 7-Day Revenue Trend
                        </h3>
                    </div>
                    <div class="p-6">
                        @php
                            $maxRevenue = max(array_column($revenueChart, 'revenue') ?: [1]) ?: 1;
                            $chartHeight = 180;
                        @endphp
                        <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; height: {{ $chartHeight }}px;">
                            @foreach($revenueChart as $day)
                                @php
                                    $heightPx = max(8, ($day['revenue'] / $maxRevenue) * $chartHeight);
                                @endphp
                                <div class="group" style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                    <div style="position: relative; width: 100%; height: {{ $chartHeight }}px; display: flex; align-items: flex-end;">
                                        <div style="width: 100%; height: {{ $heightPx }}px; background-color: var(--accent); border-radius: 6px 6px 0 0; transition: opacity 0.3s;"
                                             class="group-hover:opacity-80">
                                        </div>
                                        <div style="position: absolute; top: -24px; left: 50%; transform: translateX(-50%); font-size: 10px; font-weight: 700; color: var(--accent); white-space: nowrap; opacity: 0; transition: opacity 0.2s;"
                                             class="group-hover:opacity-100">
                                            ₱{{ number_format($day['revenue'], 0) }}
                                        </div>
                                    </div>
                                    <span style="font-size: 10px; font-weight: 700; color: var(--text-muted);">{{ $day['day'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Branch Performance Grid --}}
                <div>
                    <h2 class="text-lg font-black mb-4 flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-store" style="color: var(--accent);"></i> Branch Performance
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @forelse($branches as $branch)
                            @php $stats = $branchStats[$branch->id] ?? []; @endphp
                            <div wire:click="selectBranch({{ $branch->id }})"
                                 class="card p-4 cursor-pointer transition shadow-sm hover:shadow-md"
                                 style="{{ $selectedBranchId == $branch->id
                                     ? 'border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 30%, transparent);'
                                     : '' }}">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-sm truncate" style="color: var(--text-primary);">{{ $branch->name }}</h3>
                                    @if($branch->is_main)
                                        <span class="px-2 py-0.5 rounded-md text-[8px] font-bold"
                                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">Main</span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 gap-1 text-xs">
                                    <div>
                                        <p style="color: var(--text-muted);">Revenue</p>
                                        <p class="font-bold" style="color: var(--accent);">₱{{ number_format($stats['revenue'] ?? 0, 0) }}</p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-muted);">Orders</p>
                                        <p class="font-bold" style="color: var(--text-primary);">{{ $stats['orders'] ?? 0 }}</p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-muted);">Rating</p>
                                        <p class="font-bold" style="color: var(--accent);">{{ $stats['rating'] ?? 0 }}★</p>
                                    </div>
                                    <div>
                                        <p style="color: var(--text-muted);">Staff</p>
                                        <p class="font-bold" style="color: var(--violet);">{{ $stats['staff'] ?? 0 }}</p>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 flex justify-between text-[10px]" style="border-top: 1px solid var(--border-soft);">
                                    <span style="color: var(--text-muted);">Products: {{ $stats['products'] ?? 0 }}</span>
                                    @if(($stats['low_stock'] ?? 0) > 0)
                                        <span class="font-bold" style="color: var(--accent);">{{ $stats['low_stock'] }} low stock</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full card p-12 text-center" style="color: var(--text-muted);">
                                <i class="fa fa-store text-3xl mb-2 block opacity-50"></i>
                                <p class="text-sm">No branches found</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Order Status --}}
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h3 class="font-black" style="color: var(--text-primary);">
                            <i class="fa fa-chart-pie mr-2" style="color: var(--accent);"></i> Order Status Distribution
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            @foreach([
                                'pending'   => ['label' => 'Pending',   'color' => 'var(--accent)'],
                                'preparing' => ['label' => 'Preparing', 'color' => 'var(--info)'],
                                'ready'     => ['label' => 'Ready',     'color' => 'var(--info)'],
                                'completed' => ['label' => 'Completed', 'color' => 'var(--ok)'],
                                'cancelled' => ['label' => 'Cancelled', 'color' => 'var(--bad)'],
                            ] as $status => $meta)
                                <div class="text-center rounded-xl p-4"
                                     style="background-color: color-mix(in srgb, {{ $meta['color'] }} 10%, transparent); border: 1px solid color-mix(in srgb, {{ $meta['color'] }} 20%, transparent);">
                                    <p class="text-2xl font-black" style="color: {{ $meta['color'] }};">{{ $orderStatusDistribution[$status] ?? 0 }}</p>
                                    <p class="text-[10px] uppercase font-bold mt-1" style="color: var(--text-muted);">{{ $meta['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Top Products --}}
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h2 class="text-lg font-black flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-trophy" style="color: var(--accent);"></i> Top Selling Products
                        </h2>
                    </div>
                    <div>
                        @forelse($topProducts as $product)
                            <div class="px-6 py-3 flex items-center justify-between transition" style="border-bottom: 1px solid var(--border-soft);">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0"
                                          style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                        {{ $loop->iteration }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium truncate" style="color: var(--text-primary);">{{ $product->name }}</p>
                                        <p class="text-xs" style="color: var(--text-muted);">₱{{ number_format($product->price, 2) }}</p>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="font-bold" style="color: var(--accent);">{{ $product->total_sold ?? 0 }} sold</p>
                                    <p class="text-xs" style="color: var(--text-muted);">₱{{ number_format($product->total_revenue ?? 0, 2) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center" style="color: var(--text-muted);">
                                <i class="fa fa-box text-2xl mb-2 block opacity-50"></i> No products sold yet
                            </div>
                        @endforelse
                    </div>
                </div>

            @endif

            {{-- ==================== DETAILS MODE ==================== --}}
            @if($viewMode === 'details')
                <div class="space-y-4">
                    @forelse($branches as $branch)
                        @php $stats = $branchStats[$branch->id] ?? []; @endphp
                        <div wire:click="selectBranch({{ $branch->id }})"
                             class="card cursor-pointer transition shadow-sm overflow-hidden"
                             style="{{ $selectedBranchId == $branch->id
                                 ? 'border-color: var(--accent); box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 30%, transparent);'
                                 : '' }}">
                            <div class="p-5">
                                <div class="flex items-start justify-between mb-4 flex-wrap gap-2">
                                    <div>
                                        <h3 class="text-lg font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                                            {{ $branch->name }}
                                            @if($branch->is_main)
                                                <span class="px-2 py-0.5 rounded-md text-[8px] font-bold"
                                                      style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">Main</span>
                                            @endif
                                        </h3>
                                        <p class="text-xs" style="color: var(--text-muted);">{{ $branch->code }} • {{ $branch->address ?? 'No address' }}</p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[8px] font-bold uppercase"
                                          style="background-color: color-mix(in srgb, {{ $branch->is_active ? 'var(--ok)' : 'var(--bad)' }} 10%, transparent);
                                                 color: {{ $branch->is_active ? 'var(--ok)' : 'var(--bad)' }};
                                                 border: 1px solid color-mix(in srgb, {{ $branch->is_active ? 'var(--ok)' : 'var(--bad)' }} 20%, transparent);">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                                    @foreach([
                                        ['label' => 'Revenue',      'value' => '₱' . number_format($stats['revenue'] ?? 0, 0), 'color' => 'var(--accent)'],
                                        ['label' => 'Orders',       'value' => $stats['orders'] ?? 0,       'color' => 'var(--info)'],
                                        ['label' => 'Rating',       'value' => ($stats['rating'] ?? 0) . '★', 'color' => 'var(--accent)'],
                                        ['label' => 'Staff',        'value' => $stats['staff'] ?? 0,        'color' => 'var(--violet)'],
                                        ['label' => 'Products',     'value' => $stats['products'] ?? 0,     'color' => 'var(--ok)'],
                                        ['label' => 'Out of Stock', 'value' => $stats['out_of_stock'] ?? 0, 'color' => 'var(--bad)'],
                                    ] as $stat)
                                        <div class="text-center">
                                            <p class="text-xs font-bold" style="color: {{ $stat['color'] }};">{{ $stat['value'] }}</p>
                                            <p class="text-[8px] uppercase" style="color: var(--text-muted);">{{ $stat['label'] }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- ✅ FIXED: 7-Day Sales Trend — inline flex style --}}
                                @if(!empty($stats['daily_sales']))
                                    @php
                                        $maxBranchSales = max(array_column($stats['daily_sales'], 'sales') ?: [1]) ?: 1;
                                        $miniHeight = 56;
                                    @endphp
                                    <div class="mt-4 pt-4" style="border-top: 1px solid var(--border-soft);">
                                        <p class="text-[10px] font-bold mb-2" style="color: var(--text-muted);">7-Day Sales Trend</p>
                                        <div style="display: flex; align-items: flex-end; justify-content: space-between; gap: 6px; height: {{ $miniHeight + 16 }}px;">
                                            @foreach($stats['daily_sales'] as $day)
                                                @php
                                                    $heightPx = max(6, ($day['sales'] / $maxBranchSales) * $miniHeight);
                                                @endphp
                                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                                    <div style="position: relative; width: 100%; height: {{ $miniHeight }}px; display: flex; align-items: flex-end;">
                                                        <div style="width: 100%; height: {{ $heightPx }}px; background-color: var(--accent); border-radius: 4px 4px 0 0;"></div>
                                                    </div>
                                                    <span style="font-size: 6px; color: var(--text-muted);">{{ $day['day'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="card p-12 text-center" style="color: var(--text-muted);">
                            <i class="fa fa-store text-3xl mb-2 block opacity-50"></i>
                            <p class="text-sm">No branches found</p>
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- ==================== RATINGS MODE ==================== --}}
            @if($viewMode === 'ratings')

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($branchRatings as $rating)
                        <div class="card p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                                <h3 class="font-bold truncate" style="color: var(--text-primary);">{{ $rating['branch_name'] }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[8px] font-bold"
                                      style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                                    {{ $rating['count'] }} reviews
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-3xl font-black" style="color: var(--accent);">{{ $rating['rating'] }}</span>
                                <div class="flex">
                                    @for($i=1; $i<=5; $i++)
                                        <i class="fa fa-star text-sm"
                                           style="color: {{ $i <= round((float) $rating['rating']) ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 20%, transparent)' }};"></i>
                                    @endfor
                                </div>
                            </div>
                            <div class="mt-2 h-1 w-full rounded-full overflow-hidden" style="background-color: var(--border-soft);">
                                <div class="h-full rounded-full" style="width: {{ ((float) $rating['rating'] / 5) * 100 }}%; background-color: var(--accent);"></div>
                            </div>
                            <button wire:click="selectBranch({{ $rating['branch_id'] }})"
                                    class="mt-3 text-xs font-semibold hover:underline" style="color: var(--accent);">
                                View all reviews →
                            </button>
                        </div>
                    @empty
                        <div class="col-span-full card p-12 text-center" style="color: var(--text-muted);">
                            <i class="fa fa-star text-3xl mb-2 block opacity-50"></i>
                            <p class="text-sm">No branches found</p>
                        </div>
                    @endforelse
                </div>

                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h2 class="text-lg font-black flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-comment-dots" style="color: var(--accent);"></i> Recent Reviews
                        </h2>
                    </div>
                    <div>
                        @forelse($recentReviews as $review)
                            <div class="px-6 py-4 transition" style="border-bottom: 1px solid var(--border-soft);">
                                <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0"
                                             style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                            {{ substr($review->user->name ?? 'A', 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-sm truncate" style="color: var(--text-primary);">{{ $review->user->name ?? 'Anonymous' }}</p>
                                            <p class="text-xs truncate" style="color: var(--text-muted);">{{ $review->branch->name ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="flex gap-0.5">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="fa fa-star text-xs"
                                                   style="color: {{ $i <= $review->rating ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 20%, transparent)' }};"></i>
                                            @endfor
                                        </div>
                                        <p class="text-[10px]" style="color: var(--text-muted);">{{ $review->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                @if($review->comment)
                                    <p class="text-sm italic" style="color: var(--text-muted);">"{{ $review->comment }}"</p>
                                @endif
                                @if($review->owner_reply)
                                    <div class="mt-2 pl-4" style="border-left: 2px solid var(--accent);">
                                        <p class="text-xs" style="color: var(--accent);">
                                            <i class="fa fa-reply mr-1"></i> Owner reply: {{ $review->owner_reply }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center" style="color: var(--text-muted);">
                                <i class="fa fa-comments text-3xl mb-2 block opacity-50"></i> No reviews yet
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- ==================== BRANCH DETAIL MODAL ==================== --}}
            @if($selectedBranch && $selectedBranchStats)
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
                     style="background-color: rgba(0,0,0,0.6);">
                    <div class="owner-root card w-full max-w-4xl p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto"
                         style="background-color: var(--bg-elevated);">

                        <div class="flex items-center justify-between pb-4 sticky top-0 z-10 flex-wrap gap-2"
                             style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-elevated);">
                            <div>
                                <h3 class="text-xl font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                                    <i class="fa fa-store" style="color: var(--accent);"></i> {{ $selectedBranch->name }}
                                    @if($selectedBranch->is_main)
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-bold"
                                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">Main</span>
                                    @endif
                                </h3>
                                <p class="text-xs" style="color: var(--text-muted);">
                                    {{ $selectedBranch->code }} • {{ $selectedBranch->address ?? 'No address' }}
                                </p>
                            </div>
                            <button wire:click="clearBranchSelection" class="transition" style="color: var(--text-muted);">
                                <i class="fa fa-times text-xl"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="rounded-xl p-3 text-center" style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                                <p class="text-lg font-black" style="color: var(--accent);">₱{{ number_format($selectedBranchStats['revenue'] ?? 0, 2) }}</p>
                                <p class="text-[8px] uppercase font-bold" style="color: var(--text-muted);">Revenue</p>
                            </div>
                            <div class="rounded-xl p-3 text-center" style="background-color: color-mix(in srgb, var(--info) 10%, transparent); border: 1px solid color-mix(in srgb, var(--info) 20%, transparent);">
                                <p class="text-lg font-black" style="color: var(--info);">{{ $selectedBranchStats['orders'] ?? 0 }}</p>
                                <p class="text-[8px] uppercase font-bold" style="color: var(--text-muted);">Orders</p>
                            </div>
                            <div class="rounded-xl p-3 text-center" style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                                <p class="text-lg font-black" style="color: var(--accent);">{{ $selectedBranchStats['rating'] ?? 0 }}★</p>
                                <p class="text-[8px] uppercase font-bold" style="color: var(--text-muted);">Rating</p>
                            </div>
                            <div class="rounded-xl p-3 text-center" style="background-color: color-mix(in srgb, var(--violet) 10%, transparent); border: 1px solid color-mix(in srgb, var(--violet) 20%, transparent);">
                                <p class="text-lg font-black" style="color: var(--violet);">{{ $selectedBranchStats['staff'] ?? 0 }}</p>
                                <p class="text-[8px] uppercase font-bold" style="color: var(--text-muted);">Staff</p>
                            </div>
                        </div>

                        @if($branchOrders->count() > 0)
                            <div>
                                <h4 class="font-bold text-sm mb-2" style="color: var(--text-primary);">Recent Orders</h4>
                                <div class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar">
                                    @foreach($branchOrders as $order)
                                        <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm"
                                             style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                            <div>
                                                <p class="font-mono font-bold text-xs" style="color: var(--accent);">{{ $order->order_number }}</p>
                                                <p class="text-[10px]" style="color: var(--text-muted);">{{ $order->created_at->format('M d, h:i A') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold" style="color: var(--text-primary);">₱{{ number_format($order->total_amount, 2) }}</p>
                                                <span class="px-2 py-0.5 rounded-lg text-[8px] font-bold uppercase"
                                                      style="background-color: color-mix(in srgb, {{ $order->status === 'completed' ? 'var(--ok)' : 'var(--accent)' }} 10%, transparent);
                                                             color: {{ $order->status === 'completed' ? 'var(--ok)' : 'var(--accent)' }};
                                                             border: 1px solid color-mix(in srgb, {{ $order->status === 'completed' ? 'var(--ok)' : 'var(--accent)' }} 20%, transparent);">
                                                    {{ $order->status }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($branchReviews->count() > 0)
                            <div>
                                <h4 class="font-bold text-sm mb-2" style="color: var(--text-primary);">Recent Reviews</h4>
                                <div class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar">
                                    @foreach($branchReviews as $review)
                                        <div class="rounded-lg px-3 py-2" style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-medium text-sm" style="color: var(--text-primary);">{{ $review->user->name ?? 'Anonymous' }}</p>
                                                    <div class="flex text-xs">
                                                        @for($i=1; $i<=5; $i++)
                                                            <i class="fa fa-star"
                                                               style="color: {{ $i <= $review->rating ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 20%, transparent)' }};"></i>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <p class="text-[10px]" style="color: var(--text-muted);">{{ $review->created_at->diffForHumans() }}</p>
                                            </div>
                                            @if($review->comment)
                                                <p class="text-xs mt-1" style="color: var(--text-muted);">"{{ $review->comment }}"</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-end pt-4" style="border-top: 1px solid var(--border-soft);">
                            <button wire:click="clearBranchSelection" class="px-4 py-2 text-xs font-bold transition" style="color: var(--text-muted);">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </main>
</div>
