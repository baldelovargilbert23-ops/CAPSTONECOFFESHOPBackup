<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

new #[Layout('layouts.customer')] class extends Component
{
    public array $topShops = [];
    public array $topProducts = [];
    public int $totalShops = 0;
    public int $totalProducts = 0;

    public function mount(): void
    {
        $this->topShops = Cache::remember('landing_top_shops_v15', 300, function () {

            // ── Main stores (tenants that can sell) ───────────────
            $tenants = \App\Models\Tenant::query()
                ->where('status', 'active')
                ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', 1)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('is_visible', 1)])
                ->get()
                ->map(fn ($tenant) => [
                    'type'               => 'tenant',
                    'id'                 => $tenant->id,
                    'name'               => $tenant->name ?? 'Main Store',
                    'address'            => $tenant->address ?? 'Victorias City',
                    'is_main'            => true,
                    'tenant_name'        => $tenant->name ?? 'Coffee Shop',
                    'reviews_avg_rating' => (float) ($tenant->reviews_avg_rating ?? 0),
                    'reviews_count'      => (int)   ($tenant->reviews_count ?? 0),
                    'logo_url'           => $tenant->logo_url ?? null,
                    'url'                => '/customer/shop/' . $tenant->id,
                ]);

            // ── Branches under active tenants ─────────────────────
            $branches = \App\Models\Branch::query()
                ->with('tenant')
                ->where('is_active', true)
                ->where('approval_status', 'approved')
                ->whereHas('tenant', fn ($q) => $q->where('status', 'active'))
                ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', 1)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('is_visible', 1)])
                ->get()
                ->map(fn ($branch) => [
                    'type'               => 'branch',
                    'id'                 => $branch->id,
                    'name'               => $branch->name,
                    'address'            => $branch->address,
                    'is_main'            => (bool) $branch->is_main,
                    'tenant_name'        => $branch->tenant->name ?? 'Coffee Shop',
                    'reviews_avg_rating' => (float) ($branch->reviews_avg_rating ?? 0),
                    'reviews_count'      => (int)   ($branch->reviews_count ?? 0),
                    'logo_url'           => $branch->logo_url ?? $branch->tenant->logo_url ?? null,
                    'url'                => '/customer/shop/menu/' . $branch->id,
                ]);

            // ── Merge, sort by rating desc, take top 3 ────────────
            return $tenants
                ->concat($branches)
                ->sortByDesc(fn ($shop) => sprintf(
                    '%08.4f-%08d-%d',
                    $shop['reviews_avg_rating'],
                    $shop['reviews_count'],
                    $shop['is_main'] ? 1 : 0
                ))
                ->take(3)
                ->values()
                ->toArray();
        });

        $this->topProducts = Cache::remember('landing_top_products_v15', 300, function () {
            $products = \App\Models\Product::query()
                ->with('tenant')
                ->withAvg(['reviews' => fn ($q) => $q->where('is_visible', 1)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('is_visible', 1)])
                ->where('is_active', true)
                ->whereHas('tenant', function ($q) {
                    $q->where('status', 'active')
                      ->whereHas('branches', fn ($b) => $b->where('is_active', true)
                                                            ->where('approval_status', 'approved'));
                })
                ->having('reviews_avg_rating', '>', 0)
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->orderBy('name')
                ->limit(3)
                ->get();

            return $products->map(function ($product) {
                $latestReview = \App\Models\Review::query()
                    ->with('user')
                    ->where('product_id', $product->id)
                    ->where('is_visible', true)
                    ->whereNotNull('comment')
                    ->orderByDesc('created_at')
                    ->first();

                return [
                    'id'                 => $product->id,
                    'name'               => $product->name,
                    'price'              => (float) ($product->price ?? 0),
                    'branch_name'        => $product->tenant->name ?? 'Coffee Shop',
                    'reviews_avg_rating' => (float) ($product->reviews_avg_rating ?? 0),
                    'reviews_count'      => (int)   ($product->reviews_count ?? 0),
                    'image_url'          => $product->image_url,
                    'latest_review'      => $latestReview ? [
                        'id'        => $latestReview->id,
                        'rating'    => (int) $latestReview->rating,
                        'comment'   => $latestReview->comment,
                        'user_name' => $latestReview->user->name ?? 'Customer',
                    ] : null,
                ];
            })->toArray();
        });

        $this->totalShops = Cache::remember('landing_total_shops_v15', 300, function () {
            $tenants  = \App\Models\Tenant::where('status', 'active')->count();
            $branches = \App\Models\Branch::where('is_active', true)
                ->where('approval_status', 'approved')
                ->count();
            return $tenants + $branches;
        });

        $this->totalProducts = Cache::remember('landing_total_products_v15', 300, fn () =>
            \App\Models\Product::where('is_active', true)->count()
        );
    }
};
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com">

