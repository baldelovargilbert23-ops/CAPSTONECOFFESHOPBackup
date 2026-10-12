<div>

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
        .admin-root { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .admin-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
        .admin-root input {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .admin-root input::placeholder { color: var(--text-muted); }
        .admin-root input:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
    </style>
    @endonce

    {{-- Auto-refresh online status every 30s — paused while modal is open --}}
    @if(!$showManageModal)
        <div wire:poll.30s="$refresh" class="hidden"></div>
    @endif

    <main class="admin-root relative min-h-screen font-sans transition-colors duration-300 overflow-x-hidden"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <section class="w-full pt-8 px-4 sm:px-6 md:px-8 pb-12 max-w-7xl mx-auto">

            {{-- ============================================================
                 PAGE HEADER
                 ============================================================ --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: var(--forest); color: var(--bg-primary);">
                            <i class="fa fa-users text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black" style="color: var(--text-primary);">Customer Directory</h1>
                            <p class="text-sm" style="color: var(--text-muted);">Manage global customer accounts and real-time online status.</p>
                        </div>
                    </div>

                    {{-- Online Stats Summary --}}
                    <div class="flex items-center gap-4 mt-2 flex-wrap">
                        <div class="flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                      style="background-color: var(--ok);"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2" style="background-color: var(--ok);"></span>
                            </span>
                            <span class="text-xs font-semibold" style="color: var(--ok);">
                                {{ $onlineCount }} online now
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" style="background-color: color-mix(in srgb, var(--text-primary) 30%, transparent);"></span>
                            <span class="text-xs" style="color: var(--text-muted);">{{ $offlineCount }} offline</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <i class="fa fa-users text-[10px]" style="color: var(--accent);"></i>
                            <span class="text-xs" style="color: var(--text-muted);">{{ $customers->total() }} total</span>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search by name or email..."
                        class="pl-10 pr-4 py-2.5 rounded-xl text-sm outline-none w-full md:w-80 transition"
                    >
                </div>
            </div>

            {{-- ============================================================
                 FLASH MESSAGES
                 ============================================================ --}}
            @if (session()->has('success'))
                <div class="mb-6 p-4 rounded-xl text-sm flex items-start gap-2 shadow-sm"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok);">
                    <i class="fa fa-check-circle mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-6 p-4 rounded-xl text-sm flex items-start gap-2 shadow-sm"
                     style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); color: var(--bad);">
                    <i class="fa fa-exclamation-circle mt-0.5"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- ============================================================
                 CUSTOMERS TABLE
                 ============================================================ --}}
            <div class="card overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                            <tr>
                                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Customer Profile</th>
                                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Online Status</th>
                                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Joined Date</th>
                                <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-right" style="color: var(--text-muted);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0"
                                                 style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                                {{ strtoupper(substr($customer->name, 0, 2)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <span class="text-sm font-bold block leading-tight truncate" style="color: var(--text-primary);">
                                                    {{ $customer->name }}
                                                </span>
                                                <span class="text-xs truncate block" style="color: var(--text-muted);">
                                                    {{ $customer->email }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($customer->is_online ?? false)
                                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[10px] font-bold uppercase"
                                                  style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                <span class="relative flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: var(--ok);"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2" style="background-color: var(--ok);"></span>
                                                </span>
                                                Online
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[10px] font-bold uppercase"
                                                  style="background-color: color-mix(in srgb, var(--text-primary) 10%, transparent); color: var(--text-muted); border: 1px solid color-mix(in srgb, var(--text-primary) 20%, transparent);">
                                                <span class="w-2 h-2 rounded-full" style="background-color: color-mix(in srgb, var(--text-primary) 30%, transparent);"></span>
                                                Offline
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">
                                        {{ $customer->created_at->format('M d, Y') }}
                                        <span class="text-[10px] block" style="color: var(--text-muted);">
                                            {{ $customer->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-1.5">
                                            <button
                                                wire:click="openManageModal({{ $customer->id }})"
                                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                                style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent); color: var(--accent);">
                                                <i class="fa fa-user-cog mr-1"></i> Manage
                                            </button>
                                            <button
                                                wire:click="deleteCustomer({{ $customer->id }})"
                                                wire:confirm="Permanent action: Are you sure you want to delete this customer account?"
                                                class="p-2 transition-colors"
                                                style="color: var(--text-muted);"
                                                onmouseover="this.style.color='var(--bad)'"
                                                onmouseout="this.style.color='var(--text-muted)'">
                                                <i class="fa fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                                                 style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                                <i class="fa fa-users-slash text-3xl" style="color: var(--accent);"></i>
                                            </div>
                                            <p class="text-sm font-semibold" style="color: var(--text-muted);">
                                                @if($search)
                                                    No customers match "{{ $search }}"
                                                @else
                                                    No customers found.
                                                @endif
                                            </p>
                                            <p class="text-xs mt-1" style="color: var(--text-muted);">
                                                @if($search)
                                                    Try a different search term.
                                                @else
                                                    Customers will appear here once they register.
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4" style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                    {{ $customers->links() }}
                </div>
            </div>
        </section>
    </main>

    {{-- ============================================================
         MANAGEMENT MODAL
         ✅ admin-root on the wrapper so all CSS variables resolve.
         ✅ Solid background color explicitly set — no bleed-through.
         ============================================================ --}}
    @if($showManageModal)
        <div class="admin-root"
             wire:key="manage-customer-modal"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2147483647; display: flex; align-items: center; justify-content: center; padding: 1rem; overflow-y: auto;">

            {{-- Backdrop --}}
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0,0,0,0.75); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);"
                 wire:click="closeModal"></div>

            {{-- Card — solid background so nothing shows through --}}
            <div class="w-full max-w-md overflow-hidden shadow-2xl"
                 style="position: relative;
                        z-index: 1;
                        margin: auto;
                        background-color: var(--bg-elevated);
                        border: 1px solid color-mix(in srgb, var(--accent) 40%, transparent);
                        border-radius: 1rem;">

                {{-- Modal Header --}}
                <div class="p-6" style="border-bottom: 1px solid var(--border-soft); background-color: color-mix(in srgb, var(--accent) 5%, transparent);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 style="background-color: var(--forest); color: var(--bg-primary);">
                                <i class="fa fa-user-edit"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-black" style="color: var(--text-primary);">Manage Customer</h3>
                                <p class="text-xs truncate" style="color: var(--text-muted);">{{ $targetUserName }}</p>
                            </div>
                        </div>
                        <button wire:click="closeModal"
                                class="w-8 h-8 rounded-full flex items-center justify-center transition"
                                style="color: var(--text-muted);">
                            <i class="fa fa-times text-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 space-y-5">
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wider mb-1.5 block" style="color: var(--text-muted);">
                            <i class="fa fa-user mr-1" style="color: var(--accent);"></i> Full Name
                        </label>
                        <input wire:model="editName" type="text"
                               class="w-full rounded-xl px-4 py-3 text-sm outline-none transition">
                        @error('editName')
                            <span class="text-[10px] mt-1 block" style="color: var(--bad);">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wider mb-1.5 block" style="color: var(--text-muted);">
                            <i class="fa fa-envelope mr-1" style="color: var(--accent);"></i> Email Address
                        </label>
                        <input wire:model="editEmail" type="email"
                               class="w-full rounded-xl px-4 py-3 text-sm outline-none transition">
                        @error('editEmail')
                            <span class="text-[10px] mt-1 block" style="color: var(--bad);">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wider mb-1.5 block" style="color: var(--text-muted);">
                            <i class="fa fa-key mr-1" style="color: var(--accent);"></i> New Password (Optional)
                        </label>
                        <input wire:model="editPassword" type="text"
                               class="w-full rounded-xl px-4 py-3 text-sm font-mono outline-none transition"
                               placeholder="Leave blank to keep current password">
                        <p class="text-[10px] mt-1" style="color: var(--text-muted);">Min. 8 characters if you want to change</p>
                        @error('editPassword')
                            <span class="text-[10px] mt-1 block" style="color: var(--bad);">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-4 flex gap-3" style="background-color: var(--bg-inner); border-top: 1px solid var(--border-soft);">
                    <button wire:click="closeModal"
                            class="flex-1 px-4 py-2.5 rounded-xl font-bold transition"
                            style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border-soft);">
                        Cancel
                    </button>
                    <button wire:click="updateCustomer"
                            wire:loading.attr="disabled"
                            wire:target="updateCustomer"
                            class="flex-1 px-4 py-2.5 rounded-xl font-bold transition disabled:opacity-50"
                            style="background-color: var(--accent); color: var(--bg-primary);">
                        <i class="fa fa-save mr-1" wire:loading.remove wire:target="updateCustomer"></i>
                        <i class="fa fa-spinner fa-spin mr-1" wire:loading wire:target="updateCustomer"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>