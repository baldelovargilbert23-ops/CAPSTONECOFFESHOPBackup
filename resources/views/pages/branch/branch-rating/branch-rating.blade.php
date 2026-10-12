<?php

use App\Models\Branch;
use App\Models\Review;
use App\Notifications\ReviewRepliedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.branch')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $ratingFilter = null;
    public $visibilityFilter = 'all';
    public $sortBy = 'newest';
    public $replyingTo = null;
    public $replyContent = '';
    public $expandedReply = null;
    public $tenantId;
    public $branchId;
    public $branch;

    protected function rules()
    {
        return [
            'replyContent' => 'nullable|string|max:1000',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $this->tenantId = $user->tenant_id;
        $this->branchId = $user->branch_id;
        $this->branch = Branch::find($this->branchId);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingRatingFilter() { $this->resetPage(); }
    public function updatingVisibilityFilter() { $this->resetPage(); }
    public function updatingSortBy() { $this->resetPage(); }

    public function toggleStatus($id)
    {
        $review = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->findOrFail($id);
        $review->update(['is_visible' => !$review->is_visible]);
    }

    public function setReply($id)
    {
        $this->replyingTo = $id;
        $review = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->find($id);
        $this->replyContent = $review->owner_reply ?? '';
    }

    public function saveReply()
    {
        $this->validate();

        $review = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->findOrFail($this->replyingTo);

        $hadReplyBefore = !empty($review->owner_reply);
        $replyText = trim($this->replyContent);

        $review->update(['owner_reply' => $replyText]);

        if ($replyText !== '' && $review->user) {
            $this->notifyCustomer($review);
        }

        $this->replyingTo = null;
        $this->replyContent = '';

        session()->flash('success', $hadReplyBefore
            ? 'Reply updated successfully! Customer has been notified.'
            : 'Reply sent successfully! Customer has been notified.');
    }

    protected function notifyCustomer(Review $review): void
    {
        try {
            $original = config('queue.default');
            config(['queue.default' => 'sync']);

            $review->refresh();
            $review->load(['user', 'product', 'branch', 'tenant']);

            if ($review->user) {
                $review->user->notify(
                    new ReviewRepliedNotification($review, Auth::user()->name)
                );
                Log::info("Review reply notification sent to customer (review #{$review->id})");
            }

            config(['queue.default' => $original]);
        } catch (\Throwable $e) {
            Log::error('Review reply notification failed: ' . $e->getMessage());
        }
    }

    public function toggleReplyExpand($id)
    {
        $this->expandedReply = $this->expandedReply === $id ? null : $id;
    }

    public function setVisibilityFilter($filter)
    {
        $this->visibilityFilter = $filter;
        $this->resetPage();
    }

    public function render()
    {
        $baseQuery = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'average' => round((clone $baseQuery)->avg('rating'), 1) ?: 0,
            'hidden' => (clone $baseQuery)->where('is_visible', false)->count(),
            'replied' => (clone $baseQuery)->whereNotNull('owner_reply')->where('owner_reply', '!=', '')->count(),
        ];
        $stats['reply_rate'] = $stats['total'] > 0
            ? round(($stats['replied'] / $stats['total']) * 100)
            : 0;

        $distribution = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = (clone $baseQuery)->where('rating', $star)->count();
            $distribution[$star] = [
                'count' => $count,
                'percent' => $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0,
            ];
        }

        $trend = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trendDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trendDays[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('D'),
                'avg' => $trend[$date]->avg_rating ?? 0,
                'count' => $trend[$date]->count ?? 0,
            ];
        }

        $query = Review::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->with(['product', 'user'])
            ->when($this->ratingFilter, fn($q) => $q->where('rating', $this->ratingFilter))
            ->when($this->search, fn($q) => $q->where(function ($sub) {
                $sub->whereHas('product', fn($p) => $p->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('comment', 'like', "%{$this->search}%");
            }))
            ->when($this->visibilityFilter === 'visible', fn($q) => $q->where('is_visible', true))
            ->when($this->visibilityFilter === 'hidden', fn($q) => $q->where('is_visible', false))
            ->when($this->visibilityFilter === 'replied', fn($q) => $q->whereNotNull('owner_reply')->where('owner_reply', '!=', ''))
            ->when($this->visibilityFilter === 'unreplied', fn($q) => $q->where(fn($sub) => $sub->whereNull('owner_reply')->orWhere('owner_reply', '')));

        $query = match ($this->sortBy) {
            'oldest'  => $query->oldest(),
            'highest' => $query->orderByDesc('rating'),
            'lowest'  => $query->orderBy('rating'),
            default   => $query->latest(),
        };

        $reviews = $query->paginate(10);

        return view('pages.branch.branch-rating.branch-rating', [
            'reviews' => $reviews,
            'stats' => $stats,
            'distribution' => $distribution,
            'trendDays' => $trendDays,
            'branch' => $this->branch,
        ]);
    }
};
?>

