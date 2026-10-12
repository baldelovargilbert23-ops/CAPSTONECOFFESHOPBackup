<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.shop_owner')] class extends Component
{
    public $branches = [];
    public $selectedBranchId = null;
    public $staffMembers = [];
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'cashier';
    public $branch_id = null;
    public $editingStaffId = null;

    public function mount()
    {
        $this->loadBranches();
        $this->loadStaff();
    }

    public function loadBranches()
    {
        $tenantId = Auth::user()->tenant_id;
        $this->branches = Branch::where('tenant_id', $tenantId)->get();
        
        if ($this->branches->count() > 0 && !$this->selectedBranchId) {
            $this->selectedBranchId = $this->branches->first()->id;
        }
    }

    public function loadStaff()
    {
        $tenantId = Auth::user()->tenant_id;
        $query = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista']);
        
        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }
        
        $this->staffMembers = $query->with('branch')->latest()->get();
    }

    public function selectBranch($branchId)
    {
        $this->selectedBranchId = $branchId;
        $this->loadStaff();
    }

    public function openStaffModal($staffId = null)
    {
        $this->resetValidation();
        $this->reset(['name', 'email', 'password', 'role', 'branch_id', 'editingStaffId']);

        if ($staffId) {
            $staff = User::findOrFail($staffId);
            $this->editingStaffId = $staffId;
            $this->name = $staff->name;
            $this->email = $staff->email;
            $this->role = $staff->role;
            $this->branch_id = $staff->branch_id;
        } else {
            $this->branch_id = $this->selectedBranchId;
        }

        $this->dispatch('open-staff-modal');
    }

    public function saveStaff()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingStaffId,
            'password' => $this->editingStaffId ? 'nullable|min:8' : 'required|min:8',
            'role' => 'required|in:branch_manager,cashier,barista',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'branch_id' => $this->branch_id,
            'tenant_id' => $tenantId,
            'is_active' => true,
        ];

        if ($this->editingStaffId) {
            $staff = User::findOrFail($this->editingStaffId);
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }
            $staff->update($data);
            $message = 'Staff updated successfully!';
        } else {
            $data['password'] = Hash::make($this->password);
            User::create($data);
            $message = 'Staff added successfully!';
        }

        $this->dispatch('close-staff-modal');
        $this->loadStaff();
        session()->flash('success', $message);
    }

    public function toggleStatus($staffId)
    {
        $staff = User::findOrFail($staffId);
        $staff->update(['is_active' => !$staff->is_active]);
        $this->loadStaff();
        session()->flash('success', 'Staff status updated!');
    }

    public function deleteStaff($staffId)
    {
        User::findOrFail($staffId)->delete();
        $this->loadStaff();
        session()->flash('success', 'Staff deleted successfully!');
    }

    public function render()
    {
        return view('pages.shop_owner.⚡branch-staff.branch-staff');
    }
};
?>
<div>
    <div x-data="{ showStaffModal: false }" x-on:open-staff-modal.window="showStaffModal = true" x-on:close-staff-modal.window="showStaffModal = false">
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
                            <i class="fa fa-users text-[#d2964b]"></i>
                            Branch Staff
                        </h1>
                        <p class="text-sm text-gray-500">Manage staff members by branch</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="$dispatch('open-staff-modal')" class="px-4 py-2.5 font-bold text-white rounded-xl bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] hover:opacity-90 shadow-lg shadow-[#d2964b]/30 transition text-sm flex items-center gap-2">
                            <i class="fa fa-user-plus"></i> Add Staff
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="bg-emerald-100 dark:bg-emerald-500/20 border border-emerald-400 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl flex items-center justify-between">
                        <span><i class="fa fa-check-circle mr-2"></i> {{ session('success') }}</span>
                        <button @click="show = false" class="text-emerald-700 dark:text-emerald-400">×</button>
                    </div>
                @endif

                <!-- Branch Selector -->
                <div class="flex flex-wrap gap-2 bg-white dark:bg-[#11131A] p-3 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                    <button wire:click="selectBranch(null)" class="px-3 py-1.5 text-xs font-bold rounded-lg {{ !$selectedBranchId ? 'bg-[#d2964b] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }}">All Branches</button>
                    @foreach($branches as $branch)
                        <button wire:click="selectBranch({{ $branch->id }})" class="px-3 py-1.5 text-xs font-bold rounded-lg {{ $selectedBranchId == $branch->id ? 'bg-[#d2964b] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }}">
                            {{ $branch->name }}
                        </button>
                    @endforeach
                </div>

                <!-- Staff Table -->
                <div class="bg-white dark:bg-[#11131A] rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-[#0D0F14] border-b">
                                <tr class="text-xs font-bold text-gray-500 uppercase">
                                    <th class="px-6 py-3 text-left">Staff</th>
                                    <th class="px-6 py-3 text-left">Role</th>
                                    <th class="px-6 py-3 text-left">Branch</th>
                                    <th class="px-6 py-3 text-left">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($staffMembers as $staff)
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#d2964b]/5 transition">
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-[#d2964b]/20 flex items-center justify-center text-[#d2964b] font-bold text-xs">
                                                {{ strtoupper(substr($staff->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 dark:text-white">{{ $staff->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $staff->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase bg-[#d2964b]/10 text-[#d2964b]">
                                            {{ str_replace('_', ' ', $staff->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-500">{{ $staff->branch->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[8px] font-bold {{ $staff->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $staff->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button @click="$dispatch('open-staff-modal')" wire:click="openStaffModal({{ $staff->id }})" class="text-xs text-[#d2964b] hover:underline">Edit</button>
                                            <button wire:click="toggleStatus({{ $staff->id }})" class="text-xs {{ $staff->is_active ? 'text-red-500 hover:text-red-700' : 'text-emerald-500 hover:text-emerald-700' }}">
                                                {{ $staff->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                            <button wire:confirm="Delete this staff?" wire:click="deleteStaff({{ $staff->id }})" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fa fa-users-slash text-3xl mb-2 block"></i>
                                        No staff members found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 dark:bg-[#0D0F14] text-xs text-gray-500">
                        Total: {{ $staffMembers->count() }} staff members
                    </div>
                </div>
            </div>

            <!-- Staff Modal -->
            <div x-show="showStaffModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition>
                <div class="bg-white dark:bg-[#11131A] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-md p-6 space-y-4 shadow-2xl" @click.away="showStaffModal = false">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                            {{ $editingStaffId ? 'Edit Staff' : 'Add New Staff' }}
                        </h3>
                        <button @click="showStaffModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="saveStaff" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name *</label>
                            <input wire:model="name" type="text" placeholder="e.g. John Doe" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                            @error('name') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email *</label>
                            <input wire:model="email" type="email" placeholder="staff@example.com" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                            @error('email') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">{{ $editingStaffId ? 'New Password (Optional)' : 'Password *' }}</label>
                            <input wire:model="password" type="password" placeholder="{{ $editingStaffId ? 'Leave blank' : 'Min 8 characters' }}" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                            @error('password') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role *</label>
                            <select wire:model="role" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                                <option value="branch_manager">Branch Manager</option>
                                <option value="cashier">Cashier</option>
                                <option value="barista">Barista</option>
                            </select>
                            @error('role') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Branch *</label>
                            <select wire:model="branch_id" class="w-full bg-gray-50 dark:bg-[#0D0F14] text-gray-800 dark:text-white text-sm rounded-xl px-3 py-2 border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-[#d2964b]">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ !$branch->is_active ? 'disabled' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id') <span class="text-rose-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="showStaffModal = false" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 dark:hover:text-white">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-[#d2964b] to-[#8b5a2b] rounded-xl shadow-lg shadow-[#d2964b]/30">
                                {{ $editingStaffId ? 'Update Staff' : 'Add Staff' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<style>
    [x-cloak] { display: none !important; }
</style>