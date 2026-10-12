<?php

use App\Models\Review;
use App\Models\Tenant;
use App\Models\Order;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Inventory;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.customer')] class extends Component
{
    public string $globalSearch = '';
    public bool   $showSearchResults = false;
    public bool   $showMobileSearch = false;
    public array  $searchResults = [];
    public string $sortBy = 'rating';
    public string $timeFilter = 'all';

    public function mount()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }
    }

    /* ================================================================
     *  GLOBAL SEARCH
     * ================================================================ */

    public function searchGlobal(): void
    {
        if (strlen($this->globalSearch) < 2) {
            $this->showSearchResults = false;
            $this->searchResults = [];
            return;
        }

        $this->showSearchResults = true;

        $shops = Tenant::where('status', 'active')
            ->where('name', 'like', '%' . $this->globalSearch . '%')
            ->take(3)
            ->get()
            ->map(fn ($shop) => [
                'title' => $shop->name,
                'type'  => 'Coffee Shop',
                'icon'  => 'fa-store',
                'url'   => route('customer.shopdetail', $shop->id),
            ]);

        $products = Product::where('type', 'finished')
            ->where('is_active', true)
            ->where('name', 'like', '%' . $this->globalSearch . '%')
            ->whereHas('tenant', fn ($q) => $q->where('status', 'active'))
            ->where(function ($query) {
                $query->whereNull('branch_id')->orWhereHas('branch', fn ($branch) =>
                    $branch->where('is_active', true)->where('approval_status', 'approved')
                );
            })
            ->with(['tenant:id,name', 'branch:id,name'])
            ->take(3)
            ->get()
            ->map(fn ($product) => [
                'title' => $product->name,
                'type'  => 'Product • ' . ($product->tenant->name ?? 'Unknown Shop')
                    . ($product->branch ? ' · ' . $product->branch->name : ' · Main Store'),
                'icon'  => 'fa-cube',
                'url'   => route('customer.shopmenu', [
                    'tenant' => $product->tenant_id,
                    'branch' => $product->branch_id ?? 0,
                ]),
            ]);

        $this->searchResults = $shops->concat($products)->take(6)->toArray();
    }

    public function openSearchModal(): void
    {
        $this->showMobileSearch = true;
    }

    public function closeSearchModal(): void
    {
        $this->showMobileSearch = false;
        $this->globalSearch = '';
        $this->searchResults = [];
        $this->showSearchResults = false;
    }

    /* ================================================================
     *  HELPERS
     * ================================================================ */

    protected function timeFilterStart(): ?Carbon
    {
        return match ($this->timeFilter) {
            'week'  => now()->subDays(7),
            'month' => now()->subDays(30),
            'year'  => now()->subDays(365),
            default => null,
        };
    }

    protected function getFavoriteShop(int $userId): ?Tenant
    {
        $tenantId = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->select('tenant_id', DB::raw('COUNT(*) as order_count'))
            ->groupBy('tenant_id')
            ->orderByDesc('order_count')
            ->value('tenant_id');

        return $tenantId ? Tenant::find($tenantId) : null;
    }

    /* ================================================================
     *  REORDER
     * ================================================================ */

    public function reorder(int $orderId)
    {
        if (! Auth::check()) {
            session()->flash('error', 'Please sign in to reorder.');
            return redirect()->route('login');
        }

        $order = Order::with('items.product')
            ->where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        // Main store OR sub-branch both work — order may have branch_id = null
        $branchId = $order->branch_id;

        if ($branchId) {
            $branch = Branch::where('tenant_id', $order->tenant_id)
                ->where('id', $branchId)
                ->where('is_active', true)
                ->first();

            if (! $branch) {
                session()->flash('error', 'This branch is no longer accepting orders.');
                return;
            }
        }

        $productIds = $order->items->pluck('product_id')->filter()->unique()->values();

        if ($productIds->isEmpty()) {
            session()->flash('error', 'None of the items from this order are currently available.');
            return;
        }

        // One query for all products
        $products = Product::forLocation((int) $order->tenant_id, $branchId)
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // One query for all inventory — respects main store (branch_id = NULL)
        $invQuery = Inventory::where('tenant_id', $order->tenant_id)
            ->whereIn('product_id', $productIds);

        if ($branchId) {
            $invQuery->where('branch_id', $branchId);
        } else {
            $invQuery->whereNull('branch_id');
        }

        $inventory = $invQuery->get()->keyBy('product_id');

        $cart    = [];
        $skipped = [];

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                $skipped[] = $item->item_name ?? 'Unknown item';
                continue;
            }

            $product = $products->get($item->product_id);

            if (! $product) {
                $skipped[] = ($item->product->name ?? 'Discontinued item');
                continue;
            }

            $availableStock = (int) ($inventory->get($item->product_id)?->quantity ?? 0);

            if ($availableStock <= 0) {
                $skipped[] = $product->name . ' (out of stock)';
                continue;
            }

            $cart[$item->product_id] = [
                'id'    => $item->product_id,
                'name'  => $product->name,
                'price' => $product->price,
                'qty'   => min($item->quantity, $availableStock),
            ];
        }

        if (empty($cart)) {
            session()->flash('error', 'None of the items from this order are currently available.');
            return;
        }

        session()->put('cart_' . ($branchId ?? 0), $cart);

        session()->flash(
            'success',
            empty($skipped)
                ? 'Items added to cart!'
                : count($cart) . ' item(s) added. Some items were skipped: ' . implode(', ', $skipped)
        );

        // ✅ Both tenant + branch (0 = main store)
        return redirect()->route('customer.shopmenu', [
            'tenant' => $order->tenant_id,
            'branch' => $branchId ?? 0,
        ]);
    }

    /* ================================================================
     *  RENDER
     * ================================================================ */

    public function render()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $filterStart = $this->timeFilterStart();

        $topProducts   = $this->buildTopProducts($filterStart);
        $trendingShops = $this->buildTrendingShops();
        $newestShops   = $this->buildNewestShops();
        $allShops      = $this->buildAllShops($filterStart);

        $userStats = $this->buildUserStats($user);

        $recent_reviews = Review::where('user_id', $user->id)
            ->with(['product', 'tenant'])
            ->latest()
            ->take(5)
            ->get();

        $activeOrder = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->with(['branch', 'tenant', 'items.product'])
            ->latest()
            ->first();

        $recentOrders = Order::where('user_id', $user->id)
            ->with(['branch', 'tenant', 'items.product'])
            ->latest()
            ->take(4)
            ->get();

        return view('pages.customer.⚡dashboard.dashboard', [
            'stats'              => $userStats,
            'recent_reviews'     => $recent_reviews,
            'topProducts'        => $topProducts,
            'trendingShops'      => $trendingShops,
            'newestShops'        => $newestShops,
            'allShopsComparison' => $allShops,
            'globalSearch'       => $this->globalSearch,
            'showSearchResults'  => $this->showSearchResults,
            'searchResults'      => $this->searchResults,
            'showMobileSearch'   => $this->showMobileSearch,
            'sortBy'             => $this->sortBy,
            'timeFilter'         => $this->timeFilter,
            'activeOrder'        => $activeOrder,
            'recentOrders'       => $recentOrders,
        ]);
    }

    /* ================================================================
     *  BUILDER METHODS
     * ================================================================ */

    protected function buildTopProducts(?Carbon $filterStart)
    {
        $query = DB::table('products as p')
            ->join('tenants as t', 'p.tenant_id', '=', 't.id')
            ->where('p.is_active', 1)
            ->where('t.status', 'active')
            ->select([
                'p.id',
                'p.name',
                'p.price',
                'p.image',
                'p.tenant_id',
                't.name as shop_name',
                DB::raw('(
                    SELECT COALESCE(SUM(oi.quantity), 0)
                    FROM order_items oi
                    INNER JOIN orders o ON oi.order_id = o.id
                    WHERE oi.product_id = p.id
                      AND o.status = "completed"
                      ' . ($filterStart ? 'AND o.created_at >= ?' : '') . '
                ) as total_quantity_sold'),
                DB::raw('(
                    SELECT COALESCE(AVG(r.rating), 0)
                    FROM reviews r
                    WHERE r.product_id = p.id
                ) as product_rating'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM reviews r
                    WHERE r.product_id = p.id
                ) as review_count'),
            ]);

        if ($filterStart) {
            $query->addBinding($filterStart, 'select');
        }

        $query->havingRaw('total_quantity_sold > 0 OR review_count > 0');

        match ($this->sortBy) {
            'orders'  => $query->orderByDesc('total_quantity_sold'),
            'reviews' => $query->orderByDesc('review_count'),
            default   => $query->orderByDesc('product_rating'),
        };

        return $query->orderByDesc('total_quantity_sold')->limit(3)->get();
    }

    protected function buildTrendingShops()
    {
        $since = now()->subDays(30);

        return DB::table('tenants as t')
            ->where('t.status', 'active')
            ->select([
                't.id',
                't.name',
                DB::raw('(
                    SELECT COALESCE(AVG(r.rating), 0)
                    FROM reviews r
                    WHERE r.tenant_id = t.id
                      AND r.created_at >= ?
                ) as recent_rating'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM reviews r
                    WHERE r.tenant_id = t.id
                      AND r.created_at >= ?
                ) as recent_reviews'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM orders o
                    WHERE o.tenant_id = t.id
                      AND o.status = "completed"
                      AND o.created_at >= ?
                ) as recent_orders'),
                DB::raw('(
                    SELECT COALESCE(SUM(oi.quantity), 0)
                    FROM orders o
                    INNER JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.tenant_id = t.id
                      AND o.status = "completed"
                      AND o.created_at >= ?
                ) as recent_items_sold'),
            ])
            ->addBinding([$since, $since, $since, $since])
            ->havingRaw('recent_reviews > 0 OR recent_orders > 0')
            ->orderByDesc('recent_rating')
            ->orderByDesc('recent_items_sold')
            ->limit(5)
            ->get();
    }

    protected function buildNewestShops()
    {
        return DB::table('branches as b')
            ->join('tenants as t', 'b.tenant_id', '=', 't.id')
            ->where('t.status', 'active')
            ->where('b.approval_status', 'approved')
            ->where('b.is_active', 1)
            ->select([
                'b.id as branch_id',
                'b.name as branch_name',
                'b.address as branch_address',
                'b.logo_path as branch_logo_path',
                'b.created_at as branch_created_at',
                't.id as tenant_id',
                't.name as tenant_name',
                't.logo_path as tenant_logo_path',
            ])
            ->orderByDesc('b.created_at')
            ->limit(4)
            ->get();
    }

    protected function buildAllShops(?Carbon $filterStart)
    {
        $query = DB::table('tenants as t')
            ->where('t.status', 'active')
            ->select([
                't.id',
                't.name',
                't.address',
                DB::raw('(
                    SELECT COALESCE(AVG(r.rating), 0)
                    FROM reviews r
                    WHERE r.tenant_id = t.id
                ) as rating'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM reviews r
                    WHERE r.tenant_id = t.id
                ) as reviews'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM orders o
                    WHERE o.tenant_id = t.id
                      AND o.status = "completed"
                      ' . ($filterStart ? 'AND o.created_at >= ?' : '') . '
                ) as orders'),
                DB::raw('(
                    SELECT COALESCE(SUM(oi.quantity), 0)
                    FROM orders o
                    INNER JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.tenant_id = t.id
                      AND o.status = "completed"
                      ' . ($filterStart ? 'AND o.created_at >= ?' : '') . '
                ) as total_items_sold'),
                DB::raw('(
                    SELECT MAX(o.created_at)
                    FROM orders o
                    WHERE o.tenant_id = t.id
                      AND o.status = "completed"
                ) as last_order_date'),
            ]);

        if ($filterStart) {
            $query->addBinding([$filterStart, $filterStart], 'select');
        }

        $query->havingRaw('reviews > 0 OR orders > 0');

        match ($this->sortBy) {
            'orders'  => $query->orderByDesc('orders'),
            'reviews' => $query->orderByDesc('reviews'),
            default   => $query->orderByDesc('rating'),
        };

        return $query->orderByDesc('orders')->get();
    }

    protected function buildUserStats($user): array
    {
        $orderBase = Order::where('user_id', $user->id)->where('status', 'completed');

        return [
            'total_reviews'  => Review::where('user_id', $user->id)->count(),
            'total_orders'   => (clone $orderBase)->count(),
            'total_spent'    => (float) (clone $orderBase)->sum('total_amount'),
            'favorite_shop'  => $this->getFavoriteShop($user->id),
            'latest_reply'   => Review::where('user_id', $user->id)
                                    ->whereNotNull('owner_reply')
                                    ->with('tenant')
                                    ->latest('updated_at')
                                    ->first(),
            'monthly_orders' => Order::where('user_id', $user->id)
                                    ->where('status', 'completed')
                                    ->whereMonth('created_at', now()->month)
                                    ->count(),
            'monthly_spent'  => (float) Order::where('user_id', $user->id)
                                    ->where('status', 'completed')
                                    ->whereMonth('created_at', now()->month)
                                    ->sum('total_amount'),
        ];
    }
};
?>