<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,900;1,400;1,600;1,700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,900;1,400;1,600;1,700&display=swap"></noscript>

<link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>

<style>
    :root {
        /* ☀️ LIGHT (default) — Cream + Forest palette */
        --bg-primary: #f5f0e8;
        --bg-secondary: #faf6ef;
        --bg-elevated: #ffffff;
        --bg-inverse: #0f2922;

        --text-primary: #1a3a3a;
        --text-secondary: #1a3a3a;
        --text-muted: #6b7f7a;
        --text-inverse: #f5f0e8;

        --accent: #c8965f;
        --accent-warm: #d4a574;
        --accent-bright: #b8874a;
        --sage: #7a9a8a;

        --border-soft: rgba(26, 58, 58, 0.10);
        --border-medium: rgba(26, 58, 58, 0.20);

        --card-sage: #e0e5d8;
        --card-peach: #f0e0d0;
        --card-rose: #f5dcdc;

        --img-placeholder: #e8e2d5;
    }

    /* 🌙 DARK MODE */
    .dark {
        --bg-primary: #0a1f1a;
        --bg-secondary: #0f2922;
        --bg-elevated: #14332c;
        --bg-inverse: #f5f0e8;

        --text-primary: #f0ebe0;
        --text-secondary: #d1c8b8;
        --text-muted: #8a9a94;
        --text-inverse: #0a1f1a;

        --accent: #d4a574;
        --accent-warm: #e0b585;
        --accent-bright: #c8965f;
        --sage: #7a9a8a;

        --border-soft: rgba(212, 165, 116, 0.12);
        --border-medium: rgba(212, 165, 116, 0.25);

        --card-sage: #1a3a30;
        --card-peach: #2a2420;
        --card-rose: #2a1f1f;

        --img-placeholder: #14332c;
    }

    body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
    .serif { font-family: 'Playfair Display', serif; }

    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: var(--bg-primary); }
    ::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 10px; }

    [x-cloak] { display: none !important; }

    .nav-link { position: relative; transition: color 0.2s; }
    .nav-link::after {
        content: '';
        position: absolute;
        left: 0; bottom: -6px;
        width: 0; height: 2px;
        background: var(--text-primary);
        transition: width 0.3s ease;
    }
    .nav-link:hover::after,
    .nav-link.active::after { width: 100%; }

    .dotted-circle {
        border: 1.5px dashed var(--text-primary);
        border-radius: 999px;
    }
</style>

