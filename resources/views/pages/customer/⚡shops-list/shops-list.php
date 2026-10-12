<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component
{
    #[Url(history: true)]
    public $search = '';

    #[Url(history: true, as: 'sort')]
    public $sortBy = 'rating';

    public function setSort($sort)
    {
        $this->sortBy = $sort;
    }

    public function render()
    {
        $user = Auth::user();

        // ✅ Handles guests correctly (null user)
        $isCustomerOrGuest = is_null($user)
            || ($user->role === 'customer' && is_null($user->tenant_id));

        $tenantFilter = $isCustomerOrGuest ? null : $user->tenant_id;

        // ✅ Cache returns plain arrays — safe to serialize
        // Bumped to v6 so the old cached data with is_main is abandoned
        $cacheKey = 'shops_list_v6_' . md5(($tenantFilter ?? 'all'));

        $shopsArray = Cache::remember($cacheKey, 300, function () use ($tenantFilter) {
            // 1) Load tenants + branches
            $tenants = Tenant::query()
                ->where('status', 'active')
                ->when($tenantFilter, fn($q) => $q->where('id', $tenantFilter))
                ->with(['branches' => function ($q) {
                    // No is_main — order by name only
                    $q->where('is_active', true)
                      ->where('approval_status', 'approved')
                      ->orderBy('name');
                }])
                ->withCount(['branches' => function ($q) {
                    $q->where('is_active', true)->where('approval_status', 'approved');
                }])
                ->get();

            if ($tenants->isEmpty()) {
                return [];
            }

            $allBranchIds = $tenants->flatMap(fn($t) => $t->branches->pluck('id'))->unique()->values()->all();

            if (empty($allBranchIds)) {
                return $tenants->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'logo_path' => $t->logo_path,
                    'address' => $t->address,
                    'branches_count' => (int) $t->branches_count,
                    'avg_rating' => 0.0,
                    'review_count' => 0,
                    'min_price' => 0.0,
                    'max_price' => 0.0,
                    'primary_city' => null,
                    'top_products' => [],
                ])->all();
            }

            // 2) Review stats
            $reviewStats = [];
            $rows = DB::table('reviews')
                ->join('branches', 'branches.id', '=', 'reviews.branch_id')
                ->whereIn('reviews.branch_id', $allBranchIds)
                ->where('reviews.is_visible', true)
                ->selectRaw('branches.tenant_id as tenant_id, COUNT(*) as total, AVG(reviews.rating) as avg_rating')
                ->groupBy('branches.tenant_id')
                ->get();

            foreach ($rows as $row) {
                $reviewStats[(int) $row->tenant_id] = [
                    'total' => (int) $row->total,
                    'avg_rating' => (float) $row->avg_rating,
                ];
            }

            // 3) Price stats — products are tenant-wide, use tenant_id
            $tenantIds = $tenants->pluck('id')->all();

            $priceStats = [];
            $rows = DB::table('products')
                ->whereIn('tenant_id', $tenantIds)
                ->where('is_active', true)
                ->selectRaw('tenant_id, MIN(price) as min_price, MAX(price) as max_price')
                ->groupBy('tenant_id')
                ->get();

            foreach ($rows as $row) {
                $priceStats[(int) $row->tenant_id] = [
                    'min_price' => (float) $row->min_price,
                    'max_price' => (float) $row->max_price,
                ];
            }

            // 4) Top products — by tenant_id
            $topProducts = [];
            $rows = DB::table('products')
                ->whereIn('tenant_id', $tenantIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->select(['tenant_id', 'name'])
                ->get();

            foreach ($rows as $row) {
                $tid = (int) $row->tenant_id;
                if (!isset($topProducts[$tid])) {
                    $topProducts[$tid] = [];
                }
                if (count($topProducts[$tid]) < 3) {
                    $topProducts[$tid][] = $row->name;
                }
            }

            // 5) Merge to plain arrays
            return $tenants->map(function ($tenant) use ($reviewStats, $priceStats, $topProducts) {
                $tid = (int) $tenant->id;

                // No is_main — just take the first branch
                $primaryBranch = $tenant->branches->first();

                $review = $reviewStats[$tid] ?? null;
                $price = $priceStats[$tid] ?? null;

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'logo_path' => $tenant->logo_path,
                    'address' => $tenant->address,
                    'branches_count' => (int) $tenant->branches_count,
                    'avg_rating' => $review['avg_rating'] ?? 0.0,
                    'review_count' => $review['total'] ?? 0,
                    'min_price' => $price['min_price'] ?? 0.0,
                    'max_price' => $price['max_price'] ?? 0.0,
                    'primary_city' => $primaryBranch?->city ?? $primaryBranch?->address,
                    'top_products' => $topProducts[$tid] ?? [],
                ];
            })->all();
        });

        // ✅ Convert plain arrays to objects AFTER cache
        $shops = collect($shopsArray)->map(fn($s) => (object) $s);

        // Search filter
        if ($this->search) {
            $term = Str::lower($this->search);
            $shops = $shops->filter(function ($shop) use ($term) {
                return Str::contains(Str::lower($shop->name), $term)
                    || Str::contains(Str::lower($shop->address ?? ''), $term)
                    || Str::contains(Str::lower($shop->primary_city ?? ''), $term)
                    || collect($shop->top_products)->contains(fn($p) => Str::contains(Str::lower($p), $term));
            });
        }

        // Sort
        $shops = match ($this->sortBy) {
            'branches' => $shops->sortByDesc('branches_count'),
            'price' => $shops->sortBy('min_price'),
            default => $shops->sortByDesc('avg_rating'),
        };

        $shops = $shops->values();

        return view('pages.customer.⚡shops-list.shops-list', [
            'shops' => $shops,
            'totalShops' => $shops->count(),
            'totalBranches' => $shops->sum('branches_count'),
        ]);
    }
};
?>