<div>
    {{-- Poll only when viewing active orders --}}
    @if($activeTab === 'active' && $tabCounts['active'] > 0)
        <div wire:poll.45s="$refresh" class="hidden"></div>
    @endif

    <main class="relative min-h-screen bg-[#f5f0e8] dark:bg-[#0a1f1a] font-sans transition-colors duration-300">
        <div class="p-4 sm:p-6 lg:p-8 w-full max-w-5xl mx-auto">

            {{-- ═══════════════ PAGE TITLE ═══════════════ --}}
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-black text-[#1a3a3a] dark:text-[#f0ebe0]">
                    Your Orders
                </h1>
                <p class="text-xs sm:text-sm text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-1">
                    Track and manage your coffee orders
                </p>
            </div>

            {{-- ═══════════════ FLASH ═══════════════ --}}
            @if(session()->has('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 rounded-xl text-sm font-medium flex items-center justify-between gap-3">
                    <span class="flex items-center gap-2"><i class="fa fa-check-circle"></i> {{ session('success') }}</span>
                    <button @click="show = false"><i class="fa fa-times text-xs"></i></button>
                </div>
            @endif

            @if(session()->has('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="mb-4 p-3 bg-red-500/10 border border-red-500/30 text-red-700 dark:text-red-400 rounded-xl text-sm font-medium flex items-center justify-between gap-3">
                    <span class="flex items-center gap-2"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</span>
                    <button @click="show = false"><i class="fa fa-times text-xs"></i></button>
                </div>
            @endif

            {{-- ═══════════════ TABS + SEARCH ROW ═══════════════ --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">

                {{-- Tabs (pill style like reference) --}}
                <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide">
                    @foreach([
                        'active'    => 'Order',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled Orders',
                        'all'       => 'All',
                    ] as $key => $label)
                        <button wire:click="setTab('{{ $key }}')"
                                class="flex-shrink-0 px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all whitespace-nowrap
                                    {{ $activeTab === $key
                                        ? 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a]'
                                        : 'text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:text-[#1a3a3a] dark:hover:text-[#f0ebe0] hover:bg-[#1a3a3a]/5 dark:hover:bg-[#c8965f]/10' }}">
                            {{ $label }}
                            @if($tabCounts[$key] > 0)
                                <span class="ml-1.5 text-[10px] opacity-60">{{ $tabCounts[$key] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Search --}}
                @if($tabCounts['all'] > 0)
                    <div class="relative sm:w-64">
                        <i class="fa fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 text-xs"></i>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               placeholder="Search order or shop..."
                               class="w-full pl-9 pr-3 py-2 rounded-lg border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 bg-[#faf6ef] dark:bg-[#0f2922] text-xs text-[#1a3a3a] dark:text-[#f0ebe0] placeholder-[#1a3a3a]/40 dark:placeholder-[#f0ebe0]/40 focus:border-[#c8965f] focus:ring-2 focus:ring-[#c8965f]/20 outline-none transition">
                    </div>
                @endif
            </div>

            {{-- ═══════════════ ORDERS LIST ═══════════════ --}}
            <div class="space-y-4">
                @forelse($orders as $order)
                    @php
                        $status = $this->getStatusConfig($order->status);
                        $isActive = $this->isActiveOrder($order->status);
                        $canReorder = in_array($order->status, ['completed', 'cancelled']);
                    @endphp

                    {{-- ═══ ORDER CARD ═══ --}}
                    <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 overflow-hidden">

                        {{-- ═══ TOP ROW: Order meta + Actions ═══ --}}
                        <div class="px-4 sm:px-6 py-4 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            {{-- Meta grid --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-3 flex-1">
                                <div>
                                    <p class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider mb-1">Order Date</p>
                                    <p class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">{{ $order->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider mb-1">Total Amount</p>
                                    <p class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">₱{{ number_format($order->total_amount, 2) }}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider mb-1">Shop</p>
                                    <p class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate">{{ $order->tenant?->name ?? 'Coffee Shop' }}</p>
                                </div>
                            </div>

                            {{-- Right: Order # + actions --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <div class="hidden sm:block text-right mr-2">
                                    <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider font-bold">Order</p>
                                    <p class="text-[11px] font-mono font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">#{{ $order->order_number }}</p>
                                </div>

                                @if(in_array($order->status, ['completed', 'cancelled']))
                                    <button wire:click="openReceiptModal({{ $order->id }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-[#1a3a3a] dark:text-[#f0ebe0] bg-transparent border border-[#1a3a3a]/15 dark:border-[#c8965f]/25 hover:bg-[#1a3a3a]/5 dark:hover:bg-[#c8965f]/10 transition">
                                        <i class="fa fa-receipt text-[10px]"></i> View Receipt
                                    </button>
                                @endif

                                @if($order->status === 'pending'
                                    && $order->payment_status === 'pending'
                                    && $order->payment_method !== 'cash'
                                    && empty($order->payment_session_id))
                                    <a href="{{ route('payment.checkout', ['order' => $order->id]) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-[11px] font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                        <i class="fa fa-credit-card text-[10px]"></i> Pay Now
                                    </a>
                                @endif

                                @if($order->status === 'pending')
                                    <button wire:click="modifyOrder({{ $order->id }})"
                                            wire:confirm="Modify this order? Your current order will be cancelled and items added to cart."
                                            wire:loading.attr="disabled"
                                            wire:target="modifyOrder({{ $order->id }})"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-[11px] font-bold bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] hover:opacity-90 transition disabled:opacity-50">
                                        <span wire:loading.remove wire:target="modifyOrder({{ $order->id }})">
                                            <i class="fa fa-pen text-[10px]"></i> Modify Order
                                        </span>
                                        <span wire:loading wire:target="modifyOrder({{ $order->id }})">
                                            <i class="fa fa-spinner fa-spin text-[10px]"></i> Loading...
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- ═══ SECOND ROW: Status + progress ═══ --}}
                        <div class="px-4 sm:px-6 py-3 flex flex-wrap items-center gap-3 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $status['color'] }}">
                                <i class="fa {{ $status['icon'] }} text-[8px]"></i>
                                {{ $status['label'] }}
                            </span>

                            <span class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 flex items-center gap-1.5">
                                <i class="fa fa-store text-[9px]"></i>
                                {{ $order->branch?->name ?? 'Branch' }}
                            </span>

                            <span class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 flex items-center gap-1.5 ml-auto">
                                <i class="fa fa-clock text-[9px]"></i>
                                {{ $order->created_at->diffForHumans() }}
                            </span>
                        </div>

                        {{-- ═══ Active order progress bar ═══ --}}
                        @if($isActive)
                            <div class="px-4 sm:px-6 py-3 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 bg-[#c8965f]/5 dark:bg-[#d4a574]/5">
                                <div class="w-full bg-[#1a3a3a]/10 dark:bg-[#c8965f]/15 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-[#c8965f] dark:bg-[#d4a574] h-1.5 rounded-full transition-all duration-500"
                                         style="width: {{ $status['progress'] }}%"></div>
                                </div>
                                <p class="text-[10px] text-[#c8965f] dark:text-[#d4a574] mt-1.5 flex items-center gap-1 font-medium">
                                    <i class="fa fa-hourglass-half"></i>
                                    {{ $status['note'] }} · {{ $status['eta'] }}
                                </p>
                            </div>
                        @endif

                        {{-- ═══ ITEMS PREVIEW ═══ --}}
                        <div class="px-4 sm:px-6 py-4">
                            <p class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider mb-3">
                                Items ({{ $order->items->count() }})
                            </p>

                            <div class="space-y-3">
                                @foreach($order->items->take(3) as $item)
                                    <div class="flex items-center gap-3">
                                        {{-- Thumb --}}
                                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 border border-[#1a3a3a]/5 dark:border-[#c8965f]/10 flex-shrink-0 flex items-center justify-center">
                                            @if($item->product?->image_url)
                                                <img src="{{ $item->product->image_url }}"
                                                     class="w-full h-full object-cover"
                                                     alt="{{ $item->product->name }}"
                                                     loading="lazy"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="w-full h-full items-center justify-center hidden">
                                                    <i class="fa fa-mug-hot text-[#c8965f]/60 dark:text-[#d4a574]/60"></i>
                                                </div>
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fa fa-mug-hot text-[#c8965f]/60 dark:text-[#d4a574]/60"></i>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Info --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate">
                                                {{ $item->product?->name ?? ($item->item_name ?? 'Item') }}
                                            </p>
                                            <p class="text-[11px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-0.5">
                                                {{ $item->quantity }} × ₱{{ number_format($item->price, 2) }}
                                            </p>
                                        </div>

                                        {{-- Subtotal --}}
                                        <p class="text-sm font-black text-[#1a3a3a] dark:text-[#f0ebe0] whitespace-nowrap">
                                            ₱{{ number_format($item->subtotal, 2) }}
                                        </p>
                                    </div>
                                @endforeach

                                @if($order->items->count() > 3)
                                    <p class="text-[11px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 italic pt-1">
                                        + {{ $order->items->count() - 3 }} more item(s)
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- ═══ BOTTOM ACTIONS ═══ --}}
                        <div class="px-4 sm:px-6 py-3 border-t border-[#1a3a3a]/5 dark:border-[#c8965f]/10 bg-[#f5f0e8] dark:bg-[#0a1f1a]/40 flex flex-wrap items-center gap-2">

                            @if($order->status === 'pending')
                                <button wire:click="cancelOrder({{ $order->id }})"
                                        wire:confirm="Cancel this order?"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelOrder({{ $order->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/30 hover:bg-red-500/20 transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="cancelOrder({{ $order->id }})">
                                        <i class="fa fa-ban text-[10px]"></i> Cancel Order
                                    </span>
                                    <span wire:loading wire:target="cancelOrder({{ $order->id }})">
                                        <i class="fa fa-spinner fa-spin text-[10px]"></i>
                                    </span>
                                </button>
                            @endif

                            @if($order->status === 'completed')
                                <button wire:click="openReviewModal({{ $order->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-[#1a3a3a] dark:text-[#f0ebe0] bg-transparent border border-[#1a3a3a]/15 dark:border-[#c8965f]/25 hover:bg-[#c8965f]/10 transition">
                                    <i class="fa fa-star text-[10px]"></i> Write Review
                                </button>
                            @endif

                            @if($canReorder)
                                <button wire:click="reorder({{ $order->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="reorder({{ $order->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] hover:opacity-90 transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="reorder({{ $order->id }})">
                                        <i class="fa fa-redo text-[10px]"></i> Buy It Again
                                    </span>
                                    <span wire:loading wire:target="reorder({{ $order->id }})">
                                        <i class="fa fa-spinner fa-spin text-[10px]"></i> Adding...
                                    </span>
                                </button>
                            @endif

                            {{-- Pending/Preparing note --}}
                            @if(in_array($order->status, ['pending', 'preparing', 'ready']))
                                <span class="ml-auto text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 italic flex items-center gap-1">
                                    <i class="fa fa-clock text-[9px]"></i>
                                    Receipt available once {{ $order->status === 'pending' ? 'confirmed' : 'picked up' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    {{-- ═══════════════ EMPTY STATE ═══════════════ --}}
                    <div class="text-center py-16 bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border-2 border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20">
                        <div class="w-20 h-20 mx-auto rounded-2xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center mb-4">
                            @if($activeTab === 'active')
                                <i class="fa fa-mug-hot text-3xl text-[#c8965f] dark:text-[#d4a574]"></i>
                            @elseif($activeTab === 'completed')
                                <i class="fa fa-check-double text-3xl text-emerald-600 dark:text-emerald-400"></i>
                            @elseif($activeTab === 'cancelled')
                                <i class="fa fa-ban text-3xl text-red-500"></i>
                            @else
                                <i class="fa fa-shopping-bag text-3xl text-[#c8965f] dark:text-[#d4a574]"></i>
                            @endif
                        </div>

                        <h3 class="text-base sm:text-lg font-bold text-[#1a3a3a] dark:text-[#f0ebe0] mb-1">
                            @if($search)
                                No orders match "{{ $search }}"
                            @elseif($activeTab === 'active')
                                No active orders
                            @elseif($activeTab === 'completed')
                                No completed orders yet
                            @elseif($activeTab === 'cancelled')
                                No cancelled orders
                            @else
                                No orders yet
                            @endif
                        </h3>
                        <p class="text-xs sm:text-sm text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mb-5 max-w-xs mx-auto">
                            @if($search)
                                Try a different search term.
                            @elseif($activeTab === 'active')
                                Ready for your next cup?
                            @elseif($activeTab === 'completed')
                                Complete an order to see it here.
                            @elseif($activeTab === 'cancelled')
                                Nothing cancelled — that's a good thing!
                            @else
                                Discover coffee shops in Victorias City.
                            @endif
                        </p>

                        @if($search)
                            <button wire:click="$set('search', '')"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a] dark:text-[#f0ebe0] rounded-xl font-bold text-sm hover:bg-[#1a3a3a]/10 dark:hover:bg-[#c8965f]/20 transition">
                                <i class="fa fa-times-circle"></i> Clear Search
                            </button>
                        @else
                            <a href="{{ route('customer.shoplist') }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-xl font-bold text-sm hover:opacity-90 transition">
                                <i class="fa fa-mug-hot"></i> Browse Shops
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- REVIEW MODAL --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($showReviewModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 animate-fadeIn">
            <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl w-full max-w-md overflow-hidden shadow-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                <div class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 mx-auto bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 rounded-full flex items-center justify-center mb-3">
                            <i class="fa fa-star text-xl text-[#c8965f] dark:text-[#d4a574]"></i>
                        </div>
                        <h3 class="text-lg font-black text-[#1a3a3a] dark:text-[#f0ebe0]">Share Your Experience</h3>
                        <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-1">Your feedback helps improve quality</p>
                    </div>

                    <form wire:submit.prevent="submitReview" class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider block mb-1.5">Product</label>
                            <select wire:model="selectedProductId"
                                    class="w-full rounded-xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 p-3 text-sm font-medium text-[#1a3a3a] dark:text-[#f0ebe0] focus:border-[#c8965f] dark:focus:border-[#d4a574] outline-none transition">
                                @foreach($orderItems as $item)
                                    <option value="{{ $item->product_id }}">{{ $item->product?->name ?? 'Product' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider block mb-1.5">Rating</label>
                            <div class="flex justify-center gap-2 py-2.5 bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 rounded-xl">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('rating', {{ $i }})"
                                            class="focus:outline-none transition-transform hover:scale-110">
                                        <i class="fa fa-star text-2xl {{ $rating >= $i ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/20 dark:text-[#f0ebe0]/20' }}"></i>
                                    </button>
                                @endfor
                            </div>
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider block mb-1.5">Review</label>
                            <textarea wire:model="comment"
                                      rows="3"
                                      placeholder="Tell us about your experience..."
                                      class="w-full rounded-xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 p-3 text-sm text-[#1a3a3a] dark:text-[#f0ebe0] placeholder-[#1a3a3a]/40 dark:placeholder-[#f0ebe0]/40 focus:border-[#c8965f] dark:focus:border-[#d4a574] outline-none transition resize-none"></textarea>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button type="button" wire:click="closeReviewModal"
                                    class="flex-1 py-2.5 bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a] dark:text-[#f0ebe0] rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-[#1a3a3a]/10 dark:hover:bg-[#c8965f]/20 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="flex-1 py-2.5 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-xl font-bold text-xs uppercase tracking-wider hover:opacity-90 transition">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- RECEIPT MODAL --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($showReceiptModal && $receiptOrder)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 animate-fadeIn"
             wire:key="receipt-{{ $receiptOrder->id }}">
            <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl w-full max-w-md max-h-[90vh] overflow-hidden shadow-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 flex flex-col">

                <div class="p-4 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 flex items-center justify-between gap-3 flex-shrink-0">
                    <div>
                        <h3 class="text-base font-black text-[#1a3a3a] dark:text-[#f0ebe0] flex items-center gap-2">
                            <i class="fa fa-receipt text-[#c8965f] dark:text-[#d4a574]"></i> Order Receipt
                        </h3>
                        <p class="text-[10px] font-mono text-[#c8965f] dark:text-[#d4a574] font-bold">
                            #{{ $receiptOrder->order_number }}
                        </p>
                    </div>
                    <button wire:click="closeReceiptModal"
                            class="w-8 h-8 rounded-full hover:bg-[#1a3a3a]/5 dark:hover:bg-[#c8965f]/10 flex items-center justify-center text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 hover:text-[#1a3a3a] dark:hover:text-[#f0ebe0] transition">
                        <i class="fa fa-times text-sm"></i>
                    </button>
                </div>

                <div class="overflow-y-auto p-5 flex-grow">
                    <div class="text-center mb-4 pb-4 border-b border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20">
                        <h4 class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] text-sm">{{ $receiptOrder->tenant?->name ?? 'Coffee Shop' }}</h4>
                        <p class="text-[10px] text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">{{ $receiptOrder->branch?->name ?? 'Branch' }}</p>
                        <p class="text-[9px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 font-mono mt-1">{{ $receiptOrder->created_at->format('M d, Y • h:i A') }}</p>
                    </div>

                    <div class="space-y-2 mb-4 pb-4 border-b border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20">
                        @foreach($receiptOrder->items as $item)
                            <div class="flex items-start justify-between gap-2 text-xs">
                                <span class="text-[#1a3a3a] dark:text-[#f0ebe0]">
                                    {{ $item->quantity }}× {{ $item->product?->name ?? ($item->item_name ?? 'Item') }}
                                </span>
                                <span class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] whitespace-nowrap">
                                    ₱{{ number_format($item->subtotal, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-between items-center py-3 border-t border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                        <span class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] text-sm">Total</span>
                        <span class="font-black text-[#c8965f] dark:text-[#d4a574] text-lg">₱{{ number_format($receiptOrder->total_amount, 2) }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-[10px] mt-3">
                        <div class="p-2.5 rounded-lg bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                            <p class="font-bold text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 uppercase tracking-wider mb-0.5">Payment</p>
                            <p class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] capitalize">{{ $receiptOrder->payment_method }}</p>
                        </div>
                        <div class="p-2.5 rounded-lg bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                            <p class="font-bold text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 uppercase tracking-wider mb-0.5">Status</p>
                            <p class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] capitalize">{{ $receiptOrder->status }}</p>
                        </div>
                    </div>

                    <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 italic text-center mt-4">Thank you for your order! ☕</p>
                </div>

                <div class="p-3 border-t border-[#1a3a3a]/5 dark:border-[#c8965f]/10 bg-[#f5f0e8] dark:bg-[#0a1f1a]/40 flex gap-2 flex-shrink-0">
                    <button onclick="window.print()"
                            class="flex-1 py-2 bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a] dark:text-[#f0ebe0] rounded-lg font-bold text-[10px] uppercase tracking-wider hover:bg-[#1a3a3a]/10 dark:hover:bg-[#c8965f]/20 transition flex items-center justify-center gap-1.5">
                        <i class="fa fa-print"></i> Print
                    </button>
                    <button wire:click="closeReceiptModal"
                            class="flex-1 py-2 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-lg font-bold text-[10px] uppercase tracking-wider hover:opacity-90 transition flex items-center justify-center gap-1.5">
                        <i class="fa fa-check"></i> Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.97); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-fadeIn { animation: fadeIn 0.2s ease-out; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

        @media print {
            body * { visibility: hidden; }
            .fixed.inset-0, .fixed.inset-0 * { visibility: visible; }
            .fixed.inset-0 { position: absolute; top: 0; left: 0; }
        }
    </style>
</div>
