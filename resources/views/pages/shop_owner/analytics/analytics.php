<?php

namespace App\Livewire\Pages\ShopOwner;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts.shop_owner')] class extends Component
{
    /**
     * Location filter.
     *   'all'            → every location
     *   '__own_store__'  → only the tenant's own store (branch_id null)
     *   <int>            → a specific extra branch
     */
    public string $selectedBranch = 'all';

    /**
     * Period filter.
     *   'daily'   → today
     *   'weekly'  → last 7 days (existing default)
     *   'monthly' → current month
     *   'yearly'  → current year
     */
    public string $period = 'weekly';

    public function updatedSelectedBranch(): void
    {
        // auto re-render
    }

    public function updatedPeriod(): void
    {
        // auto re-render
    }

    public function setPeriod(string $period): void
    {
        if (in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            $this->period = $period;
        }
    }

    #[On('refresh-analytics')]
    public function refreshAnalytics(): void
    {
        foreach ([
            'branches', 'mainBranchId', 'branchLabel', 'periodLabel',
            'periodRange', 'todayRevenue', 'totalOrders', 'avgOrderValue', 'averageRating',
            'salesTrend', 'salesTrendTitle', 'categorySales', 'topProducts', 'recentFeedback',
            'peakHours', 'avgDailyRevenue',
        ] as $key) {
            unset($this->$key);
        }
    }

    // ================================================================
    // HELPERS
    // ================================================================

    protected function tenantId(): ?int
    {
        return Auth::user()->tenant_id ?? session('tenant_id');
    }

    /**
     * Apply branch filter to a query.
     */
    protected function applyBranchFilter($query)
    {
        if ($this->selectedBranch === 'all'
            || $this->selectedBranch === null
            || $this->selectedBranch === ''
        ) {
            return $query;
        }

        if ($this->selectedBranch === '__own_store__') {
            return $query->whereNull('branch_id');
        }

        return $query->where('branch_id', (int) $this->selectedBranch);
    }

