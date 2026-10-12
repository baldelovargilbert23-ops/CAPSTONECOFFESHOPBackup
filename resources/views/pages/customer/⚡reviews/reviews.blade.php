<div>
    {{-- Background --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index: 0;">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-amber-500 rounded-full blur-[120px] opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-amber-400 rounded-full blur-[100px] opacity-15 animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <main class="relative min-h-screen bg-stone-50 dark:bg-stone-950 backdrop-blur-sm font-sans transition-colors duration-300" style="z-index: 1;">
        <div class="p-4 sm:p-6 lg:p-8 w-full">
            {{-- Header --}}
            <div class="mb-5 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 flex items-center justify-center shadow-lg shadow-amber-600/30 flex-shrink-0">
                        <i class="fa fa-star text-white text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-xl sm:text-2xl font-black text-stone-900 dark:text-white">My Feedback</h1>
                        <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">Your ratings & shop replies</p>
                    </div>
                </div>
            </div>

            {{-- Flash --}}
            @if(session()->has('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-xl text-sm font-medium flex items-center justify-between gap-3">
                    <span class="flex items-center gap-2"><i class="fa fa-check-circle"></i> {{ session('success') }}</span>
                    <button @click="show = false"><i class="fa fa-times text-xs"></i></button>
                </div>
            @endif

            @if(session()->has('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="mb-4 p-3 bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 rounded-xl text-sm font-medium flex items-center justify-between gap-3">
                    <span class="flex items-center gap-2"><i class="fa fa-exclamation-circle"></i> {{ session('error') }}</span>
                    <button @click="show = false"><i class="fa fa-times text-xs"></i></button>
                </div>
            @endif

            @if($stats['total'] > 0)
                {{-- Compact stats bar --}}
                <div class="bg-white dark:bg-stone-900/60 rounded-2xl border border-stone-200 dark:border-stone-800/80 p-4 mb-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <span class="text-3xl font-black text-stone-900 dark:text-white">{{ number_format($stats['average'], 1) }}</span>
                                <div>
                                    <div class="flex text-amber-400">
                                        @for($i=1; $i<=5; $i++)
                                            <i class="fa fa-star text-xs {{ $i <= round($stats['average']) ? 'text-amber-400' : 'text-stone-300 dark:text-stone-700' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="text-[10px] text-stone-500">{{ $stats['total'] }} {{ Str::plural('review', $stats['total']) }}</p>
                                </div>
                            </div>

                            <div class="hidden sm:block h-10 w-px bg-stone-200 dark:bg-stone-800"></div>

                            <div>
                                <p class="text-[10px] font-bold text-stone-500 uppercase tracking-wider">With Replies</p>
                                <p class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ $stats['withReplies'] }}</p>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold text-stone-500 uppercase tracking-wider">Awaiting</p>
                                <p class="text-lg font-black text-amber-600 dark:text-amber-400">{{ $stats['awaitingReply'] }}</p>
                            </div>
                        </div>

                        {{-- Star distribution (compact) --}}
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($distribution as $star => $count)
                                <button wire:click="setRatingFilter({{ $star }})"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition border
                                            {{ $ratingFilter == $star
                                                ? 'bg-amber-500 border-amber-500 text-stone-950'
                                                : 'bg-stone-100 dark:bg-stone-950/60 border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-400 hover:border-amber-500/40 hover:text-amber-600' }}">
                                    {{ $star }}<i class="fa fa-star text-[8px] ml-0.5"></i>
                                    <span class="ml-1 opacity-70">{{ $count }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Filter bar --}}
            @if($stats['total'] > 0)
                <div class="bg-white dark:bg-stone-900/60 rounded-xl border border-stone-200 dark:border-stone-800/80 p-3 mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Reply filter --}}
                        @foreach([
                            'all' => ['label' => 'All', 'icon' => 'fa-list'],
                            'replied' => ['label' => 'Replied', 'icon' => 'fa-check-circle'],
                            'awaiting' => ['label' => 'Awaiting', 'icon' => 'fa-clock'],
                        ] as $key => $meta)
                            <button wire:click="setReplyFilter('{{ $key }}')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold transition
                                        {{ $replyFilter === $key
                                            ? 'bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950'
                                            : 'bg-stone-100 dark:bg-stone-950/60 text-stone-600 dark:text-stone-400 hover:text-amber-600' }}">
                                <i class="fa {{ $meta['icon'] }} text-[9px]"></i>
                                {{ $meta['label'] }}
                            </button>
                        @endforeach

                        {{-- Search --}}
                        <div class="relative flex-grow min-w-[180px]">
                            <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-stone-400 text-[10px]"></i>
                            <input type="text"
                                   wire:model.live.debounce.300ms="search"
                                   placeholder="Search..."
                                   class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950/60 text-stone-900 dark:text-white text-[11px] font-medium focus:border-amber-500 outline-none transition">
                        </div>

                        {{-- Sort --}}
                        <div class="relative">
                            <select wire:model.live="sortBy"
                                    class="pl-3 pr-8 py-1.5 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-950/60 text-stone-900 dark:text-white text-[11px] font-bold focus:border-amber-500 outline-none appearance-none cursor-pointer">
                                <option value="newest">Newest</option>
                                <option value="oldest">Oldest</option>
                                <option value="highest">Highest ★</option>
                                <option value="lowest">Lowest ★</option>
                            </select>
                            <i class="fa fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 text-[9px] pointer-events-none"></i>
                        </div>

                        @if($ratingFilter || $replyFilter !== 'all' || $search)
                            <button wire:click="clearFilters"
                                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-[11px] font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 transition">
                                <i class="fa fa-times-circle text-[9px]"></i> Clear
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Reviews List --}}
            <div class="space-y-3">
                @forelse($reviews as $review)
                    <div class="bg-white dark:bg-stone-900/60 rounded-2xl border border-stone-200 dark:border-stone-800/80 overflow-hidden shadow-sm hover:border-amber-500/30 transition-all duration-300">

                        {{-- Gold accent --}}
                        <div class="h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent opacity-60"></div>

                        <div class="p-4 sm:p-5">
                            {{-- Header --}}
                            <div class="flex items-start justify-between gap-3 mb-3 flex-wrap">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-stone-100 dark:bg-stone-950/60 flex-shrink-0 border border-stone-200 dark:border-stone-800 flex items-center justify-center">
                                        @if($review->product?->image)
                                            <img src="{{ asset('storage/' . $review->product->image) }}"
                                                 class="w-full h-full object-cover"
                                                 alt="{{ $review->product->name }}"
                                                 loading="lazy">
                                        @else
                                            <i class="fa fa-mug-hot text-xl text-stone-400 dark:text-stone-600"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                            <h4 class="font-bold text-stone-900 dark:text-white text-sm sm:text-base truncate">{{ $review->product->name ?? 'Product' }}</h4>
                                            @if($review->owner_reply)
                                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
                                                    <i class="fa fa-check-circle text-[8px]"></i> Replied
                                                </span>
                                            @else
                                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center gap-1">
                                                    <i class="fa fa-clock text-[8px]"></i> Awaiting
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 flex-wrap text-[10px] text-stone-500">
                                            <span><i class="fa fa-store mr-1"></i>{{ $review->tenant->name ?? 'Store' }}</span>
                                            <span class="text-stone-300 dark:text-stone-700">•</span>
                                            <span>{{ $review->created_at->format('M d, Y') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex text-amber-400 flex-shrink-0">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fa fa-star text-xs {{ $i <= $review->rating ? 'text-amber-400' : 'text-stone-300 dark:text-stone-700' }}"></i>
                                    @endfor
                                </div>
                            </div>

                            {{-- Comment --}}
                            @if($review->comment)
                                <p class="text-sm text-stone-700 dark:text-stone-300 leading-relaxed italic mb-3 pl-3 border-l-2 border-amber-500/40">
                                    "{{ $review->comment }}"
                                </p>
                            @endif

                            {{-- Owner reply --}}
                            @if($review->owner_reply)
                                <div class="mt-3 p-3 rounded-xl bg-emerald-500/5 border border-emerald-500/20">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <div class="w-5 h-5 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center">
                                            <i class="fa fa-store text-white text-[9px]"></i>
                                        </div>
                                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Shop Owner</span>
                                        <span class="text-[9px] text-stone-400 ml-auto">{{ $review->updated_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-stone-700 dark:text-stone-300 leading-relaxed">
                                        "{{ $review->owner_reply }}"
                                    </p>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="mt-3 pt-3 border-t border-stone-100 dark:border-stone-800 flex items-center justify-end gap-2 flex-wrap">
                                <button wire:click="openEditModal({{ $review->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400 hover:bg-amber-500/20 hover:text-amber-600 transition">
                                    <i class="fa fa-pen text-[9px]"></i> Edit
                                </button>
                                <button wire:click="confirmDelete({{ $review->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400 hover:bg-red-500/20 hover:text-red-600 transition">
                                    <i class="fa fa-trash text-[9px]"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    {{-- Empty --}}
                    <div class="text-center py-12 sm:py-16 bg-white dark:bg-stone-900/60 rounded-2xl border-2 border-dashed border-stone-200 dark:border-stone-800">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-amber-500/20 to-amber-700/20 border border-amber-500/30 flex items-center justify-center mb-3">
                            <i class="fa fa-star text-2xl text-amber-500"></i>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-stone-900 dark:text-white mb-1">
                            @if($ratingFilter || $replyFilter !== 'all' || $search)
                                No matching reviews
                            @else
                                No reviews yet
                            @endif
                        </h3>
                        <p class="text-xs text-stone-500 mb-4 max-w-xs mx-auto">
                            @if($ratingFilter || $replyFilter !== 'all' || $search)
                                Try adjusting your filters.
                            @else
                                Order coffee and share your experience to see it here.
                            @endif
                        </p>
                        @if($ratingFilter || $replyFilter !== 'all' || $search)
                            <button wire:click="clearFilters"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 rounded-xl font-bold text-xs hover:bg-stone-200 dark:hover:bg-stone-700 transition">
                                <i class="fa fa-times-circle"></i> Clear Filters
                            </button>
                        @else
                            <a href="{{ route('customer.shoplist') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 rounded-xl font-bold text-xs hover:shadow-lg transition">
                                <i class="fa fa-mug-hot"></i> Browse Shops
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    {{-- ============================================ --}}
    {{-- EDIT MODAL --}}
    {{-- ============================================ --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
            <div class="bg-white dark:bg-stone-900 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl border border-amber-500/30">
                <div class="p-6">
                    <div class="text-center mb-6">
                        <div class="w-14 h-14 mx-auto bg-gradient-to-br from-amber-500/20 to-amber-700/20 border border-amber-500/20 rounded-full flex items-center justify-center mb-3">
                            <i class="fa fa-pen text-xl text-amber-500"></i>
                        </div>
                        <h3 class="text-lg font-black text-stone-900 dark:text-white">Edit Your Review</h3>
                        <p class="text-xs text-stone-500 mt-1">{{ $edit_product_name }}</p>
                    </div>

                    <form wire:submit.prevent="updateReview" class="space-y-5">
                        <div>
                            <label class="text-[10px] font-bold text-stone-500 uppercase tracking-wider block mb-1.5">Rating</label>
                            <div class="flex justify-center gap-2 py-2.5 bg-stone-50 dark:bg-stone-950/60 rounded-xl">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="$set('edit_rating', {{ $i }})"
                                            class="focus:outline-none transition-transform hover:scale-110">
                                        <i class="fa fa-star text-2xl {{ $edit_rating >= $i ? 'text-amber-400' : 'text-stone-300 dark:text-stone-700' }}"></i>
                                    </button>
                                @endfor
                            </div>
                            @error('edit_rating') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-stone-500 uppercase tracking-wider block mb-1.5">Your Review</label>
                            <textarea wire:model="edit_comment"
                                      rows="4"
                                      placeholder="Tell us about your experience..."
                                      class="w-full rounded-xl border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-950/60 p-3 text-sm text-stone-900 dark:text-white focus:border-amber-500 outline-none transition resize-none"></textarea>
                            @error('edit_comment') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button type="button" wire:click="closeEditModal"
                                    class="flex-1 py-2.5 bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-stone-200 dark:hover:bg-stone-700 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="flex-1 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-stone-950 rounded-xl font-bold text-xs uppercase tracking-wider hover:shadow-lg transition">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================ --}}
    {{-- DELETE CONFIRM --}}
    {{-- ============================================ --}}
    @if($deletingReviewId)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
            <div class="bg-white dark:bg-stone-900 rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl border border-red-500/30">
                <div class="p-6 text-center">
                    <div class="w-14 h-14 mx-auto bg-red-500/10 border border-red-500/20 rounded-full flex items-center justify-center mb-4">
                        <i class="fa fa-trash text-xl text-red-500"></i>
                    </div>
                    <h3 class="text-base font-black text-stone-900 dark:text-white mb-2">Delete this review?</h3>
                    <p class="text-xs text-stone-500 mb-5">
                        This action cannot be undone.
                    </p>

                    <div class="flex gap-2">
                        <button wire:click="cancelDelete"
                                class="flex-1 py-2.5 bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-stone-200 dark:hover:bg-stone-700 transition">
                            Cancel
                        </button>
                        <button wire:click="deleteReview"
                                wire:loading.attr="disabled"
                                class="flex-1 py-2.5 bg-red-500 text-white rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-red-600 transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="deleteReview"><i class="fa fa-trash mr-1"></i> Delete</span>
                            <span wire:loading wire:target="deleteReview"><i class="fa fa-spinner fa-spin mr-1"></i> Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-fadeIn { animation: fadeIn 0.2s ease-out; }
    </style>
</div>