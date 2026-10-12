<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.shop_owner')] class extends Component
{
    public $search = '';
    public $showBranchModal = false;
    public $showStaffModal = false;
    public $showBranchDetail = false;
    public $selectedBranchId = null;
    public $period = 'today';

    // Branch fields
    public $branchId = null;
    public $name = '';
    public $code = '';
    public $address = '';
    public $contact_number = '';
    public $is_main = false;
    public $is_active = true;

    // Staff fields
    public $staffId = null;
    public $staff_name = '';
    public $staff_email = '';
    public $staff_password = '';
    public $staff_role = 'cashier';
    public $staff_branch_id = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:branches,code,' . $this->branchId,
            'address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:50',
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected function staffRules()
    {
        return [
            'staff_name' => 'required|string|max:255',
            'staff_email' => 'required|email|unique:users,email,' . $this->staffId,
            'staff_password' => $this->staffId ? 'nullable|min:8' : 'required|min:8',
            'staff_role' => 'required|in:branch_manager,cashier,barista',
            'staff_branch_id' => 'required|exists:branches,id',
        ];
    }

    public function openBranchModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['name', 'code', 'address', 'contact_number', 'is_main', 'is_active', 'branchId']);

        if ($id) {
            $branch = Branch::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
            $this->branchId = $branch->id;
            $this->name = $branch->name;
            $this->code = $branch->code ?? '';
            $this->address = $branch->address ?? '';
            $this->contact_number = $branch->contact_number ?? '';
            $this->is_main = $branch->is_main;
            $this->is_active = $branch->is_active;
        }

        $this->showBranchModal = true;
    }

    public function closeBranchModal()
    {
        $this->showBranchModal = false;
    }

    public function saveBranch()
    {
        $validated = $this->validate();
        $tenantId = Auth::user()->tenant_id;

        DB::transaction(function () use ($validated, $tenantId) {
            if ($this->is_main) {
                Branch::where('tenant_id', $tenantId)->update(['is_main' => false]);
            }

            Branch::updateOrCreate(
                ['id' => $this->branchId, 'tenant_id' => $tenantId],
                $validated + ['tenant_id' => $tenantId]
            );
        });

        $this->closeBranchModal();
        session()->flash('success', 'Branch saved successfully!');
    }

    public function toggleBranchStatus($id)
    {
        $branch = Branch::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $branch->update(['is_active' => !$branch->is_active]);
        session()->flash('success', 'Branch status updated!');
    }

    public function deleteBranch($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $branch = Branch::where('tenant_id', $tenantId)->findOrFail($id);

        if ($branch->is_main && Branch::where('tenant_id', $tenantId)->count() <= 1) {
            session()->flash('error', 'Cannot delete the only main branch.');
            return;
        }

        if (User::where('tenant_id', $tenantId)->where('branch_id', $id)->exists()) {
            session()->flash('error', 'Cannot delete branch with assigned staff.');
            return;
        }

        $branch->delete();
        session()->flash('success', 'Branch deleted successfully!');
    }

    public function openStaffModal($branchId = null, $staffId = null)
    {
        $this->resetValidation();
        $this->reset(['staff_name', 'staff_email', 'staff_password', 'staff_role', 'staff_branch_id', 'staffId']);

        if ($branchId) {
            $this->staff_branch_id = $branchId;
        }

        if ($staffId) {
            $staff = User::where('tenant_id', Auth::user()->tenant_id)
                ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                ->findOrFail($staffId);
            $this->staffId = $staff->id;
            $this->staff_name = $staff->name;
            $this->staff_email = $staff->email;
            $this->staff_role = $staff->role;
            $this->staff_branch_id = $staff->branch_id;
        }

        $this->showStaffModal = true;
    }

    public function closeStaffModal()
    {
        $this->showStaffModal = false;
    }

    public function saveStaff()
    {
        $validated = $this->validate($this->staffRules());
        $tenantId = Auth::user()->tenant_id;

        $data = [
            'tenant_id' => $tenantId,
            'branch_id' => $validated['staff_branch_id'],
            'name' => $validated['staff_name'],
            'email' => $validated['staff_email'],
            'role' => $validated['staff_role'],
            'is_active' => true,
        ];

        if ($this->staffId) {
            $staff = User::where('tenant_id', $tenantId)->whereIn('role', ['branch_manager', 'cashier', 'barista'])->findOrFail($this->staffId);
            if (!empty($validated['staff_password'])) {
                $data['password'] = Hash::make($validated['staff_password']);
            }
            $staff->update($data);
            $message = 'Staff updated successfully!';
        } else {
            $data['password'] = Hash::make($validated['staff_password']);
            User::create($data);
            $message = 'Staff added successfully!';
        }

        $this->closeStaffModal();
        session()->flash('success', $message);
    }

    public function toggleStaffStatus($id)
    {
        $staff = User::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->findOrFail($id);
        $staff->update(['is_active' => !$staff->is_active]);
        session()->flash('success', 'Staff status updated!');
    }

    public function deleteStaff($id)
    {
        User::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->where('id', $id)
            ->delete();
        session()->flash('success', 'Staff deleted successfully!');
    }

    public function viewBranchDetail($id)
    {
        $this->selectedBranchId = $id;
        $this->showBranchDetail = true;
    }

    public function closeBranchDetail()
    {
        $this->showBranchDetail = false;
        $this->selectedBranchId = null;
    }

    public function setPeriod($period)
    {
        $this->period = $period;
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        $branches = Branch::where('tenant_id', $tenantId)
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('code', 'like', '%' . $this->search . '%')
                ->orWhere('address', 'like', '%' . $this->search . '%'))
            ->withCount(['users as staff_count' => fn($q) => $q->whereIn('role', ['branch_manager', 'cashier', 'barista'])])
            ->get();

        foreach ($branches as $branch) {
            $branch->total_revenue = Order::where('tenant_id', $tenantId)
                ->where('branch_id', $branch->id)
                ->where('status', 'completed')
                ->sum('total_amount');
            $branch->total_orders = Order::where('tenant_id', $tenantId)->where('branch_id', $branch->id)->count();
            $branch->avg_rating = Review::where('tenant_id', $tenantId)->where('branch_id', $branch->id)->avg('rating') ?? 0;
        }

        $staff = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->with('branch')
            ->latest()
            ->get();

        $selectedBranch = null;
        $branchOrders = [];
        $branchReviews = [];

        if ($this->selectedBranchId) {
            $selectedBranch = Branch::where('tenant_id', $tenantId)->withCount(['users as staff_count' => fn($q) => $q->whereIn('role', ['branch_manager', 'cashier', 'barista'])])->find($this->selectedBranchId);

            if ($selectedBranch) {
                $selectedBranch->total_revenue = Order::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->where('status', 'completed')
                    ->when($this->period === 'today', fn($q) => $q->whereDate('created_at', today()))
                    ->when($this->period === 'week', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->when($this->period === 'month', fn($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
                    ->when($this->period === 'year', fn($q) => $q->whereYear('created_at', now()->year))
                    ->sum('total_amount');

                $selectedBranch->total_orders = Order::where('tenant_id', $tenantId)->where('branch_id', $this->selectedBranchId)->count();
                $selectedBranch->avg_rating = Review::where('tenant_id', $tenantId)->where('branch_id', $this->selectedBranchId)->avg('rating') ?? 0;

                $branchOrders = Order::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->latest()
                    ->take(10)
                    ->get();

                $branchReviews = Review::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->with('user')
                    ->latest()
                    ->take(5)
                    ->get();
            }
        }

        return view('pages.shop_owner.⚡branch-management.branch-management', [
            'branches' => $branches,
            'staff' => $staff,
            'selectedBranch' => $selectedBranch,
            'branchOrders' => $branchOrders,
            'branchReviews' => $branchReviews,
        ]);
    }
}
?>
<div>
    <div class="fixed inset-0 overflow-hidden pointer-events-none" style="z-index: 0;">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#d2964b] rounded-full blur-[120px] opacity-20 animate-pulse"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-[#f5a623] rounded-full blur-[100px] opacity-15 animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <main class="relative min-h-screen bg-gray-50 dark:bg-[#0A0C10]/90 backdrop-blur-sm font-sans p-4 sm:p-6 space-y-6" style="z-index: 1;">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#d2964b] to-[#8b5a2b] flex items-center justify-center shadow-lg shadow-[#d2964b]/30">
                            <i class="fa fa-code-branch text-white text-lg"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Branch Management</h1>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Manage physical locations and assign staff members</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="openBranchModal" class="px-4 py-2.5 font-bold text-white rounded-xl bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] hover:opacity-90 shadow-lg shadow-[#d2964b]/30 transition text-sm flex items-center gap-2">
                        <i class="fa fa-plus"></i> Add Branch
                    </button>
                </div>
            </div>

            <!-- Messages -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="bg-emerald-100 dark:bg-emerald-500/20 border border-emerald-400 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl flex items-center justify-between">
                    <span><i class="fa fa-check-circle mr-2"></i> {{ session('success') }}</span>
                    <button @click="show = false" class="text-emerald-700 dark:text-emerald-400">×</button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-red-100 dark:bg-red-500/20 border border-red-400 dark:border-red-500/30 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl flex items-center justify-between">
                    <span><i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}</span>
                    <button @click="show = false" class="text-red-700 dark:text-red-400">×</button>
                </div>
            @endif

            <!-- Search -->
            <div class="bg-white dark:bg-[#11131A] p-4 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="relative">
                    <i class="fa fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search branches by name, code, or location..." class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                </div>
            </div>

            <!-- Branch Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($branches as $branch)
                <div class="bg-white dark:bg-[#11131A] rounded-2xl p-5 border border-gray-200 dark:border-gray-800 flex flex-col justify-between space-y-4 hover:border-[#d2964b]/50 transition shadow-sm hover:shadow-lg cursor-pointer" wire:click="viewBranchDetail({{ $branch->id }})">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-gray-800 dark:text-white text-lg">{{ $branch->name }}</h3>
                                    @if($branch->is_main)
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase bg-amber-500/10 text-amber-500 border border-amber-500/20">Main</span>
                                    @endif
                                </div>
                                @if($branch->code)
                                    <span class="text-[10px] font-mono font-semibold text-[#d2964b]">{{ $branch->code }}</span>
                                @endif
                            </div>
                            <button wire:click.stop="toggleBranchStatus({{ $branch->id }})" class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase {{ $branch->is_active ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-400' }}">
                                {{ $branch->is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </div>

                        @if($branch->address)
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-start gap-2 mb-1.5 mt-2">
                                <i class="fa fa-location-dot text-[#d2964b] mt-0.5"></i>
                                <span>{{ $branch->address }}</span>
                            </p>
                        @endif

                        <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <div class="text-center">
                                <p class="text-xs font-bold text-[#d2964b]">₱{{ number_format($branch->total_revenue ?? 0, 0) }}</p>
                                <p class="text-[8px] text-gray-400 uppercase">Revenue</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-bold text-gray-700 dark:text-white">{{ $branch->total_orders ?? 0 }}</p>
                                <p class="text-[8px] text-gray-400 uppercase">Orders</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-bold text-amber-500">{{ number_format($branch->avg_rating ?? 0, 1) }} ★</p>
                                <p class="text-[8px] text-gray-400 uppercase">Rating</p>
                            </div>
                        </div>

                        <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-2 mt-2">
                            <i class="fa fa-users text-[#d2964b]"></i>
                            <span>{{ $branch->staff_count ?? 0 }} staff member(s)</span>
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click.stop="openStaffModal({{ $branch->id }})" class="px-3 py-1.5 text-xs font-bold text-[#d2964b] hover:bg-[#d2964b]/10 rounded-lg transition">
                            <i class="fa fa-user-plus mr-1"></i> Add Staff
                        </button>
                        <button wire:click.stop="openBranchModal({{ $branch->id }})" class="px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition">
                            <i class="fa fa-pen mr-1"></i> Edit
                        </button>
                        <button wire:confirm="Are you sure you want to delete this branch?" wire:click.stop="deleteBranch({{ $branch->id }})" class="px-3 py-1.5 text-xs font-bold text-rose-500 hover:bg-rose-500/10 rounded-lg transition">
                            <i class="fa fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                </div>
                @empty
                <div class="col-span-full bg-white dark:bg-[#11131A] rounded-2xl p-12 text-center text-gray-400 border border-gray-200 dark:border-gray-800 shadow-sm">
                    <i class="fa fa-store text-4xl mb-3 text-gray-300 dark:text-gray-600"></i>
                    <p class="text-sm font-semibold">No branches found</p>
                    <p class="text-xs text-gray-500 mt-1">Add a new branch location to start organizing orders and inventory.</p>
                </div>
                @endforelse
            </div>

            <!-- Staff Table -->
            <div class="bg-white dark:bg-[#11131A] rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#0D0F14] flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fa fa-users text-[#d2964b]"></i> Branch Staff ({{ $staff->count() }})
                    </h2>
                    <button wire:click="openStaffModal()" class="text-sm text-[#d2964b] hover:text-[#8b5a2b]">
                        <i class="fa fa-plus mr-1"></i> Add Staff
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-[#0D0F14]">
                            <tr class="text-gray-500 dark:text-gray-400 text-xs font-bold uppercase">
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Branch</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/50">
                            @forelse($staff as $member)
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#d2964b]/5 transition">
                                <td class="px-6 py-3 font-medium text-gray-800 dark:text-white">{{ $member->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $member->email }}</td>
                                <td class="px-6 py-3 capitalize">
                                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase bg-[#d2964b]/10 text-[#8b5a2b] dark:text-[#f5a623]">
                                        {{ str_replace('_', ' ', $member->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-500">{{ $member->branch->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase {{ $member->is_active ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
                                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="openStaffModal(null, {{ $member->id }})" class="text-xs text-[#d2964b] hover:text-[#8b5a2b]">Edit</button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:click="toggleStaffStatus({{ $member->id }})" class="text-xs {{ $member->is_active ? 'text-red-500 hover:text-red-700' : 'text-emerald-500 hover:text-emerald-700' }}">
                                            {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button wire:confirm="Are you sure?" wire:click="deleteStaff({{ $member->id }})" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fa fa-users text-3xl mb-2 block"></i>
                                    No staff members added yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Branch Modal -->
        @if($showBranchModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#11131A] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ $branchId ? 'Edit Branch' : 'Add New Branch' }}</h3>
                    <button wire:click="closeBranchModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i class="fa fa-times"></i></button>
                </div>

                <form wire:submit.prevent="saveBranch" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Branch Name *</label>
                        <input wire:model="name" type="text" placeholder="e.g. Main Branch" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                        @error('name') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Branch Code</label>
                        <input wire:model="code" type="text" placeholder="e.g. BR-001" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                        @error('code') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Address</label>
                        <input wire:model="address" type="text" placeholder="Branch address" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Contact Number</label>
                        <input wire:model="contact_number" type="text" placeholder="09123456789" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                    </div>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center gap-2">
                            <input wire:model="is_main" type="checkbox" id="is_main" class="rounded text-[#d2964b] focus:ring-[#d2964b]">
                            <label for="is_main" class="text-xs font-semibold text-gray-700 dark:text-gray-300">Set as Main Branch</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input wire:model="is_active" type="checkbox" id="is_active" class="rounded text-[#d2964b] focus:ring-[#d2964b]">
                            <label for="is_active" class="text-xs font-semibold text-gray-700 dark:text-gray-300">Active</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" wire:click="closeBranchModal" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 dark:hover:text-white">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] rounded-xl shadow-lg shadow-[#d2964b]/30">Save Branch</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Staff Modal -->
        @if($showStaffModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#11131A] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">{{ $staffId ? 'Edit Staff' : 'Add New Staff' }}</h3>
                    <button wire:click="closeStaffModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i class="fa fa-times"></i></button>
                </div>

                <form wire:submit.prevent="saveStaff" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name *</label>
                        <input wire:model="staff_name" type="text" placeholder="e.g. John Doe" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                        @error('staff_name') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email *</label>
                        <input wire:model="staff_email" type="email" placeholder="staff@example.com" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                        @error('staff_email') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">{{ $staffId ? 'New Password (Optional)' : 'Password *' }}</label>
                        <input wire:model="staff_password" type="password" placeholder="{{ $staffId ? 'Leave blank' : 'Min 8 characters' }}" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                        @error('staff_password') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role *</label>
                        <select wire:model="staff_role" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                            <option value="branch_manager">Branch Manager</option>
                            <option value="cashier">Cashier</option>
                            <option value="barista">Barista</option>
                        </select>
                        @error('staff_role') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Assign to Branch *</label>
                        <select wire:model="staff_branch_id" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ !$branch->is_active ? 'disabled' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('staff_branch_id') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" wire:click="closeStaffModal" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 dark:hover:text-white">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] rounded-xl shadow-lg shadow-[#d2964b]/30">{{ $staffId ? 'Update Staff' : 'Add Staff' }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Branch Detail Modal -->
        @if($showBranchDetail && $selectedBranch)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white dark:bg-[#11131A] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-4xl p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between sticky top-0 bg-white dark:bg-[#11131A] pb-4 border-b border-gray-200 dark:border-gray-800">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="fa fa-store text-[#d2964b]"></i> {{ $selectedBranch->name }}
                            @if($selectedBranch->is_main) <span class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-500/10 text-amber-500">Main</span> @endif
                        </h3>
                        <p class="text-xs text-gray-500">{{ $selectedBranch->code }} • {{ $selectedBranch->address ?? 'No address' }}</p>
                    </div>
                    <button wire:click="closeBranchDetail" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i class="fa fa-times text-xl"></i></button>
                </div>

                <!-- Period Filter -->
                <div class="flex gap-2 flex-wrap">
                    @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                        <button wire:click="setPeriod('{{ $key }}')" class="px-3 py-1.5 text-xs font-bold rounded-lg {{ $period === $key ? 'bg-[#d2964b] text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">{{ $label }}</button>
                    @endforeach
                </div>

                <!-- Stats -->
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

                <!-- Staff List -->
                @if($selectedBranch->staff_count > 0)
                <div>
                    <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-2">Staff Members</h4>
                    @php $branchStaff = \App\Models\User::where('branch_id', $selectedBranch->id)->whereIn('role', ['branch_manager', 'cashier', 'barista'])->get(); @endphp
                    <div class="space-y-2">
                        @foreach($branchStaff as $staffMember)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-[#0D0F14] rounded-lg px-4 py-2">
                            <div>
                                <p class="font-medium text-sm">{{ $staffMember->name }}</p>
                                <p class="text-xs text-gray-500">{{ $staffMember->email }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase bg-[#d2964b]/10 text-[#8b5a2b] dark:text-[#f5a623]">{{ str_replace('_', ' ', $staffMember->role) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Recent Orders -->
                @if($branchOrders->count() > 0)
                <div>
                    <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-2">Recent Orders</h4>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        @foreach($branchOrders as $order)
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-[#0D0F14] rounded-lg px-3 py-2 text-sm">
                            <div>
                                <p class="font-mono font-bold text-[#d2964b] text-xs">{{ $order->order_number }}</p>
                                <p class="text-[10px] text-gray-500">{{ $order->created_at->format('M d, h:i A') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold">₱{{ number_format($order->total_amount, 2) }}</p>
                                <span class="px-2 py-0.5 rounded-lg text-[8px] font-bold uppercase {{ $order->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $order->status }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Recent Reviews -->
                @if($branchReviews->count() > 0)
                <div>
                    <h4 class="font-bold text-sm text-gray-700 dark:text-gray-300 mb-2">Recent Reviews</h4>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        @foreach($branchReviews as $review)
                        <div class="bg-gray-50 dark:bg-[#0D0F14] rounded-lg px-3 py-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-sm">{{ $review->user->name ?? 'Anonymous' }}</p>
                                    <div class="flex text-amber-400 text-xs">
                                        @for($i=1; $i<=5; $i++)
                                            <i class="fa fa-star {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-300' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                            </div>
                            @if($review->comment)
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">"{{ $review->comment }}"</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                    <button wire:click="closeBranchDetail" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 dark:hover:text-white">Close</button>
                </div>
            </div>
        </div>
        @endif
    </main>
</div>