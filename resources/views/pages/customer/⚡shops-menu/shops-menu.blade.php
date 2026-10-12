
@php
    $cartItems = $this->cartItems;
    $cartCount = $this->cartCount;
    $cartTotal = $this->cartTotal;
    $d         = $this->detail;
@endphp

<div>
    {{-- ============ TOASTS ============ --}}
    <div x-data="{
            toasts: [],
            push(t) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, ...t });
                setTimeout(() => this.toasts = this.toasts.filter(x => x.id !== id), 3500);
            }
         }"
         x-init="
            @if(session('success')) push({ type: 'success', message: @js(session('success')) }); @endif
            @if(session('error')) push({ type: 'error', message: @js(session('error')) }); @endif
         "
         @toast.window="push(Array.isArray($event.detail) ? $event.detail[0] : $event.detail)"
         class="fixed top-4 inset-x-4 sm:left-auto sm:right-4 sm:w-80 z-[130] space-y-2 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div role="status"
                 class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl border shadow-lg text-sm font-bold bg-[#faf6ef] dark:bg-[#0f2922]"
                 :class="t.type === 'error'
                    ? 'border-red-500/40 text-red-600 dark:text-red-400'
                    : 'border-emerald-500/40 text-emerald-700 dark:text-emerald-400'">
                <i class="fa" :class="t.type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>

    <main class="relative min-h-screen bg-[#f5f0e8] dark:bg-[#0a1f1a] font-sans text-[#1a3a3a] dark:text-[#f0ebe0] transition-colors">
        <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto pb-28">

            <a href="{{ route('customer.shopdetail', $tenantId) }}"
               class="inline-flex items-center gap-2 text-sm font-bold text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:text-[#c8965f] dark:hover:text-[#d4a574] transition-colors mb-5 group">
                <i class="fa fa-arrow-left text-xs transition-transform group-hover:-translate-x-1"></i>
                Back to {{ $tenantName }}
            </a>

            {{-- ============ BRANCH HEADER ============ --}}
            <header class="bg-[#0f2922] dark:bg-[#0a1f1a] rounded-3xl border border-[#1a3a3a] p-5 sm:p-6 mb-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-[#c8965f] overflow-hidden flex items-center justify-center flex-shrink-0">
                            @if(!empty($branchLogoUrl))
                                <img src="{{ $branchLogoUrl }}" alt="{{ $branchName }}" class="w-full h-full object-cover">
                            @else
                                <i class="fa fa-store text-xl sm:text-2xl text-[#0a1f1a]"></i>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl font-black text-[#f5f0e8] truncate">{{ $branchName }}</h1>
                            <a href="{{ route('customer.shopdetail', $tenantId) }}"
                               class="text-[#d4a574] hover:text-[#f5f0e8] text-xs font-bold transition-colors">
                                {{ $tenantName }}
                            </a>
                            @if($branchAddress)
                                <p class="text-[#f0ebe0]/55 text-[11px] mt-1 truncate">
                                    <i class="fa fa-map-pin text-[#d4a574] text-[9px] mr-1"></i>{{ $branchAddress }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('customer.shopdetail', $tenantId) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-[#f5f0e8] font-bold text-xs transition-colors">
                        <i class="fa fa-store text-xs text-[#d4a574]"></i>
                        Switch branch
                    </a>
                </div>
            </header>

            {{-- ============ STEPS ============ --}}
            <nav aria-label="Order progress"
                 class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-4 mb-6">
                @php
                    $steps = [
                        1 => ['Menu',    'fa-mug-hot'],
                        2 => ['Cart',    'fa-shopping-bag'],
                        3 => ['Payment', 'fa-credit-card'],
                        4 => ['Confirm', 'fa-check-circle'],
                    ];
                @endphp
                <ol class="flex items-center">
                    @foreach($steps as $num => [$label, $icon])
                        @php
                            $done   = $num < $step;
                            $active = $num === $step;
                        @endphp
                        <li class="flex items-center {{ $num < 4 ? 'flex-1' : '' }}">
                            <button type="button" wire:click="goToStep({{ $num }})"
                                    @disabled($num >= $step)
                                    @if($active) aria-current="step" @endif
                                    class="flex flex-col items-center gap-1 flex-shrink-0">
                                <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-full flex items-center justify-center transition-colors
                                    @if($done) bg-emerald-500 text-white hover:bg-emerald-600
                                    @elseif($active) bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a]
                                    @else bg-[#1a3a3a]/10 dark:bg-[#c8965f]/10 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 cursor-not-allowed
                                    @endif">
                                    <i class="fa {{ $done ? 'fa-check' : $icon }} text-sm"></i>
                                </span>
                                <span class="text-[11px] font-bold
                                    @if($active) text-[#c8965f] dark:text-[#d4a574]
                                    @elseif($done) text-emerald-500
                                    @else text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40
                                    @endif">{{ $label }}</span>
                            </button>
                            @if($num < 4)
                                <span class="flex-1 h-0.5 mx-2 rounded-full {{ $done ? 'bg-emerald-500' : 'bg-[#1a3a3a]/10 dark:bg-[#c8965f]/15' }}"></span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>

            {{-- ============ STEP 1: MENU ============ --}}
            @if($step === 1)
                @php
                    $products   = $this->products;
                    $categories = $this->categories;
                @endphp

                <div class="space-y-5">
                    @guest
                        <div class="bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/30 dark:border-[#d4a574]/30 rounded-2xl p-3 flex items-center gap-3">
                            <i class="fa fa-info-circle text-[#c8965f] dark:text-[#d4a574]"></i>
                            <p class="text-xs text-[#9a6a35] dark:text-[#d4a574]">
                                <a href="{{ route('login') }}" class="underline font-bold">Sign in</a> to add items and place an order.
                            </p>
                        </div>
                    @endguest

                    @if($categories->isNotEmpty())
                        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide" role="tablist">
                            @php $allActive = $activeCategory === 'all' || $activeCategory === null; @endphp
                            <button wire:click="setCategory('all')" role="tab" aria-selected="{{ $allActive ? 'true' : 'false' }}"
                                    class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors
                                        {{ $allActive
                                            ? 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a]'
                                            : 'bg-[#faf6ef] dark:bg-[#0f2922] border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/50' }}">
                                All
                            </button>
                            @foreach($categories as $cat)
                                @php $catActive = $activeCategory == $cat->id; @endphp
                                <button wire:key="cat-{{ $cat->id }}" wire:click="setCategory({{ $cat->id }})" role="tab" aria-selected="{{ $catActive ? 'true' : 'false' }}"
                                        class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors
                                            {{ $catActive
                                                ? 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a]'
                                                : 'bg-[#faf6ef] dark:bg-[#0f2922] border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/50' }}">
                                    {{ $cat->cat_name }}
                                    <span class="opacity-60 ml-1">{{ $cat->products_count }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div wire:loading.delay.short wire:target="setCategory" class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">
                        <i class="fa fa-circle-notch fa-spin mr-1"></i> Loading menu
                    </div>

                    @if($products->isEmpty())
                        <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border-2 border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20 p-12 text-center">
                            <i class="fa fa-coffee text-3xl text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 mb-3 block"></i>
                            <p class="font-bold text-sm">Nothing to order here right now</p>
                            <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-1">
                                This branch has no items in stock. Try another branch.
                            </p>
                            <a href="{{ route('customer.shopdetail', $tenantId) }}"
                               class="inline-block mt-4 text-xs font-bold text-[#c8965f] dark:text-[#d4a574] underline">See other branches</a>
                        </div>
                    @else
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            @foreach($products as $product)
                                @php
                                    $inCart = (int) ($cart[$product->id] ?? 0);
                                    $avg    = round($product->reviews_avg_rating ?? 0);
                                    $left   = (int) $product->stock;
                                @endphp

                                <article wire:key="product-{{ $product->id }}"
                                         class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/50 transition-colors overflow-hidden flex flex-col">
                                    <button type="button" wire:click="openProductDetail({{ $product->id }})"
                                            aria-label="View {{ $product->name }}"
                                            class="relative w-full aspect-square bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 overflow-hidden group focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#c8965f]">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="" loading="lazy"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        @else
                                            <span class="w-full h-full flex items-center justify-center">
                                                <i class="fa fa-mug-hot text-4xl text-[#1a3a3a]/25 dark:text-[#f0ebe0]/25"></i>
                                            </span>
                                        @endif

                                        @if($left > 0 && $left <= 5)
                                            <span class="absolute top-2 left-2 text-[10px] font-bold px-2 py-1 rounded-md bg-[#c8965f] text-[#0a1f1a]">
                                                Only {{ $left }} left
                                            </span>
                                        @endif
                                        @if($inCart > 0)
                                            <span class="absolute top-2 right-2 min-w-6 h-6 px-1.5 rounded-full bg-emerald-500 text-white text-[11px] font-black flex items-center justify-center">
                                                {{ $inCart }}
                                            </span>
                                        @endif
                                    </button>

                                    <div class="p-3 flex flex-col flex-grow">
                                        <h3 class="font-bold text-sm truncate">{{ $product->name }}</h3>

                                        <div class="flex items-center gap-1 mt-1">
                                            @if(($product->reviews_count ?? 0) > 0)
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fa fa-star text-[9px] {{ $i <= $avg ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/20 dark:text-[#f0ebe0]/20' }}"></i>
                                                @endfor
                                                <span class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">({{ $product->reviews_count }})</span>
                                            @else
                                                <span class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50">No reviews yet</span>
                                            @endif
                                        </div>

                                        <p class="text-lg font-black text-[#c8965f] dark:text-[#d4a574] mt-1 mb-3">
                                            ₱{{ number_format($product->price, 2) }}
                                        </p>

                                        <div class="mt-auto">
                                            @guest
                                                <a href="{{ route('login') }}"
                                                   class="w-full flex items-center justify-center gap-1.5 text-xs font-bold bg-[#1a3a3a] dark:bg-[#c8965f] hover:bg-[#0f2922] dark:hover:bg-[#d4a574] text-[#f5f0e8] dark:text-[#0a1f1a] py-2.5 rounded-xl transition-colors">
                                                    <i class="fa fa-lock text-xs"></i> Sign in to order
                                                </a>
                                            @else
                                                @if(! $product->in_stock)
                                                    <button disabled class="w-full text-xs font-bold text-red-500 bg-red-500/10 py-2.5 rounded-xl cursor-not-allowed">
                                                        Sold out
                                                    </button>
                                                @elseif($inCart > 0)
                                                    <div class="flex items-center justify-between bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 rounded-xl p-1">
                                                        <button wire:click="updateQuantity({{ $product->id }}, {{ $inCart - 1 }})"
                                                                aria-label="Remove one {{ $product->name }}"
                                                                class="w-9 h-9 rounded-lg hover:bg-[#1a3a3a] dark:hover:bg-[#c8965f] hover:text-[#f5f0e8] dark:hover:text-[#0a1f1a] transition-colors">
                                                            <i class="fa fa-minus text-xs"></i>
                                                        </button>
                                                        <span class="text-sm font-black">{{ $inCart }}</span>
                                                        <button wire:click="updateQuantity({{ $product->id }}, {{ $inCart + 1 }})"
                                                                aria-label="Add one more {{ $product->name }}"
                                                                @disabled($inCart >= $left)
                                                                class="w-9 h-9 rounded-lg hover:bg-[#1a3a3a] dark:hover:bg-[#c8965f] hover:text-[#f5f0e8] dark:hover:text-[#0a1f1a] transition-colors disabled:opacity-30 disabled:pointer-events-none">
                                                            <i class="fa fa-plus text-xs"></i>
                                                        </button>
                                                    </div>
                                                @else
                                                    <button type="button" wire:click="addToCart({{ $product->id }})"
                                                            wire:loading.attr="disabled" wire:target="addToCart({{ $product->id }})"
                                                            class="w-full flex items-center justify-center gap-1.5 text-xs font-bold bg-[#1a3a3a] dark:bg-[#c8965f] hover:bg-[#0f2922] dark:hover:bg-[#d4a574] text-[#f5f0e8] dark:text-[#0a1f1a] py-2.5 rounded-xl transition-colors disabled:opacity-50">
                                                        <i class="fa fa-cart-plus text-xs"></i> Add to cart
                                                    </button>
                                                @endif
                                            @endguest
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============ STEP 2: CART ============ --}}
            @if($step === 2)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-black flex items-center gap-2">
                            Your cart
                            <span class="text-sm font-normal opacity-50">({{ $cartCount }} {{ Str::plural('item', $cartCount) }})</span>
                        </h2>
                        @if(!empty($cartItems))
                            <button wire:click="clearCart" wire:confirm="Remove all items from your cart?"
                                    class="text-xs font-bold text-red-500 hover:text-red-600">
                                <i class="fa fa-trash mr-1"></i> Clear cart
                            </button>
                        @endif
                    </div>

                    @if(empty($cartItems))
                        <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border-2 border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20 p-12 text-center">
                            <i class="fa fa-shopping-bag text-4xl opacity-30 mb-3 block"></i>
                            <p class="font-bold">Your cart is empty</p>
                            <button wire:click="goToStep(1)" class="mt-4 text-sm font-bold text-[#c8965f] dark:text-[#d4a574]">
                                <i class="fa fa-arrow-left mr-1"></i> Back to menu
                            </button>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($cartItems as $id => $item)
                                <div wire:key="cart-{{ $id }}"
                                     class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-3 flex items-center gap-3">
                                    <div class="w-16 h-16 rounded-xl bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                        @if(!empty($item['image']))
                                            <img src="{{ $item['image'] }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <i class="fa fa-mug-hot text-xl opacity-30"></i>
                                        @endif
                                    </div>

                                    <div class="flex-grow min-w-0">
                                        <h3 class="font-bold text-sm truncate">{{ $item['name'] }}</h3>
                                        <p class="text-xs opacity-60 mt-0.5">₱{{ number_format($item['price'], 2) }} each</p>
                                        <button wire:click="removeFromCart({{ $id }})" class="text-[11px] text-red-500 hover:text-red-600 mt-1 sm:hidden">
                                            Remove
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-1 bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 rounded-xl p-1">
                                        <button wire:click="updateQuantity({{ $id }}, {{ $item['qty'] - 1 }})"
                                                aria-label="Decrease quantity"
                                                class="w-8 h-8 rounded-lg hover:bg-[#1a3a3a] dark:hover:bg-[#c8965f] hover:text-[#f5f0e8] dark:hover:text-[#0a1f1a] transition-colors">
                                            <i class="fa fa-minus text-xs"></i>
                                        </button>
                                        <span class="text-sm font-black w-7 text-center">{{ $item['qty'] }}</span>
                                        <button wire:click="updateQuantity({{ $id }}, {{ $item['qty'] + 1 }})"
                                                aria-label="Increase quantity"
                                                @disabled($item['qty'] >= $item['available'])
                                                class="w-8 h-8 rounded-lg hover:bg-[#1a3a3a] dark:hover:bg-[#c8965f] hover:text-[#f5f0e8] dark:hover:text-[#0a1f1a] transition-colors disabled:opacity-30 disabled:pointer-events-none">
                                            <i class="fa fa-plus text-xs"></i>
                                        </button>
                                    </div>

                                    <div class="text-right flex-shrink-0 w-20">
                                        <p class="text-sm font-black text-[#c8965f] dark:text-[#d4a574]">₱{{ number_format($item['subtotal'], 2) }}</p>
                                        <button wire:click="removeFromCart({{ $id }})" class="hidden sm:inline text-[11px] text-red-500 hover:text-red-600 mt-1">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-4 flex justify-between text-lg font-black">
                            <span>Total</span>
                            <span class="text-[#c8965f] dark:text-[#d4a574]">₱{{ number_format($cartTotal, 2) }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============ STEP 3: PAYMENT ============ --}}
            @if($step === 3)
                @php
                    $choice = fn (bool $on, string $ring = 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] border-[#1a3a3a] dark:border-[#c8965f]')
                        => $on ? $ring : 'border-[#1a3a3a]/15 dark:border-[#c8965f]/20 hover:border-[#c8965f]/60';
                @endphp

                <div class="space-y-4 max-w-2xl">
                    <h2 class="text-xl font-black">How would you like it?</h2>

                    <fieldset class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-4">
                        <legend class="sr-only">Order type</legend>
                        <p class="text-xs font-bold opacity-60 mb-3">Order type</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('orderType', 'pickup')" aria-pressed="{{ $orderType === 'pickup' ? 'true' : 'false' }}"
                                    class="flex items-center gap-3 p-3 rounded-xl border-2 transition-colors text-left {{ $choice($orderType === 'pickup') }}">
                                <i class="fa fa-bag-shopping text-lg"></i>
                                <span><span class="block text-sm font-bold">Pickup</span><span class="block text-[11px] opacity-70">Get it at the counter</span></span>
                            </button>
                            <button type="button" wire:click="$set('orderType', 'dine_in')" aria-pressed="{{ $orderType === 'dine_in' ? 'true' : 'false' }}"
                                    class="flex items-center gap-3 p-3 rounded-xl border-2 transition-colors text-left {{ $choice($orderType === 'dine_in') }}">
                                <i class="fa fa-chair text-lg"></i>
                                <span><span class="block text-sm font-bold">Dine in</span><span class="block text-[11px] opacity-70">Eat at the shop</span></span>
                            </button>
                        </div>
                    </fieldset>

                    <fieldset class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-4">
                        <legend class="sr-only">Payment method</legend>
                        <p class="text-xs font-bold opacity-60 mb-3">Payment method</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('paymentMethod', 'cash')" aria-pressed="{{ $paymentMethod === 'cash' ? 'true' : 'false' }}"
                                    class="flex items-center gap-3 p-3 rounded-xl border-2 transition-colors text-left {{ $choice($paymentMethod === 'cash') }}">
                                <i class="fa fa-money-bill text-lg"></i>
                                <span><span class="block text-sm font-bold">Cash</span><span class="block text-[11px] opacity-70">Pay at the counter</span></span>
                            </button>
                            <button type="button" wire:click="$set('paymentMethod', 'paymongo')" aria-pressed="{{ $paymentMethod === 'paymongo' ? 'true' : 'false' }}"
                                    class="flex items-center gap-3 p-3 rounded-xl border-2 transition-colors text-left {{ $choice($paymentMethod === 'paymongo', 'bg-blue-600 border-blue-600 text-white') }}">
                                <i class="fa fa-mobile-alt text-lg"></i>
                                <span><span class="block text-sm font-bold">E-pay</span><span class="block text-[11px] opacity-70">GCash or card</span></span>
                            </button>
                        </div>

                        <p class="text-[11px] opacity-60 mt-3">
                            @if($paymentMethod === 'cash')
                                Your order is sent to the cashier. Pay when you pick it up.
                            @else
                                You'll be taken to a secure page to pay after you place the order.
                            @endif
                        </p>
                    </fieldset>
                </div>
            @endif

            {{-- ============ STEP 4: CONFIRM ============ --}}
            @if($step === 4)
                <div class="space-y-4 max-w-2xl">
                    <h2 class="text-xl font-black">Review your order</h2>

                    <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 divide-y divide-[#1a3a3a]/10 dark:divide-[#c8965f]/15">
                        @foreach($cartItems as $item)
                            <div class="p-3 flex items-center gap-3">
                                <div class="flex-grow min-w-0">
                                    <p class="font-bold text-sm truncate">{{ $item['name'] }}</p>
                                    <p class="text-[11px] opacity-60">{{ $item['qty'] }} × ₱{{ number_format($item['price'], 2) }}</p>
                                </div>
                                <p class="font-black text-sm text-[#c8965f] dark:text-[#d4a574]">₱{{ number_format($item['subtotal'], 2) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-[#0f2922] rounded-2xl p-5 text-[#f5f0e8] space-y-2">
                        <div class="flex justify-between text-xs text-[#f0ebe0]/70">
                            <span>Order type</span>
                            <span class="font-bold text-[#f5f0e8]">{{ $orderType === 'dine_in' ? 'Dine in' : 'Pickup' }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-[#f0ebe0]/70">
                            <span>Payment</span>
                            <span class="font-bold text-[#f5f0e8]">{{ $paymentMethod === 'cash' ? 'Cash at the counter' : 'E-pay (GCash / card)' }}</span>
                        </div>
                        <div class="flex justify-between items-baseline pt-3 border-t border-[#1a3a3a]">
                            <span class="text-base font-black">Total</span>
                            <span class="text-3xl font-black text-[#d4a574]">₱{{ number_format($cartTotal, 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ============ FOOTER BAR ============ --}}
        <div class="fixed bottom-0 inset-x-0 z-40 px-3 pb-3 sm:px-6 pointer-events-none">
            <div class="max-w-6xl mx-auto pointer-events-auto flex items-center justify-between gap-3 bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 p-3 shadow-lg">
                <div class="min-w-0">
                    @if($step > 1)
                        <button type="button" wire:click="previousStep"
                                class="px-3 py-2.5 text-sm font-bold opacity-70 hover:opacity-100 transition-opacity flex items-center gap-2">
                            <i class="fa fa-arrow-left text-xs"></i> Back
                        </button>
                    @else
                        <p class="text-xs pl-2">
                            <span class="font-bold">{{ $cartCount }}</span>
                            <span class="opacity-60">{{ Str::plural('item', $cartCount) }} in cart</span>
                        </p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-[10px] opacity-50">Total</p>
                        <p class="text-lg font-black text-[#c8965f] dark:text-[#d4a574] leading-tight">₱{{ number_format($cartTotal, 2) }}</p>
                    </div>

                    @if($step < $totalSteps)
                        <button type="button" wire:click="nextStep"
                                @disabled(empty($cart) && $step < 3)
                                class="px-6 py-3 text-sm font-bold text-[#f5f0e8] dark:text-[#0a1f1a] bg-[#1a3a3a] dark:bg-[#c8965f] hover:bg-[#0f2922] dark:hover:bg-[#d4a574] rounded-xl transition-colors flex items-center gap-2 disabled:opacity-40 disabled:pointer-events-none">
                            {{ $step === 1 ? 'View cart' : 'Next' }} <i class="fa fa-arrow-right text-xs"></i>
                        </button>
                    @else
                        <button type="button" wire:click="placeOrder"
                                wire:loading.attr="disabled" wire:target="placeOrder"
                                class="px-6 py-3 text-sm font-black text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl transition-colors flex items-center gap-2 disabled:opacity-60">
                            <span wire:loading.remove wire:target="placeOrder"><i class="fa fa-check-circle mr-1"></i> Place order</span>
                            <span wire:loading wire:target="placeOrder"><i class="fa fa-circle-notch fa-spin mr-1"></i> Placing order</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </main>

    {{-- ============ PRODUCT DETAIL MODAL ============ --}}
    @if($d)
        @php
            $dp = $d['product'];
        @endphp

        <div class="fixed inset-0 z-[110] flex items-center justify-center p-3 sm:p-4 bg-black/70"
             role="dialog" aria-modal="true" aria-label="{{ $dp->name }}"
             x-data="{ init() { document.body.style.overflow = 'hidden' }, destroy() { document.body.style.overflow = '' } }"
             @keydown.escape.window="$wire.closeProductDetail()"
             wire:click.self="closeProductDetail">
            <div class="bg-[#faf6ef] dark:bg-[#0f2922] text-[#1a3a3a] dark:text-[#f0ebe0] rounded-3xl w-full max-w-4xl max-h-[92vh] overflow-hidden shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                    <span class="text-sm font-bold truncate">{{ $dp->name }}</span>
                    <button wire:click="closeProductDetail" aria-label="Close"
                            class="w-8 h-8 rounded-full bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 hover:bg-[#1a3a3a]/10 flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-times text-xs"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 sm:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative aspect-square rounded-3xl overflow-hidden bg-[#f5f0e8] dark:bg-[#0a1f1a]/60">
                            @if($dp->image_url)
                                <img src="{{ $dp->image_url }}" class="w-full h-full object-cover" alt="{{ $dp->name }}">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fa fa-mug-hot text-7xl opacity-30"></i>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col">
                            <h2 class="text-2xl sm:text-3xl font-black mb-2">{{ $dp->name }}</h2>

                            <div class="flex items-center gap-2 mb-4">
                                @if($d['total'] > 0)
                                    <i class="fa fa-star text-[#c8965f] dark:text-[#d4a574] text-sm"></i>
                                    <span class="text-sm font-bold">{{ number_format($d['avg'], 1) }}</span>
                                    <span class="text-xs opacity-60">({{ $d['total'] }} {{ Str::plural('review', $d['total']) }})</span>
                                @else
                                    <span class="text-xs opacity-60">No reviews yet</span>
                                @endif
                            </div>

                            <p class="text-3xl font-black text-[#c8965f] dark:text-[#d4a574] mb-5">₱{{ number_format($dp->price, 2) }}</p>

                            @if($d['stock'] > 0)
                                <div class="mb-5">
                                    <p class="text-xs font-bold opacity-60 mb-2">Quantity</p>
                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="decrementDetailQty" aria-label="Decrease quantity"
                                                class="w-10 h-10 rounded-full border border-[#1a3a3a]/15 dark:border-[#c8965f]/25 hover:border-[#c8965f] flex items-center justify-center transition-colors">
                                            <i class="fa fa-minus text-xs"></i>
                                        </button>
                                        <span class="w-12 text-center text-lg font-black">{{ $detailQty }}</span>
                                        <button type="button" wire:click="incrementDetailQty" aria-label="Increase quantity"
                                                class="w-10 h-10 rounded-full border border-[#1a3a3a]/15 dark:border-[#c8965f]/25 hover:border-[#c8965f] flex items-center justify-center transition-colors">
                                            <i class="fa fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @auth
                                @if($d['stock'] > 0)
                                    <button type="button" wire:click="addToCartFromDetail"
                                            wire:loading.attr="disabled" wire:target="addToCartFromDetail"
                                            class="w-full py-4 rounded-full bg-[#1a3a3a] dark:bg-[#c8965f] hover:bg-[#0f2922] dark:hover:bg-[#d4a574] text-[#f5f0e8] dark:text-[#0a1f1a] font-black text-sm flex items-center justify-center gap-2 transition-colors disabled:opacity-60">
                                        <i class="fa fa-cart-plus"></i>
                                        Add to cart · ₱{{ number_format($dp->price * $detailQty, 2) }}
                                    </button>
                                @else
                                    <button disabled class="w-full py-4 rounded-full bg-[#1a3a3a]/10 opacity-50 font-black text-sm cursor-not-allowed">
                                        Sold out
                                    </button>
                                @endif
                            @else
                                <a href="{{ route('login') }}"
                                   class="w-full py-4 rounded-full bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] font-black text-sm flex items-center justify-center gap-2">
                                    <i class="fa fa-lock"></i> Sign in to order
                                </a>
                            @endauth
                        </div>
                    </div>

                    {{-- Reviews --}}
                    <div class="border-t border-[#1a3a3a]/10 dark:border-[#c8965f]/15 mt-8 pt-6">
                        <h3 class="text-lg font-black mb-4">Reviews ({{ $d['total'] }})</h3>

                        @if($d['total'] > 0)
                            <div class="grid sm:grid-cols-[auto_1fr] gap-6 items-center mb-6">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-5xl font-black">{{ number_format($d['avg'], 1) }}</span>
                                    <span class="text-lg opacity-40 font-bold">/5</span>
                                </div>
                                <div class="space-y-1.5 max-w-sm">
                                    @foreach($d['dist'] as $star => $n)
                                        <div class="flex items-center gap-2 text-[11px]">
                                            <span class="w-3 text-right">{{ $star }}</span>
                                            <i class="fa fa-star text-[9px] text-[#c8965f]"></i>
                                            <div class="flex-1 h-1.5 rounded-full bg-[#1a3a3a]/10 dark:bg-[#c8965f]/15 overflow-hidden">
                                                <div class="h-full bg-[#c8965f] rounded-full" style="width: {{ round($n / $d['total'] * 100) }}%"></div>
                                            </div>
                                            <span class="w-6 opacity-50">{{ $n }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
                            @forelse($d['reviews'] as $review)
                                <div class="p-4 rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 bg-[#f5f0e8] dark:bg-[#0a1f1a]/60">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-9 h-9 rounded-full bg-[#1a3a3a] dark:bg-[#c8965f] flex items-center justify-center text-[#f5f0e8] dark:text-[#0a1f1a] font-black text-sm">
                                            {{ strtoupper(substr($review->user->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold">{{ $review->user->name ?? 'Customer' }}</p>
                                            <div class="flex items-center gap-0.5">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fa fa-star text-[9px] {{ $i <= $review->rating ? 'text-[#c8965f]' : 'opacity-20' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                    @if($review->comment)
                                        <p class="text-sm opacity-75 italic">"{{ $review->comment }}"</p>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-8 border border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20 rounded-2xl">
                                    <p class="text-xs opacity-60">Be the first to review this after you order.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</div>