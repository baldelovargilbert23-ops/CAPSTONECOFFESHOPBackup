
<div>
    {{-- Auto-refresh for real-time data --}}
    <div wire:poll.30s="$refresh" class="hidden"></div>

    {{-- Neon Background Spotlights --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index: 0;">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#d2964b] rounded-full blur-[120px] opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-[#f5a623] rounded-full blur-[100px] opacity-15 animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#ffcc66] rounded-full blur-[150px] opacity-10"></div>
    </div>

    <main class="relative min-h-screen bg-gray-50 dark:bg-[#0A0C10]/90 backdrop-blur-sm font-sans" style="z-index: 1;">
        <div class="max-w-7xl mx-auto p-6 space-y-6">

            {{-- HEADER SECTION --}}
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#d2964b] to-[#8b5a2b] flex items-center justify-center shadow-lg shadow-[#d2964b]/30">
                            <i class="fa fa-star text-white text-lg"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Review Control</h1>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm ml-1">
                        Welcome back, <span class="text-[#d2964b] font-bold">{{ explode(' ', auth()->user()->name)[0] }}</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold ml-2">
                            <i class="fa fa-circle text-[6px] animate-pulse"></i>
                            Customer Sentiment Engine
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-4 bg-white dark:bg-[#11131A]/80 backdrop-blur-md p-3 rounded-2xl border border-gray-200 dark:border-[#d2964b]/20 shadow-sm">
                    <div class="relative">
                        <i class="fa fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search product or customer..."
                            class="pl-11 pr-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#1A1D26] text-gray-800 dark:text-white text-sm focus:border-[#d2964b] focus:ring-2 focus:ring-[#d2964b]/20 outline-none transition-all w-full md:w-72">
                    </div>
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-400 text-sm">
                    <i class="fa fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif

            {{-- STATS GRID --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-[#11131A] rounded-2xl p-6 border border-gray-200 dark:border-gray-800/50 shadow-sm hover:border-[#d2964b]/30 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Feed</h3>
                        <i class="fa fa-envelope text-[#d2964b] text-lg"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>

                <div class="bg-gradient-to-br from-[#d2964b] to-[#8b5a2b] rounded-2xl p-6 shadow-lg shadow-[#d2964b]/30">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-bold text-white/70 uppercase tracking-wider">Average Rating</h3>
                        <i class="fa fa-star text-white/50 text-lg"></i>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <p class="text-3xl font-bold text-white tracking-tight">{{ $stats['average'] }}</p>
                        <span class="text-sm text-white/60">/ 5.0</span>
                    </div>
                    <div class="flex text-amber-400 gap-0.5 mt-2">
                        @for($i=1; $i<=5; $i++)
                            <i class="fa fa-star text-xs {{ $i <= round($stats['average']) ? 'text-amber-400' : 'text-white/30' }}"></i>
                        @endfor
                    </div>
                </div>

                <div class="bg-white dark:bg-[#11131A] rounded-2xl p-6 border border-gray-200 dark:border-gray-800/50 shadow-sm hover:border-[#d2964b]/30 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Hidden from Public</h3>
                        <i class="fa fa-eye-slash text-gray-400 text-lg"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['hidden'] }}</p>
                    @if($stats['hidden'] > 0)
                        <span class="inline-flex items-center gap-1 mt-2 text-[10px] text-red-500">
                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span>
                            Awaiting review
                        </span>
                    @endif
                </div>
            </div>

            {{-- REVIEWS TABLE --}}
            <div class="bg-white dark:bg-[#11131A] rounded-2xl border border-gray-200 dark:border-[#d2964b]/30 overflow-hidden shadow-sm">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 flex flex-wrap justify-between items-center gap-4 bg-gray-50 dark:bg-[#0D0F14]">
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-white text-lg flex items-center gap-2">
                            <i class="fa fa-comments text-[#d2964b]"></i> Live Submissions
                        </h3>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase mt-1">Real-time feedback stream</p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        @foreach([5,4,3,2,1] as $star)
                            <button type="button"
                                wire:click="$set('ratingFilter', {{ $star }})"
                                class="px-4 py-1.5 rounded-full text-[10px] font-bold transition-all {{ $ratingFilter == $star ? 'bg-[#d2964b] text-white shadow-sm' : 'bg-gray-100 dark:bg-[#1A1D26] text-gray-500 dark:text-gray-400 hover:bg-[#d2964b]/20' }}">
                                {{ $star }}★
                            </button>
                        @endforeach
                        @if($ratingFilter)
                            <button type="button" wire:click="$set('ratingFilter', null)" class="px-3 py-1.5 text-[10px] font-bold text-red-500 hover:text-red-700 transition-colors">Clear</button>
                        @endif
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800/50">
                    @forelse($reviews as $review)
                        <div class="p-6 hover:bg-gray-50 dark:hover:bg-[#d2964b]/5 transition-all">
                            <div class="flex flex-col lg:flex-row justify-between gap-6">

                                <div class="flex-1 space-y-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 dark:bg-[#1A1D26] border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                            @if($review->product?->image && file_exists(public_path('storage/' . $review->product->image)))
                                                <img src="{{ asset('storage/' . $review->product->image) }}"
                                                     class="w-full h-full object-cover"
                                                     alt="{{ $review->product->name }}">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fa fa-coffee text-gray-400 text-lg"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-bold px-3 py-1.5 bg-gray-100 dark:bg-[#1A1D26] text-gray-700 dark:text-gray-300 rounded-full">
                                                {{ $review->product?->name ?? 'Deleted product' }}
                                            </span>
                                            <div class="flex text-amber-400 gap-0.5">
                                                @for($i=1; $i<=5; $i++)
                                                    <i class="fa fa-star text-xs {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                            {{ $review->user->name ?? 'Verified Guest' }}
                                            @if($review->user_id)
                                                <i class="fa fa-check-circle text-blue-500 text-xs"></i>
                                            @endif
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm italic leading-relaxed mt-2 bg-gray-50 dark:bg-[#1A1D26]/50 p-4 rounded-xl border-l-2 border-[#d2964b]">
                                            "{{ $review->comment }}"
                                        </p>
                                    </div>

                                    @if($review->owner_reply)
                                        <div class="mt-3 ml-6 p-4 bg-gray-50 dark:bg-[#1A1D26] rounded-xl border border-gray-100 dark:border-gray-800 relative">
                                            <div class="absolute -left-3 top-5 w-3 h-0.5 bg-[#d2964b]"></div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-5 h-5 rounded-full bg-[#d2964b]/20 flex items-center justify-center">
                                                    <i class="fa fa-store text-[10px] text-[#d2964b]"></i>
                                                </div>
                                                <p class="text-[9px] font-bold text-[#d2964b] uppercase tracking-wider">Your Response</p>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 italic">"{{ $review->owner_reply }}"</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-row lg:flex-col gap-3 justify-end items-center">
                                    <button type="button" wire:click="toggleStatus({{ $review->id }})"
                                        class="flex-1 px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all
                                        {{ $review->is_visible ? 'bg-gray-100 dark:bg-[#1A1D26] text-gray-600 dark:text-gray-400 hover:bg-red-100 dark:hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400' : 'bg-emerald-500 text-white hover:bg-emerald-600 shadow-lg' }}">
                                        <i class="fa {{ $review->is_visible ? 'fa-eye-slash' : 'fa-eye' }} mr-1"></i>
                                        {{ $review->is_visible ? 'Hide' : 'Restore' }}
                                    </button>
                                    <button type="button" wire:click="setReply({{ $review->id }})"
                                        class="flex-1 px-5 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] text-white hover:from-[#b87c3a] hover:to-[#7a4e25] transition-all shadow-md">
                                        <i class="fa fa-reply mr-1"></i>
                                        {{ $review->owner_reply ? 'Edit Reply' : 'Send Reply' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-20 text-center flex flex-col items-center">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-[#1A1D26] rounded-full flex items-center justify-center mb-4">
                                <i class="fa fa-inbox text-3xl text-gray-300 dark:text-gray-600"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">No feedback found.</p>
                        </div>
                    @endforelse
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#0D0F14]">
                    {{ $reviews->links() }}
                </div>
            </div>
        </div>
    </main>

    {{-- MODERN MODAL --}}
    @if($replyingTo)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" wire:click="$set('replyingTo', null)"></div>

            <div class="bg-white dark:bg-[#11131A] rounded-2xl w-full max-w-xl shadow-2xl border border-[#d2964b]/40 relative z-10 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-800 bg-gradient-to-r from-[#d2964b]/10 to-transparent">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#d2964b] to-[#8b5a2b] flex items-center justify-center">
                                <i class="fa fa-reply-all text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Draft Response</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Personalize your customer engagement</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('replyingTo', null)" class="text-gray-400 hover:text-[#d2964b] transition">
                            <i class="fa fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <textarea wire:model="replyContent" rows="5"
                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#1A1D26] p-4 text-sm text-gray-800 dark:text-white focus:border-[#d2964b] focus:ring-2 focus:ring-[#d2964b]/20 outline-none transition-all"
                        placeholder="Type your message here..."></textarea>

                    <div class="flex gap-3 mt-6">
                        <button type="button" wire:click="saveReply" class="flex-1 bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] text-white font-bold py-3 rounded-xl uppercase tracking-wider text-xs hover:from-[#b87c3a] hover:to-[#7a4e25] transition-all shadow-md">
                            <i class="fa fa-paper-plane mr-1"></i> Send Response
                        </button>
                        <button type="button" wire:click="$set('replyingTo', null)" class="px-6 bg-gray-100 dark:bg-[#1A1D26] text-gray-600 dark:text-gray-400 font-bold py-3 rounded-xl uppercase tracking-wider text-xs hover:bg-gray-200 dark:hover:bg-gray-800 transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>