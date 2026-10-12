<?php

use App\Models\Order;
use App\Models\RewardRedemption;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
new #[Layout('layouts.cashier')] class extends Component
{
    use WithPagination;

    public $branchId;
    public $tenantId;
    public $search = '';
    public $selectedOrderId = null;
    public $statusFilter = 'pending';
    public $viewMode = 'grid';
    public $lastAction = null;

    protected array $allowedStatuses = ['pending', 'preparing', 'ready', 'completed'];

    public function mount()
    {
        $user = Auth::user();
        $this->branchId = $user->branch_id;
        $this->tenantId = $user->tenant_id;
    }

    /* Reset to page 1 whenever the list changes */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /* ================================================================
     *  COUNTS
     * ================================================================ */

    private array $statusCountsCache = [];
    private bool $statusCountsLoaded = false;

    private function loadStatusCounts(): void
    {
        // Only hit the database once per request (was running 3x before)
        if ($this->statusCountsLoaded) {
            return;
        }

        $this->statusCountsCache = Order::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('payment_status', ['paid', 'pending'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $this->statusCountsLoaded = true;
    }

    public function getPendingCountProperty(): int
    {
        $this->loadStatusCounts();
        return (int) ($this->statusCountsCache['pending'] ?? 0);
    }

    public function getPreparingCountProperty(): int
    {
        $this->loadStatusCounts();
        return (int) ($this->statusCountsCache['preparing'] ?? 0);
    }

    public function getReadyCountProperty(): int
    {
        $this->loadStatusCounts();
        return (int) ($this->statusCountsCache['ready'] ?? 0);
    }

    public function getCompletedOrdersProperty(): int
    {
        return Order::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('status', 'completed')
            ->whereIn('payment_status', ['paid', 'pending'])
            ->whereDate('created_at', today())
            ->count();
    }

    public function getTotalOrdersProperty(): int
    {
        return Order::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('payment_status', ['paid', 'pending'])
            ->count();
    }

    public function getDailyStatsProperty(): array
    {
        $row = Order::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('payment_status', ['paid', 'pending'])
            ->whereDate('created_at', today())
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                COUNT(CASE WHEN status IN ("pending","preparing","ready") THEN 1 END) as active,
                AVG(CASE WHEN status = "completed" THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) END) as avg_prep
            ')
            ->first();

        return [
            'today_total'  => (int) ($row->total ?? 0),
            'completed'    => (int) ($row->completed ?? 0),
            'active'       => (int) ($row->active ?? 0),
            'avg_prep_min' => (int) round($row->avg_prep ?? 0),
        ];
    }

    /* ================================================================
     *  ORDERS LIST
     * ================================================================ */

    public function getOrdersProperty()
    {
        $query = Order::query()
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->whereIn('payment_status', ['paid', 'pending'])
            ->with(['items.product', 'user:id,name'])
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', '%' . $this->search . '%'));

        if ($this->statusFilter === 'all') {
            $query->whereIn('status', ['pending', 'preparing', 'ready', 'completed']);
        } elseif (in_array($this->statusFilter, $this->allowedStatuses)) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'asc')->paginate(10);
    }

    /* ================================================================
     *  UI ACTIONS
     * ================================================================ */

    #[On('toggle-view')]
    public function toggleView(): void
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    public function dismissUndo(): void
    {
        $this->lastAction = null;
    }

    /* ================================================================
     *  UNDO
     * ================================================================ */

    #[On('undo-last')]
    public function undoLastAction()
    {
        if (! $this->lastAction) return;

        $order = Order::where('id', $this->lastAction['order_id'])
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->first();

        if (! $order) {
            session()->flash('error', 'Order no longer exists.');
            $this->lastAction = null;
            return;
        }

        $fromStatus = $this->lastAction['from_status'];

        if ($this->lastAction['to_status'] === 'completed') {
            app(LoyaltyService::class)->reverseForOrder($order);

            RewardRedemption::where('tenant_id', $this->tenantId)
                ->where('order_id', $order->id)
                ->where('status', 'fulfilled')
                ->update(['status' => 'pending']);
        }

        $order->update(['status' => $fromStatus]);

        $this->statusFilter = $fromStatus;

        session()->flash('success', "Undone: {$this->lastAction['label']}");
        $this->lastAction = null;
    }

    /* ================================================================
     *  ORDER LIFECYCLE
     *  NOTE: these still require payment_status = 'paid'.
     * ================================================================ */

    public function acceptOrder(int $orderId): void
    {
        $order = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('payment_status', 'paid')
            ->find($orderId);

        if (! $order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        if ($order->status !== 'pending') {
            session()->flash('error', 'This order has already been accepted.');
            return;
        }

        $fromStatus = $order->status;

        $order->update([
            'status' => 'preparing',
            'notes'  => ($order->notes ? $order->notes . "\n" : '') . 'Order accepted by barista at ' . now()->format('g:i A'),
        ]);

        $this->lastAction = [
            'order_id'    => $order->id,
            'from_status' => $fromStatus,
            'to_status'   => 'preparing',
            'label'       => "Order #{$order->order_number} accepted",
        ];

        $this->statusFilter = 'preparing';

        session()->flash('success', "Order #{$order->order_number} accepted! Now preparing.");
        $this->dispatch('order-updated');
        $this->resetPage();
    }

    public function markReady(int $orderId): void
    {
        $order = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('payment_status', 'paid')
            ->find($orderId);

        if (! $order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        if ($order->status !== 'preparing') {
            session()->flash('error', 'Order must be in preparing status.');
            return;
        }

        $fromStatus = $order->status;

        $order->update([
            'status' => 'ready',
            'notes'  => ($order->notes ? $order->notes . "\n" : '') . 'Order ready for pickup at ' . now()->format('g:i A'),
        ]);

        $this->lastAction = [
            'order_id'    => $order->id,
            'from_status' => $fromStatus,
            'to_status'   => 'ready',
            'label'       => "Order #{$order->order_number} marked ready",
        ];

        $this->statusFilter = 'ready';

        session()->flash('success', "Order #{$order->order_number} is ready for pickup!");
        $this->dispatch('order-updated');
    }

    public function completeOrder(int $orderId): void
    {
        $order = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('payment_status', 'paid')
            ->find($orderId);

        if (! $order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        if ($order->status !== 'ready') {
            session()->flash('error', 'Order must be ready first.');
            return;
        }

        $fromStatus = $order->status;

        $awardedPoints   = 0;
        $rewardFulfilled = false;

        try {
            DB::transaction(function () use ($orderId, &$order, &$awardedPoints, &$rewardFulfilled) {
                $order = Order::where('tenant_id', $this->tenantId)
                    ->where('branch_id', $this->branchId)
                    ->where('payment_status', 'paid')
                    ->lockForUpdate()
                    ->findOrFail($orderId);

                if ($order->status !== 'ready') {
                    throw new \RuntimeException('This order is no longer ready to complete.');
                }

                $order->update([
                    'status' => 'completed',
                    'notes'  => ($order->notes ? $order->notes . "\n" : '') . 'Order completed at ' . now()->format('g:i A') . ' (Payment: ' . ucfirst($order->payment_method) . ')',
                ]);

                $order->refresh();
                $awardedPoints = (int) ($order->loyalty_points_awarded ?? 0);

                $rewardFulfilled = RewardRedemption::where('tenant_id', $this->tenantId)
                    ->where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'fulfilled']) > 0;
            });
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Could not complete the order: ' . $e->getMessage());
            return;
        }

        $this->lastAction = [
            'order_id'    => $order->id,
            'from_status' => $fromStatus,
            'to_status'   => 'completed',
            'label'       => "Order #{$order->order_number} completed",
        ];

        $this->statusFilter = 'completed';

        $paymentMethod = ucfirst($order->payment_method);
        $message = $this->buildCompletionMessage($order, $awardedPoints, $rewardFulfilled, $paymentMethod);

        session()->flash('success', $message);

        $this->dispatch('loyalty-awarded',
            orderId: $order->id,
            points: $awardedPoints,
            isRewardOrder: (bool) $order->is_reward_order,
        );

        $this->dispatch('order-updated');
    }

    private function buildCompletionMessage(Order $order, int $awardedPoints, bool $rewardFulfilled, string $paymentMethod): string
    {
        if ($order->is_reward_order) {
            return $rewardFulfilled
                ? "Reward order #{$order->order_number} completed! Redemption marked fulfilled. (No new points — reward order)"
                : "Reward order #{$order->order_number} completed! (No new points — reward order)";
        }

        if (! $order->user_id) {
            return "Order #{$order->order_number} completed! No points awarded — walk-in guest. (Payment: {$paymentMethod})";
        }

        if ($awardedPoints > 0) {
            return "Order #{$order->order_number} completed! +{$awardedPoints} loyalty points. (Payment: {$paymentMethod})";
        }

        return "Order #{$order->order_number} completed! No points awarded — loyalty is disabled or unavailable. (Payment: {$paymentMethod})";
    }

    /* ================================================================
     *  PRINT
     * ================================================================ */

    public function printTicket(int $orderId): void
    {
        $order = Order::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->with(['items.product', 'user'])
            ->find($orderId);

        if (! $order) {
            session()->flash('error', 'Order not found.');
            return;
        }

        $this->dispatch('print-ticket', orderId: $order->id);
    }

    /* ================================================================
     *  RENDER
     * ================================================================ */

    public function render()
    {
        return view('pages.branch.barista-dashboard.barista-dashboard', [
            'orders'         => $this->orders,
            'pendingCount'   => $this->pendingCount,
            'preparingCount' => $this->preparingCount,
            'readyCount'     => $this->readyCount,
            'completedCount' => $this->completedOrders,
            'totalOrders'    => $this->totalOrders,
            'dailyStats'     => $this->dailyStats,
        ]);
    }
};
?>

@php
    // Friendly, low-number wording instead of "12m ago" / full clock + date.
    $friendlyAgo = function (int $m): string {
        if ($m < 2)  return 'Just now';
        if ($m < 10) return 'A few min ago';
        if ($m < 30) return 'Over 10 min ago';
        if ($m < 60) return 'Over 30 min ago';
        return 'Over an hour ago';
    };

    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', trim(auth()->user()->name ?? ''))[0] ?: 'Barista';
@endphp

<div class="barista-root relative min-h-screen font-sans transition-colors duration-300"
     x-data="{
        theme: localStorage.getItem('barista-theme') || 'light',
        init() {
            this.applyTheme();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('barista-theme')) {
                    this.theme = e.matches ? 'dark' : 'light';
                    this.applyTheme();
                }
            });
        },
        applyTheme() {
            const root = document.documentElement;
            if (this.theme === 'dark') {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
        },
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('barista-theme', this.theme);
            this.applyTheme();
        }
     }"
     style="background-color: var(--bg-primary); color: var(--text-primary);">

    <style>
        .barista-root {
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
        }
        .dark .barista-root {
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

        .barista-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .barista-root .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
        .barista-root input,
        .barista-root select,
        .barista-root textarea {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .barista-root input::placeholder { color: var(--text-muted); }
        .barista-root input:focus,
        .barista-root select:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }

        .barista-root .pill-accent { background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent); }
        .barista-root .pill-ok { background: color-mix(in srgb, var(--ok) 12%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); }
        .barista-root .pill-warn { background: color-mix(in srgb, var(--warn) 12%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent); }
        .barista-root .pill-bad { background: color-mix(in srgb, var(--bad) 12%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); }
        .barista-root .pill-info { background: color-mix(in srgb, var(--info) 12%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent); }
        .barista-root .pill-violet { background: color-mix(in srgb, var(--violet) 12%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent); }

        .barista-root .btn-accent {
            background-image: linear-gradient(135deg,
                color-mix(in srgb, var(--accent) 95%, white 5%) 0%,
                color-mix(in srgb, var(--accent-bright) 90%, black 10%) 100%);
            color: #fff;
        }
        .barista-root .btn-accent:hover {
            background-image: linear-gradient(135deg,
                color-mix(in srgb, var(--accent-bright) 95%, white 5%) 0%,
                color-mix(in srgb, var(--accent) 90%, black 10%) 100%);
        }

        /* Compact action buttons (Accept / Ready / Complete) */
        .barista-root .btn-action {
            padding: 0.4rem 0.8rem;
            font-size: 11px;
            line-height: 1.2;
            border-radius: 0.6rem;
            color: #fff;
            font-weight: 700;
            white-space: nowrap;
        }
        .barista-root .btn-action:hover { filter: brightness(1.08); }
        .barista-root .btn-action:active { transform: scale(0.97); }

        /* Compact icon buttons */
        .barista-root .btn-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.6rem;
        }

        /* Log out / account buttons coming from the cashier layout.
           The layout isn't part of this file, so we target them by common selectors. */
        form[action*="logout"] button,
        a[href*="logout"],
        button[wire\:click*="logout"],
        button[type="submit"][class*="logout" i],
        .logout-btn,
        #logout-button {
            padding: 0.35rem 0.75rem !important;
            font-size: 11px !important;
            line-height: 1.2 !important;
            width: auto !important;
            height: auto !important;
            min-height: 0 !important;
            min-width: 0 !important;
            border-radius: 0.6rem !important;
            gap: 0.35rem !important;
        }
        form[action*="logout"] button svg,
        a[href*="logout"] svg,
        button[wire\:click*="logout"] svg,
        .logout-btn svg,
        #logout-button svg {
            width: 0.85rem !important;
            height: 0.85rem !important;
        }

        @keyframes slide-down {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-down { animation: slide-down 0.3s ease-out; }

        .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: color-mix(in srgb, var(--accent) 40%, transparent);
            border-radius: 10px;
        }

        .barista-root,
        .barista-root * {
            transition-property: background-color, border-color, color;
            transition-duration: 300ms;
            transition-timing-function: ease;
        }
        .barista-root button,
        .barista-root a {
            transition-property: background-color, border-color, color, transform, opacity, box-shadow, filter;
        }

        @media print {
            .barista-root main > *:not(.print-keep) { display: none !important; }
        }
    </style>

    <div wire:poll.5s class="hidden"></div>

    {{-- HIDDEN TRACKER --}}
    <div id="order-tracker"
         data-pending="{{ $pendingCount }}"
         data-preparing="{{ $preparingCount }}"
         data-ready="{{ $readyCount }}"
         wire:key="tracker-{{ $pendingCount }}-{{ $preparingCount }}-{{ $readyCount }}"
         class="hidden"
         aria-hidden="true"></div>

    <main class="relative z-10 p-3 sm:p-4 md:p-5 max-w-7xl mx-auto space-y-3">

        {{-- HEADER --}}
        <div class="card p-3 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa-solid fa-mug-hot text-sm"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-base sm:text-lg font-black tracking-tight" style="color: var(--text-primary);">
                            Barista Station
                        </h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold pill-accent">
                            <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background-color: var(--accent);"></span>
                            {{ $pendingCount + $preparingCount }} active
                        </span>
                    </div>
                    <p class="text-[11px] truncate" style="color: var(--text-muted);">
                        {{ $greeting }}, {{ $firstName }} • {{ now()->format('l') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none sm:w-52">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[10px]" style="color: var(--text-muted);"></i>
                    <input type="text"
                           wire:model.live.debounce.250ms="search"
                           placeholder="Search order #..."
                           id="barista-search"
                           class="w-full pl-8 pr-3 py-1.5 rounded-lg text-xs outline-none transition-all">
                </div>

                <button @click="toggleTheme()"
                        class="flex-shrink-0 btn-icon inner flex items-center justify-center transition hover:scale-105"
                        style="color: var(--text-muted);"
                        :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'">
                    <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="4"/>
                        <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/>
                    </svg>
                    <svg x-show="theme === 'light'" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                </button>

                <button wire:click="toggleView"
                        class="flex-shrink-0 btn-icon inner flex items-center justify-center transition"
                        style="color: var(--text-muted);"
                        title="Toggle grid / list view (G)">
                    @if($viewMode === 'grid')
                        <i class="fa-solid fa-list text-xs"></i>
                    @else
                        <i class="fa-solid fa-table-cells-large text-xs"></i>
                    @endif
                </button>
            </div>
        </div>

        {{-- AUDIO UNLOCK BANNER --}}
        <div x-data="{ needsClick: true }"
             x-show="needsClick"
             x-init="
                const enable = () => { needsClick = false; };
                document.addEventListener('click', enable, { once: true });
                document.addEventListener('keydown', enable, { once: true });
             "
             x-transition
             class="px-3 py-2 rounded-xl text-[11px] font-mono font-bold flex items-center justify-between pill-info">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-volume-high animate-pulse"></i>
                <span>Tap anywhere to turn on order sounds</span>
            </div>
            <button @click="needsClick = false" class="px-1"><i class="fa-solid fa-xmark"></i></button>
        </div>

        {{-- FLASH MESSAGES --}}
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="px-3 py-2 rounded-xl text-[11px] font-mono font-bold flex items-center justify-between pill-ok">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="px-1"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="px-3 py-2 rounded-xl text-[11px] font-mono font-bold flex items-center justify-between pill-bad">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="px-1"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        {{-- UNDO BANNER --}}
        @if($lastAction)
            <div x-data="{ show: true }" x-show="show"
                 x-init="setTimeout(() => { show = false; @this.call('dismissUndo') }, 8000)"
                 class="px-3 py-2 rounded-xl flex items-center justify-between gap-3 animate-slide-down pill-warn">
                <div class="flex items-center gap-2.5 min-w-0">
                    <i class="fa-solid fa-clock-rotate-left text-xs flex-shrink-0"></i>
                    <p class="text-[11px] font-mono font-bold truncate">{{ $lastAction['label'] }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button wire:click="undoLastAction"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold transition"
                            style="background-color: color-mix(in srgb, var(--warn) 20%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                        <i class="fa-solid fa-rotate-left text-[9px] mr-1"></i> Undo (U)
                    </button>
                    <button wire:click="dismissUndo" class="px-1"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        @endif

        {{-- KPI CARDS --}}
        @php
            $stats = [
                ['filter' => 'pending',   'label' => 'Pending',   'icon' => 'fa-clock',            'var' => '--warn',   'count' => $pendingCount],
                ['filter' => 'preparing', 'label' => 'Preparing', 'icon' => 'fa-blender',          'var' => '--info',   'count' => $preparingCount],
                ['filter' => 'ready',     'label' => 'Ready',     'icon' => 'fa-bell-concierge',   'var' => '--violet', 'count' => $readyCount],
                ['filter' => 'completed', 'label' => 'Completed', 'icon' => 'fa-circle-check',     'var' => '--ok',     'count' => $completedCount],
            ];
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
            @foreach($stats as $stat)
                <button wire:click="$set('statusFilter', '{{ $stat['filter'] }}')"
                        class="card p-3 text-left transition-all duration-300 hover:-translate-y-0.5"
                        style="{{ $statusFilter === $stat['filter']
                            ? 'border-color: color-mix(in srgb, var(' . $stat['var'] . ') 40%, transparent); background-color: color-mix(in srgb, var(' . $stat['var'] . ') 5%, var(--bg-elevated));'
                            : '' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-mono font-bold uppercase tracking-wider" style="color: var(--text-muted);">
                                {{ $stat['label'] }}
                            </div>
                            <div class="text-xl sm:text-2xl font-black font-mono mt-0.5" style="color: var(--text-primary);">
                                {{ $stat['count'] }}
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                             style="background-color: color-mix(in srgb, var({{ $stat['var'] }}) 12%, transparent); color: var({{ $stat['var'] }});">
                            <i class="fa-solid {{ $stat['icon'] }} text-xs"></i>
                        </div>
                    </div>
                    @if($statusFilter === $stat['filter'])
                        <div class="mt-2 h-0.5 rounded-full" style="background-color: var({{ $stat['var'] }});"></div>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- DAILY PREP STATS --}}
        @if($dailyStats['today_total'] > 0)
            <div class="card p-3 shadow-sm">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @php
                        $dailyCards = [
                            ['icon' => 'fa-chart-simple', 'label' => 'Today',     'value' => $dailyStats['today_total'], 'var' => '--accent'],
                            ['icon' => 'fa-check-double', 'label' => 'Completed', 'value' => $dailyStats['completed'],   'var' => '--ok'],
                            ['icon' => 'fa-spinner',      'label' => 'Active',    'value' => $dailyStats['active'],      'var' => '--warn'],
                            ['icon' => 'fa-stopwatch',    'label' => 'Avg prep',  'value' => $dailyStats['avg_prep_min'] . ' min', 'var' => '--violet'],
                        ];
                    @endphp
                    @foreach($dailyCards as $c)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background-color: color-mix(in srgb, var({{ $c['var'] }}) 12%, transparent); color: var({{ $c['var'] }});">
                                <i class="fa-solid {{ $c['icon'] }} text-[10px]"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] font-mono font-bold uppercase tracking-wider truncate" style="color: var(--text-muted);">
                                    {{ $c['label'] }}
                                </p>
                                <p class="text-sm font-black font-mono" style="color: var(--text-primary);">{{ $c['value'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ORDERS --}}
        <div class="card overflow-hidden print-keep">

            @if($viewMode === 'list')

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[700px]">
                        <thead class="font-mono text-[10px] uppercase tracking-wider"
                               style="background-color: var(--bg-inner); color: var(--text-muted); border-bottom: 1px solid var(--border-soft);">
                            <tr>
                                <th class="px-4 py-2.5">Order</th>
                                <th class="px-4 py-2.5">Customer</th>
                                <th class="px-4 py-2.5">Items</th>
                                <th class="px-4 py-2.5">Amount</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                @php
                                    $elapsed = $order->created_at->diffInMinutes(now());
                                    $urgency = $elapsed >= 15 ? '--bad' : ($elapsed >= 5 ? '--warn' : '--ok');
                                    $statusMap = [
                                        'pending'   => ['label' => 'Pending',   'var' => '--warn'],
                                        'preparing' => ['label' => 'Preparing', 'var' => '--info'],
                                        'ready'     => ['label' => 'Ready',     'var' => '--violet'],
                                        'completed' => ['label' => 'Completed', 'var' => '--ok'],
                                    ];
                                    $status = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'var' => '--accent'];
                                @endphp
                                <tr wire:key="order-{{ $order->id }}"
                                    style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-mono font-bold" style="color: var(--accent);">#{{ $order->order_number }}</span>
                                            @if($order->is_reward_order)
                                                <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold pill-violet">
                                                    REWARD
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-medium text-xs" style="color: var(--text-primary);">
                                            {{ $order->user->name ?? 'Walk-in Guest' }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-[10px] font-mono font-bold" style="color: var({{ $urgency }});">
                                                <i class="fa-solid fa-clock text-[8px] mr-0.5"></i>{{ $friendlyAgo($elapsed) }}
                                            </span>
                                            @if($urgency === '--bad')
                                                <span class="w-1.5 h-1.5 rounded-full animate-ping" style="background-color: var(--bad);"></span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="space-y-0.5 max-h-16 overflow-y-auto scrollbar-thin">
                                            @foreach($order->items as $item)
                                                <div class="flex items-center gap-2 text-[11px]" style="color: var(--text-primary);">
                                                    <span class="font-mono font-bold text-[10px]" style="color: var(--accent);">{{ $item->quantity }}x</span>
                                                    <span>{{ $item->product->name ?? $item->item_name ?? 'Product' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 font-mono font-bold text-xs" style="color: var(--text-primary);">
                                        ₱{{ number_format($order->total_amount, 2) }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                                              style="background-color: color-mix(in srgb, var({{ $status['var'] }}) 12%, transparent); color: var({{ $status['var'] }}); border: 1px solid color-mix(in srgb, var({{ $status['var'] }}) 30%, transparent);">
                                            <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background-color: var({{ $status['var'] }});"></span>
                                            {{ $status['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            @if($order->status === 'pending')
                                                <button wire:click="acceptOrder({{ $order->id }})"
                                                        class="btn-action"
                                                        style="background-color: var(--info);">
                                                    <i class="fa-solid fa-blender text-[10px] mr-1"></i> Accept
                                                </button>
                                            @elseif($order->status === 'preparing')
                                                <button wire:click="markReady({{ $order->id }})"
                                                        class="btn-action"
                                                        style="background-color: var(--violet);">
                                                    <i class="fa-solid fa-bell-concierge text-[10px] mr-1"></i> Ready
                                                </button>
                                            @elseif($order->status === 'ready')
                                                <button wire:click="completeOrder({{ $order->id }})"
                                                        class="btn-action"
                                                        style="background-color: var(--ok);">
                                                    <i class="fa-solid fa-check-double text-[10px] mr-1"></i> Complete
                                                </button>
                                            @elseif($order->status === 'completed')
                                                <span class="inline-flex items-center gap-1.5 font-mono font-bold text-[11px]" style="color: var(--ok);">
                                                    <i class="fa-solid fa-check-circle"></i> Done
                                                </span>
                                            @endif

                                            <button wire:click="printTicket({{ $order->id }})"
                                                    class="btn-icon inner flex items-center justify-center transition"
                                                    style="color: var(--text-muted);"
                                                    title="Print ticket">
                                                <i class="fa-solid fa-print text-[10px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-14 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background-color: var(--bg-inner);">
                                                <i class="fa-solid fa-mug-hot text-2xl" style="color: var(--text-muted);"></i>
                                            </div>
                                            <span class="text-xs font-mono" style="color: var(--text-muted);">
                                                @if($statusFilter === 'pending')
                                                    No pending orders. Waiting for customers...
                                                @else
                                                    No orders in this queue
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            @else

                <div class="p-3 sm:p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        @forelse($orders as $order)
                            @php
                                $elapsed = $order->created_at->diffInMinutes(now());
                                $urgency = $elapsed >= 15 ? '--bad' : ($elapsed >= 5 ? '--warn' : '--ok');
                                $statusMap = [
                                    'pending'   => ['label' => 'Pending',   'var' => '--warn'],
                                    'preparing' => ['label' => 'Preparing', 'var' => '--info'],
                                    'ready'     => ['label' => 'Ready',     'var' => '--violet'],
                                    'completed' => ['label' => 'Completed', 'var' => '--ok'],
                                ];
                                $status = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'var' => '--accent'];

                                $noteLines = collect(explode("\n", (string) $order->notes))
                                    ->filter(fn ($line) => $line && !str_starts_with(trim($line), 'Order'))
                                    ->map(fn ($line) => trim($line))
                                    ->filter()
                                    ->values();
                            @endphp

                            <div wire:key="card-{{ $order->id }}"
                                 class="relative rounded-2xl flex flex-col overflow-hidden"
                                 style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                                <div class="absolute top-0 left-0 right-0 h-1" style="background-color: var({{ $urgency }});"></div>

                                <div class="p-3.5 flex flex-col flex-grow">
                                    <div class="flex items-start justify-between gap-2 mb-2.5">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-mono font-bold text-sm truncate" style="color: var(--accent);">
                                                    #{{ $order->order_number }}
                                                </span>
                                                @if($order->is_reward_order)
                                                    <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold pill-violet">REWARD</span>
                                                @endif
                                            </div>
                                            <p class="text-[11px] font-medium mt-0.5 truncate" style="color: var(--text-primary);">
                                                {{ $order->user->name ?? 'Walk-in Guest' }}
                                            </p>
                                        </div>
                                        <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                                              style="background-color: color-mix(in srgb, var({{ $status['var'] }}) 12%, transparent); color: var({{ $status['var'] }}); border: 1px solid color-mix(in srgb, var({{ $status['var'] }}) 30%, transparent);">
                                            <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background-color: var({{ $status['var'] }});"></span>
                                            {{ $status['label'] }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 mb-2.5">
                                        <i class="fa-solid fa-clock text-[10px]" style="color: var({{ $urgency }});"></i>
                                        <span class="text-[10px] font-mono font-bold" style="color: var({{ $urgency }});">
                                            {{ $friendlyAgo($elapsed) }}
                                        </span>
                                        @if($urgency === '--bad')
                                            <span class="w-1.5 h-1.5 rounded-full animate-ping" style="background-color: var(--bad);"></span>
                                        @endif
                                    </div>

                                    <div class="space-y-1 mb-3 flex-grow max-h-32 overflow-y-auto scrollbar-thin">
                                        @foreach($order->items as $item)
                                            <div class="flex items-center gap-2 text-[11px]" style="color: var(--text-primary);">
                                                <span class="font-mono font-bold text-[10px] w-5" style="color: var(--accent);">{{ $item->quantity }}x</span>
                                                <span class="truncate">{{ $item->product->name ?? $item->item_name ?? 'Product' }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($noteLines->count() > 0)
                                        <div class="mb-3 p-2 rounded-lg pill-warn">
                                            <p class="text-[9px] font-mono font-bold uppercase tracking-wider mb-0.5">
                                                <i class="fa-solid fa-note-sticky mr-1"></i>Note
                                            </p>
                                            <p class="text-[11px] line-clamp-2">{{ $noteLines->first() }}</p>
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-between mb-2.5 pt-2.5" style="border-top: 1px solid var(--border-soft);">
                                        <span class="text-[9px] font-mono uppercase" style="color: var(--text-muted);">Total</span>
                                        <span class="font-mono font-black text-sm" style="color: var(--text-primary);">
                                            ₱{{ number_format($order->total_amount, 2) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        @if($order->status === 'pending')
                                            <button wire:click="acceptOrder({{ $order->id }})"
                                                    class="btn-action flex-1 flex items-center justify-center gap-1.5"
                                                    style="background-color: var(--info);">
                                                <i class="fa-solid fa-blender text-[10px]"></i> Accept
                                            </button>
                                        @elseif($order->status === 'preparing')
                                            <button wire:click="markReady({{ $order->id }})"
                                                    class="btn-action flex-1 flex items-center justify-center gap-1.5"
                                                    style="background-color: var(--violet);">
                                                <i class="fa-solid fa-bell-concierge text-[10px]"></i> Mark Ready
                                            </button>
                                        @elseif($order->status === 'ready')
                                            <button wire:click="completeOrder({{ $order->id }})"
                                                    class="btn-action flex-1 flex items-center justify-center gap-1.5"
                                                    style="background-color: var(--ok);">
                                                <i class="fa-solid fa-check-double text-[10px]"></i> Complete
                                            </button>
                                        @elseif($order->status === 'completed')
                                            <div class="flex-1 py-1.5 rounded-lg pill-ok text-[11px] font-mono font-bold flex items-center justify-center gap-1.5">
                                                <i class="fa-solid fa-check-circle text-[10px]"></i> Done
                                            </div>
                                        @endif

                                        <button wire:click="printTicket({{ $order->id }})"
                                                class="btn-icon inner flex items-center justify-center flex-shrink-0 transition"
                                                style="color: var(--text-muted);"
                                                title="Print ticket">
                                            <i class="fa-solid fa-print text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-14">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background-color: var(--bg-inner);">
                                        <i class="fa-solid fa-mug-hot text-2xl" style="color: var(--text-muted);"></i>
                                    </div>
                                    <span class="text-xs font-mono" style="color: var(--text-muted);">
                                        @if($statusFilter === 'pending')
                                            No pending orders. Waiting for customers...
                                        @else
                                            No orders in this queue
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

            @endif

            @if ($orders->hasPages())
                <div class="p-3" style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </main>
</div>

<script>
    // Apply stored theme ASAP to avoid flash
    (function applyStoredTheme() {
        const stored = localStorage.getItem('barista-theme');
        if (stored === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (stored === 'light') {
            document.documentElement.classList.remove('dark');
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    })();

    // Audio context
    var audioCtx = null;
    var audioUnlocked = false;

    function unlockAudio() {
        if (audioUnlocked) return;
        try {
            var AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) return;
            audioCtx = new AudioContextClass();

            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            var buffer = audioCtx.createBuffer(1, 1, 22050);
            var source = audioCtx.createBufferSource();
            source.buffer = buffer;
            source.connect(audioCtx.destination);
            source.start(0);

            audioUnlocked = true;
        } catch (e) {
            console.warn('[Audio] Unlock failed:', e);
        }
    }

    document.addEventListener('click', unlockAudio, { once: true });
    document.addEventListener('touchstart', unlockAudio, { once: true });
    document.addEventListener('keydown', unlockAudio, { once: true });

    // Play the chime
    function playNewOrderSound() {
        try {
            if (!audioCtx) {
                var AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return;
                audioCtx = new AudioContextClass();
            }

            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            var now = audioCtx.currentTime;
            var notes = [
                { freq: 1047, start: 0.00, dur: 0.35 },
                { freq: 1319, start: 0.18, dur: 0.45 },
                { freq: 1568, start: 0.36, dur: 0.55 }
            ];

            notes.forEach(function(note) {
                var osc = audioCtx.createOscillator();
                var gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.value = note.freq;

                var t = now + note.start;
                gain.gain.setValueAtTime(0, t);
                gain.gain.linearRampToValueAtTime(0.22, t + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, t + note.dur);

                osc.connect(gain).connect(audioCtx.destination);
                osc.start(t);
                osc.stop(t + note.dur + 0.05);
            });
        } catch (e) {
            console.warn('[Audio] Play failed:', e);
        }
    }

    // Read pending count from the DOM tracker
    var lastPendingCount = null;

    function getCurrentPending() {
        var el = document.getElementById('order-tracker');
        if (!el) return null;
        var v = parseInt(el.dataset.pending || '0', 10);
        return isNaN(v) ? 0 : v;
    }

    function checkForNewOrders() {
        var newPending = getCurrentPending();
        if (newPending === null) return;

        if (lastPendingCount === null) {
            lastPendingCount = newPending;
            return;
        }

        if (newPending > lastPendingCount) {
            playNewOrderSound();
            flashTitle('(' + newPending + ') New Order!');
        }

        lastPendingCount = newPending;
    }

    var titleInterval = null;
    var originalTitle = document.title;

    function flashTitle(message) {
        if (titleInterval) clearInterval(titleInterval);
        var showing = true;
        titleInterval = setInterval(function() {
            document.title = showing ? message : originalTitle;
            showing = !showing;
        }, 800);

        setTimeout(function() {
            clearInterval(titleInterval);
            document.title = originalTitle;
            titleInterval = null;
        }, 10000);
    }

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            clearInterval(titleInterval);
            document.title = originalTitle;
        }
    });

    // Livewire hooks
    document.addEventListener('livewire:init', function() {
        Livewire.hook('morphed', function() {
            checkForNewOrders();
        });

        Livewire.on('print-ticket', function() {
            setTimeout(function() { window.print(); }, 100);
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.target.matches('input, textarea, select')) {
            if (e.key === 'Escape') e.target.blur();
            return;
        }
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        if (e.key === 'F2' || e.key === '/') {
            e.preventDefault();
            var searchEl = document.getElementById('barista-search');
            if (searchEl) searchEl.focus();
        } else if (e.key === 'g' || e.key === 'G') {
            e.preventDefault();
            if (window.Livewire) window.Livewire.dispatch('toggle-view');
        } else if (e.key === 'u' || e.key === 'U') {
            e.preventDefault();
            if (window.Livewire) window.Livewire.dispatch('undo-last');
        } else if (e.key === 't' || e.key === 'T') {
            e.preventDefault();
            playNewOrderSound();
        }
    });
</script>