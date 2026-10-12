<?php

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.branch')] class extends Component
{
    public $name, $email, $password;
    public $editingStaffId = null;
    public $role = 'cashier';
    public $branch_id = null;
    public $tenantId;
    public $currentBranch;
    public $staffMembers = [];

    protected function rules()
    {
        return [
            'name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s.]+$/',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingStaffId),
            ],
            'password' => $this->editingStaffId ? 'nullable|min:8|max:255' : 'required|min:8|max:255',
            'role' => 'required|in:cashier,barista,branch_manager',
            'branch_id' => 'required|exists:branches,id',
        ];
    }

    protected function messages()
    {
        return [
            'name.required' => 'Staff name is required.',
            'name.regex' => 'Name should only contain letters, spaces, and dots.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address (e.g., name@example.com).',
            'email.unique' => 'This email is already registered. Please use a different email.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'role.required' => 'Please select a role.',
            'role.in' => 'Invalid role selected.',
            'branch_id.required' => 'Please select a branch.',
        ];
    }

    public function mount()
    {
        $user = Auth::user();

        if ($user->role !== 'branch_manager') {
            abort(403, 'Only branch managers can manage staff.');
        }

        $this->tenantId = $user->tenant_id;
        $this->branch_id = $user->branch_id;
        $this->currentBranch = Branch::find($this->branch_id);

        $this->loadStaff();
    }

    public function loadStaff()
    {
        $this->staffMembers = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branch_id)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->with('branch')
            ->latest()
            ->get();
    }

    private function hasActiveManager()
    {
        return User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branch_id)
            ->where('role', 'branch_manager')
            ->where('is_active', true)
            ->exists();
    }

    private function getActiveManager()
    {
        return User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branch_id)
            ->where('role', 'branch_manager')
            ->where('is_active', true)
            ->first();
    }

    public function createStaff()
    {
        $this->name = trim(strip_tags($this->name));
        $this->email = trim(strtolower($this->email));

        $this->validate();

        $this->branch_id = $this->currentBranch->id;

        if ($this->role === 'branch_manager') {
            if ($this->hasActiveManager()) {
                $existingManager = $this->getActiveManager();
                session()->flash('error', '⚠️ This branch already has an active manager: ' . $existingManager->name);
                return;
            }
        }

        if (!in_array($this->role, ['cashier', 'barista', 'branch_manager'])) {
            session()->flash('error', '⚠️ Invalid role selected.');
            return;
        }

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branch_id,
            'role' => $this->role,
            'is_active' => true,
        ]);

        $this->reset(['name', 'email', 'password']);
        $this->role = 'cashier';
        $this->loadStaff();
        session()->flash('success', '✅ Staff account created successfully!');
    }

    public function editStaff($id)
    {
        $this->resetErrorBag();
        $staff = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branch_id)
            ->findOrFail($id);

        $this->editingStaffId = $id;
        $this->name = $staff->name;
        $this->email = $staff->email;
        $this->role = $staff->role ?? 'cashier';
        $this->branch_id = $staff->branch_id;
        $this->password = '';
    }

    public function updateStaff()
    {
        $this->name = trim(strip_tags($this->name));
        $this->email = trim(strtolower($this->email));

        $this->validate();

        $staff = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branch_id)
            ->findOrFail($this->editingStaffId);

        if ($this->branch_id != $this->currentBranch->id) {
            session()->flash('error', '⚠️ Staff cannot be assigned to other branches.');
            return;
        }

        if ($this->role === 'branch_manager' && $staff->role !== 'branch_manager') {
            if ($this->hasActiveManager()) {
                $existingManager = $this->getActiveManager();
                session()->flash('error', '⚠️ This branch already has an active manager: ' . $existingManager->name);
                return;
            }
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'branch_id' => $this->branch_id,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $staff->update($data);
        $this->cancelEdit();
        $this->loadStaff();
        session()->flash('success', '✅ Staff updated successfully!');
    }

    public function toggleStaffStatus($id)
    {
        $staff = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branch_id)
            ->findOrFail($id);

        if ($staff->role === 'branch_manager' && $staff->is_active) {
            $activeManagers = User::where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branch_id)
                ->where('role', 'branch_manager')
                ->where('is_active', true)
                ->where('id', '!=', $staff->id)
                ->count();

            if ($activeManagers === 0) {
                session()->flash('error', '⚠️ Cannot deactivate the only active manager.');
                return;
            }
        }

        $staff->update(['is_active' => !$staff->is_active]);
        $this->loadStaff();
        session()->flash('success', '✅ Staff ' . ($staff->is_active ? 'activated' : 'deactivated') . '!');
    }

    public function deleteStaff($id)
    {
        $staff = User::where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branch_id)
            ->findOrFail($id);

        if ($staff->role === 'branch_manager') {
            $activeManagers = User::where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branch_id)
                ->where('role', 'branch_manager')
                ->where('is_active', true)
                ->where('id', '!=', $staff->id)
                ->count();

            if ($activeManagers === 0) {
                session()->flash('error', '⚠️ Cannot delete the only active manager.');
                return;
            }
        }

        $staff->delete();
        $this->loadStaff();
        session()->flash('success', '✅ Staff removed successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'email', 'password', 'editingStaffId']);
        $this->role = 'cashier';
        $this->resetErrorBag();
        $this->loadStaff();
    }

    public function render()
    {
        return view('pages.branch.branch-staff.branch-staff', [
            'staffMembers' => $this->staffMembers,
            'branch' => $this->currentBranch,
            'hasActiveManager' => $this->hasActiveManager(),
        ]);
    }
}
?>

