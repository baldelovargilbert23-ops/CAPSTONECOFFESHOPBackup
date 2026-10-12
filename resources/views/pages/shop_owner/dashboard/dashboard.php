<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Carbon\Carbon;

new #[Layout('layouts.shop_owner')] class extends Component
{
    // ================================================================
    // STATE
    // ================================================================
    public $revenuePeriod = 'today';
    public $userFirstName = '';

    public function mount()
    {
        $name = Auth::user()->name ?? 'there';
        $this->userFirstName = explode(' ', trim($name))[0] ?: 'there';
    }

    public function setRevenuePeriod($period)
    {
        if (in_array($period, ['today', 'week', 'month', 'year'], true)) {
            $this->revenuePeriod = $period;
        }
    }

    // ================================================================
    // THE OWNER'S SHOP IS THE TENANT ITSELF (branch_id = NULL)
    // ================================================================
    private function ownStoreQuery()
    {
        return Order::where('tenant_id', Auth::user()->tenant_id)
            ->whereNull('branch_id');
    }

    // ================================================================
    // REVENUE — CURRENT PERIOD
    // ================================================================
    public function getPeriodRevenueProperty()
    {
        $query = $this->ownStoreQuery()->where('status', 'completed');
        $this->applyPeriod($query, $this->revenuePeriod);

        return (float) $query->sum('total_amount');
    }

    // ================================================================
    // REVENUE — PREVIOUS PERIOD
    // ================================================================
    public function getPreviousPeriodRevenueProperty()
    {
        $query = $this->ownStoreQuery()->where('status', 'completed');
        $this->applyFairPreviousPeriod($query, $this->revenuePeriod);

        return (float) $query->sum('total_amount');
    }

    // ================================================================
    // REVENUE — COMPARISON
    // ================================================================
    public function getRevenueComparisonProperty()
    {
        $current  = $this->periodRevenue;
        $previous = $this->previousPeriodRevenue;

        if ($previous == 0) {
            $percentage = $current > 0 ? 100 : 0;
        } else {
            $percentage = round((($current - $previous) / $previous) * 100, 1);
        }

        return [
            'percentage' => $percentage,
            'current'    => $current,
            'previous'   => $previous,
        ];
    }

    // ================================================================
    // STATS
    // ================================================================
    public function getActiveOrdersProperty()
    {
        return $this->ownStoreQuery()
            ->whereIn('status', ['pending', 'brewing', 'preparing', 'ready'])
            ->count();
    }

    public function getTotalBranchesProperty()
    {
        return Branch::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->count();
    }

    public function getTotalStaffProperty()
    {
        return User::where('tenant_id', Auth::user()->tenant_id)
            ->whereNull('branch_id')
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->count();
    }

    public function getTotalOrdersProperty()
    {
        return $this->ownStoreQuery()
            ->where('status', 'completed')
            ->count();
    }

    // ================================================================
    // WEEKLY BREAKDOWN
    // ================================================================
    public function getWeeklyBreakdownProperty()
    {
        $start = Carbon::today()->subDays(6)->startOfDay();
        $end   = Carbon::today()->endOfDay();

        $totals = $this->ownStoreQuery()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->toArray();

        return collect(range(6, 0))->map(function ($daysAgo) use ($totals) {
            $date    = Carbon::today()->subDays($daysAgo);
            $dateKey = $date->toDateString();

            return [
                'day'     => $date->format('D'),
                'date'    => $date->format('M d'),
                'revenue' => (float) ($totals[$dateKey] ?? 0),
            ];
        })->toArray();
    }

    // ================================================================
    // RECENT ORDERS — own store only
    // ================================================================
    public function getRecentOrdersProperty()
    {
        return Order::with('branch')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->whereNull('branch_id')
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->take(10)
            ->get();
    }

    // ================================================================
    // TOP PRODUCTS — own store only
    // ================================================================
    public function getTopProductsProperty()
    {
        $tenantId = Auth::user()->tenant_id;

        return Product::query()
            ->select('products.*')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as orders_count')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.status', '=', 'completed')
                     ->whereNull('orders.branch_id');
            })
            ->where('products.tenant_id', $tenantId)
            ->whereNull('products.branch_id')
            ->groupBy('products.id')
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();
    }

    // ================================================================
    // RECENT FEEDBACK — own store only
    // ================================================================
    public function getRecentFeedbackProperty()
    {
        return Review::with('user')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->whereNull('branch_id')
            ->where('is_visible', true)
            ->latest()
            ->take(5)
            ->get();
    }

    // ================================================================
    // SUB-BRANCHES — every extra branch (tenant store is not a branch row)
    // ================================================================
    public function getSubBranchesProperty()
    {
        $tenantId = Auth::user()->tenant_id;

        $branches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('manager:id,name')
            ->orderBy('name')
            ->get();

        if ($branches->isEmpty()) {
            return collect();
        }

        $today = Carbon::today()->toDateString();

        $revenue = Order::where('tenant_id', $tenantId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->selectRaw('branch_id, SUM(total_amount) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id')
            ->toArray();

        $active = Order::where('tenant_id', $tenantId)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->whereIn('status', ['pending', 'brewing', 'preparing', 'ready'])
            ->selectRaw('branch_id, COUNT(*) as c')
            ->groupBy('branch_id')
            ->pluck('c', 'branch_id')
            ->toArray();

        return $branches->map(fn ($b) => [
            'id'            => $b->id,
            'name'          => $b->name,
            'code'          => $b->code,
            'manager_name'  => $b->manager->name ?? '—',
            'today_revenue' => (float) ($revenue[$b->id] ?? 0),
            'active_orders' => (int) ($active[$b->id] ?? 0),
        ]);
    }

    // ================================================================
    // PERIOD HELPERS
    // ================================================================
    private function applyPeriod($query, string $period): void
    {
        $now = Carbon::now();

        match ($period) {
            'today' => $query->whereDate('created_at', $now->copy()->today()),
            'week'  => $query->whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ]),
            'month' => $query->whereMonth('created_at', $now->month)
                             ->whereYear('created_at', $now->year),
            'year'  => $query->whereYear('created_at', $now->year),
            default => null,
        };
    }

    private function applyFairPreviousPeriod($query, string $period): void
    {
        $now = Carbon::now();

        match ($period) {
            'today' => $query->whereDate('created_at', $now->copy()->subDay()->toDateString()),

            'week' => $query->whereBetween('created_at', [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->startOfWeek()->addDays($now->dayOfWeek),
            ]),

            'month' => $query->whereBetween('created_at', [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->startOfMonth()->addDays($now->day - 1),
            ]),

            'year' => $query->whereBetween('created_at', [
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->startOfYear()->addDays($now->dayOfYear - 1),
            ]),

            default => null,
        };
    }

    // ================================================================
    // RENDER
    // ================================================================
    public function render()
    {
        return view('pages.shop_owner.dashboard.dashboard', [
            'userFirstName'     => $this->userFirstName,
            'revenuePeriod'     => $this->revenuePeriod,
            'periodRevenue'     => $this->periodRevenue,
            'revenueComparison' => $this->revenueComparison,
            'activeOrders'      => $this->activeOrders,
            'totalBranches'     => $this->totalBranches,
            'totalStaff'        => $this->totalStaff,
            'totalOrders'       => $this->totalOrders,
            'weeklyBreakdown'   => $this->weeklyBreakdown,
            'recentOrders'      => $this->recentOrders,
            'topProducts'       => $this->topProducts,
            'recentFeedback'    => $this->recentFeedback,
            'subBranches'       => $this->subBranches,
        ]);
    }
};