<div class="relative overflow-x-hidden transition-colors duration-300"
     style="background-color: var(--bg-primary); color: var(--text-primary);">

    {{-- ═══════════════ HERO SECTION ═══════════════ --}}
    <section class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 pt-12 sm:pt-16 lg:pt-20 pb-16 sm:pb-24">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                {{-- LEFT: Text --}}
                <div class="order-2 lg:order-1">
                    <p class="serif italic text-base sm:text-lg mb-4" style="color: var(--accent);">
                        Life Happens, Coffee Helps — Victorias City
                    </p>

                    <h1 class="serif text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl font-bold leading-[0.95] tracking-tight mb-6"
                        style="color: var(--text-primary);">
                        Sweet Moments<br>
                        Start <span class="italic" style="color: var(--accent);">Here.</span>
                    </h1>

                    <p class="text-base sm:text-lg leading-relaxed mb-8 max-w-lg"
                       style="color: var(--text-secondary); opacity: 0.75;">
                        Indulge in handcrafted coffee and delicious treats from every local shop in Victorias City —
                        made to brighten your day and warm your heart.
                    </p>

                    <a href="#menu"
                       class="inline-flex items-center gap-3 px-7 py-3.5 rounded-full font-semibold text-sm tracking-wide hover:gap-4 transition-all group"
                       style="background-color: var(--text-primary); color: var(--bg-primary);">
                        <span>EXPLORE MORE</span>
                        <i class="fa-solid fa-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>

                    {{-- Feature Strip --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-12 sm:mt-14 pt-10"
                         style="border-top: 1px solid var(--border-soft);">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background-color: var(--border-soft); color: var(--text-primary);">
                                <i class="fa-solid fa-leaf text-sm"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm" style="color: var(--text-primary);">Finest Ingredients</p>
                                <p class="text-xs mt-0.5" style="color: var(--text-secondary); opacity: 0.65;">Sourced from the best coffee farms.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                <i class="fa-solid fa-mug-hot text-sm"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm" style="color: var(--text-primary);">Perfectly Brewed</p>
                                <p class="text-xs mt-0.5" style="color: var(--text-secondary); opacity: 0.65;">Expertly roasted for rich flavor.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background-color: color-mix(in srgb, var(--sage) 15%, transparent); color: var(--sage);">
                                <i class="fa-solid fa-heart text-sm"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm" style="color: var(--text-primary);">Made with Love</p>
                                <p class="text-xs mt-0.5" style="color: var(--text-secondary); opacity: 0.65;">Crafted with passion for you.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Hero Image --}}
                <div class="order-1 lg:order-2 relative">
                    <div class="relative aspect-square max-w-lg mx-auto">
                        <div class="absolute inset-0 rounded-full transform translate-x-3 translate-y-3"
                             style="background-color: var(--text-primary);"></div>

                        <div class="relative w-full h-full rounded-full overflow-hidden border-8"
                             style="background-color: var(--bg-secondary); border-color: var(--bg-primary);">
                            <img src="https://images.unsplash.com/photo-1541167760496-1628856ab772?w=1000&q=85"
                                 alt="Perfect latte art"
                                 class="w-full h-full object-cover"
                                 fetchpriority="high">
                        </div>

                        <div class="absolute -bottom-4 -left-4 w-16 h-16 flex items-center justify-center">
                            <i class="fa-solid fa-seedling text-2xl rotate-12 opacity-60"
                               style="color: var(--text-primary);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════ FEATURED SHOPS ═══════════════ --}}
    <section id="shops" class="relative py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">

            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
                <p class="serif italic text-base mb-2" style="color: var(--accent);">Our Community</p>
                <h2 class="serif text-3xl sm:text-4xl md:text-5xl font-bold" style="color: var(--text-primary);">
                    Top Rated Shops
                </h2>
                <p class="text-sm mt-3" style="color: var(--text-secondary); opacity: 0.65;">
                    The best coffee spots across Victorias City — main stores and their branches.
                </p>
            </div>

            @if($totalShops > 0 && count($topShops) > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
                    @php
                        $cardColors = ['var(--card-sage)', 'var(--card-peach)', 'var(--card-rose)'];
                    @endphp

                    @foreach($topShops as $index => $shop)
                        @php
                            $shopLogoUrl = $shop['logo_url'] ?? null;
                            $bg = $cardColors[$index % 3];
                            $badgeText = $shop['type'] === 'tenant' ? 'Main Store' : 'Branch';
                        @endphp

                        <a href="{{ $shop['url'] }}"
                           wire:key="shop-{{ $shop['type'] }}-{{ $shop['id'] }}"
                           class="rounded-[2rem] p-6 sm:p-7 transition-all hover:shadow-xl hover:-translate-y-1 duration-300 flex flex-col"
                           style="background-color: {{ $bg }};">

                            <div class="text-center mb-4">
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full mb-2"
                                      style="background-color: color-mix(in srgb, var(--accent) 25%, transparent); color: var(--accent-bright);">
                                    {{ $badgeText }}
                                </span>
                                <h3 class="serif text-3xl sm:text-4xl font-bold mb-1" style="color: var(--text-primary);">
                                    {{ $shop['name'] }}
                                </h3>
                                <p class="serif italic text-lg" style="color: var(--accent);">
                                    {{ $shop['tenant_name'] }}
                                </p>
                            </div>

                            <div class="relative aspect-square rounded-3xl overflow-hidden mb-6"
                                 style="background-color: var(--img-placeholder);">
                                @if($shopLogoUrl)
                                    <img src="{{ $shopLogoUrl }}"
                                         class="w-full h-full object-cover"
                                         alt="{{ $shop['name'] }}"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fa-solid fa-mug-hot text-7xl opacity-20" style="color: var(--text-primary);"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center justify-between mt-auto">
                                <div>
                                    <p class="text-sm font-semibold" style="color: var(--text-primary);">
                                        <i class="fa-solid fa-star mr-1" style="color: var(--accent);"></i>
                                        {{ number_format($shop['reviews_avg_rating'] ?? 0, 1) }}
                                        <span class="font-normal" style="opacity: 0.5;">
                                            ({{ $shop['reviews_count'] ?? 0 }})
                                        </span>
                                    </p>
                                    <p class="text-xs mt-0.5 truncate max-w-[140px]"
                                       style="color: var(--text-secondary); opacity: 0.7;">
                                        {{ \Illuminate\Support\Str::limit($shop['address'] ?? 'Victorias City', 25) }}
                                    </p>
                                </div>
                                <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 transition"
                                     style="background-color: var(--text-primary); color: var(--bg-primary);">
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16" style="color: var(--text-secondary); opacity: 0.5;">
                    <i class="fa-solid fa-store text-4xl mb-3 block opacity-30"></i>
                    <p class="font-semibold">No shops available yet.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- ═══════════════ TOP PRODUCTS ═══════════════ --}}
    <section id="menu" class="relative py-16 sm:py-24" style="background-color: var(--bg-secondary);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">

            <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
                <p class="serif italic text-base mb-2" style="color: var(--accent);">Crowd Favorites</p>
                <h2 class="serif text-3xl sm:text-4xl md:text-5xl font-bold" style="color: var(--text-primary);">
                    Top Brews & Reviews
                </h2>
                <p class="text-sm mt-3" style="color: var(--text-secondary); opacity: 0.65;">
                    The most-loved drinks from shops around Victorias City.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
                @php
                    $cardColors = ['var(--card-sage)', 'var(--card-peach)', 'var(--card-rose)'];
                @endphp

                @forelse($topProducts as $product)
                    @php
                        $rev = $product['latest_review'] ?? null;
                        $productImgUrl = $product['image_url'] ?? null;
                        $bg = $cardColors[$loop->index % 3] ?? 'var(--card-sage)';
                    @endphp

                    <div wire:key="tp-{{ $product['id'] }}"
                         class="rounded-[2rem] p-6 sm:p-7 transition-all hover:shadow-xl hover:-translate-y-1 duration-300 flex flex-col"
                         style="background-color: {{ $bg }};">

                        <div class="text-center mb-5">
                            <h3 class="serif text-2xl sm:text-3xl font-bold mb-1" style="color: var(--text-primary);">
                                {{ $product['name'] ?? 'Product' }}
                            </h3>
                            <p class="serif italic text-base" style="color: var(--accent);">
                                {{ $product['branch_name'] ?? 'Coffee Shop' }}
                            </p>
                        </div>

                        <div class="relative aspect-square rounded-3xl overflow-hidden mb-5"
                             style="background-color: var(--img-placeholder);">
                            @if($productImgUrl)
                                <img src="{{ $productImgUrl }}"
                                     alt="{{ $product['name'] }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fa-solid fa-mug-hot text-6xl opacity-20" style="color: var(--text-primary);"></i>
                                </div>
                            @endif

                            @if(($product['reviews_count'] ?? 0) > 0)
                                <div class="absolute top-3 right-3 backdrop-blur-md text-[10px] font-bold px-3 py-1.5 rounded-full flex items-center gap-1"
                                     style="background-color: color-mix(in srgb, var(--text-primary) 90%, transparent); color: var(--bg-primary);">
                                    <i class="fa-solid fa-star" style="color: var(--accent-warm);"></i>
                                    {{ number_format($product['reviews_avg_rating'] ?? 0, 1) }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-auto">
                            <div class="flex items-center justify-between mb-4">
                                <span class="serif font-bold text-xl" style="color: var(--text-primary);">
                                    ₱{{ number_format($product['price'] ?? 0, 2) }}
                                </span>
                                <span class="text-xs" style="color: var(--text-secondary); opacity: 0.65;">
                                    {{ $product['reviews_count'] ?? 0 }} reviews
                                </span>
                            </div>

                            @if($rev)
                                <div class="pt-4" style="border-top: 1px solid var(--border-soft);">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-[10px] flex-shrink-0"
                                             style="background-color: var(--text-primary); color: var(--bg-primary);">
                                            {{ strtoupper(substr($rev['user_name'] ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <p class="font-bold text-[11px] truncate" style="color: var(--text-primary);">{{ $rev['user_name'] ?? 'Customer' }}</p>
                                                <div class="flex items-center gap-0.5 flex-shrink-0">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="fa-solid fa-star text-[8px]"
                                                           style="color: {{ $i <= ($rev['rating'] ?? 0) ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 20%, transparent)' }};"></i>
                                                    @endfor
                                                </div>
                                            </div>
                                            <p class="text-[11px] italic line-clamp-2" style="color: var(--text-secondary); opacity: 0.75;">"{{ $rev['comment'] ?? '' }}"</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16" style="color: var(--text-secondary); opacity: 0.5;">
                        <i class="fa-solid fa-mug-hot text-4xl mb-3 block opacity-30"></i>
                        <p class="font-semibold">No rated products yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>