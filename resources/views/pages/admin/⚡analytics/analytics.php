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

    // ================================================================
    // PERIOD
    // ================================================================
    
    public function setPeriod($period)
    {
        $this->period = $period;
    }

    // ================================================================
    // ANCHOR DATE — the key fix
    // ================================================================
    // Anchors the chart to the latest completed order's date instead of
    // now(), so the chart always shows a period that actually has data.
    // If we're early in the current month (before day 5), we anchor to
    // the END of last month so the chart shows a full month of data.

    private function anchorDate(): Carbon
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        $latest = Order::where('status', 'completed')
            ->whereNotNull('created_at')
            ->max('created_at');

        $latestDate = $latest ? Carbon::parse($latest) : now();
        $today      = now();

        // ✅ If we're early in the month (before day 5), anchor to the END of
        //    last month so the chart shows a complete month of data instead
        //    of a mostly-empty current month.
        if ($today->day < 5) {
            return $cached = $today->copy()->subMonthNoOverflow()->endOfMonth();
        }

        return $cached = $latestDate;
    }

    // ================================================================
    // MODAL CONTROLS
    // ================================================================
    
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

    // ================================================================
    // PROPERTIES
    // ================================================================
    
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
    
    // ================================================================
    // CHART DATA — fixed with anchor date
    // ================================================================
    
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

        // ---------- WEEK ----------
        if ($this->period === 'week') {
            return collect(range(6, 0))->map(fn($d) =>
                Order::where('status', 'completed')
                    ->whereDate('created_at', $anchor->copy()->subDays($d))
                    ->sum('total_amount')
            )->toArray();
        }

        // ---------- MONTH ----------
        if ($this->period === 'month') {
            return collect(range(1, $anchor->daysInMonth))->map(fn($d) =>
                Order::where('status', 'completed')
                    ->whereDay('created_at', $d)
                    ->whereMonth('created_at', $anchor->month)
                    ->whereYear('created_at', $anchor->year)
                    ->sum('total_amount')
            )->toArray();
        }

        // ---------- YEAR ----------
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
    
    // ================================================================
    // TENANT PERFORMANCE
    // ================================================================
    
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
    
    // ================================================================
    // BRANCH PERFORMANCE
    // ================================================================
    
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

    // ================================================================
    // SELECTED TENANT DATA
    // ================================================================
    
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
    
    // ================================================================
    // SELECTED BRANCH DATA
    // ================================================================
    
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
    
    // ================================================================
    // RENDER
    // ================================================================
    
    public function render()
    {
        return view('pages.admin.⚡analytics.analytics', [
            'totalSales' => Order::where('status', 'completed')->sum('total_amount'),
            'ordersToday' => Order::whereDate('created_at', today())->count(),
           'topProduct' => Product::withCount('orderItems as orders_count')->orderByDesc('orders_count')->first(),
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