    /**
     * [start, end] Carbon range for the current period.
     */
    protected function periodRange(): array
    {
        $now = Carbon::now();

        return match ($this->period) {
            'daily'   => [$now->copy()->startOfDay(),   $now->copy()->endOfDay()],
            'weekly'  => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'yearly'  => [$now->copy()->startOfYear(),  $now->copy()->endOfYear()],
            default   => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    protected function applyPeriodFilter($query, string $column = 'created_at')
    {
        [$start, $end] = $this->periodRange();
        return $query->whereBetween($column, [$start, $end]);
    }

    // ================================================================
    // COMPUTED — branches & labels
    // ================================================================

    #[Computed]
    public function branches()
    {
        return Branch::where('tenant_id', $this->tenantId())
            ->where('approval_status', 'approved')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function mainBranchId(): ?int
    {
        return null;
    }

    #[Computed]
    public function branchLabel(): string
    {
        if ($this->selectedBranch === 'all' || $this->selectedBranch === null) {
            return 'All Locations';
        }

        if ($this->selectedBranch === '__own_store__') {
            return 'Your Shop';
        }

        return $this->branches->firstWhere('id', (int) $this->selectedBranch)?->name ?? 'Branch';
    }

    #[Computed]
    public function periodLabel(): string
    {
        [$start, $end] = $this->periodRange();

        return match ($this->period) {
            'daily'   => 'Today · ' . $start->format('M d, Y'),
            'weekly'  => 'Last 7 Days · ' . $start->format('M d') . ' – ' . $end->format('M d, Y'),
            'monthly' => $start->format('F Y'),
            'yearly'  => 'Year ' . $start->format('Y'),
            default   => 'Last 7 Days',
        };
    }

    // ================================================================
    // COMPUTED — KPIs (all filtered by period + branch)
    // ================================================================

    #[Computed]
    public function todayRevenue(): float
    {
        $q = Order::where('tenant_id', $this->tenantId())
            ->where('status', 'completed');

        $q = $this->applyBranchFilter($q);
        $q = $this->applyPeriodFilter($q);

        return (float) $q->sum('total_amount');
    }

    #[Computed]
    public function totalOrders(): int
    {
        $q = Order::where('tenant_id', $this->tenantId())
            ->where('status', 'completed');

        $q = $this->applyBranchFilter($q);
        $q = $this->applyPeriodFilter($q);

        return (int) $q->count();
    }

    #[Computed]
    public function avgOrderValue(): float
    {
        if ($this->totalOrders === 0) return 0;

        $q = Order::where('tenant_id', $this->tenantId())
            ->where('status', 'completed');

        $q = $this->applyBranchFilter($q);
        $q = $this->applyPeriodFilter($q);

        return (float) $q->avg('total_amount');
    }

    #[Computed]
    public function averageRating(): float
    {
        $q = Review::where('tenant_id', $this->tenantId())->where('is_visible', true);
        $q = $this->applyBranchFilter($q);
        $q = $this->applyPeriodFilter($q);

        return (float) ($q->avg('rating') ?? 0);
    }

    // ================================================================
    // COMPUTED — Sales trend (adapts to period)
    // ================================================================

    #[Computed]
    public function salesTrend()
    {
        $q = Order::where('tenant_id', $this->tenantId())
            ->where('status', 'completed');

        $q = $this->applyBranchFilter($q);

        // Bucket by day for daily/weekly/monthly, by month for yearly
        if ($this->period === 'yearly') {
            $q->whereYear('created_at', now()->year)
              ->select(
                  DB::raw('DATE_FORMAT(created_at, "%Y-%m") as bucket'),
                  DB::raw('SUM(total_amount) as total')
              )
              ->groupBy('bucket')
              ->orderBy('bucket');

            $rows = $q->get()->keyBy('bucket');

            $out = collect();
            for ($m = 1; $m <= 12; $m++) {
                $key = now()->year . '-' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                $out->push((object) [
                    'date'  => $key,
                    'label' => Carbon::create(now()->year, $m, 1)->format('M'),
                    'total' => (float) ($rows->get($key)->total ?? 0),
                ]);
            }
            return $out;
        }

        // daily → today only; weekly → last 7; monthly → current month
        [$start, $end] = $this->periodRange();

        $q->whereBetween('created_at', [$start, $end])
          ->select(
              DB::raw('DATE(created_at) as bucket'),
              DB::raw('SUM(total_amount) as total')
          )
          ->groupBy('bucket')
          ->orderBy('bucket');

        $rows = $q->get()->keyBy('bucket');

        $out = collect();
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $out->push((object) [
                'date'  => $key,
                'label' => $cursor->format('D'),
                'total' => (float) ($rows->get($key)->total ?? 0),
            ]);
            $cursor->addDay();
        }
        return $out;
    }

    #[Computed]
    public function salesTrendTitle(): string
    {
        return match ($this->period) {
            'daily'   => 'Hourly Revenue Stream',
            'weekly'  => 'Weekly Revenue Stream',
            'monthly' => 'Monthly Revenue Stream',
            'yearly'  => 'Yearly Revenue Stream',
            default   => 'Revenue Stream',
        };
    }

    // ================================================================
    // COMPUTED — Category mix (filtered by period)
    // ================================================================

    #[Computed]
    public function categorySales()
    {
        $q = DB::table('categories')
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('categories.tenant_id', $this->tenantId())
            ->where('orders.status', 'completed')
            ->where('products.type', 'finished');

        [$start, $end] = $this->periodRange();
        $q->whereBetween('orders.created_at', [$start, $end]);

        if ($this->selectedBranch === '__own_store__') {
            $q->whereNull('orders.branch_id');
        } elseif ($this->selectedBranch !== 'all' && $this->selectedBranch !== null) {
            $q->where('orders.branch_id', (int) $this->selectedBranch);
        }

        return $q->select(
                'categories.cat_name as label',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as value')
            )
            ->groupBy('categories.id', 'categories.cat_name')
            ->having('value', '>', 0)
            ->get();
    }

    // ================================================================
    // COMPUTED — Top products (filtered by period)
    // ================================================================

    #[Computed]
    public function topProducts()
    {
        $branchId     = null;
        $ownStoreOnly = false;

        if ($this->selectedBranch === '__own_store__') {
            $ownStoreOnly = true;
        } elseif ($this->selectedBranch !== 'all' && $this->selectedBranch !== null) {
            $branchId = (int) $this->selectedBranch;
        }

        [$start, $end] = $this->periodRange();

        return Product::where('tenant_id', $this->tenantId())
            ->where('type', 'finished')
            ->where('is_active', true)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->when($ownStoreOnly, fn ($query) => $query->whereNull('branch_id'))
            ->addSelect([
                'orders_count' => function ($query) use ($branchId, $ownStoreOnly, $start, $end) {
                    $query->selectRaw('IFNULL(SUM(quantity), 0)')
                        ->from('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->whereColumn('order_items.product_id', 'products.id')
                        ->where('orders.status', 'completed')
                        ->whereBetween('orders.created_at', [$start, $end])
                        ->when($branchId, fn ($q) => $q->where('orders.branch_id', $branchId))
                        ->when($ownStoreOnly, fn ($q) => $q->whereNull('orders.branch_id'));
                },
            ])
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();
    }

    // ================================================================
    // COMPUTED — Recent feedback (filtered by period)
    // ================================================================

    #[Computed]
    public function recentFeedback()
    {
        $q = Review::with('user')
            ->where('tenant_id', $this->tenantId())
            ->where('is_visible', true);

        $q = $this->applyBranchFilter($q);
        $q = $this->applyPeriodFilter($q);

        return $q->latest()->take(3)->get()->map(fn ($review) => [
            'customer' => $review->user->name ?? 'Anonymous',
            'stars'    => (int) $review->rating,
            'comment'  => (string) $review->comment,
        ]);
    }

    // ================================================================
    // COMPUTED — Peak hours (filtered by period)
    // ================================================================

    #[Computed]
    public function peakHours(): array
    {
        $q = DB::table('orders')
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'completed');

        [$start, $end] = $this->periodRange();
        $q->whereBetween('created_at', [$start, $end]);

        if ($this->selectedBranch === '__own_store__') {
            $q->whereNull('branch_id');
        } elseif ($this->selectedBranch !== 'all' && $this->selectedBranch !== null) {
            $q->where('branch_id', (int) $this->selectedBranch);
        }

        $raw = $q->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $labels = [];
        $data   = [];
        $peak   = 0;
        $max    = 0;

        for ($i = 6; $i <= 22; $i++) {
            $count    = (int) ($raw[$i] ?? 0);
            $labels[] = $i . ':00';
            $data[]   = $count;

            if ($count > $max) {
                $max  = $count;
                $peak = $i;
            }
        }

        return [
            'labels'   => $labels,
            'data'     => $data,
            'peakHour' => $peak,
        ];
    }

    // ================================================================
    // COMPUTED — Avg daily revenue (filtered by period)
    // ================================================================

    #[Computed]
    public function avgDailyRevenue(): float
    {
        $totalQuery = Order::where('tenant_id', $this->tenantId())->where('status', 'completed');
        $totalQuery = $this->applyBranchFilter($totalQuery);
        $totalQuery = $this->applyPeriodFilter($totalQuery);
        $total = (float) $totalQuery->sum('total_amount');

        $daysQuery = Order::where('tenant_id', $this->tenantId())
            ->where('status', 'completed')
            ->select(DB::raw('COUNT(DISTINCT DATE(created_at)) as days'));
        $daysQuery = $this->applyBranchFilter($daysQuery);
        $daysQuery = $this->applyPeriodFilter($daysQuery);

        $daysActive = (int) ($daysQuery->value('days') ?: 1);

        return $total / max(1, $daysActive);
    }
};
