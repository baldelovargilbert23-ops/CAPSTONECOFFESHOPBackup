<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('layouts.admin')] class extends Component
{
    public $period = 'month';
    public $selectedTenantId = null;
    public $selectedBranchId = null;
    public $showTenantModal = false;
    public $showBranchModal = false;
    public $selectedTenantName = '';
    public $selectedBranchName = '';
    public $viewMode = 'tenant';

    public function setPeriod($period)
    {
        $this->period = $period;
    }

    private function anchorDate(): Carbon
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        $latest = Order::where('status', 'completed')
            ->whereNotNull('created_at')
            ->max('created_at');

        $latestDate = $latest ? Carbon::parse($latest) : now();
        $today      = now();

        if ($today->day < 5) {
            return $cached = $today->copy()->subMonthNoOverflow()->endOfMonth();
        }

        return $cached = $latestDate;
    }

    public function viewTenantSales($tenantId)
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->selectedTenantId = $tenantId;
        $this->selectedTenantName = $tenant->name;
        $this->showTenantModal = true;
        $this->viewMode = 'tenant';
    }

    public function viewBranchSales($branchId)
    {
        $branch = Branch::with(['tenant', 'manager'])->findOrFail($branchId);
        $this->selectedBranchId = $branchId;
        $this->selectedBranchName = $branch->name;
        $this->selectedTenantId = $branch->tenant_id;
        $this->selectedTenantName = $branch->tenant->name ?? 'Unknown';
        $this->showBranchModal = true;
        $this->viewMode = 'branch';
    }

    public function closeTenantModal()
    {
        $this->showTenantModal = false;
        $this->selectedTenantId = null;
    }

    public function closeBranchModal()
    {
        $this->showBranchModal = false;
        $this->selectedBranchId = null;
    }

    public function switchToTenantView($tenantId)
    {
        $this->closeBranchModal();
        $this->viewTenantSales($tenantId);
    }

    public function getRevenueGrowthProperty()
    {
        $current = Order::where('status', 'completed')->sum('total_amount');
        $previous = Order::where('status', 'completed')
            ->where('created_at', '<', now()->subMonth())
            ->sum('total_amount');

        if ($previous == 0) return 100;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function getTotalOrdersProperty()
    {
        return Order::where('status', 'completed')->count();
    }

    public function getActiveTenantsProperty()
    {
        return Tenant::where('status', 'active')->count();
    }

    public function getTotalTenantsProperty()
    {
        return Tenant::count();
    }

    public function getTotalBranchesProperty()
    {
        return Branch::count();
    }

    public function getApprovedBranchesProperty()
    {
        return Branch::where('approval_status', 'approved')->count();
    }

    public function getPendingBranchesProperty()
    {
        return Branch::where('approval_status', 'pending')->count();
    }

    public function getAvgOrderValueProperty()
    {
        return Order::where('status', 'completed')->avg('total_amount') ?? 0;
    }

    public function getTrendLabelsProperty()
    {
        $anchor = $this->anchorDate();

        if ($this->period === 'week') {
            return collect(range(6, 0))
                ->map(fn($d) => $anchor->copy()->subDays($d)->format('D'))
                ->toArray();
        }

        if ($this->period === 'month') {
            return collect(range(1, $anchor->daysInMonth))
                ->map(fn($d) => (string) $d)
                ->toArray();
        }

        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }

    public function getTrendDataProperty()
    {
        $anchor = $this->anchorDate();

        if ($this->period === 'week') {
            return collect(range(6, 0))->map(fn($d) =>
                Order::where('status', 'completed')
                    ->whereDate('created_at', $anchor->copy()->subDays($d))
                    ->sum('total_amount')
            )->toArray();
        }

        if ($this->period === 'month') {
            return collect(range(1, $anchor->daysInMonth))->map(fn($d) =>
                Order::where('status', 'completed')
                    ->whereDay('created_at', $d)
                    ->whereMonth('created_at', $anchor->month)
                    ->whereYear('created_at', $anchor->year)
                    ->sum('total_amount')
            )->toArray();
        }

        return collect(range(1, 12))->map(fn($m) =>
            Order::where('status', 'completed')
                ->whereMonth('created_at', $m)
                ->whereYear('created_at', $anchor->year)
                ->sum('total_amount')
        )->toArray();
    }

    public function getTopTenantNamesProperty()
    {
        return Tenant::withSum('orders', 'total_amount')
            ->whereHas('orders', function($q) {
                $q->where('status', 'completed');
            })
            ->orderByDesc('orders_sum_total_amount')
            ->take(5)
            ->get()
            ->pluck('name')
            ->toArray();
    }

    public function getTopTenantSalesProperty()
    {
        return Tenant::withSum('orders', 'total_amount')
            ->whereHas('orders', function($q) {
                $q->where('status', 'completed');
            })
            ->orderByDesc('orders_sum_total_amount')
            ->take(5)
            ->get()
            ->map(fn($t) => $t->orders_sum_total_amount ?? 0)
            ->toArray();
    }

    public function getTenantPerformanceProperty()
    {
        return Tenant::withSum(['orders' => fn($q) => $q->where('status', 'completed')], 'total_amount')
            ->withCount(['orders' => fn($q) => $q->where('status', 'completed')])
            ->withCount('branches')
            ->where(function($query) {
                $query->whereHas('orders', function($q) {
                    $q->where('status', 'completed');
                })->orWhere('status', 'active');
            })
            ->orderByDesc('orders_sum_total_amount')
            ->take(10)
            ->get()
            ->map(function($t) {
                $currentMonthSales = $t->orders()->where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_amount');
                $previousMonthSales = $t->orders()->where('status', 'completed')
                    ->whereMonth('created_at', now()->subMonth()->month)
                    ->sum('total_amount');

                $growth = $previousMonthSales > 0
                    ? round((($currentMonthSales - $previousMonthSales) / $previousMonthSales) * 100, 1)
                    : ($currentMonthSales > 0 ? 100 : 0);

                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'sales' => $t->orders_sum_total_amount ?? 0,
                    'orders' => $t->orders_count,
                    'avg_order' => $t->orders_count > 0 ? round($t->orders_sum_total_amount / $t->orders_count, 2) : 0,
                    'growth' => $growth,
                    'status' => $t->status,
                    'branches' => $t->branches_count,
                ];
            })
            ->filter(fn($t) => $t['sales'] > 0 || $t['status'] === 'active')
            ->values()
            ->toArray();
    }

    public function getBranchPerformanceProperty()
    {
        return Branch::with(['tenant', 'manager'])
            ->withSum(['orders' => fn($q) => $q->where('status', 'completed')], 'total_amount')
            ->withCount(['orders' => fn($q) => $q->where('status', 'completed')])
            ->where('approval_status', 'approved')
            ->orderByDesc('orders_sum_total_amount')
            ->take(10)
            ->get()
            ->map(function($b) {
                $manager = $b->manager;

                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'tenant_id' => $b->tenant_id,
                    'tenant_name' => $b->tenant->name ?? 'N/A',
                    'manager_name' => $manager ? $manager->name : 'Not Assigned',
                    'manager_email' => $manager ? $manager->email : null,
                    'manager_id' => $manager ? $manager->id : null,
                    'sales' => $b->orders_sum_total_amount ?? 0,
                    'orders' => $b->orders_count,
                    'avg_order' => $b->orders_count > 0 ? round($b->orders_sum_total_amount / $b->orders_count, 2) : 0,
                    'is_active' => $b->is_active,
                    'approval_status' => $b->approval_status,
                ];
            })
            ->filter(fn($b) => $b['sales'] > 0 || $b['is_active'])
            ->values()
            ->toArray();
    }

    public function getSelectedTenantSalesDataProperty()
    {
        if (!$this->selectedTenantId) return null;

        $tenant = Tenant::find($this->selectedTenantId);
        if (!$tenant) return null;

        $branches = Branch::where('tenant_id', $tenant->id)
            ->with(['manager'])
            ->withSum(['orders' => fn($q) => $q->where('status', 'completed')], 'total_amount')
            ->withCount(['orders' => fn($q) => $q->where('status', 'completed')])
            ->get()
            ->map(function($b) {
                $manager = $b->manager;

                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'manager' => $manager ? $manager->name : 'Not Assigned',
                    'manager_email' => $manager ? $manager->email : null,
                    'manager_id' => $manager ? $manager->id : null,
                    'sales' => $b->orders_sum_total_amount ?? 0,
                    'orders' => $b->orders_count,
                    'avg_order' => $b->orders_count > 0 ? round($b->orders_sum_total_amount / $b->orders_count, 2) : 0,
                    'status' => $b->is_active ? 'Active' : 'Inactive',
                    'approval_status' => $b->approval_status,
                ];
            });

        $cashierPerformance = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('orders.tenant_id', $tenant->id)
            ->where('orders.status', 'completed')
            ->where('users.role', 'cashier')
            ->select(
                'users.name as cashier_name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as total_sales'),
                DB::raw('ROUND(AVG(orders.total_amount), 2) as avg_order')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->get();

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.tenant_id', $tenant->id)
            ->where('orders.status', 'completed')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.subtotal) as total_sales'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        $recentOrders = Order::where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->with(['user', 'branch', 'branch.manager'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function($order) {
                $isCashier = $order->user && $order->user->role === 'cashier';
                $manager = $order->branch ? $order->branch->manager : null;

                return [
                    'date' => $order->created_at->format('M d, Y H:i'),
                    'branch' => $order->branch->name ?? 'N/A',
                    'branch_manager' => $manager ? $manager->name : 'Not Assigned',
                    'cashier' => $isCashier ? ($order->user->name ?? 'Unknown') : null,
                    'customer' => $order->user && !$isCashier ? $order->user->name : ($order->customer_name ?? 'Walk-in'),
                    'amount' => $order->total_amount,
                    'payment' => strtoupper($order->payment_method ?? 'CASH'),
                    'order_type' => $isCashier ? 'counter' : 'self_service',
                ];
            });

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'status' => $tenant->status,
            'total_sales' => $tenant->orders()->where('status', 'completed')->sum('total_amount'),
            'total_orders' => $tenant->orders()->where('status', 'completed')->count(),
            'avg_order' => $tenant->orders()->where('status', 'completed')->avg('total_amount') ?? 0,
            'branches' => $branches,
            'cashier_performance' => $cashierPerformance,
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
        ];
    }

    public function getSelectedBranchSalesDataProperty()
    {
        if (!$this->selectedBranchId) return null;

        $branch = Branch::with(['tenant', 'manager'])->find($this->selectedBranchId);
        if (!$branch) return null;

        $manager = $branch->manager;

        $staff = User::where('branch_id', $branch->id)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->get();

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.branch_id', $branch->id)
            ->where('orders.status', 'completed')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.subtotal) as total_sales'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        $recentOrders = Order::where('branch_id', $branch->id)
            ->where('status', 'completed')
            ->with(['user'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function($order) {
                $isCashier = $order->user && $order->user->role === 'cashier';

                return [
                    'date' => $order->created_at->format('M d, Y H:i'),
                    'cashier' => $isCashier ? ($order->user->name ?? 'Unknown') : null,
                    'customer' => $order->user && !$isCashier ? $order->user->name : ($order->customer_name ?? 'Walk-in'),
                    'amount' => $order->total_amount,
                    'payment' => strtoupper($order->payment_method ?? 'CASH'),
                ];
            });

        $dailySales = collect(range(6, 0))->map(function($daysAgo) use ($branch) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D, M d'),
                'sales' => Order::where('branch_id', $branch->id)
                    ->where('status', 'completed')
                    ->whereDate('created_at', $date)
                    ->sum('total_amount'),
                'orders' => Order::where('branch_id', $branch->id)
                    ->where('status', 'completed')
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        });

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'tenant_name' => $branch->tenant->name ?? 'N/A',
            'manager' => $manager ? $manager->name : 'Not Assigned',
            'manager_email' => $manager ? $manager->email : null,
            'manager_id' => $manager ? $manager->id : null,
            'total_sales' => Order::where('branch_id', $branch->id)->where('status', 'completed')->sum('total_amount'),
            'total_orders' => Order::where('branch_id', $branch->id)->where('status', 'completed')->count(),
            'avg_order' => Order::where('branch_id', $branch->id)->where('status', 'completed')->avg('total_amount') ?? 0,
            'staff_count' => $staff->count(),
            'staff' => $staff,
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
            'daily_sales' => $dailySales,
            'is_active' => $branch->is_active,
            'approval_status' => $branch->approval_status,
        ];
    }

    public function render()
    {
        return view('pages.admin.⚡analytics.analytics', [
            'totalSales' => Order::where('status', 'completed')->sum('total_amount'),
            'ordersToday' => Order::whereDate('created_at', today())->count(),
            'topProduct' => Product::withCount('orderItems as orders_count')
                ->orderByDesc('orders_count')
                ->first(),
            'recentTransactions' => Order::with('tenant')->latest()->take(10)->get(),
            'revenueGrowth' => $this->revenueGrowth,
            'totalOrders' => $this->totalOrders,
            'activeTenants' => $this->activeTenants,
            'totalTenants' => $this->totalTenants,
            'totalBranches' => $this->totalBranches,
            'approvedBranches' => $this->approvedBranches,
            'pendingBranches' => $this->pendingBranches,
            'avgOrderValue' => $this->avgOrderValue,
            'trendLabels' => $this->trendLabels,
            'trendData' => $this->trendData,
            'topTenantNames' => $this->topTenantNames,
            'topTenantSales' => $this->topTenantSales,
            'tenantPerformance' => $this->tenantPerformance,
            'branchPerformance' => $this->branchPerformance,
            'period' => $this->period,
            'selectedTenantData' => $this->selectedTenantSalesData,
            'selectedBranchData' => $this->selectedBranchSalesData,
            'showTenantModal' => $this->showTenantModal,
            'showBranchModal' => $this->showBranchModal,
            'selectedTenantName' => $this->selectedTenantName,
            'selectedBranchName' => $this->selectedBranchName,
            'viewMode' => $this->viewMode,
        ]);
    }
};
?>
<div>

    <div wire:poll.60s="$refresh" class="hidden"></div>

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
        .admin-root { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .admin-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .admin-root .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
        .admin-root table thead tr th {
            color: var(--text-muted);
        }
    </style>
    @endonce

    <main class="admin-root relative min-h-screen font-sans transition-colors duration-300 overflow-x-hidden"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <section class="w-full pt-8 px-4 sm:px-6 md:px-8 pb-12 max-w-7xl mx-auto">

            <div class="mb-8">
                <h1 class="text-2xl font-black" style="color: var(--text-primary);">Super Admin Analytics</h1>
                <p class="text-sm mt-1" style="color: var(--text-muted);">Multi-tenant performance & business intelligence</p>
            </div>

            <div class="card p-4 mb-8 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <i class="fa-regular fa-calendar-alt text-lg" style="color: var(--accent);"></i>
                        <span class="font-medium text-sm" style="color: var(--text-primary);">Analytics Period</span>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @foreach([
                            ['key' => 'week',  'label' => 'This Week'],
                            ['key' => 'month', 'label' => 'This Month'],
                            ['key' => 'year',  'label' => 'This Year'],
                        ] as $p)
                            <button wire:click="setPeriod('{{ $p['key'] }}')"
                                    class="px-5 py-1.5 rounded-full text-xs font-bold transition-all duration-300"
                                    style="{{ $period === $p['key']
                                        ? 'background-color: var(--accent); color: var(--bg-primary);'
                                        : 'background-color: var(--bg-inner); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">
                                {{ $p['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6 mb-8">

                <div class="card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Total Revenue</span>
                        <i class="fa fa-dollar-sign" style="color: var(--accent);"></i>
                    </div>
                    <p class="text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($totalSales, 2) }}</p>
                    <p class="text-[10px] mt-1 font-semibold" style="color: var(--ok);">
                        <i class="fa fa-arrow-up mr-0.5"></i>{{ $revenueGrowth }}% from last period
                    </p>
                </div>

                <div class="card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Total Orders</span>
                        <i class="fa fa-shopping-cart" style="color: var(--accent);"></i>
                    </div>
                    <p class="text-2xl font-black" style="color: var(--text-primary);">{{ number_format($totalOrders) }}</p>
                    <p class="text-[10px] mt-1" style="color: var(--text-muted);">{{ $ordersToday }} today</p>
                </div>

                <div class="card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Active Tenants</span>
                        <i class="fa fa-store" style="color: var(--accent);"></i>
                    </div>
                    <p class="text-2xl font-black" style="color: var(--text-primary);">{{ $activeTenants }}</p>
                    <p class="text-[10px] mt-1" style="color: var(--text-muted);">{{ $totalTenants }} total registered</p>
                </div>

                <div class="card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Avg Order Value</span>
                        <i class="fa fa-chart-simple" style="color: var(--accent);"></i>
                    </div>
                    <p class="text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($avgOrderValue, 2) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-8">
                <div class="card p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-black flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-chart-line" style="color: var(--accent);"></i> Sales Trend
                        </h3>
                        <span class="text-[10px]" style="color: var(--text-muted);">{{ ucfirst($period) }}ly performance</span>
                    </div>
                    <div wire:key="sales-trend-{{ $period }}">
                        <div id="salesTrendChart"
                             data-labels='@json($trendLabels)'
                             data-values='@json($trendData)'
                             style="min-height: 280px;"></div>
                    </div>
                </div>

                <div class="card p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-black flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-chart-column" style="color: var(--accent);"></i> Top Tenants by Sales
                        </h3>
                        <span class="text-[10px]" style="color: var(--text-muted);">Last 30 days</span>
                    </div>
                    <div wire:key="tenant-sales-{{ $period }}">
                        <div id="tenantSalesChart"
                             data-labels='@json($topTenantNames)'
                             data-values='@json($topTenantSales)'
                             style="min-height: 280px;"></div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden mb-8 shadow-sm">
                <div class="p-5" style="border-bottom: 1px solid var(--border-soft);">
                    <h3 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                        <i class="fa fa-ranking-star" style="color: var(--accent);"></i> Tenant Performance
                        <span class="text-xs font-normal" style="color: var(--text-muted);">(Click to view details & branches)</span>
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px]">
                        <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Rank</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Tenant</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Total Sales</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Orders</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Avg Order</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Branches</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Growth</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tenantPerformance as $index => $tenant)
                                <tr class="cursor-pointer transition-colors"
                                    style="border-bottom: 1px solid var(--border-soft);"
                                    wire:click="viewTenantSales({{ $tenant['id'] }})">
                                    <td class="px-6 py-4">
                                        <span class="text-lg font-black" style="color: {{ $index < 3 ? 'var(--accent)' : 'var(--text-muted)' }};">
                                            #{{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black"
                                                 style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                                {{ substr($tenant['name'], 0, 2) }}
                                            </div>
                                            <span class="text-sm font-bold" style="color: var(--text-primary);">
                                                {{ $tenant['name'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-bold" style="color: var(--text-primary);">₱{{ number_format($tenant['sales'], 2) }}</td>
                                    <td class="px-6 py-4" style="color: var(--text-muted);">{{ $tenant['orders'] }}</td>
                                    <td class="px-6 py-4" style="color: var(--text-muted);">₱{{ number_format($tenant['avg_order'], 2) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                                              style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                            <i class="fa fa-store text-[10px]"></i>
                                            {{ $tenant['branches'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold" style="color: var(--ok);">
                                            <i class="fa fa-arrow-up"></i> {{ $tenant['growth'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card overflow-hidden mb-8 shadow-sm">
                <div class="p-5 flex justify-between items-center flex-wrap gap-3" style="border-bottom: 1px solid var(--border-soft);">
                    <h3 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                        <i class="fa fa-store" style="color: var(--accent);"></i> Top Branches by Sales
                        <span class="text-xs font-normal" style="color: var(--text-muted);">(Click to view details)</span>
                    </h3>
                    <span class="text-xs" style="color: var(--text-muted);">
                        <i class="fa-regular fa-building mr-1"></i> {{ count($branchPerformance) }} active branches
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px]">
                        <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Rank</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Branch</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Tenant</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Manager</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Sales</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Orders</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchPerformance as $index => $branch)
                                <tr class="cursor-pointer transition-colors"
                                    style="border-bottom: 1px solid var(--border-soft);"
                                    wire:click="viewBranchSales({{ $branch['id'] }})">
                                    <td class="px-6 py-4">
                                        <span class="text-lg font-black" style="color: {{ $index < 3 ? 'var(--accent)' : 'var(--text-muted)' }};">
                                            #{{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black"
                                                 style="background-color: color-mix(in srgb, var(--info) 20%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                {{ substr($branch['name'], 0, 2) }}
                                            </div>
                                            <span class="text-sm font-bold" style="color: var(--text-primary);">
                                                {{ $branch['name'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">{{ $branch['tenant_name'] }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if($branch['manager_name'] && $branch['manager_name'] !== 'Not Assigned')
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black"
                                                     style="background-color: color-mix(in srgb, var(--violet) 20%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent);">
                                                    {{ substr($branch['manager_name'], 0, 1) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="font-semibold text-sm truncate block" style="color: var(--text-primary);">{{ $branch['manager_name'] }}</span>
                                                    @if($branch['manager_email'])
                                                        <p class="text-[10px] truncate" style="color: var(--text-muted);">{{ $branch['manager_email'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs" style="color: var(--text-muted);">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-bold" style="color: var(--text-primary);">₱{{ number_format($branch['sales'], 2) }}</td>
                                    <td class="px-6 py-4" style="color: var(--text-muted);">{{ $branch['orders'] }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                              style="background-color: color-mix(in srgb, {{ $branch['is_active'] ? 'var(--ok)' : 'var(--bad)' }} 10%, transparent);
                                                     color: {{ $branch['is_active'] ? 'var(--ok)' : 'var(--bad)' }};
                                                     border: 1px solid color-mix(in srgb, {{ $branch['is_active'] ? 'var(--ok)' : 'var(--bad)' }} 30%, transparent);">
                                            <i class="fa-solid fa-circle text-[6px]"></i>
                                            {{ $branch['is_active'] ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    @if($showTenantModal && $selectedTenantData)
        <div class="admin-root"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2147483647; display: flex; align-items: flex-start; justify-content: center; padding: 1rem; overflow-y: auto;">

            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"
                 wire:click="closeTenantModal"></div>

            <div class="w-full max-w-6xl overflow-hidden relative shadow-2xl"
                 style="position: relative;
                        z-index: 1;
                        margin: 1rem auto;
                        background-color: var(--bg-elevated);
                        border: 1px solid var(--border-soft);
                        border-radius: 1rem;
                        max-height: 90vh;
                        display: flex;
                        flex-direction: column;">

                <div class="p-5 flex justify-between items-center flex-shrink-0"
                     style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-elevated);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: var(--forest); color: var(--bg-primary);">
                            <i class="fa fa-store"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black" style="color: var(--text-primary);">{{ $selectedTenantData['name'] }}</h3>
                            <p class="text-xs" style="color: var(--text-muted);">Detailed Sales Analytics</p>
                        </div>
                    </div>
                    <button wire:click="closeTenantModal"
                            class="text-2xl w-10 h-10 rounded-full flex items-center justify-center transition"
                            style="color: var(--text-muted);"
                            onmouseover="this.style.color='var(--accent)'"
                            onmouseout="this.style.color='var(--text-muted)'">
                        &times;
                    </button>
                </div>

                <div class="p-6 overflow-y-auto" style="flex: 1;">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Total Sales</p>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($selectedTenantData['total_sales'], 2) }}</p>
                        </div>
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Total Orders</p>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">{{ $selectedTenantData['total_orders'] }}</p>
                        </div>
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Average Order</p>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($selectedTenantData['avg_order'], 2) }}</p>
                        </div>
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Branches</p>
                            <p class="text-2xl font-black" style="color: var(--info);">{{ count($selectedTenantData['branches']) }}</p>
                        </div>
                    </div>

                    <h4 class="font-black mb-3 flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-store" style="color: var(--accent);"></i> Branch Breakdown
                        <span class="text-xs font-normal" style="color: var(--text-muted);">(Click branch to view details)</span>
                    </h4>
                    <div class="inner overflow-hidden mb-6">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[600px]">
                                <thead style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Branch</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Manager</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Sales</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Orders</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Avg Order</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedTenantData['branches'] as $branch)
                                        <tr class="cursor-pointer transition" style="border-bottom: 1px solid var(--border-soft);"
                                            wire:click="viewBranchSales({{ $branch['id'] }})">
                                            <td class="px-4 py-3 text-sm font-semibold" style="color: var(--text-primary);">{{ $branch['name'] }}</td>
                                            <td class="px-4 py-3">
                                                @if($branch['manager'] && $branch['manager'] !== 'Not Assigned')
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                                             style="background-color: color-mix(in srgb, var(--violet) 20%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent);">
                                                            {{ substr($branch['manager'], 0, 1) }}
                                                        </div>
                                                        <span class="text-sm" style="color: var(--text-primary);">{{ $branch['manager'] }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-xs" style="color: var(--text-muted);">Not Assigned</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 font-bold" style="color: var(--accent);">₱{{ number_format($branch['sales'], 2) }}</td>
                                            <td class="px-4 py-3" style="color: var(--text-muted);">{{ $branch['orders'] }}</td>
                                            <td class="px-4 py-3" style="color: var(--text-muted);">₱{{ number_format($branch['avg_order'], 2) }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                      style="background-color: color-mix(in srgb, {{ $branch['status'] === 'Active' ? 'var(--ok)' : 'var(--bad)' }} 10%, transparent);
                                                             color: {{ $branch['status'] === 'Active' ? 'var(--ok)' : 'var(--bad)' }};
                                                             border: 1px solid color-mix(in srgb, {{ $branch['status'] === 'Active' ? 'var(--ok)' : 'var(--bad)' }} 30%, transparent);">
                                                    {{ $branch['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(!empty($selectedTenantData['top_products']) && count($selectedTenantData['top_products']) > 0)
                        <h4 class="font-black mb-3 flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-crown" style="color: var(--accent);"></i> Top Products
                        </h4>
                        <div class="inner overflow-hidden mb-6">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[500px]">
                                    <thead style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-soft);">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Product</th>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Sales</th>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Orders</th>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedTenantData['top_products'] as $product)
                                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                                <td class="px-4 py-3 text-sm font-semibold" style="color: var(--text-primary);">{{ $product->name }}</td>
                                                <td class="px-4 py-3 font-bold" style="color: var(--accent);">₱{{ number_format($product->total_sales, 2) }}</td>
                                                <td class="px-4 py-3" style="color: var(--text-muted);">{{ $product->order_count }}</td>
                                                <td class="px-4 py-3" style="color: var(--text-muted);">{{ $product->total_quantity }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <h4 class="font-black mb-3 flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-receipt" style="color: var(--accent);"></i> Recent Orders
                    </h4>
                    <div class="inner overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px]">
                                <thead style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Date & Time</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Branch</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Manager</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Type</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Cashier</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Customer</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Amount</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedTenantData['recent_orders'] as $order)
                                        <tr style="border-bottom: 1px solid var(--border-soft);">
                                            <td class="px-4 py-3 text-xs whitespace-nowrap" style="color: var(--text-muted);">{{ $order['date'] }}</td>
                                            <td class="px-4 py-3 text-xs" style="color: var(--text-muted);">{{ $order['branch'] }}</td>
                                            <td class="px-4 py-3">
                                                @if($order['branch_manager'] && $order['branch_manager'] !== 'Not Assigned')
                                                    <span class="text-xs" style="color: var(--text-primary);">{{ $order['branch_manager'] }}</span>
                                                @else
                                                    <span class="text-xs" style="color: var(--text-muted);">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold"
                                                      style="background-color: color-mix(in srgb, {{ $order['order_type'] === 'counter' ? 'var(--violet)' : 'var(--info)' }} 10%, transparent);
                                                             color: {{ $order['order_type'] === 'counter' ? 'var(--violet)' : 'var(--info)' }};
                                                             border: 1px solid color-mix(in srgb, {{ $order['order_type'] === 'counter' ? 'var(--violet)' : 'var(--info)' }} 30%, transparent);">
                                                    <i class="fa {{ $order['order_type'] === 'counter' ? 'fa-user-check' : 'fa-mobile-alt' }} text-xs"></i>
                                                    {{ $order['order_type'] === 'counter' ? 'Counter' : 'Self-Service' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($order['cashier'])
                                                    <span class="text-sm" style="color: var(--text-primary);">{{ $order['cashier'] }}</span>
                                                @else
                                                    <span class="text-xs" style="color: var(--text-muted);">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm" style="color: var(--text-primary);">{{ $order['customer'] }}</td>
                                            <td class="px-4 py-3 font-bold whitespace-nowrap" style="color: var(--accent);">₱{{ number_format($order['amount'], 2) }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold"
                                                      style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                    {{ $order['payment'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center" style="color: var(--text-muted);">No orders found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="p-4 text-center flex-shrink-0"
                     style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                    <button wire:click="closeTenantModal"
                            class="px-6 py-2.5 rounded-xl font-bold transition-all"
                            style="background-color: var(--accent); color: var(--bg-primary);">
                        <i class="fa fa-times mr-2"></i> Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showBranchModal && $selectedBranchData)
        <div class="admin-root"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2147483647; display: flex; align-items: flex-start; justify-content: center; padding: 1rem; overflow-y: auto;">

            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"
                 wire:click="closeBranchModal"></div>

            <div class="w-full max-w-5xl overflow-hidden relative shadow-2xl"
                 style="position: relative;
                        z-index: 1;
                        margin: 1rem auto;
                        background-color: var(--bg-elevated);
                        border: 1px solid var(--border-soft);
                        border-radius: 1rem;
                        max-height: 90vh;
                        display: flex;
                        flex-direction: column;">

                <div class="p-5 flex justify-between items-center flex-wrap gap-3 flex-shrink-0"
                     style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-elevated);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: var(--info); color: var(--bg-primary);">
                            <i class="fa fa-store"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black" style="color: var(--text-primary);">{{ $selectedBranchData['name'] }}</h3>
                            <p class="text-xs" style="color: var(--text-muted);">
                                {{ $selectedBranchData['tenant_name'] }} •
                                <span style="color: var(--info);">{{ $selectedBranchData['manager'] }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="closeBranchModal"
                                class="text-2xl w-10 h-10 rounded-full flex items-center justify-center transition"
                                style="color: var(--text-muted);">
                            &times;
                        </button>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto" style="flex: 1;">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Total Sales</p>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($selectedBranchData['total_sales'], 2) }}</p>
                        </div>
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Total Orders</p>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">{{ $selectedBranchData['total_orders'] }}</p>
                        </div>
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Average Order</p>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($selectedBranchData['avg_order'], 2) }}</p>
                        </div>
                        <div class="inner p-4">
                            <p class="text-xs" style="color: var(--text-muted);">Staff</p>
                            <p class="text-2xl font-black" style="color: var(--violet);">{{ $selectedBranchData['staff_count'] }}</p>
                        </div>
                    </div>

                    <div class="inner p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                 style="background-color: color-mix(in srgb, var(--violet) 20%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent);">
                                <i class="fa fa-user-tie"></i>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wider font-bold" style="color: var(--text-muted);">Branch Manager</p>
                                @if($selectedBranchData['manager'] && $selectedBranchData['manager'] !== 'Not Assigned')
                                    <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $selectedBranchData['manager'] }}</p>
                                    @if($selectedBranchData['manager_email'])
                                        <p class="text-xs" style="color: var(--text-muted);">{{ $selectedBranchData['manager_email'] }}</p>
                                    @endif
                                @else
                                    <p class="text-sm" style="color: var(--text-muted);">Not Assigned</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <h4 class="font-black mb-3 flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-chart-bar" style="color: var(--info);"></i> Daily Sales (Last 7 Days)
                    </h4>
                    <div class="inner p-4 mb-6">
                        <div class="flex items-end justify-between gap-2 h-32">
                            @php $maxSales = max(collect($selectedBranchData['daily_sales'])->pluck('sales')->toArray()) ?: 1; @endphp
                            @foreach($selectedBranchData['daily_sales'] as $day)
                                @php $height = ($day['sales'] / $maxSales) * 100; @endphp
                                <div class="w-full text-center group">
                                    <div class="relative flex flex-col items-center">
                                        <div class="w-full rounded-t-lg transition-all duration-500"
                                             style="height: {{ max($height, 5) }}px; background-color: color-mix(in srgb, var(--info) 20%, transparent); border-top: 1px solid color-mix(in srgb, var(--info) 40%, transparent);">
                                            <div class="absolute -top-8 scale-0 group-hover:scale-100 transition-all duration-200 text-[10px] font-medium px-2 py-1 rounded-lg whitespace-nowrap z-50"
                                                 style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid color-mix(in srgb, var(--info) 50%, transparent);">
                                                ₱{{ number_format($day['sales'], 2) }}
                                                <br>
                                                {{ $day['orders'] }} orders
                                            </div>
                                        </div>
                                        <span class="text-[8px] mt-1" style="color: var(--text-muted);">{{ substr($day['date'], 0, 3) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <h4 class="font-black mb-3 flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-users" style="color: var(--violet);"></i> Staff
                    </h4>
                    <div class="inner overflow-hidden mb-6">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[600px]">
                                <thead style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Name</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Email</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Role</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedBranchData['staff'] as $member)
                                        <tr style="border-bottom: 1px solid var(--border-soft);">
                                            <td class="px-4 py-3 text-sm font-semibold" style="color: var(--text-primary);">{{ $member->name }}</td>
                                            <td class="px-4 py-3 text-sm" style="color: var(--text-muted);">{{ $member->email }}</td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $roleColor = match($member->role) {
                                                        'branch_manager' => 'var(--violet)',
                                                        'cashier' => 'var(--info)',
                                                        default => 'var(--ok)',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                      style="background-color: color-mix(in srgb, {{ $roleColor }} 10%, transparent); color: {{ $roleColor }}; border: 1px solid color-mix(in srgb, {{ $roleColor }} 30%, transparent);">
                                                    {{ ucfirst(str_replace('_', ' ', $member->role)) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                      style="background-color: color-mix(in srgb, {{ $member->is_active ? 'var(--ok)' : 'var(--bad)' }} 10%, transparent);
                                                             color: {{ $member->is_active ? 'var(--ok)' : 'var(--bad)' }};
                                                             border: 1px solid color-mix(in srgb, {{ $member->is_active ? 'var(--ok)' : 'var(--bad)' }} 30%, transparent);">
                                                    <i class="fa-solid fa-circle text-[5px]"></i>
                                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center" style="color: var(--text-muted);">No staff found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(!empty($selectedBranchData['top_products']) && count($selectedBranchData['top_products']) > 0)
                        <h4 class="font-black mb-3 flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-crown" style="color: var(--accent);"></i> Top Products
                        </h4>
                        <div class="inner overflow-hidden mb-6">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[500px]">
                                    <thead style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-soft);">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Product</th>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Sales</th>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Orders</th>
                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedBranchData['top_products'] as $product)
                                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                                <td class="px-4 py-3 text-sm font-semibold" style="color: var(--text-primary);">{{ $product->name }}</td>
                                                <td class="px-4 py-3 font-bold" style="color: var(--accent);">₱{{ number_format($product->total_sales, 2) }}</td>
                                                <td class="px-4 py-3" style="color: var(--text-muted);">{{ $product->order_count }}</td>
                                                <td class="px-4 py-3" style="color: var(--text-muted);">{{ $product->total_quantity }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <h4 class="font-black mb-3 flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-receipt" style="color: var(--accent);"></i> Recent Orders
                    </h4>
                    <div class="inner overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[700px]">
                                <thead style="background-color: var(--bg-elevated); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Date & Time</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Cashier</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Customer</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Amount</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-widest">Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedBranchData['recent_orders'] as $order)
                                        <tr style="border-bottom: 1px solid var(--border-soft);">
                                            <td class="px-4 py-3 text-xs whitespace-nowrap" style="color: var(--text-muted);">{{ $order['date'] }}</td>
                                            <td class="px-4 py-3">
                                                @if($order['cashier'])
                                                    <span class="text-sm" style="color: var(--text-primary);">{{ $order['cashier'] }}</span>
                                                @else
                                                    <span class="text-xs" style="color: var(--text-muted);">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm" style="color: var(--text-primary);">{{ $order['customer'] }}</td>
                                            <td class="px-4 py-3 font-bold whitespace-nowrap" style="color: var(--accent);">₱{{ number_format($order['amount'], 2) }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 rounded-full text-[10px] font-bold"
                                                      style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                    {{ $order['payment'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center" style="color: var(--text-muted);">No orders found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="p-4 text-center flex justify-center gap-3 flex-wrap flex-shrink-0"
                     style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                    <button wire:click="closeBranchModal"
                            class="px-6 py-2.5 rounded-xl font-bold transition-all"
                            style="background-color: var(--info); color: var(--bg-primary);">
                        <i class="fa fa-times mr-2"></i> Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
    (function () {
        let salesChart  = null;
        let tenantChart = null;

        function cssVar(name, fallback) {
            const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            return v || fallback;
        }

        function readData(el) {
            if (!el) return { labels: [], values: [] };
            let labels = [];
            let values = [];
            try { labels = JSON.parse(el.dataset.labels || '[]'); } catch (e) {}
            try { values = JSON.parse(el.dataset.values || '[]'); } catch (e) {}
            return { labels, values };
        }

        function renderCharts() {
            const salesEl  = document.querySelector('#salesTrendChart');
            const tenantEl = document.querySelector('#tenantSalesChart');
            if (!salesEl && !tenantEl) return;
            if (typeof ApexCharts === 'undefined') return;

            const isDark   = document.documentElement.classList.contains('dark');
            const accent   = cssVar('--accent', '#c8965f');
            const muted    = cssVar('--text-muted', isDark ? '#8a9a94' : '#6b7f7a');
            const cream    = cssVar('--bg-primary', '#f5f0e8');
            const grid     = isDark ? 'rgba(212,165,116,0.12)' : 'rgba(26,58,58,0.10)';

            if (salesEl) {
                const { labels, values } = readData(salesEl);

                if (salesChart) {
                    salesChart.destroy();
                    salesChart = null;
                }

                salesChart = new ApexCharts(salesEl, {
                    chart: {
                        type: 'area',
                        height: 280,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        fontFamily: 'Inter, ui-sans-serif',
                        foreColor: muted
                    },
                    series: [{
                        name: 'Sales',
                        data: values
                    }],
                    colors: [accent],
                    stroke: { curve: 'smooth', width: 3 },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.35,
                            opacityTo: 0.02,
                            stops: [0, 90, 100]
                        }
                    },
                    dataLabels: { enabled: false },
                    grid: {
                        borderColor: grid,
                        strokeDashArray: 3,
                        padding: { left: 8, right: 8 }
                    },
                    xaxis: {
                        categories: labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: { colors: muted, fontSize: '11px' }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: muted, fontSize: '11px' },
                            formatter: (v) => '₱' + Number(v).toLocaleString()
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: (v) => '₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 })
                        }
                    },
                    markers: {
                        size: 4,
                        strokeWidth: 2,
                        strokeColors: cream,
                        hover: { size: 6 }
                    }
                });

                salesChart.render();
            }

            if (tenantEl) {
                const { labels, values } = readData(tenantEl);

                if (tenantChart) {
                    tenantChart.destroy();
                    tenantChart = null;
                }

                tenantChart = new ApexCharts(tenantEl, {
                    chart: {
                        type: 'bar',
                        height: 280,
                        toolbar: { show: false },
                        fontFamily: 'Inter, ui-sans-serif',
                        foreColor: muted
                    },
                    series: [{
                        name: 'Sales',
                        data: values
                    }],
                    colors: [accent],
                    plotOptions: {
                        bar: {
                            borderRadius: 8,
                            columnWidth: '55%',
                            distributed: false
                        }
                    },
                    dataLabels: { enabled: false },
                    grid: {
                        borderColor: grid,
                        strokeDashArray: 3
                    },
                    xaxis: {
                        categories: labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: { colors: muted, fontSize: '11px' }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: muted, fontSize: '11px' },
                            formatter: (v) => '₱' + Number(v).toLocaleString()
                        }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: (v) => '₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 })
                        }
                    }
                });

                tenantChart.render();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderCharts);
        } else {
            setTimeout(renderCharts, 0);
        }

        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ el }) => {
                if (!el || !el.querySelector) return;
                if (
                    el.querySelector('#salesTrendChart') ||
                    el.querySelector('#tenantSalesChart') ||
                    el.id === 'salesTrendChart' ||
                    el.id === 'tenantSalesChart'
                ) {
                    requestAnimationFrame(renderCharts);
                }
            });
        });

        document.addEventListener('livewire:navigated', renderCharts);
        window.addEventListener('theme-changed', renderCharts);
    })();
    </script>

</div>