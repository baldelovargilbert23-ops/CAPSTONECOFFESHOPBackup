<?php

namespace App\Livewire\Pages\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.admin')] class extends Component

{
    public $activeTimeRange = 'monthly';
    public $tenantSearch = '';
    
    public function setTimeRange($range)
    {
        $this->activeTimeRange = $range;
    }
    
    public function getFilteredTenantsProperty()
    {
        $query = Tenant::withSum(['orders as total_sales' => function($q) {
            $q->where('orders.status', 'completed');
        }], 'total_amount')
        ->where('tenants.status', 'active')
        ->orderByDesc('total_sales')
        ->take(5);
        
        if ($this->tenantSearch) {
            $query->where('tenants.name', 'like', '%' . $this->tenantSearch . '%');
        }
        
        return $query->get();
    }
    
    public function getCurrentPeriodLabelProperty()
    {
        return match($this->activeTimeRange) {
            'daily' => now()->format('F j, Y'),
            'monthly' => now()->format('F Y'),
            'yearly' => now()->format('Y'),
            default => 'Current Period'
        };
    }
    
    public function getWeeklyGrowthProperty()
    {
        $lastWeek = Order::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->sum('total_amount');
        $thisWeek = Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('total_amount');
        
        if ($lastWeek == 0) return 100;
        return round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1);
    }
    
    public function getDailyChartDataProperty()
    {
        return collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'label' => $date->format('D, M j'),
                'short_label' => $date->format('D'),
                'tenants' => Tenant::whereDate('created_at', $date)->count(),
                'customers' => User::where('role', 'customer')->whereDate('created_at', $date)->count(),
                'revenue_k' => Order::where('status', 'completed')->whereDate('created_at', $date)->sum('total_amount') / 1000,
            ];
        });
    }
    
    public function getMonthlyChartDataProperty()
    {
        return collect(range(1, 12))->map(function ($month) {
            $date = now()->month($month);
            return [
                'month' => $date->format('F'),
                'short' => $date->format('M'),
                'tenants' => Tenant::whereMonth('created_at', $month)->whereYear('created_at', now()->year)->count(),
                'customers' => User::where('role', 'customer')->whereMonth('created_at', $month)->whereYear('created_at', now()->year)->count(),
                'revenue_k' => Order::where('status', 'completed')->whereMonth('created_at', $month)->whereYear('created_at', now()->year)->sum('total_amount') / 1000,
            ];
        });
    }
    
    public function getYearlyChartDataProperty()
    {
        return collect(range(now()->year - 4, now()->year))->map(function ($year) {
            return [
                'year' => $year,
                'tenants' => Tenant::whereYear('created_at', $year)->count(),
                'customers' => User::where('role', 'customer')->whereYear('created_at', $year)->count(),
                'revenue_k' => Order::where('status', 'completed')->whereYear('created_at', $year)->sum('total_amount') / 1000,
            ];
        });
    }
    
    public function getActiveShopsProperty()
    {
        return Tenant::where('status', 'active')->count();
    }

    // ✅ NEW: Top Rated Shops
    public function getTopRatedShopsProperty()
    {
        return Tenant::where('status', 'active')
            ->withCount(['reviews'])
            ->with(['reviews'])
            ->get()
            ->map(function($tenant) {
                $avgRating = $tenant->reviews->avg('rating') ?? 0;
                $reviewCount = $tenant->reviews->count();
                
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'rating' => round($avgRating, 1),
                    'review_count' => $reviewCount,
                    'code' => $tenant->code,
                ];
            })
            ->filter(function($tenant) {
                return $tenant['review_count'] > 0;
            })
            ->sortByDesc('rating')
            ->take(5)
            ->values()
            ->toArray();
    }

    // ✅ NEW: Rating distribution for top shops
    public function getRatingDistributionProperty()
    {
        $tenants = Tenant::where('status', 'active')
            ->with(['reviews'])
            ->get()
            ->map(function($tenant) {
                $avgRating = $tenant->reviews->avg('rating') ?? 0;
                return [
                    'name' => $tenant->name,
                    'rating' => round($avgRating, 1),
                    'review_count' => $tenant->reviews->count(),
                ];
            })
            ->filter(function($tenant) {
                return $tenant['review_count'] > 0;
            })
            ->sortByDesc('rating')
            ->take(3)
            ->values()
            ->toArray();

        return $tenants;
    }
    
    public function render()
    {
        $stats = (object) [
            'totalShops'       => Tenant::count(),
            'activeShops'      => $this->activeShops,
            'totalCustomers'   => User::where('role', 'customer')->count(),
            'totalTransactions'=> Order::count(),
            'totalRevenue'     => Order::where('status', 'completed')->sum('total_amount'),
            'pendingCount'     => Tenant::where('status', 'pending')->count(),
        ];

        $topTenants = Tenant::where('tenants.status', 'active')
            ->withSum(['orders as total_sales' => function($query) {
                $query->where('orders.status', 'completed');
            }], 'total_amount')
            ->orderByDesc('total_sales')
            ->take(5)
            ->get();

        $topWeeklyShops = Tenant::where('tenants.status', 'active')
            ->join('orders', 'tenants.id', '=', 'orders.tenant_id')
            ->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('orders.status', 'completed')
            ->select('tenants.id', 'tenants.name', 'tenants.status')
            ->selectRaw('SUM(orders.total_amount) as weekly_sales')
            ->groupBy('tenants.id', 'tenants.name', 'tenants.status')
            ->orderByDesc('weekly_sales')
            ->take(3)
            ->get();

        $chartData = collect(range(1, 12))->map(function ($month) {
            return [
                'month'     => date('M', mktime(0, 0, 0, $month, 1)),
                'tenants'   => Tenant::whereMonth('created_at', $month)->whereYear('created_at', date('Y'))->count(),
                'customers' => User::where('role', 'customer')->whereMonth('created_at', $month)->whereYear('created_at', date('Y'))->count(),
            ];
        });

        return view('pages.admin.⚡dashboard.dashboard', [
            'stats'              => $stats,
            'topTenants'         => $topTenants,
            'topWeeklyShops'     => $topWeeklyShops,
            'chartData'          => $chartData,
            'filteredTenants'    => $this->filteredTenants,
            'activeTimeRange'    => $this->activeTimeRange,
            'currentPeriodLabel' => $this->currentPeriodLabel,
            'weeklyGrowth'       => $this->weeklyGrowth,
            'dailyChartData'     => $this->dailyChartData,
            'monthlyChartData'   => $this->monthlyChartData,
            'yearlyChartData'    => $this->yearlyChartData,
            'topRatedShops'      => $this->topRatedShops,
            'ratingDistribution' => $this->ratingDistribution,
        ]);
    }
};