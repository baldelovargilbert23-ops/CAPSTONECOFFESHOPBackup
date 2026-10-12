<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.branch')] class extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $phone;

    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public $branch_name;
    public $branch_email;
    public $branch_address;
    public $branch_barangay;
    public $branch_city;
    public $branch_province;
    public $branch_contact;

    public $open_time = '07:00';
    public $close_time = '22:00';
    public $open_days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    public $is_open_24h = false;

    public $current_logo;
    public $new_logo;

    public $notify_orders = true;
    public $notify_low_stock = true;
    public $notify_reviews = true;
    public $notify_staff = true;

    protected function messages()
    {
        return [
            'name.required' => 'Your full name is required.',
            'name.regex' => 'Name should only contain letters, spaces, and dashes.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'Please enter a valid phone number.',

            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'Password must be at least 8 characters.',
            'new_password.confirmed' => 'Passwords do not match.',

            'branch_name.required' => 'Branch name is required.',
            'branch_name.regex' => 'Branch name should only contain letters, numbers, spaces, and dashes.',

            'new_logo.required' => 'Please select a logo file.',
            'new_logo.image' => 'The logo must be a valid image file.',
            'new_logo.mimes' => 'Logo must be JPG, PNG, WEBP, or SVG.',
            'new_logo.max' => 'Logo size cannot exceed 2 MB.',

            'open_time.required' => 'Opening time is required.',
            'close_time.required' => 'Closing time is required.',
            'open_days.required' => 'Please select at least one operating day.',
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $branch = $user->branch;

        if (!$branch) {
            abort(403, 'No branch assigned to your account.');
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        $this->branch_name = $branch->name ?? '';
        $this->branch_email = $branch->email ?? '';
        $this->branch_address = $branch->address ?? '';
        $this->branch_barangay = $branch->barangay ?? '';
        $this->branch_city = $branch->city ?? '';
        $this->branch_province = $branch->province ?? '';
        $this->branch_contact = $branch->contact_number ?? '';

        $this->open_time = $branch->open_time ? \Carbon\Carbon::parse($branch->open_time)->format('H:i') : '07:00';
        $this->close_time = $branch->close_time ? \Carbon\Carbon::parse($branch->close_time)->format('H:i') : '22:00';
        $this->open_days = $branch->open_days ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $this->is_open_24h = (bool) ($branch->is_open_24h ?? false);

        $this->current_logo = $branch->logo_path ?? null;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s\-\.]+$/',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
        ]);

        Auth::user()->update([
            'name' => $this->sanitizeInput($this->name),
            'email' => strtolower(trim($this->email)),
            'phone' => $this->sanitizeInput($this->phone),
        ]);

        session()->flash('success', 'Profile updated successfully!');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|max:255|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make(trim($this->new_password))]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success', 'Password updated successfully!');
    }

    public function updateBranch()
    {
        $rules = [
            'branch_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-\.]+$/',
            'branch_email' => 'nullable|email|max:255',
            'branch_address' => 'nullable|string|max:500',
            'branch_barangay' => 'nullable|string|max:255',
            'branch_city' => 'nullable|string|max:255',
            'branch_province' => 'nullable|string|max:255',
            'branch_contact' => 'nullable|string|max:30|regex:/^[0-9+\-\s()]+$/',
        ];

        if (!$this->is_open_24h) {
            $rules['open_time'] = 'required';
            $rules['close_time'] = 'required|after:open_time';
            $rules['open_days'] = 'required|array|min:1';
        }

        $this->validate($rules);

        $branch = Auth::user()->branch;

        $branch->update([
            'name' => $this->sanitizeInput($this->branch_name),
            'email' => $this->branch_email ? strtolower(trim($this->branch_email)) : null,
            'address' => $this->sanitizeInput($this->branch_address),
            'barangay' => $this->sanitizeInput($this->branch_barangay),
            'city' => $this->sanitizeInput($this->branch_city),
            'province' => $this->sanitizeInput($this->branch_province),
            'contact_number' => $this->sanitizeInput($this->branch_contact),

            'is_open_24h' => $this->is_open_24h,
            'open_time' => $this->is_open_24h ? null : $this->open_time,
            'close_time' => $this->is_open_24h ? null : $this->close_time,
            'open_days' => $this->is_open_24h ? null : $this->open_days,
        ]);

        session()->flash('success', 'Branch settings updated!');
    }

    public function updateLogo()
    {
        $this->validate([
            'new_logo' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);

        $branch = Auth::user()->branch;
        $oldLogo = $branch->logo_path;

        $path = $this->new_logo->store('branch-logos/' . $branch->id, 'public');
        $branch->update(['logo_path' => $path]);

        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $this->current_logo = $path;
        $this->new_logo = null;

        session()->flash('success', 'Branch logo updated successfully!');
    }

    public function removeLogo()
    {
        $branch = Auth::user()->branch;

        if ($branch->logo_path && Storage::disk('public')->exists($branch->logo_path)) {
            Storage::disk('public')->delete($branch->logo_path);
        }

        $branch->update(['logo_path' => null]);
        $this->current_logo = null;
        $this->new_logo = null;

        session()->flash('success', 'Branch logo removed. Shop logo will be used instead.');
    }

    public function updateNotifications()
    {
        $this->notify_orders = filter_var($this->notify_orders, FILTER_VALIDATE_BOOLEAN);
        $this->notify_low_stock = filter_var($this->notify_low_stock, FILTER_VALIDATE_BOOLEAN);
        $this->notify_reviews = filter_var($this->notify_reviews, FILTER_VALIDATE_BOOLEAN);
        $this->notify_staff = filter_var($this->notify_staff, FILTER_VALIDATE_BOOLEAN);

        session()->flash('success', 'Notification preferences saved!');
    }

    private function sanitizeInput($input)
    {
        if (empty($input)) return '';
        $input = strip_tags($input);
        $input = trim($input);
        $input = preg_replace('/\s+/', ' ', $input);
        return $input;
    }

    public function render()
    {
        return view('pages.branch.settings.settings');
    }
};
?>

