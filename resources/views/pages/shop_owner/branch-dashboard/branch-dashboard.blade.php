<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.shop_owner')] class extends Component
{
    public $branches = [];
    public $period = 'today';
    public $selectedBranchId = null;
    
    // Branch creation properties
    public $showCreateModal = false;
    public $branchName = '';
    public $branchAddress = '';
    public $branchPhone = '';
    public $branchEmail = '';
    public $branchDescription = '';
    
    // Branch manager assignment
    public $showManagerModal = false;
    public $selectedBranchForManager = null;
    public $selectedManagerId = null;
    public $availableManagers = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $tenantId = Auth::user()->tenant_id;
        
        $this->branches = Branch::where('tenant_id', $tenantId)
            ->with(['manager', 'approvedBy'])
            ->withCount(['users as staff_count' => function ($query) {
                $query->whereIn('role', ['branch_manager', 'cashier', 'barista']);
            }, 'users as active_manager_count' => function ($query) {
                $query->where('role', 'branch_manager')->where('is_active', true);
            }])
            ->get();

        foreach ($this->branches as $branch) {
            $branch->total_revenue = Order::where('tenant_id', $tenantId)
                ->where('branch_id', $branch->id)
                ->where('status', 'completed')
                ->when($this->period === 'today', function ($q) {
                    return $q->whereDate('created_at', today());
                })
                ->when($this->period === 'week', function ($q) {
                    return $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                })
                ->when($this->period === 'month', function ($q) {
                    return $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                })
                ->when($this->period === 'year', function ($q) {
                    return $q->whereYear('created_at', now()->year);
                })
                ->sum('total_amount');

            $branch->total_orders = Order::where('tenant_id', $tenantId)
                ->where('branch_id', $branch->id)
                ->where('status', 'completed')
                ->count();

            $branch->avg_rating = Review::where('tenant_id', $tenantId)
                ->where('branch_id', $branch->id)
                ->avg('rating') ?? 0;
        }

        if ($this->branches->count() > 0 && !$this->selectedBranchId) {
            $this->selectedBranchId = $this->branches->first()->id;
        }
    }

    public function selectBranch($branchId)
    {
        $this->selectedBranchId = $branchId;
    }

    public function setPeriod($period)
    {
        $this->period = $period;
        $this->loadData();
    }

    // ================================================================
    // BRANCH CREATION
    // ================================================================
    
    public function openCreateModal()
    {
        // Use the canonical branch wizard, which collects approval documents
        // and creates the branch manager account in the same transaction.
        return $this->redirectRoute('owner.branches');
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->reset(['branchName', 'branchAddress', 'branchPhone', 'branchEmail', 'branchDescription']);
    }

    public function createBranch()
    {
        // Keep this legacy Livewire action safe if an old page still calls it.
        return $this->redirectRoute('owner.branches');
    }

    // ================================================================
    // BRANCH MANAGER ASSIGNMENT
    // ================================================================
    
    public function openManagerModal($branchId)
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $this->selectedBranchForManager = Branch::where('tenant_id', $tenantId)->find($branchId);
        
        if (!$this->selectedBranchForManager || !$this->selectedBranchForManager->isApproved()) {
            session()->flash('error', 'Branch must be approved before assigning a manager.');
            return;
        }

        // Get available staff (users without branch assigned)
        $this->availableManagers = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->whereNull('branch_id')
            ->where('is_active', true)
            ->get();

        $this->selectedManagerId = null;
        $this->showManagerModal = true;
    }

    public function closeManagerModal()
    {
        $this->showManagerModal = false;
        $this->selectedBranchForManager = null;
        $this->selectedManagerId = null;
        $this->availableManagers = [];
    }

    public function assignManager()
    {
        $this->validate([
            'selectedManagerId' => 'required|integer',
        ]);

        try {
            DB::transaction(function () {
                $tenantId = (int) Auth::user()->tenant_id;
                $branchId = $this->selectedBranchForManager?->id;
                $branch = Branch::where('tenant_id', $tenantId)
                    ->where('approval_status', 'approved')
                    ->whereKey($branchId)
                    ->firstOrFail();
                $user = User::where('tenant_id', $tenantId)
                    ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                    ->whereNull('branch_id')
                    ->where('is_active', true)
                    ->findOrFail((int) $this->selectedManagerId);

                $managerAlreadyAssigned = User::where('tenant_id', $tenantId)
                    ->where('branch_id', $branch->id)
                    ->where('role', 'branch_manager')
                    ->where('is_active', true)
                    ->exists();

                if ($managerAlreadyAssigned) {
                    throw new \RuntimeException('This branch already has an active manager.');
                }

                // Update user's branch
                $user->update([
                    'branch_id' => $branch->id,
                    'role' => 'branch_manager',
                ]);

                // Update branch manager
                $branch->update([
                    'branch_manager_id' => $user->id,
                ]);
            });

            $this->closeManagerModal();
            $this->loadData();
            
            session()->flash('success', '✅ Branch manager assigned successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to assign manager: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $selectedBranch = null;
        if ($this->selectedBranchId) {
            $selectedBranch = $this->branches->firstWhere('id', $this->selectedBranchId);
        }

        return view('pages.shop_owner.⚡branch-dashboard.branch-dashboard', [
            'selectedBranch' => $selectedBranch,
        ]);
    }
}
?>
<div>
    <div class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index: 0;">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#d2964b] rounded-full blur-[120px] opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-[#f5a623] rounded-full blur-[100px] opacity-15 animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <main class="relative min-h-screen bg-gray-50 dark:bg-[#0A0C10]/90 backdrop-blur-sm font-sans p-6" style="z-index: 1;">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa fa-chart-pie text-[#d2964b]"></i>
                        Branch Dashboard
                    </h1>
                    <p class="text-sm text-gray-500">Overview of all branches performance</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button wire:click="setPeriod('today')" class="px-3 py-1.5 text-xs font-bold rounded-lg {{ $period === 'today' ? 'bg-[#d2964b] text-white shadow-md' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">Today</button>
                    <button wire:click="setPeriod('week')" class="px-3 py-1.5 text-xs font-bold rounded-lg {{ $period === 'week' ? 'bg-[#d2964b] text-white shadow-md' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">Week</button>
                    <button wire:click="setPeriod('month')" class="px-3 py-1.5 text-xs font-bold rounded-lg {{ $period === 'month' ? 'bg-[#d2964b] text-white shadow-md' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">Month</button>
                    <button wire:click="setPeriod('year')" class="px-3 py-1.5 text-xs font-bold rounded-lg {{ $period === 'year' ? 'bg-[#d2964b] text-white shadow-md' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">Year</button>
                    <a href="{{ route('owner.branches') }}"
                       class="px-3 py-1.5 text-xs font-bold rounded-lg bg-[#d2964b] text-white shadow-md hover:shadow-lg transition">
                        <i class="fa fa-plus mr-1"></i> New Branch
                    </a>
                </div>
            </div>

            <!-- Branch Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($branches as $branch)
                <div wire:click="selectBranch({{ $branch->id }})" 
                    class="bg-white dark:bg-[#11131A] rounded-2xl p-5 border-2 cursor-pointer transition hover:shadow-lg {{ $selectedBranchId == $branch->id ? 'border-[#d2964b] ring-2 ring-[#d2964b]/30' : 'border-gray-200 dark:border-gray-800 hover:border-[#d2964b]/50' }}">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-white">{{ $branch->name }}</h3>
                            <p class="text-xs text-gray-500">{{ $branch->code }}</p>
                        </div>
                        <div class="flex flex-col gap-1 items-end">
                            @if($branch->approval_status)
                                <span class="px-2 py-0.5 rounded-md text-[8px] font-bold 
                                    {{ $branch->approval_status === 'approved' ? 'bg-emerald-500/10 text-emerald-500' : 
                                       ($branch->approval_status === 'pending' ? 'bg-amber-500/10 text-amber-500 animate-pulse' : 
                                       'bg-red-500/10 text-red-500') }}">
                                    {{ ucfirst($branch->approval_status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <div class="text-center">
                            <p class="text-sm font-bold text-[#d2964b]">₱{{ number_format($branch->total_revenue ?? 0, 0) }}</p>
                            <p class="text-[8px] text-gray-400 uppercase">Revenue</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-blue-500">{{ $branch->total_orders ?? 0 }}</p>
                            <p class="text-[8px] text-gray-400 uppercase">Orders</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-amber-500">{{ number_format($branch->avg_rating ?? 0, 1) }}★</p>
                            <p class="text-[8px] text-gray-400 uppercase">Rating</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-emerald-500">{{ $branch->staff_count ?? 0 }}</p>
                            <p class="text-[8px] text-gray-400 uppercase">Staff</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                        <span class="px-2 py-0.5 rounded-full text-[8px] font-bold {{ $branch->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400' }}">
                            {{ $branch->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <div class="flex gap-2">
                            @if($branch->isApproved() && (int) $branch->active_manager_count === 0)
                                <button wire:click.stop="openManagerModal({{ $branch->id }})" 
                                        class="text-[10px] text-purple-500 hover:underline">
                                    <i class="fa fa-user-tie mr-1"></i> Assign Manager
                                </button>
                                <span class="text-gray-300 dark:text-gray-700">|</span>
                            @endif
                            <a href="{{ route('owner.branch-manage') }}?branch={{ $branch->id }}" class="text-[10px] text-[#d2964b] hover:underline">Manage</a>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <a href="{{ route('owner.branch-analytics') }}?branch={{ $branch->id }}" class="text-[10px] text-blue-500 hover:underline">Analytics</a>
                        </div>
                    </div>
                    @if($branch->manager)
                        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2 text-[10px] text-gray-500">
                            <i class="fa fa-user text-purple-500"></i>
                            <span>Manager: <span class="text-white font-medium">{{ $branch->manager->name }}</span></span>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>

            <!-- Selected Branch Detail -->
            @if($selectedBranch)
            <div class="bg-white dark:bg-[#11131A] rounded-2xl border border-[#d2964b]/30 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                        <i class="fa fa-store text-[#d2964b] mr-2"></i>
                        {{ $selectedBranch->name }} - Details
                    </h3>
                    <span class="text-xs text-gray-500">Showing: {{ ucfirst($period) }}</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-[#d2964b]/10 to-[#8b5a2b]/10 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-[#d2964b]">₱{{ number_format($selectedBranch->total_revenue ?? 0, 2) }}</p>
                        <p class="text-[10px] text-gray-500 uppercase">Revenue</p>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-500/10 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-blue-500">{{ $selectedBranch->total_orders ?? 0 }}</p>
                        <p class="text-[10px] text-gray-500 uppercase">Orders</p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-500/10 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-amber-500">{{ number_format($selectedBranch->avg_rating ?? 0, 1) }}★</p>
                        <p class="text-[10px] text-gray-500 uppercase">Rating</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-500/10 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-emerald-500">{{ $selectedBranch->staff_count ?? 0 }}</p>
                        <p class="text-[10px] text-gray-500 uppercase">Staff</p>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-center">
                        <p class="text-xs text-gray-400">Avg Order Value</p>
                        <p class="text-sm font-bold text-gray-800 dark:text-white">
                            ₱{{ $selectedBranch->total_orders > 0 ? number_format($selectedBranch->total_revenue / $selectedBranch->total_orders, 2) : '0.00' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400">Approval Status</p>
                        <p class="text-sm font-bold 
                            {{ $selectedBranch->approval_status === 'approved' ? 'text-emerald-500' : 
                               ($selectedBranch->approval_status === 'pending' ? 'text-amber-500' : 'text-red-500') }}">
                            {{ ucfirst($selectedBranch->approval_status ?? 'N/A') }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400">Created</p>
                        <p class="text-sm font-bold text-gray-800 dark:text-white">
                            {{ $selectedBranch->created_at ? $selectedBranch->created_at->format('M d, Y') : 'N/A' }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400">Branch Code</p>
                        <p class="text-sm font-bold text-gray-800 dark:text-white font-mono">
                            {{ $selectedBranch->code ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                
                <!-- Manager Info -->
                @if($selectedBranch->manager)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-500">
                        <i class="fa fa-user-tie"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800 dark:text-white">Branch Manager</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedBranch->manager->name }}</p>
                        <p class="text-xs text-gray-500">{{ $selectedBranch->manager->email }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('owner.branch-manage') }}" class="bg-white dark:bg-[#11131A] rounded-2xl p-5 border border-gray-200 dark:border-gray-800 hover:border-[#d2964b]/50 transition shadow-sm flex items-center gap-4 group">
                    <div class="w-12 h-12 bg-[#d2964b]/10 rounded-xl flex items-center justify-center text-[#d2964b] group-hover:bg-[#d2964b] group-hover:text-white transition-all">
                        <i class="fa fa-store text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-800 dark:text-white">Manage Branches</p>
                        <p class="text-xs text-gray-500">Add, edit, or delete branches</p>
                    </div>
                    <i class="fa fa-arrow-right ml-auto text-gray-400 group-hover:text-[#d2964b] transition-all"></i>
                </a>
                <a href="{{ route('owner.branch-staff') }}" class="bg-white dark:bg-[#11131A] rounded-2xl p-5 border border-gray-200 dark:border-gray-800 hover:border-[#d2964b]/50 transition shadow-sm flex items-center gap-4 group">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-all">
                        <i class="fa fa-users text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-800 dark:text-white">Manage Staff</p>
                        <p class="text-xs text-gray-500">Add or remove staff members</p>
                    </div>
                    <i class="fa fa-arrow-right ml-auto text-gray-400 group-hover:text-blue-500 transition-all"></i>
                </a>
                <a href="{{ route('owner.branch-analytics') }}" class="bg-white dark:bg-[#11131A] rounded-2xl p-5 border border-gray-200 dark:border-gray-800 hover:border-[#d2964b]/50 transition shadow-sm flex items-center gap-4 group">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                        <i class="fa fa-chart-line text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-800 dark:text-white">Full Analytics</p>
                        <p class="text-xs text-gray-500">Deep dive into performance</p>
                    </div>
                    <i class="fa fa-arrow-right ml-auto text-gray-400 group-hover:text-emerald-500 transition-all"></i>
                </a>
            </div>

            <!-- Branch Comparison Chart -->
            @if($branches->count() > 1)
            <div class="bg-white dark:bg-[#11131A] rounded-2xl border border-gray-200 dark:border-[#d2964b]/30 p-6 shadow-sm">
                <h3 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa fa-chart-bar text-[#d2964b]"></i>
                    Branch Comparison
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-400 mb-2">Revenue by Branch</p>
                        @foreach($branches as $branch)
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400 w-24 truncate">{{ $branch->name }}</span>
                            <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                @php
                                    $maxRevenue = max($branches->pluck('total_revenue')->toArray()) ?: 1;
                                    $width = ($branch->total_revenue / $maxRevenue) * 100;
                                @endphp
                                <div class="h-full bg-gradient-to-r from-[#d2964b] to-[#f5a623] rounded-full transition-all duration-500" style="width: {{ max(4, $width) }}%;"></div>
                            </div>
                            <span class="text-xs font-bold text-[#d2964b]">₱{{ number_format($branch->total_revenue, 0) }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-2">Orders by Branch</p>
                        @foreach($branches as $branch)
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400 w-24 truncate">{{ $branch->name }}</span>
                            <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                @php
                                    $maxOrders = max($branches->pluck('total_orders')->toArray()) ?: 1;
                                    $width = ($branch->total_orders / $maxOrders) * 100;
                                @endphp
                                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-500 rounded-full transition-all duration-500" style="width: {{ max(4, $width) }}%;"></div>
                            </div>
                            <span class="text-xs font-bold text-blue-500">{{ $branch->total_orders }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </main>

    {{-- ================================================================ --}}
    {{-- CREATE BRANCH MODAL --}}
    {{-- ================================================================ --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" 
         x-data="{ open: true }" 
         x-show="open" 
         x-cloak>
        
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" wire:click="closeCreateModal"></div>
        
        <div class="bg-gradient-to-br from-[#11131A] to-[#0A0C10] rounded-2xl border border-[#d2964b]/40 shadow-[0_0_50px_rgba(210,150,75,0.3)] w-full max-w-md relative z-10">
            
            <div class="p-6 border-b border-[#d2964b]/20 bg-gradient-to-r from-[#d2964b]/10 to-transparent">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#d2964b]/20 flex items-center justify-center border border-[#d2964b]/30">
                            <i class="fa fa-store text-[#d2964b]"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Create New Branch</h3>
                            <p class="text-xs text-gray-400">Branch will be pending approval</p>
                        </div>
                    </div>
                    <button wire:click="closeCreateModal" class="text-gray-500 hover:text-[#d2964b] transition">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Branch Name *</label>
                        <input type="text" wire:model="branchName" 
                               class="w-full bg-[#1A1D26] text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-[#d2964b] focus:ring-1 focus:ring-[#d2964b] outline-none transition mt-1"
                               placeholder="Main Branch">
                        @error('branchName') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Address *</label>
                        <input type="text" wire:model="branchAddress" 
                               class="w-full bg-[#1A1D26] text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-[#d2964b] focus:ring-1 focus:ring-[#d2964b] outline-none transition mt-1"
                               placeholder="123 Main St, City">
                        @error('branchAddress') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Phone Number</label>
                        <input type="text" wire:model="branchPhone" 
                               class="w-full bg-[#1A1D26] text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-[#d2964b] focus:ring-1 focus:ring-[#d2964b] outline-none transition mt-1"
                               placeholder="(123) 456-7890">
                        @error('branchPhone') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Email</label>
                        <input type="email" wire:model="branchEmail" 
                               class="w-full bg-[#1A1D26] text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-[#d2964b] focus:ring-1 focus:ring-[#d2964b] outline-none transition mt-1"
                               placeholder="branch@yourbusiness.com">
                        @error('branchEmail') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Description</label>
                        <textarea wire:model="branchDescription" rows="3"
                                  class="w-full bg-[#1A1D26] text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-[#d2964b] focus:ring-1 focus:ring-[#d2964b] outline-none transition mt-1 resize-none"
                                  placeholder="Tell us about this branch..."></textarea>
                        @error('branchDescription') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-3 mt-4">
                    <p class="text-xs text-blue-400 flex items-start gap-2">
                        <i class="fa fa-info-circle mt-0.5"></i>
                        <span>Your branch will be <strong class="text-amber-400">pending approval</strong> until reviewed by an admin. You'll be notified once approved.</span>
                    </p>
                </div>

                <div class="flex gap-3 mt-6">
                    <button wire:click="closeCreateModal" 
                            class="flex-1 px-4 py-2.5 bg-gray-800 hover:bg-gray-700 text-white rounded-xl font-bold transition">
                        Cancel
                    </button>
                    <button wire:click="createBranch" 
                            wire:loading.attr="disabled"
                            class="flex-1 px-4 py-2.5 bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] hover:shadow-[0_0_15px_rgba(210,150,75,0.5)] text-white rounded-xl font-bold transition">
                        <span wire:loading.remove>Create Branch</span>
                        <span wire:loading><i class="fa fa-spinner fa-spin mr-1"></i> Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- ASSIGN MANAGER MODAL --}}
    {{-- ================================================================ --}}
    @if($showManagerModal && $selectedBranchForManager)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" 
         x-data="{ open: true }" 
         x-show="open" 
         x-cloak>
        
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" wire:click="closeManagerModal"></div>
        
        <div class="bg-gradient-to-br from-[#11131A] to-[#0A0C10] rounded-2xl border border-purple-500/40 shadow-[0_0_50px_rgba(168,85,247,0.3)] w-full max-w-md relative z-10">
            
            <div class="p-6 border-b border-purple-500/20 bg-gradient-to-r from-purple-500/10 to-transparent">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center border border-purple-500/30">
                            <i class="fa fa-user-tie text-purple-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Assign Branch Manager</h3>
                            <p class="text-xs text-gray-400">{{ $selectedBranchForManager->name }}</p>
                        </div>
                    </div>
                    <button wire:click="closeManagerModal" class="text-gray-500 hover:text-purple-400 transition">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <p class="text-sm text-gray-300 mb-4">
                    Select a staff member to assign as manager for <strong class="text-white">{{ $selectedBranchForManager->name }}</strong>
                </p>

                <div>
                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Select Manager *</label>
                    <select wire:model="selectedManagerId" 
                            class="w-full bg-[#1A1D26] text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition mt-1">
                        <option value="">Select a staff member...</option>
                        @foreach($availableManagers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->name }} ({{ ucfirst($manager->role) }})</option>
                        @endforeach
                    </select>
                    @error('selectedManagerId') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                @if($availableManagers->isEmpty())
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 mt-4">
                        <p class="text-xs text-amber-400 flex items-start gap-2">
                            <i class="fa fa-exclamation-triangle mt-0.5"></i>
                            <span>No available staff found. <a href="{{ route('owner.branch-staff') }}" class="text-white underline">Add staff</a> first.</span>
                        </p>
                    </div>
                @endif

                <div class="flex gap-3 mt-6">
                    <button wire:click="closeManagerModal" 
                            class="flex-1 px-4 py-2.5 bg-gray-800 hover:bg-gray-700 text-white rounded-xl font-bold transition">
                        Cancel
                    </button>
                    <button wire:click="assignManager" 
                            wire:loading.attr="disabled"
                            wire:target="assignManager"
                            class="flex-1 px-4 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 hover:shadow-[0_0_15px_rgba(168,85,247,0.5)] text-white rounded-xl font-bold transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="assignManager">Assign Manager</span>
                        <span wire:loading wire:target="assignManager"><i class="fa fa-spinner fa-spin mr-1"></i> Assigning...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