<div class="staff-root relative min-h-screen font-sans transition-colors duration-300"
     style="background-color: var(--bg-primary); color: var(--text-primary);">

    {{-- Theme styles (now INSIDE the root element) --}}
    <style>
        .staff-root {
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
        .dark .staff-root {
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
        .staff-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .staff-root .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
        .staff-root input,
        .staff-root select,
        .staff-root textarea {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .staff-root input::placeholder { color: var(--text-muted); }
        .staff-root input:focus,
        .staff-root select:focus,
        .staff-root textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
    </style>

    <main class="relative z-10 p-3 sm:p-4 md:p-5 max-w-7xl mx-auto space-y-3.5">

        {{-- HEADER HUD --}}
        <div class="card p-3 sm:p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa-solid fa-user-shield text-sm"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black tracking-tight" style="color: var(--text-primary);">
                            Staff Management
                        </h1>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold"
                              style="background-color: color-mix(in srgb, var(--violet) 10%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 20%, transparent);">
                            {{ $staffMembers->count() }} MEMBERS
                        </span>
                    </div>
                    <p class="text-xs font-mono truncate" style="color: var(--text-muted);">
                        {{ $branch->name ?? 'Branch' }} • Operational Crew Roster
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="px-2.5 py-1 rounded-xl text-[11px] font-mono font-bold flex items-center gap-1"
                      style="background-color: color-mix(in srgb, var(--violet) 10%, transparent); color: var(--violet); border: 1px solid color-mix(in srgb, var(--violet) 20%, transparent);">
                    <i class="fa-solid fa-user-tie text-[10px]"></i> {{ $staffMembers->where('role', 'branch_manager')->count() }} Manager
                </span>
                <span class="px-2.5 py-1 rounded-xl text-[11px] font-mono font-bold flex items-center gap-1"
                      style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                    <i class="fa-solid fa-cash-register text-[10px]"></i> {{ $staffMembers->where('role', 'cashier')->count() }} Cashiers
                </span>
                <span class="px-2.5 py-1 rounded-xl text-[11px] font-mono font-bold flex items-center gap-1"
                      style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 20%, transparent);">
                    <i class="fa-solid fa-blender text-[10px]"></i> {{ $staffMembers->where('role', 'barista')->count() }} Baristas
                </span>
            </div>
        </div>

        {{-- FLASH MESSAGES --}}
        @if (session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 class="p-3 rounded-xl text-xs font-mono font-bold flex items-center justify-between"
                 style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok);">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" style="color: var(--ok);"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="p-3 rounded-xl text-xs font-mono font-bold flex items-center justify-between"
                 style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); color: var(--bad);">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button @click="show = false" style="color: var(--bad);"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        {{-- MAIN GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">

            {{-- FORM PANEL --}}
            <div class="lg:col-span-4 card p-4 shadow-sm">
                <div class="flex items-center justify-between pb-2.5 mb-3" style="border-bottom: 1px solid var(--border-soft);">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid {{ $editingStaffId ? 'fa-user-pen' : 'fa-user-plus' }} text-sm" style="color: var(--accent);"></i>
                        <h2 class="text-sm font-bold" style="color: var(--text-primary);">
                            {{ $editingStaffId ? 'Update Staff Member' : 'Register New Staff' }}
                        </h2>
                    </div>
                    @if($editingStaffId)
                        <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold"
                              style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn);">EDITING</span>
                    @endif
                </div>

                <form wire:submit.prevent="{{ $editingStaffId ? 'updateStaff' : 'createStaff' }}" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Full Name</label>
                        <input type="text" wire:model="name" placeholder="e.g. Juan Dela Cruz"
                            class="w-full px-3 py-2 text-xs rounded-xl outline-none transition-all">
                        @error('name') <span class="text-[10px] mt-1 block" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Email Address</label>
                        <input type="email" wire:model="email" placeholder="staff@example.com"
                            class="w-full px-3 py-2 text-xs rounded-xl outline-none transition-all">
                        @error('email') <span class="text-[10px] mt-1 block" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">
                            {{ $editingStaffId ? 'Password (Leave blank to keep)' : 'Initial Password' }}
                        </label>
                        <input type="password" wire:model="password" placeholder="••••••••"
                            class="w-full px-3 py-2 text-xs rounded-xl outline-none transition-all">
                        @error('password') <span class="text-[10px] mt-1 block" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-mono font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Role Assignment</label>
                        <select wire:model="role" class="w-full px-3 py-2 text-xs rounded-xl outline-none transition-all">
                            <option value="cashier">Cashier</option>
                            <option value="barista">Barista</option>
                            <option value="branch_manager" {{ $hasActiveManager && !$editingStaffId ? 'disabled' : '' }}>
                                Branch Manager {{ $hasActiveManager && !$editingStaffId ? '(Max 1 Active)' : '' }}
                            </option>
                        </select>
                        @error('role') <span class="text-[10px] mt-1 block" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2 flex flex-col gap-2">
                        <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold font-mono transition-all flex items-center justify-center gap-1.5"
                            style="background-color: var(--accent); color: var(--bg-primary);">
                            <i class="fa-solid {{ $editingStaffId ? 'fa-floppy-disk' : 'fa-user-check' }}"></i>
                            <span>{{ $editingStaffId ? 'Save Changes' : 'Register Staff Member' }}</span>
                        </button>
                        @if($editingStaffId)
                            <button type="button" wire:click="cancelEdit"
                                class="w-full py-1.5 text-[11px] font-mono transition"
                                style="color: var(--text-muted);">
                                Cancel Edit
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            {{-- TABLE ROSTER --}}
            <div class="lg:col-span-8 card p-4 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between pb-2.5 mb-3" style="border-bottom: 1px solid var(--border-soft);">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-sm" style="color: var(--accent);"></i>
                        <h2 class="text-sm font-bold" style="color: var(--text-primary);">Active Staff Roster</h2>
                    </div>
                    <span class="text-[11px] font-mono" style="color: var(--text-muted);">Total: {{ $staffMembers->count() }} accounts</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="font-mono text-[10px] uppercase tracking-wider"
                               style="background-color: var(--bg-inner); color: var(--text-muted); border-bottom: 1px solid var(--border-soft);">
                            <tr>
                                <th class="px-3 py-2">Staff Details</th>
                                <th class="px-3 py-2">Role</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffMembers as $member)
                                <tr style="border-bottom: 1px solid var(--border-soft); {{ $editingStaffId == $member->id ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent);' : '' }}">
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs"
                                                 style="background-color: var(--bg-inner); border: 1px solid var(--border-soft); color: var(--accent);">
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-xs" style="color: var(--text-primary);">{{ $member->name }}</div>
                                                <div class="text-[10px] font-mono" style="color: var(--text-muted);">{{ $member->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @php
                                            $roleColor = match($member->role) {
                                                'branch_manager' => 'var(--violet)',
                                                'barista' => 'var(--info)',
                                                default => 'var(--accent)',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono font-bold"
                                              style="background-color: color-mix(in srgb, {{ $roleColor }} 10%, transparent); color: {{ $roleColor }}; border: 1px solid color-mix(in srgb, {{ $roleColor }} 20%, transparent);">
                                            {{ ucfirst(str_replace('_', ' ', $member->role)) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-mono font-bold"
                                              style="background-color: color-mix(in srgb, {{ $member->is_active ? 'var(--ok)' : 'var(--bad)' }} 10%, transparent); color: {{ $member->is_active ? 'var(--ok)' : 'var(--bad)' }};">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $member->is_active ? 'var(--ok)' : 'var(--bad)' }};"></span>
                                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="editStaff({{ $member->id }})"
                                                class="p-1.5 rounded-lg transition"
                                                style="color: var(--text-muted);" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button wire:click="toggleStaffStatus({{ $member->id }})"
                                                class="p-1.5 rounded-lg transition"
                                                style="color: var(--text-muted);" title="Toggle Status">
                                                <i class="fa-solid {{ $member->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                            <button wire:click="deleteStaff({{ $member->id }})"
                                                wire:confirm="Remove this staff account?"
                                                class="p-1.5 rounded-lg transition"
                                                style="color: var(--text-muted);" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-8 text-center text-xs font-mono" style="color: var(--text-muted);">
                                        No staff accounts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-2 flex items-center justify-between text-[10px] font-mono"
                     style="border-top: 1px solid var(--border-soft); color: var(--text-muted);">
                    <span>Authorized Branch Crew</span>
                    <span class="font-bold" style="color: var(--ok);">Encrypted Access</span>
                </div>
            </div>

        </div>
    </main>
</div>