<div class="settings-root min-h-screen font-sans transition-colors duration-300"
     style="background-color: var(--bg-primary); color: var(--text-primary);"
     x-data="{ openSection: 'profile' }">

    {{-- Theme styles (now INSIDE the root element) --}}
    <style>
        .settings-root {
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
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .dark .settings-root {
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
        }
        .settings-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .settings-root .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
        .settings-root input,
        .settings-root select,
        .settings-root textarea {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .settings-root input::placeholder { color: var(--text-muted); }
        .settings-root input:focus,
        .settings-root select:focus,
        .settings-root textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
    </style>

    <main class="p-4 sm:p-6">
        <div class="max-w-4xl mx-auto space-y-6">

            {{-- HEADER --}}
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa fa-gear text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--text-primary);">Settings</h1>
                    <p class="text-sm" style="color: var(--text-muted);">Manage your account and branch preferences</p>
                </div>
            </div>

            {{-- MESSAGES --}}
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="px-4 py-3 rounded-xl flex items-center justify-between"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok);">
                    <span><i class="fa fa-check-circle mr-2"></i> {{ session('success') }}</span>
                    <button @click="show = false" style="color: var(--ok);">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="px-4 py-3 rounded-xl flex items-center justify-between"
                     style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); color: var(--bad);">
                    <span><i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}</span>
                    <button @click="show = false" style="color: var(--bad);">&times;</button>
                </div>
            @endif

            {{-- 1. PROFILE SETTINGS --}}
            <div class="card overflow-hidden">
                <button @click="openSection = openSection === 'profile' ? null : 'profile'"
                        class="w-full px-6 py-4 flex items-center justify-between transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent);">
                            <i class="fa fa-user" style="color: var(--accent);"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-bold" style="color: var(--text-primary);">Profile Settings</h3>
                            <p class="text-xs" style="color: var(--text-muted);">Update your account information</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs hidden sm:inline" style="color: var(--text-muted);">{{ $name }}</span>
                        <i class="fa fa-chevron-down transition-transform duration-300"
                           style="color: var(--text-muted);"
                           :class="openSection === 'profile' ? 'rotate-180' : ''"></i>
                    </div>
                </button>

                <div x-show="openSection === 'profile'" x-collapse class="px-6 pb-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Full Name</label>
                            <input type="text" wire:model="name" class="w-full text-sm rounded-xl px-3 py-2">
                            @error('name') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Email Address</label>
                            <input type="email" wire:model="email" class="w-full text-sm rounded-xl px-3 py-2">
                            @error('email') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Phone Number</label>
                        <input type="text" wire:model="phone" placeholder="+63 912 345 6789" class="w-full text-sm rounded-xl px-3 py-2">
                        @error('phone') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="updateProfile"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition"
                                style="background-color: var(--accent); color: var(--bg-primary);">
                            <i class="fa fa-save mr-1"></i> Update Profile
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. CHANGE PASSWORD --}}
            <div class="card overflow-hidden">
                <button @click="openSection = openSection === 'password' ? null : 'password'"
                        class="w-full px-6 py-4 flex items-center justify-between transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent);">
                            <i class="fa fa-lock" style="color: var(--accent);"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-bold" style="color: var(--text-primary);">Change Password</h3>
                            <p class="text-xs" style="color: var(--text-muted);">Keep your account secure</p>
                        </div>
                    </div>
                    <i class="fa fa-chevron-down transition-transform duration-300"
                       style="color: var(--text-muted);"
                       :class="openSection === 'password' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSection === 'password'" x-collapse class="px-6 pb-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Current Password</label>
                        <input type="password" wire:model="current_password" class="w-full text-sm rounded-xl px-3 py-2">
                        @error('current_password') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">New Password</label>
                        <input type="password" wire:model="new_password" class="w-full text-sm rounded-xl px-3 py-2">
                        @error('new_password') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Confirm New Password</label>
                        <input type="password" wire:model="new_password_confirmation" class="w-full text-sm rounded-xl px-3 py-2">
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="updatePassword"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition"
                                style="background-color: var(--accent); color: var(--bg-primary);">
                            <i class="fa fa-key mr-1"></i> Change Password
                        </button>
                    </div>
                </div>
            </div>

            {{-- 3. BRANCH SETTINGS --}}
            <div class="card overflow-hidden">
                <button @click="openSection = openSection === 'branch' ? null : 'branch'"
                        class="w-full px-6 py-4 flex items-center justify-between transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent);">
                            <i class="fa fa-store" style="color: var(--accent);"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-bold" style="color: var(--text-primary);">Branch Settings</h3>
                            <p class="text-xs" style="color: var(--text-muted);">Update your branch information</p>
                        </div>
                    </div>
                    <i class="fa fa-chevron-down transition-transform duration-300"
                       style="color: var(--text-muted);"
                       :class="openSection === 'branch' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSection === 'branch'" x-collapse class="px-6 pb-6 space-y-6">

                    {{-- BRANCH LOGO --}}
                    <div class="p-5 rounded-2xl" style="background-color: color-mix(in srgb, var(--accent) 5%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa fa-image" style="color: var(--accent);"></i>
                            <h4 class="font-bold text-sm" style="color: var(--text-primary);">Branch Logo</h4>
                            <span class="text-[10px] font-normal" style="color: var(--text-muted);">(shown in customer views)</span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                            <div class="relative flex-shrink-0">
                                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl flex items-center justify-center overflow-hidden"
                                     style="background-color: var(--bg-elevated); border: 2px solid var(--border-soft);">
                                    @if($new_logo)
                                        <img src="{{ $new_logo->temporaryUrl() }}" class="w-full h-full object-cover" alt="New logo preview">
                                    @elseif($current_logo)
                                        <img src="{{ Storage::url($current_logo) }}" class="w-full h-full object-cover" alt="{{ $branch_name }} logo">
                                    @else
                                        <div class="text-center">
                                            <i class="fa fa-store text-3xl block mb-1" style="color: color-mix(in srgb, var(--accent) 40%, transparent);"></i>
                                            <span class="text-[9px] uppercase font-bold" style="color: var(--text-muted);">No Logo</span>
                                        </div>
                                    @endif
                                </div>
                                @if($new_logo)
                                    <span class="absolute -top-2 -right-2 text-[8px] font-bold px-2 py-0.5 rounded-full"
                                          style="background-color: var(--ok); color: var(--bg-primary); border: 2px solid var(--bg-elevated);">NEW</span>
                                @endif
                            </div>

                            <div class="flex-grow space-y-3 w-full">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color: var(--text-muted);">
                                        Upload Logo
                                        <span class="font-normal normal-case">(JPG, PNG, WEBP, SVG · Max 2 MB)</span>
                                    </label>
                                    <input type="file"
                                           wire:model="new_logo"
                                           accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                           class="w-full text-xs rounded-xl px-3 py-2">
                                    @error('new_logo') <span class="text-[10px] block mt-1" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>

                                <div wire:loading wire:target="new_logo" class="text-[10px] flex items-center gap-1.5" style="color: var(--accent);">
                                    <i class="fa fa-spinner fa-spin"></i> Uploading...
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if($new_logo)
                                        <button wire:click="updateLogo"
                                                wire:loading.attr="disabled"
                                                wire:target="updateLogo"
                                                class="px-3 py-2 text-xs font-bold rounded-xl transition disabled:opacity-50 flex items-center gap-1.5"
                                                style="background-color: var(--accent); color: var(--bg-primary);">
                                            <i class="fa fa-check"></i> Save Logo
                                        </button>
                                        <button wire:click="$set('new_logo', null)"
                                                class="px-3 py-2 text-xs font-bold rounded-xl transition"
                                                style="background-color: var(--bg-inner); color: var(--text-muted);">
                                            <i class="fa fa-times mr-1"></i> Cancel
                                        </button>
                                    @elseif($current_logo)
                                        <button wire:click="removeLogo"
                                                wire:confirm="Remove your branch logo? The shop's logo will be used instead."
                                                class="px-3 py-2 text-xs font-bold rounded-xl transition flex items-center gap-1.5"
                                                style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 20%, transparent);">
                                            <i class="fa fa-trash"></i> Remove Logo
                                        </button>
                                    @endif
                                </div>

                                <p class="text-[10px] flex items-start gap-1.5" style="color: var(--text-muted);">
                                    <i class="fa fa-info-circle mt-0.5 flex-shrink-0"></i>
                                    <span>Square logos work best (1:1 ratio). Recommended: <strong>400×400px</strong>. If not set, the shop's logo will be used instead.</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Branch Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Branch Name</label>
                            <input type="text" wire:model="branch_name" class="w-full text-sm rounded-xl px-3 py-2">
                            @error('branch_name') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Branch Email</label>
                            <input type="email" wire:model="branch_email" class="w-full text-sm rounded-xl px-3 py-2">
                            @error('branch_email') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Contact Number</label>
                        <input type="text" wire:model="branch_contact" placeholder="+63 912 345 6789" class="w-full text-sm rounded-xl px-3 py-2">
                        @error('branch_contact') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Street Address</label>
                        <input type="text" wire:model="branch_address" placeholder="123 Main St" class="w-full text-sm rounded-xl px-3 py-2">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Barangay</label>
                            <input type="text" wire:model="branch_barangay" class="w-full text-sm rounded-xl px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">City</label>
                            <input type="text" wire:model="branch_city" class="w-full text-sm rounded-xl px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Province</label>
                            <input type="text" wire:model="branch_province" class="w-full text-sm rounded-xl px-3 py-2">
                        </div>
                    </div>

                    {{-- OPERATING HOURS --}}
                    <div class="p-5 rounded-2xl" style="background-color: color-mix(in srgb, var(--accent) 5%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i class="fa fa-clock" style="color: var(--accent);"></i>
                                <h4 class="font-bold text-sm" style="color: var(--text-primary);">Operating Hours</h4>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <span class="text-[10px] font-bold uppercase" style="color: var(--text-muted);">Open 24/7</span>
                                <div class="relative">
                                    <input type="checkbox" wire:model.live="is_open_24h" class="sr-only peer">
                                    <div class="w-9 h-5 rounded-full peer transition-all after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"
                                         style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);"
                                         onmouseover="this.style.backgroundColor='var(--bg-inner)'"></div>
                                    <style>
                                        .peer:checked ~ div { background-color: var(--accent) !important; }
                                        .peer:checked ~ div::after { transform: translateX(100%); }
                                    </style>
                                </div>
                            </label>
                        </div>

                        <div class="space-y-4" style="{{ $is_open_24h ? 'opacity:0.5; pointer-events:none;' : '' }}">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider mb-2" style="color: var(--text-muted);">Operating Days</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                        <button type="button"
                                                wire:click="$toggle('open_days', '{{ $day }}')"
                                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors"
                                                style="{{ in_array($day, $open_days)
                                                    ? 'background-color: var(--accent); color: var(--bg-primary); border: 1px solid var(--accent);'
                                                    : 'background-color: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">
                                            {{ $day }}
                                        </button>
                                    @endforeach
                                </div>
                                @error('open_days') <span class="text-[10px] block mt-1" style="color: var(--bad);">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Opening Time</label>
                                    <input type="time" wire:model="open_time" class="w-full text-sm rounded-xl px-3 py-2">
                                    @error('open_time') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Closing Time</label>
                                    <input type="time" wire:model="close_time" class="w-full text-sm rounded-xl px-3 py-2">
                                    @error('close_time') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] mt-3 flex items-start gap-1.5" style="color: var(--text-muted);">
                            <i class="fa fa-info-circle mt-0.5 flex-shrink-0"></i>
                            <span>These hours will be displayed to customers on your branch page.</span>
                        </p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button wire:click="updateBranch"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition"
                                style="background-color: var(--accent); color: var(--bg-primary);">
                            <i class="fa fa-save mr-1"></i> Update Branch
                        </button>
                    </div>
                </div>
            </div>

            {{-- 4. NOTIFICATION PREFERENCES --}}
            <div class="card overflow-hidden">
                <button @click="openSection = openSection === 'notifications' ? null : 'notifications'"
                        class="w-full px-6 py-4 flex items-center justify-between transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent);">
                            <i class="fa fa-bell" style="color: var(--accent);"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-bold" style="color: var(--text-primary);">Notification Preferences</h3>
                            <p class="text-xs" style="color: var(--text-muted);">Choose what notifications you receive</p>
                        </div>
                    </div>
                    <i class="fa fa-chevron-down transition-transform duration-300"
                       style="color: var(--text-muted);"
                       :class="openSection === 'notifications' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSection === 'notifications'" x-collapse class="px-6 pb-6 space-y-3">
                    @foreach([
                        ['model' => 'notify_orders', 'title' => 'New Orders', 'desc' => 'Get notified when a new order is placed'],
                        ['model' => 'notify_low_stock', 'title' => 'Low Stock Alerts', 'desc' => 'Get notified when stock is running low'],
                        ['model' => 'notify_reviews', 'title' => 'New Reviews', 'desc' => 'Get notified when customers leave reviews'],
                        ['model' => 'notify_staff', 'title' => 'Staff Updates', 'desc' => 'Get notified about staff changes'],
                    ] as $item)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium" style="color: var(--text-primary);">{{ $item['title'] }}</p>
                                <p class="text-xs" style="color: var(--text-muted);">{{ $item['desc'] }}</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="{{ $item['model'] }}" class="sr-only peer">
                                <div class="w-11 h-6 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all"
                                     style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);"></div>
                                <style>
                                    input.peer:checked ~ div { background-color: var(--accent) !important; }
                                </style>
                            </label>
                        </div>
                    @endforeach

                    <div class="flex justify-end pt-2">
                        <button wire:click="updateNotifications"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition"
                                style="background-color: var(--accent); color: var(--bg-primary);">
                            <i class="fa fa-save mr-1"></i> Save Preferences
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>