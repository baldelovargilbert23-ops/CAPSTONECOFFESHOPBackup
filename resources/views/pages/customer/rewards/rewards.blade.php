<?php

namespace App\Livewire\Pages\Customer;

use App\Models\CustomerPoint;
use App\Models\RewardItem;
use App\Models\RewardRedemption;
use App\Models\Branch;
use App\Models\LoyaltySetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component
{
    public $customerPoints = 0;
    public $availableRewards = [];
    public $redeemedRewards = [];

    /** Composite key "tenantId:branchId" for the selected location. */
    public $selectedBranch = null;
    public $selectedBranchData = null;

    /** Each entry: ['key' => "T:B", 'branch_id' => int, 'tenant_id' => int, ...] */
    public $branches = [];

    public $isLoyaltyEnabled = false;
    public $selectedTenantId = null;

    public $rewardSort = 'cheapest';
    public $pointsEarned = 0;
    public $pointsUsed = 0;
    public $redeemedCount = 0;

    public $showRedeemModal = false;
    public $redeemStep = 1;
    public $redeemReward = null;

    /** Composite key for the branch chosen in the redeem modal. */
    public $redeemBranchKey = null;

    public $lastRedeemedReward = null;

    public function mount(): void
    {
        $this->loadBranches();
        $this->loadCustomerPoints();
        $this->loadAvailableRewards();
        $this->loadRedeemedRewards();
        $this->checkLoyaltyStatus();
    }

    public function checkLoyaltyStatus(): void
    {
        if (! $this->selectedTenantId) {
            $this->isLoyaltyEnabled = false;
            return;
        }
        $settings = LoyaltySetting::where('tenant_id', $this->selectedTenantId)->first();
        $this->isLoyaltyEnabled = (bool) ($settings && $settings->is_enabled);
    }

    public function loadCustomerPoints(): void
    {
        if (! $this->selectedTenantId) {
            $this->customerPoints = 0;
            $this->pointsEarned = 0;
            $this->pointsUsed = 0;
            return;
        }

        $points = CustomerPoint::where('tenant_id', $this->selectedTenantId)
            ->where('user_id', Auth::id())
            ->first();

        $this->customerPoints = $points ? (int) $points->points : 0;
        $this->pointsEarned   = $points ? (int) $points->lifetime_points : 0;
        $this->pointsUsed     = $points ? (int) $points->points_used : 0;
    }

    /**
     * Every main store + every approved sub-branch, with a globally
     * unique key so two main stores don't collide on `branch_id = 0`.
     */
    public function loadBranches(): void
    {
        // ── Sub-branches ────────────────────────────────────────
        $subBranches = Branch::where('is_active', true)
            ->where('approval_status', 'approved')
            ->whereHas('tenant', fn ($q) => $q->where('status', 'active'))
            ->with('tenant:id,name')
            ->get()
            ->map(fn ($branch) => [
                'key'                => $branch->tenant_id . ':' . $branch->id,
                'branch_id'          => $branch->id,
                'tenant_id'          => $branch->tenant_id,
                'name'               => $branch->name,
                'tenant_name'        => $branch->tenant->name ?? 'Shop',
                'is_main'            => false,
                'is_loyalty_enabled' => $branch->isLoyaltyEnabled(),
            ]);

        // ── Main stores ─────────────────────────────────────────
        $mainStores = Tenant::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($tenant) {
                $settings = LoyaltySetting::where('tenant_id', $tenant->id)->first();
                $enabled  = (bool) ($settings && $settings->is_enabled);

                return [
                    'key'                => $tenant->id . ':0',
                    'branch_id'          => 0,               // main store marker
                    'tenant_id'          => $tenant->id,
                    'name'               => $tenant->name . ' (Main Store)',
                    'tenant_name'        => $tenant->name,
                    'is_main'            => true,
                    'is_loyalty_enabled' => $enabled,
                ];
            });

        $this->branches = $mainStores->concat($subBranches)->values()->toArray();

        // Default selection: first enabled, else first entry
        if (! $this->selectedBranch && ! empty($this->branches)) {
            $firstEnabled = collect($this->branches)->firstWhere('is_loyalty_enabled', true);
            $pick = $firstEnabled ?? $this->branches[0];

            $this->selectedBranch     = $pick['key'];
            $this->selectedTenantId   = $pick['tenant_id'];
            $this->selectedBranchData = $pick;
        }
    }

    public function loadAvailableRewards(): void
    {
        if (! $this->selectedTenantId) {
            $this->availableRewards = [];
            return;
        }

        $rewards = RewardItem::where('tenant_id', $this->selectedTenantId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('points_required')
            ->get();

        $this->availableRewards = $rewards->map(fn ($reward) => [
            'id'              => $reward->id,
            'name'            => $reward->name,
            'description'     => $reward->description,
            'points_required' => (int) $reward->points_required,
            'image'           => $reward->image,
            'stock'           => (int) $reward->stock,
            'can_afford'      => $this->customerPoints >= $reward->points_required,
        ])->toArray();

        if ($this->rewardSort === 'expensive') {
            usort($this->availableRewards, fn ($a, $b) => $b['points_required'] <=> $a['points_required']);
        }
    }

    public function loadRedeemedRewards(): void
    {
        if (! $this->selectedTenantId) {
            $this->redeemedRewards = [];
            $this->redeemedCount = 0;
            return;
        }

        $query = RewardRedemption::where('tenant_id', $this->selectedTenantId)
            ->where('user_id', Auth::id())
            ->with(['rewardItem', 'branch'])
            ->latest();

        $this->redeemedCount = (clone $query)->count();

        $this->redeemedRewards = $query->take(10)->get()->map(fn ($r) => [
            'id'           => $r->id,
            'reward_name'  => $r->rewardItem->name ?? 'Unknown',
            'branch_name'  => $r->branch->name ?? 'Main Store',
            'points_used'  => (int) $r->points_used,
            'status'       => $r->status,
            'status_label' => $this->getStatusLabel($r->status),
            'status_color' => $this->getStatusColor($r->status),
            'redeemed_at'  => $r->created_at->format('M d, Y'),
        ])->toArray();
    }

    private function getStatusLabel($status): string
    {
        return match ($status) {
            'pending'   => 'Pending',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
            default     => ucfirst($status),
        };
    }

    private function getStatusColor($status): string
    {
        return match ($status) {
            'pending'   => 'text-amber-600 dark:text-amber-400 bg-amber-500/10 border border-amber-500/20',
            'fulfilled' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border border-emerald-500/20',
            'cancelled' => 'text-red-600 dark:text-red-400 bg-red-500/10 border border-red-500/20',
            default     => 'text-stone-500 bg-stone-500/10 border border-stone-500/20',
        };
    }

    /**
     * $key = "tenantId:branchId" — unique even when two main stores share 0.
     */
    public function selectBranch($key): void
    {
        $branch = collect($this->branches)->firstWhere('key', $key);

        if (! $branch) {
            return;
        }

        $this->selectedBranch     = $branch['key'];
        $this->selectedTenantId   = $branch['tenant_id'];
        $this->selectedBranchData = $branch;

        $this->loadCustomerPoints();
        $this->loadAvailableRewards();
        $this->loadRedeemedRewards();
        $this->checkLoyaltyStatus();
    }

    public function updatedRewardSort(): void
    {
        $this->loadAvailableRewards();
    }

    /* ================================================================
     *  REDEEM FLOW
     * ================================================================ */

    public function startRedeem($rewardId): void
    {
        if (! $this->isLoyaltyEnabled) {
            session()->flash('error', 'Loyalty is disabled for this shop.');
            return;
        }

        $reward = collect($this->availableRewards)->firstWhere('id', $rewardId);

        if (! $reward) {
            session()->flash('error', 'Reward not found.');
            return;
        }

        if (! $reward['can_afford']) {
            session()->flash('error', 'Not enough points.');
            return;
        }

        $this->redeemReward    = $reward;
        $this->redeemStep      = 1;
        $this->redeemBranchKey = $this->selectedBranch;   // composite key
        $this->showRedeemModal = true;
    }

    public function goToStep2(): void
    {
        $this->redeemStep = 2;
    }

    public function backToStep1(): void
    {
        $this->redeemStep = 1;
    }

    public function selectBranchForRedeem($key): void
    {
        $this->redeemBranchKey = $key;
    }

    public function confirmRedeem(): void
    {
        if (! $this->redeemReward || ! $this->redeemBranchKey) {
            session()->flash('error', 'Missing data.');
            return;
        }

        // Parse "tenantId:branchId"
        [$tenantId, $branchId] = array_map('intval', explode(':', $this->redeemBranchKey));
        $isMain = $branchId === 0;

        // ── Location resolution ─────────────────────────────
        if ($isMain) {
            $tenant = Tenant::where('id', $tenantId)
                ->where('status', 'active')
                ->first();

            if (! $tenant) {
                session()->flash('error', 'This shop is not active.');
                return;
            }

            $locationName = $tenant->name . ' (Main Store)';
        } else {
            $branch = Branch::where('id', $branchId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (! $branch || ! $branch->isLoyaltyEnabled()) {
                session()->flash('error', 'This branch does not accept loyalty rewards.');
                return;
            }

            $locationName = $branch->name;
        }

        // ── Tenant master switch ────────────────────────────
        $tenantSetting = LoyaltySetting::where('tenant_id', $tenantId)->first();
        if (! $tenantSetting?->is_enabled) {
            session()->flash('error', 'Loyalty is disabled for this shop.');
            return;
        }

        // ── Reward ownership ────────────────────────────────
        $reward = RewardItem::where('id', $this->redeemReward['id'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $reward) {
            session()->flash('error', 'Reward not available.');
            return;
        }

        if ($reward->stock < 1) {
            session()->flash('error', 'Out of stock.');
            return;
        }

        // ── Points check ────────────────────────────────────
        $points = CustomerPoint::where('tenant_id', $tenantId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $points || $points->points < $reward->points_required) {
            session()->flash('error', 'Insufficient points.');
            return;
        }

        DB::beginTransaction();

        try {
            $points->decrement('points', $reward->points_required);
            $points->increment('points_used', $reward->points_required);

            $orderNumber = 'RDM-' . now()->format('YmdHis') . '-' . rand(100, 999);

            $order = Order::create([
                'tenant_id'       => $tenantId,
                'branch_id'       => $isMain ? null : $branchId,
                'user_id'         => Auth::id(),
                'order_number'    => $orderNumber,
                'total_amount'    => 0,
                'payment_method'  => 'points',
                'payment_status'  => 'paid',
                'status'          => 'pending',
                'notes'           => 'Reward redemption: ' . $reward->name,
                'is_reward_order' => true,
            ]);

            OrderItem::create([
                'order_id'       => $order->id,
                'branch_id'      => $isMain ? null : $branchId,
                'product_id'     => null,
                'quantity'       => 1,
                'price'          => 0,
                'subtotal'       => 0,
                'item_name'      => $reward->name,
                'is_reward_item' => true,
            ]);

            RewardRedemption::create([
                'tenant_id'      => $tenantId,
                'branch_id'      => $isMain ? null : $branchId,
                'user_id'        => Auth::id(),
                'reward_item_id' => $reward->id,
                'order_id'       => $order->id,
                'points_used'    => $reward->points_required,
                'status'         => 'pending',
                'notes'          => 'Redeemed - Order #' . $order->order_number,
            ]);

            $reward->decrement('stock');

            DB::commit();

            $this->selectedBranch     = $this->redeemBranchKey;
            $this->selectedTenantId   = $tenantId;
            $this->selectedBranchData = collect($this->branches)->firstWhere('key', $this->redeemBranchKey);

            $this->loadCustomerPoints();
            $this->loadAvailableRewards();
            $this->loadRedeemedRewards();

            $this->lastRedeemedReward = [
                'name'         => $reward->name,
                'points'       => $reward->points_required,
                'branch'       => $locationName,
                'order_number' => $order->order_number,
            ];

            $this->redeemStep = 3;
            session()->flash('success', 'Reward redeemed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reward redemption failed: ' . $e->getMessage());
            session()->flash('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function closeRedeemModal(): void
    {
        $this->showRedeemModal    = false;
        $this->redeemStep         = 1;
        $this->redeemReward       = null;
        $this->redeemBranchKey    = null;
        $this->lastRedeemedReward = null;
    }

    public function render()
    {
        return view('pages.customer.rewards.rewards', [
            'customerPoints'     => $this->customerPoints,
            'availableRewards'   => $this->availableRewards,
            'redeemedRewards'    => $this->redeemedRewards,
            'branches'           => $this->branches,
            'selectedBranch'     => $this->selectedBranch,
            'selectedTenantId'   => $this->selectedTenantId,
            'selectedBranchData' => $this->selectedBranchData,
            'isLoyaltyEnabled'   => $this->isLoyaltyEnabled,
            'pointsEarned'       => $this->pointsEarned,
            'pointsUsed'         => $this->pointsUsed,
            'redeemedCount'      => $this->redeemedCount,
            'showRedeemModal'    => $this->showRedeemModal,
            'redeemStep'         => $this->redeemStep,
            'redeemReward'       => $this->redeemReward,
            'redeemBranchId'     => $this->redeemBranchKey,   // still named $redeemBranchId in view
            'lastRedeemedReward' => $this->lastRedeemedReward,
        ]);
    }
};
?>

<div>
    <main class="relative min-h-screen bg-[#f5f0e8] dark:bg-[#0a1f1a] font-sans text-[#1a3a3a] dark:text-[#f0ebe0] transition-colors duration-300">
        <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">

            @if(session()->has('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                     class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 rounded-2xl flex items-center gap-3">
                    <i class="fa fa-check-circle"></i>
                    <span class="font-bold text-sm">{{ session('success') }}</span>
                </div>
            @endif
            @if(session()->has('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="mb-4 p-3 bg-red-500/10 border border-red-500/30 text-red-700 dark:text-red-400 rounded-2xl flex items-center gap-3">
                    <i class="fa fa-exclamation-circle"></i>
                    <span class="font-bold text-sm">{{ session('error') }}</span>
                </div>
            @endif

            @if(empty($branches))
                <div class="bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/30 dark:border-[#d4a574]/30 rounded-2xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fa fa-exclamation-triangle text-[#c8965f] dark:text-[#d4a574] mt-0.5"></i>
                        <div>
                            <p class="text-sm font-bold text-[#c8965f] dark:text-[#d4a574]">No Branches Available</p>
                            <p class="text-xs text-[#c8965f]/70 dark:text-[#d4a574]/70 mt-0.5">Check back later.</p>
                        </div>
                    </div>
                </div>
            @else

                @php
                    $loyaltyBranches = collect($branches)->where('is_loyalty_enabled', true);
                @endphp

                @if($loyaltyBranches->count() > 1)
                    <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide mb-4">
                        @foreach($loyaltyBranches as $branch)
                            <button wire:click="selectBranch('{{ $branch['key'] }}')"
                                    wire:key="branch-tab-{{ $branch['key'] }}"
                                    class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap
                                        {{ $selectedBranch == $branch['key']
                                            ? 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a]'
                                            : 'bg-[#faf6ef] dark:bg-[#0f2922] border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 text-[#1a3a3a]/70 dark:text-[#f0ebe0]/70 hover:border-[#c8965f]/40' }}">
                                <i class="fa fa-store text-[10px] mr-1"></i>
                                {{ $branch['name'] }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @php
                    $affordable = collect($availableRewards)->where('can_afford', true);
                    $unaffordable = collect($availableRewards)->where('can_afford', false);

                    if ($unaffordable->count() > 0) {
                        $nextReward = $unaffordable->sortBy('points_required')->first();
                    } elseif ($affordable->count() > 0) {
                        $nextReward = $affordable->sortByDesc('points_required')->first();
                    } else {
                        $nextReward = null;
                    }

                    $target = $nextReward ? max($nextReward['points_required'], 1) : 100;
                    $current = min($customerPoints, $target);
                    $progress = $target > 0 ? min(100, ($current / $target) * 100) : 0;

                    $radius = 82;
                    $circumference = 2 * M_PI * $radius;
                    $offset = $circumference - ($progress / 100) * $circumference;

                    $shopName = $selectedBranchData['name'] ?? ($loyaltyBranches->first()['name'] ?? 'Shop');
                @endphp

                @if($isLoyaltyEnabled)
                    <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-6 sm:p-8 mb-4">

                        <div class="flex items-center justify-between gap-3 mb-6 pb-6 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-[#1a3a3a] dark:bg-[#c8965f] flex items-center justify-center flex-shrink-0">
                                    <i class="fa fa-store text-[#f5f0e8] dark:text-[#0a1f1a] text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-black text-[#1a3a3a] dark:text-[#f0ebe0] truncate">{{ $shopName }}</p>
                                    <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider font-bold">Loyalty Program</p>
                                </div>
                            </div>
                            @if($nextReward)
                                <div class="text-right flex-shrink-0">
                                    <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider font-bold">Next Reward</p>
                                    <p class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate max-w-[120px]">{{ $nextReward['name'] }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col items-center">
                            <div class="relative" style="width: 200px; height: 200px;">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 200 200">
                                    <circle cx="100" cy="100" r="{{ $radius }}"
                                            fill="none" stroke="currentColor" stroke-width="14"
                                            class="text-[#1a3a3a]/10 dark:text-[#c8965f]/15" />
                                    <circle cx="100" cy="100" r="{{ $radius }}"
                                            fill="none" stroke="url(#rewardGradient)" stroke-width="14"
                                            stroke-linecap="round"
                                            stroke-dasharray="{{ $circumference }}"
                                            stroke-dashoffset="{{ $offset }}"
                                            style="transition: stroke-dashoffset 0.8s ease-out;" />
                                    <defs>
                                        <linearGradient id="rewardGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#c8965f" />
                                            <stop offset="100%" stop-color="#d4a574" />
                                        </linearGradient>
                                    </defs>
                                </svg>

                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 flex items-center justify-center mb-2">
                                        <i class="fa fa-gift text-xl text-[#c8965f] dark:text-[#d4a574]"></i>
                                    </div>
                                    <p class="text-3xl font-black text-[#1a3a3a] dark:text-[#f0ebe0] leading-none">
                                        {{ number_format($customerPoints) }}<span class="text-lg font-bold text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40">/{{ number_format($target) }}</span>
                                    </p>
                                    <p class="text-[10px] font-black text-[#c8965f] dark:text-[#d4a574] uppercase tracking-[0.2em] mt-1">Points</p>
                                </div>
                            </div>

                            <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-6 text-center max-w-xs">
                                @if($nextReward && !$nextReward['can_afford'])
                                    Earn <strong class="text-[#c8965f] dark:text-[#d4a574]">{{ number_format($nextReward['points_required'] - $customerPoints) }}</strong> more points to unlock
                                    <strong class="text-[#1a3a3a] dark:text-[#f0ebe0]">{{ $nextReward['name'] }}</strong>
                                @elseif($nextReward && $nextReward['can_afford'])
                                    You can now redeem <strong class="text-[#1a3a3a] dark:text-[#f0ebe0]">{{ $nextReward['name'] }}</strong>!
                                @else
                                    Order any item to start earning points
                                @endif
                            </p>

                            <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 border border-[#1a3a3a]/10 dark:border-[#c8965f]/20">
                                <span class="text-[10px] font-bold text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 uppercase tracking-wider">Balance:</span>
                                <span class="text-sm font-black text-[#1a3a3a] dark:text-[#f0ebe0]">{{ number_format($customerPoints) }} pts</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mt-6">
                            <a href="{{ route('customer.shopmenu', ['tenant' => $selectedTenantId, 'branch' => $selectedBranchData['branch_id'] ?? 0]) }}"
                               class="flex items-center justify-center gap-2 py-3.5 rounded-2xl bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] font-black text-sm uppercase tracking-wider hover:opacity-90 transition">
                                <i class="fa fa-plus-circle"></i> Earn
                            </a>
                            <button type="button"
                                    onclick="document.getElementById('rewards-grid').scrollIntoView({behavior:'smooth', block:'start'});"
                                    class="flex items-center justify-center gap-2 py-3.5 rounded-2xl bg-[#c8965f] dark:bg-[#d4a574] text-[#f5f0e8] dark:text-[#0a1f1a] font-black text-sm uppercase tracking-wider hover:opacity-90 transition">
                                <i class="fa fa-gift"></i> Redeem
                            </button>
                        </div>

                        <div class="grid grid-cols-3 gap-3 mt-6 pt-6 border-t border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider">Earned</p>
                                <p class="text-base font-black text-[#1a3a3a] dark:text-[#f0ebe0]">{{ number_format($pointsEarned) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider">Used</p>
                                <p class="text-base font-black text-[#1a3a3a] dark:text-[#f0ebe0]">{{ number_format($pointsUsed) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[10px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 uppercase tracking-wider">Rewards</p>
                                <p class="text-base font-black text-[#1a3a3a] dark:text-[#f0ebe0]">{{ $redeemedCount }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/30 dark:border-[#d4a574]/30 rounded-2xl p-4">
                        <div class="flex items-start gap-3">
                            <i class="fa fa-exclamation-triangle text-[#c8965f] dark:text-[#d4a574] mt-0.5"></i>
                            <div>
                                <p class="text-sm font-bold text-[#c8965f] dark:text-[#d4a574]">Loyalty Program Disabled</p>
                                <p class="text-xs text-[#c8965f]/70 dark:text-[#d4a574]/70 mt-0.5">This shop has disabled its rewards program.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 p-4 sm:p-5 mb-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center">
                            <i class="fa fa-info-circle text-[#c8965f] dark:text-[#d4a574] text-xs"></i>
                        </div>
                        <h3 class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] text-sm">How to earn points</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach([
                            ['icon' => 'fa-shopping-bag', 'title' => 'Order', 'desc' => 'Earn 1 pt per ₱1 spent'],
                            ['icon' => 'fa-star', 'title' => 'Review', 'desc' => 'Bonus points for feedback'],
                            ['icon' => 'fa-gift', 'title' => 'Redeem', 'desc' => 'Trade points for rewards'],
                        ] as $step)
                            <div class="flex items-center gap-3 p-3 bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 rounded-xl">
                                <div class="w-8 h-8 rounded-lg bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fa {{ $step['icon'] }} text-[#c8965f] dark:text-[#d4a574] text-xs"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">{{ $step['title'] }}</p>
                                    <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50">{{ $step['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="rewards-grid" class="scroll-mt-8 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-black text-[#1a3a3a] dark:text-[#f0ebe0] flex items-center gap-2 text-base">
                            <i class="fa fa-gift text-[#c8965f] dark:text-[#d4a574]"></i>
                            Available Rewards
                            <span class="text-xs font-normal text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50">({{ count($availableRewards) }})</span>
                        </h2>

                        @if(count($availableRewards) > 1)
                            <div class="relative">
                                <select wire:model.live="rewardSort"
                                        class="appearance-none bg-[#faf6ef] dark:bg-[#0f2922] border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 rounded-lg pl-3 pr-7 py-1.5 text-[11px] font-bold text-[#1a3a3a] dark:text-[#f0ebe0] focus:border-[#c8965f] outline-none cursor-pointer">
                                    <option value="cheapest">Cheapest</option>
                                    <option value="expensive">Highest cost</option>
                                </select>
                                <i class="fa fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 text-[9px] pointer-events-none"></i>
                            </div>
                        @endif
                    </div>

                    @if(count($availableRewards) === 0)
                        <div class="text-center py-12 bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border-2 border-dashed border-[#1a3a3a]/15 dark:border-[#c8965f]/20">
                            <div class="w-14 h-14 mx-auto rounded-2xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center mb-3">
                                <i class="fa fa-gift text-2xl text-[#c8965f] dark:text-[#d4a574]"></i>
                            </div>
                            <p class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0] mb-1">No rewards available</p>
                            <p class="text-xs text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50">Check back later.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                            @foreach($availableRewards as $reward)
                                @php
                                    $canRedeem = $reward['can_afford'] && $isLoyaltyEnabled && $selectedBranchData && $selectedBranchData['is_loyalty_enabled'];
                                    $progress = min(100, ($customerPoints / max($reward['points_required'], 1)) * 100);
                                @endphp

                                <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 overflow-hidden flex flex-col {{ $canRedeem ? 'hover:border-[#c8965f]/40' : 'opacity-90' }} transition-all">

                                    <div class="relative aspect-square bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 flex items-center justify-center overflow-hidden">
                                        @if($reward['image'])
                                            <img src="{{ asset('storage/' . $reward['image']) }}"
                                                 class="w-full h-full object-cover"
                                                 alt="{{ $reward['name'] }}"
                                                 loading="lazy">
                                        @else
                                            <i class="fa fa-gift text-4xl text-[#c8965f]/40 dark:text-[#d4a574]/40"></i>
                                        @endif

                                        @if($canRedeem)
                                            <span class="absolute top-2 right-2 bg-emerald-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                                                Ready
                                            </span>
                                        @endif
                                    </div>

                                    <div class="p-3 flex flex-col flex-1">
                                        <h4 class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] text-xs sm:text-sm truncate" title="{{ $reward['name'] }}">
                                            {{ $reward['name'] }}
                                        </h4>

                                        @if($reward['description'])
                                            <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 mt-0.5 line-clamp-2">{{ $reward['description'] }}</p>
                                        @endif

                                        <div class="mt-auto pt-3">
                                            <div class="flex items-center justify-between text-[10px] mb-1">
                                                <span class="font-bold text-[#c8965f] dark:text-[#d4a574]">{{ number_format($reward['points_required']) }} pts</span>
                                                <span class="text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40">{{ $reward['stock'] }} left</span>
                                            </div>

                                            @if(!$canRedeem && !$reward['can_afford'])
                                                <div class="w-full bg-[#1a3a3a]/10 dark:bg-[#c8965f]/15 rounded-full h-1 mb-2 overflow-hidden">
                                                    <div class="h-full bg-[#c8965f] dark:bg-[#d4a574] transition-all" style="width: {{ $progress }}%"></div>
                                                </div>
                                                <p class="text-[9px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 mb-2">
                                                    Need <strong class="text-[#c8965f] dark:text-[#d4a574]">{{ number_format($reward['points_required'] - $customerPoints) }}</strong> more pts
                                                </p>
                                            @endif

                                            <button wire:click="startRedeem({{ $reward['id'] }})"
                                                    @disabled(!$canRedeem)
                                                    class="w-full py-2 text-[10px] font-bold uppercase tracking-wider rounded-xl transition
                                                        {{ $canRedeem
                                                            ? 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] hover:opacity-90'
                                                            : 'bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 cursor-not-allowed' }}">
                                                @if($canRedeem)
                                                    <i class="fa fa-check-circle mr-1"></i> Redeem
                                                @elseif(!$isLoyaltyEnabled || !$selectedBranchData || !$selectedBranchData['is_loyalty_enabled'])
                                                    <i class="fa fa-ban mr-1"></i> Unavailable
                                                @else
                                                    <i class="fa fa-lock mr-1"></i> Locked
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($redeemedCount > 0)
                    <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 overflow-hidden">
                        <div class="p-4 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10 flex items-center justify-between">
                            <h3 class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0] flex items-center gap-2 text-sm">
                                <i class="fa fa-history text-[#c8965f] dark:text-[#d4a574]"></i>
                                Redemption History
                            </h3>
                            <span class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50">{{ $redeemedCount }} total</span>
                        </div>

                        <div class="divide-y divide-[#1a3a3a]/5 dark:divide-[#c8965f]/10">
                            @foreach($redeemedRewards as $redemption)
                                <div class="p-4 flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center flex-shrink-0">
                                        <i class="fa fa-gift text-[#c8965f] dark:text-[#d4a574] text-sm"></i>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                            <p class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate">{{ $redemption['reward_name'] }}</p>
                                            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full {{ $redemption['status_color'] }}">
                                                {{ $redemption['status_label'] }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50">
                                            {{ $redemption['branch_name'] }} · {{ $redemption['redeemed_at'] }}
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-black text-[#c8965f] dark:text-[#d4a574]">-{{ number_format($redemption['points_used']) }}</p>
                                        <p class="text-[9px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40">pts</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </main>

    @if($showRedeemModal && $redeemReward)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 animate-fadeIn">
            <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl w-full max-w-md overflow-hidden shadow-2xl border border-[#c8965f]/20 dark:border-[#d4a574]/20">

                <div class="px-6 pt-5 pb-4 border-b border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                    <div class="flex items-center justify-between gap-2">
                        @foreach([1 => 'Confirm', 2 => 'Branch', 3 => 'Done'] as $num => $label)
                            <div class="flex items-center gap-2 flex-1">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-black transition
                                    {{ $redeemStep >= $num
                                        ? 'bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a]'
                                        : 'bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40' }}">
                                    @if($redeemStep > $num)
                                        <i class="fa fa-check text-[9px]"></i>
                                    @else
                                        {{ $num }}
                                    @endif
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider
                                    {{ $redeemStep >= $num ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40' }}">
                                    {{ $label }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($redeemStep === 1)
                    <div class="p-6">
                        <div class="text-center mb-5">
                            <div class="w-16 h-16 mx-auto bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 rounded-2xl flex items-center justify-center mb-3 overflow-hidden">
                                @if($redeemReward['image'])
                                    <img src="{{ asset('storage/' . $redeemReward['image']) }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <i class="fa fa-gift text-2xl text-[#c8965f] dark:text-[#d4a574]"></i>
                                @endif
                            </div>
                            <h3 class="text-lg font-black text-[#1a3a3a] dark:text-[#f0ebe0]">{{ $redeemReward['name'] }}</h3>
                            @if($redeemReward['description'])
                                <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-1 line-clamp-2">{{ $redeemReward['description'] }}</p>
                            @endif
                        </div>

                        <div class="bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 rounded-2xl p-4 space-y-2 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                            <div class="flex justify-between text-sm">
                                <span class="text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">Current points</span>
                                <span class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">{{ number_format($customerPoints) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">Cost</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400">-{{ number_format($redeemReward['points_required']) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                                <span class="font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">New balance</span>
                                <span class="font-black text-[#c8965f] dark:text-[#d4a574]">{{ number_format($customerPoints - $redeemReward['points_required']) }} pts</span>
                            </div>
                        </div>

                        <div class="flex gap-2 mt-5">
                            <button wire:click="closeRedeemModal"
                                    class="flex-1 py-3 bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a] dark:text-[#f0ebe0] rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-[#1a3a3a]/10 dark:hover:bg-[#c8965f]/20 transition">
                                Cancel
                            </button>
                            <button wire:click="goToStep2"
                                    class="flex-1 py-3 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-xl font-bold text-xs uppercase tracking-wider hover:opacity-90 transition flex items-center justify-center gap-2">
                                Continue <i class="fa fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if($redeemStep === 2)
                    <div class="p-6">
                        <div class="text-center mb-5">
                            <div class="w-14 h-14 mx-auto bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 rounded-2xl flex items-center justify-center mb-3">
                                <i class="fa fa-store text-xl text-[#c8965f] dark:text-[#d4a574]"></i>
                            </div>
                            <h3 class="text-base font-black text-[#1a3a3a] dark:text-[#f0ebe0]">Where will you pick up?</h3>
                        </div>

                        <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                            @foreach($branches as $branch)
                                @if($branch['is_loyalty_enabled'])
                                    <button wire:click="selectBranchForRedeem('{{ $branch['key'] }}')"
                                            wire:key="redeem-branch-{{ $branch['key'] }}"
                                            class="w-full flex items-center gap-3 p-3 rounded-xl border-2 transition text-left
                                                {{ $redeemBranchId == $branch['key']
                                                    ? 'border-[#c8965f] dark:border-[#d4a574] bg-[#c8965f]/10 dark:bg-[#d4a574]/10'
                                                    : 'border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/40' }}">
                                        <div class="w-9 h-9 rounded-lg bg-[#c8965f]/10 dark:bg-[#d4a574]/10 flex items-center justify-center flex-shrink-0">
                                            <i class="fa fa-store text-[#c8965f] dark:text-[#d4a574] text-sm"></i>
                                        </div>
                                        <span class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0] truncate flex-1">{{ $branch['name'] }}</span>
                                        @if($redeemBranchId == $branch['key'])
                                            <i class="fa fa-check-circle text-[#c8965f] dark:text-[#d4a574]"></i>
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        <div class="flex gap-2">
                            <button wire:click="backToStep1"
                                    class="flex-1 py-3 bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 text-[#1a3a3a] dark:text-[#f0ebe0] rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-[#1a3a3a]/10 dark:hover:bg-[#c8965f]/20 transition">
                                Back
                            </button>
                            <button wire:click="confirmRedeem"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmRedeem"
                                    @disabled(!$redeemBranchId)
                                    class="flex-1 py-3 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-xl font-bold text-xs uppercase tracking-wider hover:opacity-90 transition disabled:opacity-50 flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="confirmRedeem">
                                    <i class="fa fa-check text-[10px]"></i> Redeem Now
                                </span>
                                <span wire:loading wire:target="confirmRedeem">
                                    <i class="fa fa-spinner fa-spin text-[10px]"></i> Processing...
                                </span>
                            </button>
                        </div>
                    </div>
                @endif

                @if($redeemStep === 3 && $lastRedeemedReward)
                    <div class="p-6 text-center">
                        <div class="w-20 h-20 mx-auto bg-emerald-600 rounded-full flex items-center justify-center mb-4">
                            <i class="fa fa-check text-white text-3xl"></i>
                        </div>

                        <h3 class="text-xl font-black text-[#1a3a3a] dark:text-[#f0ebe0] mb-1">Redeemed!</h3>
                        <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mb-5">Your reward is ready for pickup</p>

                        <div class="bg-[#c8965f] dark:bg-[#d4a574] rounded-2xl p-4 text-left">
                            <p class="text-[#f5f0e8]/70 dark:text-[#0a1f1a]/70 text-[10px] uppercase tracking-widest font-bold">Order number</p>
                            <p class="text-[#f5f0e8] dark:text-[#0a1f1a] font-black text-lg tracking-wide">#{{ $lastRedeemedReward['order_number'] }}</p>
                            <p class="text-[#f5f0e8]/70 dark:text-[#0a1f1a]/70 text-[10px] uppercase tracking-widest font-bold mt-3">Pick up at</p>
                            <p class="text-[#f5f0e8] dark:text-[#0a1f1a] font-bold text-sm">{{ $lastRedeemedReward['branch'] }}</p>
                        </div>

                        <div class="bg-[#f5f0e8] dark:bg-[#0a1f1a]/60 rounded-xl p-3 mt-3 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 flex items-center justify-between">
                            <span class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60">Points used</span>
                            <span class="text-sm font-bold text-rose-600 dark:text-rose-400">-{{ number_format($lastRedeemedReward['points']) }}</span>
                        </div>

                        <button wire:click="closeRedeemModal"
                                class="w-full mt-5 py-3 bg-[#1a3a3a] dark:bg-[#c8965f] text-[#f5f0e8] dark:text-[#0a1f1a] rounded-xl font-bold text-sm hover:opacity-90 transition">
                            <i class="fa fa-check mr-1"></i> Got it
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-fadeIn { animation: fadeIn 0.2s ease-out; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</div>