<div class="rating-root relative min-h-screen font-sans transition-colors duration-300"
     style="background-color: var(--bg-primary); color: var(--text-primary);">

    {{-- Theme styles (now INSIDE the root element) --}}
    <style>
        .rating-root {
            --bg-primary: #f5f0e8;
            --bg-secondary: #faf6ef;
            --bg-elevated: #ffffff;
            --bg-inner: #f5f0e8;
            --text-primary: #1a3a3a;
            --text-muted: #6b7f7a;
            --accent: #c8965f;
            --accent-warm: #d4a574;
            --accent-bright: #b8874a;
            --forest: #1a3a3a;
            --border-soft: rgba(26, 58, 58, 0.10);
            --ok: #16a34a;
            --warn: #d97706;
            --bad: #dc2626;
            --info: #2563eb;
            --violet: #7c3aed;
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .dark .rating-root {
            --bg-primary: #0a1f1a;
            --bg-secondary: #0f2922;
            --bg-elevated: #14332c;
            --bg-inner: #0a1f1a;
            --text-primary: #f0ebe0;
            --text-muted: #8a9a94;
            --accent: #d4a574;
            --accent-warm: #e0b585;
            --accent-bright: #c8965f;
            --forest: #f0ebe0;
            --border-soft: rgba(212, 165, 116, 0.12);
            --ok: #4ade80;
            --warn: #fbbf24;
            --bad: #f87171;
            --info: #60a5fa;
            --violet: #a78bfa;
        }
        .rating-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .rating-root .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
        .rating-root input,
        .rating-root select,
        .rating-root textarea {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .rating-root input::placeholder,
        .rating-root textarea::placeholder { color: var(--text-muted); }
        .rating-root input:focus,
        .rating-root select:focus,
        .rating-root textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
    </style>

    <div wire:poll.30s="$refresh" class="hidden"></div>

    <main class="relative z-10 p-3 sm:p-4 md:p-5 max-w-7xl mx-auto space-y-3.5">

        {{-- HEADER --}}
        <div class="card p-3 sm:p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa-solid fa-star-half-stroke text-sm"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black tracking-tight" style="color: var(--text-primary);">
                            Review Control
                        </h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold"
                              style="background-color: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span>
                            {{ $stats['total'] }} REVIEWS
                        </span>
                    </div>
                    <p class="text-xs font-mono truncate" style="color: var(--text-muted);">
                        {{ $branch->name ?? 'Branch Node' }} • Sentiment Telemetry
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-56">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                    <input type="text" wire:model.live.debounce.250ms="search"
                           placeholder="Search item, guest, or text..."
                           class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs outline-none transition">
                </div>
                <div class="relative">
                    <select wire:model.live="sortBy"
                            class="pl-3 pr-8 py-1.5 rounded-xl text-xs font-mono font-bold outline-none transition appearance-none cursor-pointer">
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                        <option value="highest">Highest ★</option>
                        <option value="lowest">Lowest ★</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] pointer-events-none" style="color: var(--text-muted);"></i>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
                 class="p-3 rounded-xl text-xs font-mono font-bold flex items-center justify-between"
                 style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok);">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" style="color: var(--ok);"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        {{-- KPI + DISTRIBUTION --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">

            <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3.5">
                <div class="card p-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider" style="color: var(--text-muted);">Total Feedback</span>
                        <div class="text-lg font-black font-mono" style="color: var(--text-primary);">{{ $stats['total'] }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs"
                         style="background-color: var(--bg-inner); color: var(--accent);">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                </div>

                <div class="p-3 rounded-xl flex items-center justify-between shadow-sm"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider opacity-80">Average Score</span>
                        <div class="text-lg font-black font-mono flex items-center gap-1.5">
                            {{ $stats['average'] }}
                            <span class="text-xs font-normal opacity-70">/ 5.0</span>
                        </div>
                    </div>
                    <div class="flex text-xs gap-0.5">
                        @for($i=1; $i<=5; $i++)
                            <i class="fa-solid fa-star {{ $i <= round($stats['average']) ? 'text-amber-300' : 'opacity-30' }}"></i>
                        @endfor
                    </div>
                </div>

                <div class="card p-3 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider" style="color: var(--text-muted);">Reply Rate</span>
                        <div class="text-lg font-black font-mono" style="color: var(--text-primary);">
                            {{ $stats['reply_rate'] }}<span class="text-xs" style="color: var(--text-muted);">%</span>
                        </div>
                        <div class="text-[9px] font-mono mt-0.5" style="color: var(--text-muted);">{{ $stats['replied'] }} / {{ $stats['total'] }} replied</div>
                    </div>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs"
                         style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok);">
                        <i class="fa-solid fa-reply-all"></i>
                    </div>
                </div>
            </div>

            {{-- Distribution --}}
            <div class="lg:col-span-6 card p-3.5 shadow-sm">
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-chart-simple text-xs" style="color: var(--accent);"></i>
                        <span class="text-xs font-mono font-bold uppercase tracking-wider" style="color: var(--text-primary);">Rating Distribution</span>
                    </div>
                    <span class="text-[10px] font-mono" style="color: var(--text-muted);">Total: {{ $stats['total'] }}</span>
                </div>

                <div class="space-y-1.5">
                    @foreach($distribution as $star => $data)
                        @php
                            $barColor = $star >= 4 ? 'var(--ok)' : ($star == 3 ? 'var(--warn)' : 'var(--bad)');
                        @endphp
                        <div class="flex items-center gap-2.5">
                            <span class="text-[10px] font-mono font-bold w-6 flex items-center gap-0.5" style="color: var(--text-muted);">
                                {{ $star }}<i class="fa-solid fa-star text-[8px]" style="color: var(--accent);"></i>
                            </span>
                            <div class="flex-grow rounded-full h-2 overflow-hidden"
                                 style="background-color: color-mix(in srgb, var(--text-primary) 10%, transparent);">
                                <div class="h-full rounded-full transition-all duration-500"
                                     style="width: {{ $data['percent'] }}%; background-color: {{ $barColor }};"></div>
                            </div>
                            <span class="text-[10px] font-mono font-bold w-10 text-right" style="color: var(--text-primary);">{{ $data['count'] }}</span>
                            <span class="text-[10px] font-mono w-9 text-right" style="color: var(--text-muted);">{{ $data['percent'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- FILTER BAR + LIST --}}
        <div class="card overflow-hidden">

            <div class="p-3 flex flex-wrap items-center justify-between gap-2.5"
                 style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">

                <div class="flex items-center gap-2 flex-wrap">
                    <i class="fa-solid fa-filter text-xs" style="color: var(--accent);"></i>
                    <span class="text-xs font-mono font-bold" style="color: var(--text-primary);">Rating:</span>

                    <button wire:click="$set('ratingFilter', null)"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold transition"
                            style="{{ is_null($ratingFilter)
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">
                        All
                    </button>
                    @foreach([5, 4, 3, 2, 1] as $star)
                        <button wire:click="$set('ratingFilter', {{ $star }})"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-mono font-bold transition"
                                style="{{ $ratingFilter == $star
                                    ? 'background-color: var(--accent); color: var(--bg-primary);'
                                    : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">
                            {{ $star }}<i class="fa-solid fa-star text-[8px] ml-0.5"></i>
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach([
                        'all' => ['label' => 'All', 'icon' => 'fa-list'],
                        'unreplied' => ['label' => 'Needs Reply', 'icon' => 'fa-circle-exclamation'],
                        'replied' => ['label' => 'Replied', 'icon' => 'fa-check-circle'],
                        'hidden' => ['label' => 'Hidden', 'icon' => 'fa-eye-slash'],
                    ] as $key => $meta)
                        <button wire:click="setVisibilityFilter('{{ $key }}')"
                                class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition flex items-center gap-1"
                                style="{{ $visibilityFilter === $key
                                    ? 'background-color: var(--accent); color: var(--bg-primary);'
                                    : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">
                            <i class="fa-solid {{ $meta['icon'] }} text-[9px]"></i>
                            {{ $meta['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Review list --}}
            <div>
                @forelse($reviews as $review)
                    @php
                        $sentimentColor = $review->rating >= 4 ? 'var(--ok)' : ($review->rating == 3 ? 'var(--warn)' : 'var(--bad)');
                    @endphp
                    <div class="p-3.5 sm:p-4 transition"
                         style="border-bottom: 1px solid var(--border-soft); border-left: 4px solid {{ $sentimentColor }}; {{ !$review->is_visible ? 'opacity: 0.6;' : '' }}">

                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                            <div class="flex-1 space-y-2 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold"
                                          style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                                        {{ $review->product->name ?? 'General Order' }}
                                    </span>

                                    <div class="flex gap-0.5 text-xs">
                                        @for($i=1; $i<=5; $i++)
                                            <i class="fa-solid fa-star text-[10px]"
                                               style="color: {{ $i <= $review->rating ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 20%, transparent)' }};"></i>
                                        @endfor
                                    </div>

                                    @if(!$review->is_visible)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-bold"
                                              style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 20%, transparent);">
                                            <i class="fa-solid fa-eye-slash text-[8px] mr-0.5"></i>HIDDEN
                                        </span>
                                    @endif

                                    <span class="text-[10px] font-mono" style="color: var(--text-muted);">
                                        {{ $review->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <div>
                                    <div class="text-xs font-bold flex items-center gap-1.5" style="color: var(--text-primary);">
                                        {{ $review->user->name ?? 'Verified Guest' }}
                                        @if($review->user_id) <i class="fa-solid fa-circle-check text-[10px]" style="color: var(--ok);"></i> @endif
                                    </div>
                                    <p class="text-xs italic mt-1 p-2.5 rounded-xl break-words"
                                       style="color: var(--text-primary); background-color: var(--bg-inner); border-left: 2px solid var(--accent);">
                                        "{{ $review->comment }}"
                                    </p>
                                </div>

                                @if($review->owner_reply)
                                    <div class="ml-4">
                                        <button wire:click="toggleReplyExpand({{ $review->id }})"
                                                class="text-[10px] font-mono font-bold flex items-center gap-1"
                                                style="color: var(--accent);">
                                            <i class="fa-solid fa-reply"></i>
                                            {{ $expandedReply === $review->id ? 'Hide' : 'Show' }} Branch Reply
                                            <i class="fa-solid fa-chevron-{{ $expandedReply === $review->id ? 'up' : 'down' }} text-[8px]"></i>
                                        </button>

                                        @if($expandedReply === $review->id)
                                            <div class="mt-2 p-2.5 rounded-xl text-xs"
                                                 style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                                <span class="text-[9px] font-mono font-bold uppercase flex items-center gap-1 mb-0.5"
                                                      style="color: var(--accent);">
                                                    <i class="fa-solid fa-reply"></i> Official Branch Response
                                                </span>
                                                <p class="italic" style="color: var(--text-muted);">"{{ $review->owner_reply }}"</p>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="ml-4">
                                        <span class="text-[10px] font-mono font-bold flex items-center gap-1" style="color: var(--warn);">
                                            <i class="fa-solid fa-circle-exclamation text-[9px]"></i>
                                            Awaiting response
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center sm:flex-col gap-1.5 sm:self-start flex-shrink-0">
                                <button wire:click="toggleStatus({{ $review->id }})"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition flex items-center gap-1"
                                        style="{{ $review->is_visible
                                            ? 'background-color: var(--bg-inner); color: var(--text-muted);'
                                            : 'background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 20%, transparent);' }}">
                                    <i class="fa-solid {{ $review->is_visible ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                    <span>{{ $review->is_visible ? 'Hide' : 'Restore' }}</span>
                                </button>

                                <button wire:click="setReply({{ $review->id }})"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition flex items-center gap-1"
                                        style="background-color: var(--accent); color: var(--bg-primary);">
                                    <i class="fa-solid fa-reply"></i>
                                    <span>{{ $review->owner_reply ? 'Edit' : 'Reply' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-14 text-center">
                        <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-3"
                             style="background-color: var(--bg-inner);">
                            <i class="fa-solid fa-inbox text-2xl" style="color: var(--text-muted);"></i>
                        </div>
                        <h3 class="text-sm font-bold mb-1" style="color: var(--text-primary);">No reviews found</h3>
                        <p class="text-xs font-mono" style="color: var(--text-muted);">
                            @if($search || $ratingFilter || $visibilityFilter !== 'all')
                                Try clearing your filters.
                            @else
                                Waiting for customers to leave feedback.
                            @endif
                        </p>
                        @if($search || $ratingFilter || $visibilityFilter !== 'all')
                            <button wire:click="$set('search', ''); $set('ratingFilter', null); $set('visibilityFilter', 'all')"
                                    class="mt-3 px-3 py-1.5 rounded-lg text-[11px] font-mono font-bold transition"
                                    style="background-color: var(--accent); color: var(--bg-primary);">
                                <i class="fa-solid fa-rotate-left text-[10px] mr-1"></i> Clear Filters
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>

            @if($reviews->hasPages())
                <div class="p-3" style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                    {{ $reviews->links() }}
                </div>
            @endif
        </div>
    </main>

    {{-- REPLY MODAL --}}
    @if($replyingTo)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-3">
        <div class="fixed inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.6);" wire:click="$set('replyingTo', null)"></div>
        <div class="card w-full max-w-lg shadow-2xl relative z-10 overflow-hidden">

            <div class="p-3.5 flex items-center justify-between"
                 style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                <h3 class="text-sm font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <i class="fa-solid fa-reply-all" style="color: var(--accent);"></i> Customer Response Draft
                </h3>
                <button wire:click="$set('replyingTo', null)" style="color: var(--text-muted);"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="p-4 space-y-3">
                <textarea wire:model="replyContent" rows="4"
                    class="w-full rounded-xl p-3 text-xs outline-none"
                    placeholder="Compose an official branch response..."></textarea>

                <div class="flex gap-2">
                    <button wire:click="$set('replyingTo', null)"
                            class="flex-1 py-2 text-xs font-mono rounded-xl font-bold"
                            style="background-color: var(--bg-inner); color: var(--text-muted);">
                        Cancel
                    </button>
                    <button wire:click="saveReply"
                            class="flex-1 py-2 text-xs font-mono rounded-xl font-bold"
                            style="background-color: var(--accent); color: var(--bg-primary);">
                        Send Response
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>