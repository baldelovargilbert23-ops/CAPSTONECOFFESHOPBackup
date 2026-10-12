<div x-data="{ openSection: 'profile' }">
    @once
    <style>
        :root {
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
        .dark {
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
        body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }

        .settings-input {
            width: 100%;
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
            font-size: 0.875rem;
            border-radius: 0.75rem;
            padding: 0.5rem 0.75rem;
            outline: none;
            transition: all 0.15s;
        }
        .settings-input::placeholder { color: var(--text-muted); }
        .settings-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
        .settings-input[type="file"] {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
        }
        .settings-input[type="file"]::file-selector-button {
            margin-right: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            border: none;
            background-color: color-mix(in srgb, var(--accent) 20%, transparent);
            color: var(--accent);
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .settings-btn-primary {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 0.75rem;
            background-color: var(--accent);
            color: var(--bg-primary);
            transition: all 0.15s;
            cursor: pointer;
            border: none;
        }
        .settings-btn-primary:hover { opacity: 0.9; }

        .settings-btn-secondary {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 0.75rem;
            background-color: var(--bg-inner);
            color: var(--text-muted);
            transition: all 0.15s;
            cursor: pointer;
            border: none;
        }
        .settings-btn-secondary:hover { color: var(--text-primary); }

        .settings-btn-danger {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 0.75rem;
            background-color: color-mix(in srgb, var(--bad) 10%, transparent);
            color: var(--bad);
            border: 1px solid color-mix(in srgb, var(--bad) 20%, transparent);
            transition: all 0.15s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        .settings-btn-danger:hover { background-color: color-mix(in srgb, var(--bad) 20%, transparent); }
    </style>
    @endonce

    <main class="relative min-h-screen font-sans p-4 sm:p-6 space-y-6 transition-colors duration-300"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <div class="max-w-4xl mx-auto space-y-6">

            {{-- HEADER --}}
            <div class="flex items-center gap-2 mb-6">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background-color: var(--forest); color: var(--bg-primary);">
                    <i class="fa fa-gear text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--text-primary);">Settings</h1>
                    <p class="text-sm" style="color: var(--text-muted);">Manage your account and shop preferences</p>
                </div>
            </div>

            {{-- FLASH MESSAGES --}}
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     class="px-4 py-3 rounded-xl flex items-center justify-between"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok);">
                    <span><i class="fa fa-check-circle mr-2"></i> {{ session('success') }}</span>
                    <button @click="show = false">×</button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="px-4 py-3 rounded-xl flex items-center justify-between"
                     style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); color: var(--bad);">
                    <span><i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}</span>
                    <button @click="show = false">×</button>
                </div>
            @endif

            {{-- 1. PROFILE SETTINGS --}}
            <div class="settings-card" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; overflow: hidden;">
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
                            <input type="text" wire:model="name" class="settings-input">
                            @error('name') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Email Address</label>
                            <input type="email" wire:model="email" class="settings-input">
                            @error('email') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Phone Number</label>
                        <input type="text" wire:model="phone" placeholder="+63 912 345 6789" class="settings-input">
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="updateProfile" class="settings-btn-primary">
                            <i class="fa fa-save mr-1"></i> Update Profile
                        </button>
                    </div>
                </div>
            </div>

            {{-- 2. CHANGE PASSWORD --}}
            <div class="settings-card" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; overflow: hidden;">
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
                        <input type="password" wire:model="current_password" class="settings-input">
                        @error('current_password') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">New Password</label>
                        <input type="password" wire:model="new_password" class="settings-input">
                        @error('new_password') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Confirm New Password</label>
                        <input type="password" wire:model="new_password_confirmation" class="settings-input">
                    </div>
                    <div class="flex justify-end">
                        <button wire:click="updatePassword" class="settings-btn-primary">
                            <i class="fa fa-key mr-1"></i> Change Password
                        </button>
                    </div>
                </div>
            </div>

            {{-- 3. SHOP SETTINGS --}}
            <div class="settings-card" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; overflow: hidden;">
                <button @click="openSection = openSection === 'shop' ? null : 'shop'"
                        class="w-full px-6 py-4 flex items-center justify-between transition-all duration-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent);">
                            <i class="fa fa-store" style="color: var(--accent);"></i>
                        </div>
                        <div class="text-left">
                            <h3 class="font-bold" style="color: var(--text-primary);">Shop Settings</h3>
                            <p class="text-xs" style="color: var(--text-muted);">Update your shop information, branding & story</p>
                        </div>
                    </div>
                    <i class="fa fa-chevron-down transition-transform duration-300"
                       style="color: var(--text-muted);"
                       :class="openSection === 'shop' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSection === 'shop'" x-collapse class="px-6 pb-6 space-y-5">

                    {{-- SHOP LOGO --}}
                    <div class="p-5 rounded-2xl" style="background-color: color-mix(in srgb, var(--accent) 5%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa fa-image" style="color: var(--accent);"></i>
                            <h4 class="font-bold text-sm" style="color: var(--text-primary);">Shop Logo</h4>
                            <span class="text-[10px] font-normal" style="color: var(--text-muted);">(shown in customer views)</span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                            <div class="relative flex-shrink-0">
                                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl flex items-center justify-center overflow-hidden"
                                     style="background-color: var(--bg-elevated); border: 2px solid var(--border-soft);">
                                    @if($new_logo)
                                        <img src="{{ $new_logo->temporaryUrl() }}" class="w-full h-full object-cover" alt="New logo preview">
                                    @elseif($current_logo)
                                        <img src="{{ Storage::url($current_logo) }}" class="w-full h-full object-cover" alt="{{ $shop_name }} logo">
                                    @else
                                        <div class="text-center">
                                            <i class="fa fa-store text-3xl block mb-1" style="color: color-mix(in srgb, var(--accent) 40%, transparent);"></i>
                                            <span class="text-[9px] uppercase font-bold" style="color: var(--text-muted);">No Logo</span>
                                        </div>
                                    @endif
                                </div>
                                @if($new_logo)
                                    <span class="absolute -top-2 -right-2 text-[8px] font-bold px-2 py-0.5 rounded-full"
                                          style="background-color: var(--ok); color: var(--bg-primary); border: 2px solid var(--bg-elevated);">
                                        NEW
                                    </span>
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
                                           class="settings-input text-xs">
                                    @error('new_logo') <span class="text-[10px] block mt-1" style="color: var(--bad);">{{ $message }}</span> @enderror
                                </div>

                                <div wire:loading wire:target="new_logo" class="text-[10px] flex items-center gap-1.5" style="color: var(--accent);">
                                    <i class="fa fa-spinner fa-spin"></i> Uploading...
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if($new_logo)
                                        <button wire:click="updateLogo" wire:loading.attr="disabled" wire:target="updateLogo"
                                                class="settings-btn-primary disabled:opacity-50 flex items-center gap-1.5">
                                            <i class="fa fa-check"></i> Save Logo
                                        </button>
                                        <button wire:click="$set('new_logo', null)" class="settings-btn-secondary">
                                            <i class="fa fa-times mr-1"></i> Cancel
                                        </button>
                                    @elseif($current_logo)
                                        <button wire:click="removeLogo" wire:confirm="Remove your shop logo?" class="settings-btn-danger">
                                            <i class="fa fa-trash"></i> Remove Logo
                                        </button>
                                    @endif
                                </div>

                                <p class="text-[10px] flex items-start gap-1.5" style="color: var(--text-muted);">
                                    <i class="fa fa-info-circle mt-0.5 flex-shrink-0" style="color: color-mix(in srgb, var(--accent) 60%, transparent);"></i>
                                    <span>Square logos work best (1:1 ratio). Recommended size: <strong>400×400px</strong>.</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- COVER PHOTO --}}
                    <div class="p-5 rounded-2xl" style="background-color: color-mix(in srgb, var(--accent) 5%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fa fa-panorama" style="color: var(--accent);"></i>
                            <h4 class="font-bold text-sm" style="color: var(--text-primary);">Cover Photo</h4>
                            <span class="text-[10px] font-normal" style="color: var(--text-muted);">(hero banner on your shop page)</span>
                        </div>

                        <div class="space-y-4">
                            <div class="relative w-full aspect-[16/6] rounded-2xl overflow-hidden"
                                 style="border: 2px solid var(--border-soft); background-color: var(--bg-elevated);">
                                @if($new_cover)
                                    <img src="{{ $new_cover->temporaryUrl() }}" class="w-full h-full object-cover" alt="New cover preview">
                                @elseif($current_cover)
                                    <img src="{{ Storage::url($current_cover) }}" class="w-full h-full object-cover" alt="Current cover">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center" style="background-color: var(--bg-inner);">
                                        <div class="text-center">
                                            <i class="fa fa-image text-3xl block mb-1" style="color: color-mix(in srgb, var(--accent) 40%, transparent);"></i>
                                            <span class="text-[10px] uppercase font-bold" style="color: var(--text-muted);">No Cover Photo</span>
                                            <p class="text-[9px] mt-1" style="color: var(--text-muted);">The shop page will use a dark gradient instead</p>
                                        </div>
                                    </div>
                                @endif

                                @if($new_cover)
                                    <span class="absolute top-3 right-3 text-[9px] font-bold px-2 py-1 rounded-full shadow"
                                          style="background-color: var(--ok); color: var(--bg-primary);">
                                        NEW PREVIEW
                                    </span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider mb-1.5" style="color: var(--text-muted);">
                                    Upload Cover
                                    <span class="font-normal normal-case">(JPG, PNG, WEBP · Max 4 MB · 16:6 ratio)</span>
                                </label>
                                <input type="file"
                                       wire:model="new_cover"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="settings-input text-xs">
                                @error('new_cover') <span class="text-[10px] block mt-1" style="color: var(--bad);">{{ $message }}</span> @enderror
                            </div>

                            <div wire:loading wire:target="new_cover" class="text-[10px] flex items-center gap-1.5" style="color: var(--accent);">
                                <i class="fa fa-spinner fa-spin"></i> Uploading cover...
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if($new_cover)
                                    <button wire:click="updateCover" wire:loading.attr="disabled" wire:target="updateCover"
                                            class="settings-btn-primary disabled:opacity-50 flex items-center gap-1.5">
                                        <i class="fa fa-check"></i> Save Cover
                                    </button>
                                    <button wire:click="$set('new_cover', null)" class="settings-btn-secondary">
                                        <i class="fa fa-times mr-1"></i> Cancel
                                    </button>
                                @elseif($current_cover)
                                    <button wire:click="removeCover" wire:confirm="Remove your cover photo?" class="settings-btn-danger">
                                        <i class="fa fa-trash"></i> Remove Cover
                                    </button>
                                @endif
                            </div>

                            <p class="text-[10px] flex items-start gap-1.5" style="color: var(--text-muted);">
                                <i class="fa fa-info-circle mt-0.5 flex-shrink-0" style="color: color-mix(in srgb, var(--accent) 60%, transparent);"></i>
                                <span>Use a wide landscape image. Recommended size: <strong>1600×600px</strong>. It becomes the hero banner of your shop page.</span>
                            </p>
                        </div>
                    </div>

                    {{-- SHOP NAME --}}
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Shop Name</label>
                        <input type="text" wire:model="shop_name" class="settings-input">
                        @error('shop_name') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    {{-- SHOP ADDRESS --}}
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Shop Address</label>
                        <input type="text" wire:model="shop_address" placeholder="123 Main St, City, Country" class="settings-input">
                        @error('shop_address') <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span> @enderror
                    </div>

                    {{-- TAGLINE --}}
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">
                            Tagline
                            <span class="normal-case font-normal">(short line under your shop name)</span>
                        </label>
                        <input type="text" wire:model="shop_tagline" maxlength="200"
                               placeholder="e.g. Handcrafted brews, warm hospitality"
                               class="settings-input">
                        <div class="flex justify-between mt-1">
                            @error('shop_tagline')
                                <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span>
                            @else
                                <span class="text-[10px]" style="color: var(--text-muted);">Shows in the hero of your shop page</span>
                            @enderror
                            <span class="text-[10px]" style="color: var(--text-muted);">{{ strlen($shop_tagline ?? '') }}/200</span>
                        </div>
                    </div>

                    {{-- INTRODUCTION --}}
                    <div>
                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">
                            Introduction / Our Story
                            <span class="normal-case font-normal">(longer description)</span>
                        </label>
                        <textarea wire:model="shop_introduction" rows="5" maxlength="2000"
                                  placeholder="Tell customers about your shop — the story, the vibe, what makes you special..."
                                  class="settings-input resize-none"></textarea>
                        <div class="flex justify-between mt-1">
                            @error('shop_introduction')
                                <span class="text-[10px]" style="color: var(--bad);">{{ $message }}</span>
                            @else
                                <span class="text-[10px]" style="color: var(--text-muted);">Shows in the "Our Story" section</span>
                            @enderror
                            <span class="text-[10px]" style="color: var(--text-muted);">{{ strlen($shop_introduction ?? '') }}/2000</span>
                        </div>
                    </div>

                    {{-- SAVE --}}
                    <div class="flex justify-end pt-2" style="border-top: 1px solid var(--border-soft);">
                        <button wire:click="updateShop" wire:loading.attr="disabled" wire:target="updateShop"
                                class="settings-btn-primary disabled:opacity-50 flex items-center gap-2">
                            <span wire:loading.remove wire:target="updateShop">
                                <i class="fa fa-save mr-1"></i> Save Shop Settings
                            </span>
                            <span wire:loading wire:target="updateShop">
                                <i class="fa fa-spinner fa-spin mr-1"></i> Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 4. NOTIFICATION PREFERENCES --}}
            <div class="settings-card" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; overflow: hidden;">
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
                        ['model' => 'notify_orders',    'title' => 'New Orders',        'desc' => 'Get notified when a new order is placed'],
                        ['model' => 'notify_low_stock', 'title' => 'Low Stock Alerts',  'desc' => 'Get notified when stock is running low'],
                        ['model' => 'notify_reviews',   'title' => 'New Reviews',       'desc' => 'Get notified when customers leave reviews'],
                        ['model' => 'notify_staff',     'title' => 'Staff Updates',     'desc' => 'Get notified about staff changes'],
                    ] as $item)
                        <div class="flex items-center justify-between" wire:key="notify-{{ $item['model'] }}">
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
                        <button wire:click="updateNotifications" wire:loading.attr="disabled" wire:target="updateNotifications"
                                class="settings-btn-primary disabled:opacity-50">
                            <i class="fa fa-spinner fa-spin mr-1" wire:loading wire:target="updateNotifications"></i>
                            <i class="fa fa-save mr-1" wire:loading.remove wire:target="updateNotifications"></i>
                            Save Preferences
                        </button>
                    </div>
                </div>
            </div>

            {{-- 5. DANGER ZONE --}}
            <div class="rounded-2xl overflow-hidden" style="border: 2px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                <div class="px-6 py-4" style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border-bottom: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                    <h2 class="font-bold flex items-center gap-2" style="color: var(--bad);">
                        <i class="fa fa-triangle-exclamation"></i> Danger Zone
                    </h2>
                    <p class="text-xs" style="color: color-mix(in srgb, var(--bad) 70%, transparent);">Actions here cannot be undone</p>
                </div>
                <div class="p-6 space-y-4" style="background-color: var(--bg-elevated);">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--text-primary);">Delete All Data</p>
                            <p class="text-xs" style="color: var(--text-muted);">Permanently remove all shop data (orders, products, reviews)</p>
                        </div>
                        <button wire:confirm="Are you sure? This cannot be undone!" wire:click="deleteAllData"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition whitespace-nowrap"
                                style="background-color: var(--bad); color: var(--bg-primary);">
                            <i class="fa fa-trash mr-1"></i> Delete All
                        </button>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4" style="border-top: 1px solid var(--border-soft);">
                        <div>
                            <p class="text-sm font-medium" style="color: var(--text-primary);">Deactivate Shop</p>
                            <p class="text-xs" style="color: var(--text-muted);">Temporarily disable your shop (can be reactivated)</p>
                        </div>
                        <button wire:confirm="Are you sure you want to deactivate?" wire:click="deactivateShop"
                                class="px-4 py-2 text-xs font-bold rounded-xl transition whitespace-nowrap"
                                style="background-color: var(--warn); color: var(--bg-primary);">
                            <i class="fa fa-pause mr-1"></i> Deactivate
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>