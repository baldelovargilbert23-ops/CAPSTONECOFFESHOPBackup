<?php

use App\Models\LoyaltySetting;
use App\Models\RewardItem;
use App\Models\CustomerPoint;
use App\Models\Branch;
use App\Models\BranchLoyaltySetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.shop_owner')] class extends Component
{
    use WithFileUploads;

    // Tenant / Global Settings
    public bool $is_enabled = true;
    public int $points_per_peso = 1;
    public int $points_per_order = 10;
    public int $points_expiry_days = 365;
    public int $min_points_to_redeem = 100;
    public int $points_value_in_peso = 1;

    public string $activeTab = 'settings';
    public string $search = '';

    // Branch Settings Array
    public array $branchLoyaltySettings = [];

    // Reward Modal Properties
    public bool $showRewardModal = false;
    public ?int $reward_id = null;
    public string $reward_name = '';
    public string $reward_description = '';
    public int $reward_points = 100;
    public int $reward_stock = 0;
    public $reward_image = null;
    public bool $reward_is_active = true;

    public function mount(): void
    {
        $this->loadSettings();
        $this->loadBranchSettings();
    }

    protected function tenantId(): ?int
    {
        return Auth::user()->tenant_id ?? session('tenant_id');
    }

    /* ---------------- Tabs ---------------- */

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['settings', 'branches', 'rewards', 'customers', 'stats'], true)) {
            $this->activeTab = $tab;
        }
    }

    /* ---------------- Settings ---------------- */

    public function loadSettings(): void
    {
        $settings = LoyaltySetting::where('tenant_id', $this->tenantId())->first();

        if ($settings) {
            $this->is_enabled           = (bool) $settings->is_enabled;
            $this->points_per_peso      = (int) $settings->points_per_peso;
            $this->points_per_order     = (int) $settings->points_per_order;
            $this->points_expiry_days   = (int) $settings->points_expiry_days;
            $this->min_points_to_redeem = (int) $settings->min_points_to_redeem;
            $this->points_value_in_peso = (int) $settings->points_value_in_peso;
        }
    }

    public function loadBranchSettings(): void
    {
        $this->branchLoyaltySettings = [];

        $branches = Branch::where('tenant_id', $this->tenantId())->get();

        $settings = BranchLoyaltySetting::where('tenant_id', $this->tenantId())
            ->get()
            ->keyBy('branch_id');

        foreach ($branches as $branch) {
            $s = $settings->get($branch->id);
            $this->branchLoyaltySettings[$branch->id] = [
                'id'         => $branch->id,
                'name'       => $branch->name,
                'code'       => $branch->code ?? 'N/A',
                'is_enabled' => $s ? (bool) $s->is_enabled : true,
            ];
        }
    }

    public function saveSettings(): void
    {
        $this->validate([
            'points_per_peso'      => 'required|integer|min:1|max:100',
            'points_per_order'     => 'required|integer|min:0|max:1000',
            'points_expiry_days'   => 'required|integer|min:30|max:730',
            'min_points_to_redeem' => 'required|integer|min:10|max:10000',
            'points_value_in_peso' => 'required|integer|min:1|max:100',
        ]);

        LoyaltySetting::updateOrCreate(
            ['tenant_id' => $this->tenantId()],
            [
                'is_enabled'           => $this->is_enabled,
                'points_per_peso'      => $this->points_per_peso,
                'points_per_order'     => $this->points_per_order,
                'points_expiry_days'   => $this->points_expiry_days,
                'min_points_to_redeem' => $this->min_points_to_redeem,
                'points_value_in_peso' => $this->points_value_in_peso,
            ]
        );

        session()->flash('success', 'Tenant Loyalty Settings updated successfully!');
    }

    /**
     * Master switch — saves immediately, no separate Save press needed.
     */
    public function toggleMasterLoyalty(): void
    {
        $this->is_enabled = ! $this->is_enabled;

        LoyaltySetting::updateOrCreate(
            ['tenant_id' => $this->tenantId()],
            [
                'is_enabled'           => $this->is_enabled,
                'points_per_peso'      => $this->points_per_peso,
                'points_per_order'     => $this->points_per_order,
                'points_expiry_days'   => $this->points_expiry_days,
                'min_points_to_redeem' => $this->min_points_to_redeem,
                'points_value_in_peso' => $this->points_value_in_peso,
            ]
        );

        session()->flash(
            'success',
            'Tenant Loyalty Program ' . ($this->is_enabled ? 'enabled' : 'disabled') . '.'
        );
    }

    /* ---------------- Branch toggles ---------------- */

    public function toggleBranchLoyalty(int $branchId): void
    {
        if (! $this->is_enabled) {
            session()->flash('error', 'Enable the tenant master loyalty program before configuring branches.');
            return;
        }

        $branch = Branch::where('tenant_id', $this->tenantId())->find($branchId);

        if (! $branch) {
            session()->flash('error', 'Branch not found.');
            return;
        }

        $current   = $this->branchLoyaltySettings[$branchId]['is_enabled'] ?? true;
        $newStatus = ! $current;

        BranchLoyaltySetting::updateOrCreate(
            [
                'branch_id' => $branch->id,
                'tenant_id' => $this->tenantId(),
            ],
            ['is_enabled' => $newStatus]
        );

        $this->branchLoyaltySettings[$branchId]['is_enabled'] = $newStatus;

        session()->flash(
            'success',
            'Branch "' . $branch->name . '" loyalty points ' . ($newStatus ? 'activated' : 'deactivated') . '!'
        );
    }

    /* ---------------- Rewards CRUD ---------------- */

    public function openRewardModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset([
            'reward_name', 'reward_description', 'reward_points',
            'reward_stock', 'reward_image', 'reward_id',
        ]);
        $this->reward_is_active = true;

        if ($id) {
            $reward = RewardItem::where('tenant_id', $this->tenantId())->find($id);

            if ($reward) {
                $this->reward_id          = $reward->id;
                $this->reward_name        = $reward->name;
                $this->reward_description = $reward->description ?? '';
                $this->reward_points      = (int) $reward->points_required;
                $this->reward_stock       = (int) $reward->stock;
                $this->reward_is_active   = (bool) $reward->is_active;
            }
        }

        $this->showRewardModal = true;
    }

    public function closeRewardModal(): void
    {
        $this->showRewardModal = false;
        $this->reset(['reward_image']);
    }

    public function saveReward(): void
    {
        $this->validate([
            'reward_name'        => 'required|string|max:255|min:2',
            'reward_description' => 'nullable|string|max:500',
            'reward_points'      => 'required|integer|min:10|max:100000',
            'reward_stock'       => 'required|integer|min:0|max:999999',
            'reward_image'       => 'nullable|image|max:2048',
        ]);

        $tenantId = $this->tenantId();

        $existing = $this->reward_id
            ? RewardItem::where('tenant_id', $tenantId)->find($this->reward_id)
            : null;

        $imagePath = $existing?->image;

        if ($this->reward_image) {
            if ($existing?->image && Storage::disk('public')->exists($existing->image)) {
                Storage::disk('public')->delete($existing->image);
            }
            $imagePath = $this->reward_image->store('rewards/' . $tenantId, 'public');
        }

        RewardItem::updateOrCreate(
            [
                'id'        => $this->reward_id,
                'tenant_id' => $tenantId,
            ],
            [
                'name'            => $this->reward_name,
                'description'     => $this->reward_description,
                'points_required' => $this->reward_points,
                'stock'           => $this->reward_stock,
                'image'           => $imagePath,
                'is_active'       => $this->reward_is_active,
            ]
        );

        $this->closeRewardModal();
        unset($this->rewards);
        session()->flash('success', 'Reward item saved successfully!');
    }

    public function toggleRewardStatus(int $id): void
    {
        $reward = RewardItem::where('tenant_id', $this->tenantId())->find($id);

        if (! $reward) {
            session()->flash('error', 'Reward not found.');
            return;
        }

        $reward->update(['is_active' => ! $reward->is_active]);

        unset($this->rewards);
        session()->flash('success', 'Reward status updated!');
    }

    public function deleteReward(int $id): void
    {
        $reward = RewardItem::where('tenant_id', $this->tenantId())->find($id);

        if (! $reward) {
            session()->flash('error', 'Reward not found.');
            return;
        }

        if ($reward->image && Storage::disk('public')->exists($reward->image)) {
            Storage::disk('public')->delete($reward->image);
        }

        $reward->delete();
        unset($this->rewards);
        session()->flash('success', 'Reward deleted successfully!');
    }

    /* ---------------- Computed ---------------- */

    #[Computed]
    public function rewards()
    {
        return RewardItem::where('tenant_id', $this->tenantId())
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->latest()
            ->get();
    }

    #[Computed]
    public function topCustomers()
    {
        return CustomerPoint::where('tenant_id', $this->tenantId())
            ->with('user')
            ->orderByDesc('points')
            ->take(10)
            ->get();
    }

    #[Computed]
    public function redemptionStats()
    {
        return Branch::where('tenant_id', $this->tenantId())
            ->withCount(['rewardRedemptions' => fn ($q) => $q->where('status', 'fulfilled')])
            ->withSum('rewardRedemptions', 'points_used')
            ->get()
            ->map(fn ($branch) => [
                'name'        => $branch->name,
                'redemptions' => $branch->reward_redemptions_count,
                'points_used' => $branch->reward_redemptions_sum_points_used ?? 0,
            ]);
    }

    #[Computed]
    public function branches()
    {
        return Branch::where('tenant_id', $this->tenantId())->get();
    }
};
?>
<div class="loyalty-root">
    <style>
        .loyalty-root { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .loyalty-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .loyalty-root input,
        .loyalty-root textarea {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .loyalty-root input::placeholder,
        .loyalty-root textarea::placeholder { color: var(--text-muted); }
        .loyalty-root input:focus,
        .loyalty-root textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
        .loyalty-root .toggle-track {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
        }
        .loyalty-root input.peer:checked ~ .toggle-track {
            background-color: var(--accent) !important;
        }
    </style>

    <main class="relative min-h-screen font-sans p-4 sm:p-6 space-y-6 transition-colors duration-300"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <div class="max-w-6xl mx-auto space-y-6">

            {{-- Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm"
                         style="background-color: var(--accent); color: var(--bg-primary);">
                        <i class="fa fa-coins text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black" style="color: var(--text-primary);">Loyalty &amp; Rewards</h1>
                        <p class="text-xs" style="color: var(--text-muted);">Manage customer point earning and redemption for Tenant &amp; Branches</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold"
                          style="background-color: color-mix(in srgb, {{ $is_enabled ? 'var(--ok)' : 'var(--text-muted)' }} 12%, transparent);
                                 color: {{ $is_enabled ? 'var(--ok)' : 'var(--text-muted)' }};
                                 border: 1px solid color-mix(in srgb, {{ $is_enabled ? 'var(--ok)' : 'var(--text-muted)' }} 25%, transparent);">
                        <i class="fa {{ $is_enabled ? 'fa-check-circle' : 'fa-power-off' }}"></i>
                        {{ $is_enabled ? 'Tenant Loyalty Active' : 'Tenant Loyalty Disabled' }}
                    </span>
                </div>
            </div>

            {{-- Info Banner --}}
            <div class="rounded-xl p-4"
                 style="background-color: color-mix(in srgb, var(--info) 10%, transparent); border: 1px solid color-mix(in srgb, var(--info) 20%, transparent);">
                <div class="flex items-start gap-3">
                    <i class="fa fa-info-circle text-lg mt-0.5" style="color: var(--info);"></i>
                    <div class="text-xs space-y-1" style="color: var(--info);">
                        <p class="font-bold text-sm">How Tenant &amp; Branch Loyalty Operates:</p>
                        <p>• <strong>Tenant Main Store / Online:</strong> If Tenant Loyalty is active, customers earn &amp; redeem points for all shop-wide orders.</p>
                        <p>• <strong>Branch Participation:</strong> Each branch can be enabled/disabled individually to support or restrict point collection &amp; redemptions.</p>
                        <p>• <strong>Unified Customer Balance:</strong> Points earned at the Tenant or at any participating branch accumulate in the customer's account.</p>
                    </div>
                </div>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="px-4 py-3 rounded-xl flex items-center gap-2"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok);">
                    <i class="fa fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="px-4 py-3 rounded-xl flex items-center gap-2"
                     style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); color: var(--bad);">
                    <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            {{-- TABS --}}
            <div class="card p-1 w-fit shadow-sm overflow-x-auto flex gap-1">
                @foreach(['settings' => '⚙️ Tenant Settings', 'branches' => '🏪 Branch Controls', 'rewards' => '🎁 Reward Items', 'customers' => '👥 Top Customers', 'stats' => '📊 Redemption Stats'] as $tab => $label)
                    <button type="button"
                            wire:click="setTab('{{ $tab }}')"
                            class="px-4 py-2 text-xs font-bold rounded-lg transition whitespace-nowrap"
                            style="{{ $activeTab === $tab
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'color: var(--text-muted);' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- TAB 1: TENANT SETTINGS --}}
            @if($activeTab === 'settings')
                <div class="card shadow-sm overflow-hidden space-y-6">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h2 class="font-black text-base" style="color: var(--text-primary);">Tenant Loyalty Configuration</h2>
                        <p class="text-xs mt-0.5" style="color: var(--text-muted);">Configure points calculation and shop-wide loyalty support</p>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Tenant Switch --}}
                        <div class="p-4 rounded-xl flex items-center justify-between"
                             style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider block" style="color: var(--text-muted);">Master Status</span>
                                <p class="font-bold text-sm mt-0.5" style="color: var(--text-primary);">Tenant Loyalty Program</p>
                                <p class="text-xs mt-0.5" style="color: var(--text-muted);">Enable loyalty points earning &amp; redemption across this tenant.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       wire:click="toggleMasterLoyalty"
                                       @checked($is_enabled)
                                       class="sr-only peer">
                                <div class="toggle-track w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                            </label>
                        </div>

                        {{-- Calculation Rules --}}
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider mb-3" style="color: var(--text-muted);">Points &amp; Valuation Rules</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Points per ₱1 Spent</label>
                                    <input type="number" wire:model="points_per_peso" min="1" max="100" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                                    @error('points_per_peso') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Bonus Points per Order</label>
                                    <input type="number" wire:model="points_per_order" min="0" max="1000" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                                    @error('points_per_order') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Points Expiry (Days)</label>
                                    <input type="number" wire:model="points_expiry_days" min="30" max="730" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                                    @error('points_expiry_days') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Minimum Points to Redeem</label>
                                    <input type="number" wire:model="min_points_to_redeem" min="10" max="10000" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                                    @error('min_points_to_redeem') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Point Value (₱ per Point)</label>
                                    <input type="number" wire:model="points_value_in_peso" min="1" max="100" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                                    @error('points_value_in_peso') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4" style="border-top: 1px solid var(--border-soft);">
                            <button type="button"
                                    wire:click="saveSettings"
                                    wire:loading.attr="disabled"
                                    class="px-6 py-2.5 text-sm font-bold rounded-xl transition disabled:opacity-50"
                                    style="background-color: var(--accent); color: var(--bg-primary);">
                                <i class="fa fa-spinner fa-spin mr-1" wire:loading wire:target="saveSettings"></i>
                                <i class="fa fa-save mr-1" wire:loading.remove wire:target="saveSettings"></i> Save Tenant Settings
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 2: BRANCH CONTROLS --}}
            @if($activeTab === 'branches')
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <h2 class="font-black" style="color: var(--text-primary);">🏪 Branch-Level Loyalty Management</h2>
                                <p class="text-xs mt-0.5" style="color: var(--text-muted);">Enable or disable loyalty point support per branch</p>
                            </div>
                            <span class="text-xs px-2.5 py-1 rounded-full font-bold" style="background-color: var(--bg-inner); color: var(--text-muted); border: 1px solid var(--border-soft);">
                                {{ count($branchLoyaltySettings) }} Branches
                            </span>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        @if(! $is_enabled)
                            <div class="p-3 rounded-xl text-xs font-bold"
                                 style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 20%, transparent);">
                                <i class="fa fa-exclamation-triangle mr-1"></i> Tenant master loyalty program is disabled. Activate it in "Tenant Settings" for branches to process points.
                            </div>
                        @endif

                        <div class="space-y-3">
                            @forelse($branchLoyaltySettings as $branchId => $branchData)
                                <div wire:key="branch-loyalty-item-{{ $branchId }}"
                                     class="flex items-center justify-between p-4 rounded-xl transition flex-wrap gap-3"
                                     style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold"
                                             style="background-color: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent);">
                                            <i class="fa fa-store"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm" style="color: var(--text-primary);">{{ $branchData['name'] }}</p>
                                            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                                <span class="text-xs" style="color: var(--text-muted);">Code: {{ $branchData['code'] }}</span>
                                                <span class="w-1 h-1 rounded-full" style="background-color: var(--text-muted);"></span>
                                                <span class="text-xs font-bold" style="color: {{ $branchData['is_enabled'] ? 'var(--ok)' : 'var(--bad)' }};">
                                                    <i class="fa {{ $branchData['is_enabled'] ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                                    {{ $branchData['is_enabled'] ? 'Active (Earn & Redeem)' : 'Disabled' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button type="button"
                                                wire:click="toggleBranchLoyalty({{ $branchId }})"
                                                @disabled(! $is_enabled)
                                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                                                style="{{ $branchData['is_enabled']
                                                    ? 'background-color: color-mix(in srgb, var(--ok) 15%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);'
                                                    : 'background-color: color-mix(in srgb, var(--bad) 15%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);' }}">
                                            <i class="fa {{ $branchData['is_enabled'] ? 'fa-check' : 'fa-ban' }}"></i>
                                            {{ $branchData['is_enabled'] ? 'Loyalty Enabled' : 'Loyalty Disabled' }}
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12" style="color: var(--text-muted);">
                                    <i class="fa fa-store text-3xl mb-2 block opacity-40"></i>
                                    <p class="font-bold">No branches found</p>
                                    <p class="text-xs mt-1">Add branches first to configure their loyalty settings.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 3: REWARD ITEMS --}}
            @if($activeTab === 'rewards')
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4 flex justify-between items-center flex-wrap gap-3"
                         style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <div>
                            <h2 class="font-black" style="color: var(--text-primary);">Reward Items</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Items customers can redeem with points</p>
                        </div>
                        <button type="button"
                                wire:click="openRewardModal"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition shadow-sm"
                                style="background-color: var(--accent); color: var(--bg-primary);">
                            <i class="fa fa-plus mr-1"></i> Add Reward
                        </button>
                    </div>

                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft);">
                        <div class="relative">
                            <i class="fa fa-search absolute left-4 top-1/2 -translate-y-1/2" style="color: var(--text-muted);"></i>
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search rewards..."
                                   class="w-full text-sm rounded-xl pl-10 pr-4 py-2 outline-none transition">
                        </div>
                    </div>

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($this->rewards as $reward)
                            <div wire:key="reward-card-{{ $reward->id }}"
                                 class="rounded-2xl p-4 transition flex flex-col justify-between"
                                 style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                <div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-14 h-14 rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0"
                                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                                            @if($reward->image)
                                                <img src="{{ Storage::url($reward->image) }}" class="w-full h-full object-cover" alt="{{ $reward->name }}">
                                            @else
                                                <i class="fa fa-gift text-2xl" style="color: var(--accent);"></i>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-sm truncate" style="color: var(--text-primary);">{{ $reward->name }}</h4>
                                            <p class="text-xs font-bold" style="color: var(--accent);">{{ number_format($reward->points_required) }} points</p>
                                        </div>
                                    </div>
                                    <p class="text-xs mb-3 line-clamp-2" style="color: var(--text-muted);">{{ $reward->description ?? 'No description provided.' }}</p>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-xs mb-3 pt-2" style="border-top: 1px solid var(--border-soft);">
                                        <span style="color: var(--text-muted);">Stock: <strong>{{ $reward->stock }}</strong></span>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold"
                                              style="background-color: color-mix(in srgb, {{ $reward->is_active ? 'var(--ok)' : 'var(--text-muted)' }} 12%, transparent);
                                                     color: {{ $reward->is_active ? 'var(--ok)' : 'var(--text-muted)' }};">
                                            {{ $reward->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 pt-2" style="border-top: 1px solid var(--border-soft);">
                                        <button type="button" wire:click="openRewardModal({{ $reward->id }})" class="text-xs font-bold hover:underline" style="color: var(--accent);">Edit</button>
                                        <span style="color: var(--border-soft);">|</span>
                                        <button type="button" wire:click="toggleRewardStatus({{ $reward->id }})"
                                                class="text-xs font-bold"
                                                style="color: {{ $reward->is_active ? 'var(--bad)' : 'var(--ok)' }};">
                                            {{ $reward->is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                        <span style="color: var(--border-soft);">|</span>
                                        <button type="button" wire:click="deleteReward({{ $reward->id }})" wire:confirm="Are you sure you want to delete this reward?"
                                                class="text-xs font-bold" style="color: var(--bad);">Delete</button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12" style="color: var(--text-muted);">
                                <i class="fa fa-gift text-3xl mb-2 block opacity-40"></i>
                                <p class="font-bold">No reward items added yet</p>
                                <p class="text-xs mt-1">Add items that customers can redeem with their accumulated points.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- TAB 4: TOP CUSTOMERS --}}
            @if($activeTab === 'customers')
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h2 class="font-black" style="color: var(--text-primary);">Top Customers by Points</h2>
                        <p class="text-xs mt-0.5" style="color: var(--text-muted);">Highest loyalty point holders across your entire shop</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[600px]">
                            <thead style="background-color: var(--bg-inner);">
                                <tr class="text-xs font-bold uppercase tracking-wider" style="color: var(--text-muted);">
                                    <th class="px-6 py-3 text-left">#</th>
                                    <th class="px-6 py-3 text-left">Customer</th>
                                    <th class="px-6 py-3 text-left">Current Balance</th>
                                    <th class="px-6 py-3 text-left">Lifetime Points</th>
                                    <th class="px-6 py-3 text-left">Points Redeemed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->topCustomers as $index => $customer)
                                    <tr wire:key="cust-table-{{ $customer->id }}" style="border-bottom: 1px solid var(--border-soft);">
                                        <td class="px-6 py-3 font-bold" style="color: var(--text-muted);">{{ $index + 1 }}</td>
                                        <td class="px-6 py-3 font-semibold" style="color: var(--text-primary);">{{ $customer->user->name ?? 'Guest/Unknown' }}</td>
                                        <td class="px-6 py-3 font-bold" style="color: var(--accent);">{{ number_format($customer->points) }} pts</td>
                                        <td class="px-6 py-3" style="color: var(--text-muted);">{{ number_format($customer->lifetime_points) }} pts</td>
                                        <td class="px-6 py-3" style="color: var(--text-muted);">{{ number_format($customer->points_used) }} pts</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center" style="color: var(--text-muted);">
                                            <i class="fa fa-users text-3xl mb-2 block opacity-40"></i>
                                            No customer points data recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- TAB 5: REDEMPTION STATS --}}
            @if($activeTab === 'stats')
                <div class="card shadow-sm overflow-hidden">
                    <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h2 class="font-black" style="color: var(--text-primary);">Redemptions by Branch</h2>
                        <p class="text-xs mt-0.5" style="color: var(--text-muted);">Breakdown of points fulfilled at each branch location</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @forelse($this->redemptionStats as $stat)
                                <div class="flex items-center justify-between p-4 rounded-xl flex-wrap gap-2"
                                     style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold" style="background-color: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent);">
                                            <i class="fa fa-store text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm" style="color: var(--text-primary);">{{ $stat['name'] }}</p>
                                            <p class="text-xs" style="color: var(--text-muted);">{{ $stat['redemptions'] }} rewards redeemed</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-sm" style="color: var(--accent);">{{ number_format($stat['points_used']) }} pts</p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10" style="color: var(--text-muted);">
                                    <i class="fa fa-chart-bar text-3xl mb-2 block opacity-40"></i>
                                    <p class="font-bold">No redemptions recorded yet</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </main>

    {{-- REWARD MODAL --}}
    @if($showRewardModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
             style="background-color: rgba(0,0,0,0.6);">
            <div class="card w-full max-w-md p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-black" style="color: var(--text-primary);">
                        {{ $reward_id ? 'Edit Reward' : 'Add New Reward' }}
                    </h3>
                    <button type="button" wire:click="closeRewardModal" class="transition" style="color: var(--text-muted);">
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                <form wire:submit.prevent="saveReward" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Reward Name *</label>
                        <input wire:model="reward_name" type="text" placeholder="e.g. Free Large Latte" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                        @error('reward_name') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Description</label>
                        <textarea wire:model="reward_description" rows="2" placeholder="Reward details..." class="w-full text-sm rounded-xl px-3 py-2 outline-none transition"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Points Required *</label>
                            <input wire:model="reward_points" type="number" placeholder="100" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                            @error('reward_points') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Stock *</label>
                            <input wire:model="reward_stock" type="number" placeholder="10" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                            @error('reward_stock') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Reward Image</label>
                        <input wire:model="reward_image" type="file" accept="image/*" class="w-full text-sm rounded-xl px-3 py-2 outline-none transition">
                        @error('reward_image') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input wire:model="reward_is_active" type="checkbox" id="reward_is_active" style="accent-color: var(--accent);">
                        <label for="reward_is_active" class="text-xs font-semibold" style="color: var(--text-primary);">Active (Visible to customers)</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4" style="border-top: 1px solid var(--border-soft);">
                        <button type="button" wire:click="closeRewardModal"
                                class="px-4 py-2 text-xs font-bold transition" style="color: var(--text-muted);">
                            Cancel
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="saveReward, reward_image"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition disabled:opacity-50"
                                style="background-color: var(--accent); color: var(--bg-primary);">
                            <i class="fa fa-spinner fa-spin mr-1" wire:loading wire:target="reward_image, saveReward"></i>
                            <i class="fa fa-save mr-1" wire:loading.remove wire:target="reward_image, saveReward"></i> Save Reward
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>