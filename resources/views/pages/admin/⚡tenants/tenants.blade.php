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
        .admin-root .inner {
            background-color: var(--bg-inner);
            border: 1px solid var(--border-soft);
            border-radius: 0.75rem;
        }
        .admin-root input, .admin-root select, .admin-root textarea {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .admin-root input::placeholder, .admin-root textarea::placeholder { color: var(--text-muted); }
        .admin-root input:focus, .admin-root select:focus, .admin-root textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
    </style>
    @endonce

    <main class="admin-root relative min-h-screen font-sans transition-colors duration-300 overflow-x-hidden"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <section class="w-full pt-8 px-4 sm:px-6 md:px-8 pb-12 max-w-7xl mx-auto">

            {{-- ============================================================
                 HEADER
                 ============================================================ --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background-color: var(--accent);">
                        <i class="fa fa-check-double text-lg" style="color: var(--bg-primary);"></i>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-2xl font-black" style="color: var(--text-primary);">Approval Center</h1>
                        <p class="text-sm" style="color: var(--text-muted);">Review and manage tenant & branch applications</p>
                    </div>
                </div>
                <div class="inline-flex items-center gap-2 sm:gap-3 mt-2 flex-wrap">
                    <span class="px-3 py-1.5 rounded-full text-xs"
                          style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                        <i class="fa-solid fa-clock mr-1" style="color: var(--warn);"></i>
                        {{ $stats['tenants']['pending'] + $stats['branches']['pending'] }} Pending
                    </span>
                    <span class="px-3 py-1.5 rounded-full text-xs"
                          style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                        <i class="fa-solid fa-check-circle mr-1" style="color: var(--ok);"></i>
                        {{ $stats['tenants']['approved'] + $stats['branches']['approved'] }} Approved
                    </span>
                    <span class="px-3 py-1.5 rounded-full text-xs"
                          style="background-color: var(--bg-elevated); color: var(--text-primary); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                        <i class="fa-solid fa-times-circle mr-1" style="color: var(--bad);"></i>
                        {{ $stats['tenants']['rejected'] + $stats['branches']['rejected'] }} Rejected
                    </span>
                </div>
            </div>

            {{-- ============================================================
                 FLASH MESSAGES
                 ============================================================ --}}
            @if(session('success'))
                <div class="mb-4 p-4 text-sm rounded-xl flex items-start gap-2"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                    <i class="fa fa-check-circle mt-0.5"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 text-sm rounded-xl flex items-start gap-2"
                     style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                    <i class="fa fa-exclamation-circle mt-0.5"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 p-4 text-sm rounded-xl flex items-start gap-2"
                     style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                    <i class="fa fa-triangle-exclamation mt-0.5"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            {{-- ============================================================
                 TABS
                 ============================================================ --}}
            <div class="card p-1 mb-6 overflow-x-auto shadow-sm">
                <div class="flex gap-1">
                    <button wire:click="switchTab('tenants')"
                            class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold rounded-lg transition-all whitespace-nowrap"
                            style="{{ $activeTab === 'tenants'
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'color: var(--text-muted);' }}">
                        <i class="fa fa-building"></i>
                        <span class="hidden sm:inline">Tenant Approvals</span>
                        <span class="sm:hidden">Tenants</span>
                        @if($stats['tenants']['pending'] > 0)
                            <span class="ml-1 px-2 py-0.5 rounded-full text-[10px]"
                                  style="{{ $activeTab === 'tenants'
                                      ? 'background-color: rgba(0,0,0,0.2); color: var(--bg-primary);'
                                      : 'background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);' }}">
                                {{ $stats['tenants']['pending'] }}
                            </span>
                        @endif
                    </button>
                    <button wire:click="switchTab('branches')"
                            class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold rounded-lg transition-all whitespace-nowrap"
                            style="{{ $activeTab === 'branches'
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'color: var(--text-muted);' }}">
                        <i class="fa fa-store"></i>
                        <span class="hidden sm:inline">Branch Approvals</span>
                        <span class="sm:hidden">Branches</span>
                        @if($stats['branches']['pending'] > 0)
                            <span class="ml-1 px-2 py-0.5 rounded-full text-[10px]"
                                  style="{{ $activeTab === 'branches'
                                      ? 'background-color: rgba(0,0,0,0.2); color: var(--bg-primary);'
                                      : 'background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);' }}">
                                {{ $stats['branches']['pending'] }}
                            </span>
                        @endif
                    </button>
                    <button wire:click="switchTab('history')"
                            class="flex-1 min-w-[100px] flex items-center justify-center gap-2 px-3 sm:px-4 py-2.5 text-xs sm:text-sm font-bold rounded-lg transition-all whitespace-nowrap"
                            style="{{ $activeTab === 'history'
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'color: var(--text-muted);' }}">
                        <i class="fa fa-history"></i>
                        <span>History</span>
                        @if($stats['tenants']['approved'] + $stats['branches']['approved'] + $stats['tenants']['rejected'] + $stats['branches']['rejected'] > 0)
                            <span class="ml-1 px-2 py-0.5 rounded-full text-[10px]"
                                  style="{{ $activeTab === 'history'
                                      ? 'background-color: rgba(0,0,0,0.2); color: var(--bg-primary);'
                                      : 'background-color: color-mix(in srgb, var(--text-primary) 10%, transparent); color: var(--text-muted);' }}">
                                {{ $stats['tenants']['approved'] + $stats['branches']['approved'] + $stats['tenants']['rejected'] + $stats['branches']['rejected'] }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- ============================================================
                 SEARCH (only for non-history tabs)
                 ============================================================ --}}
            @if($activeTab !== 'history')
                <div class="card p-4 mb-6 shadow-sm">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               placeholder="{{ $activeTab === 'tenants' ? 'Search tenants by name or email...' : 'Search branches by name, code, or tenant...' }}"
                               class="w-full text-sm rounded-xl pl-9 pr-4 py-2.5 outline-none transition">
                    </div>
                </div>
            @endif

            {{-- ============================================================
                 TENANT APPROVALS
                 ============================================================ --}}
            @if($activeTab === 'tenants')
                <div class="card overflow-hidden shadow-sm">
                    <div class="p-5 flex justify-between items-center flex-wrap gap-3" style="border-bottom: 1px solid var(--border-soft);">
                        <h2 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                            <i class="fa fa-clock" style="color: var(--accent);"></i>
                            Pending Tenant Applications
                            @if($stats['tenants']['pending'] > 0)
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                    {{ $stats['tenants']['pending'] }}
                                </span>
                            @endif
                        </h2>
                        <span class="text-xs" style="color: var(--text-muted);">Total: {{ $stats['tenants']['total'] }}</span>
                    </div>

                    @if($pendingTenants->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left min-w-[800px]">
                                <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Tenant</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">ID</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Owner</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Email</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Requested</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-right" style="color: var(--text-muted);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingTenants as $tenant)
                                        @php $owner = $tenant->users->first(); @endphp
                                        <tr style="border-bottom: 1px solid var(--border-soft);">
                                            <td class="px-4 sm:px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black flex-shrink-0"
                                                         style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                                        {{ substr($tenant->name, 0, 1) }}
                                                    </div>
                                                    <span class="text-sm font-bold whitespace-nowrap" style="color: var(--text-primary);">{{ $tenant->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4">
                                                @if($tenant->owner_id_path)
                                                    <img src="{{ Storage::url($tenant->owner_id_path) }}"
                                                         class="w-10 h-10 rounded-lg object-cover cursor-pointer hover:scale-110 transition"
                                                         style="border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);"
                                                         onclick="window.open(this.src, '_blank')"
                                                         alt="Owner ID">
                                                @else
                                                    <span class="text-xs italic" style="color: var(--text-muted);">No ID</span>
                                                @endif
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">{{ $owner->name ?? 'N/A' }}</td>
                                            <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">{{ $owner->email ?? 'N/A' }}</td>
                                            <td class="px-4 sm:px-6 py-4 text-sm" style="color: var(--text-muted);">
                                                <div class="flex flex-col whitespace-nowrap">
                                                    <span>{{ $tenant->created_at->format('M d, Y') }}</span>
                                                    <span class="text-[10px]" style="color: var(--text-muted);">{{ $tenant->created_at->diffForHumans() }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 text-right">
                                                <div class="flex justify-end gap-2 flex-wrap">
                                                    <button wire:click="viewDetails('tenant', {{ $tenant->id }})"
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                        <i class="fa fa-eye mr-1"></i> View
                                                    </button>
                                                    <button wire:click="approveTenant({{ $tenant->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="approveTenant({{ $tenant->id }})"
                                                            wire:confirm="Approve this tenant? An email will be sent."
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50 whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                        <i class="fa fa-check mr-1"></i> Approve
                                                    </button>
                                                    <button wire:click="openRejectModal('tenant', {{ $tenant->id }})"
                                                            wire:loading.attr="disabled"
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50 whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                        <i class="fa fa-times mr-1"></i> Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-16">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                                     style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                    <i class="fa fa-check-circle text-3xl" style="color: var(--ok);"></i>
                                </div>
                                <p class="text-sm" style="color: var(--text-muted);">All caught up! No pending tenant applications.</p>
                                <p class="text-xs mt-1" style="color: var(--text-muted);">All tenants have been reviewed 🎉</p>
                            </div>
                        </div>
                    @endif

                    @if($pendingTenants->count() > 0)
                        <div class="px-6 py-3 flex justify-between items-center flex-wrap gap-2"
                             style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                            <span class="text-xs" style="color: var(--text-muted);">
                                <i class="fa-regular fa-clock mr-1"></i> {{ $pendingTenants->count() }} pending review(s)
                            </span>
                            <span class="text-xs font-semibold" style="color: var(--accent);">
                                <i class="fa-regular fa-bell mr-1"></i> Action required
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============================================================
                 BRANCH APPROVALS
                 ============================================================ --}}
            @if($activeTab === 'branches')
                <div class="card overflow-hidden shadow-sm">
                    <div class="p-5 flex justify-between items-center flex-wrap gap-3" style="border-bottom: 1px solid var(--border-soft);">
                        <h2 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                            <i class="fa fa-clock" style="color: var(--accent);"></i>
                            Pending Branch Applications
                            @if($stats['branches']['pending'] > 0)
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                    {{ $stats['branches']['pending'] }}
                                </span>
                            @endif
                        </h2>
                        <span class="text-xs" style="color: var(--text-muted);">Total: {{ $stats['branches']['total'] }}</span>
                    </div>

                    @if($pendingBranches->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left min-w-[800px]">
                                <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Branch</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Tenant</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Address</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Requested</th>
                                        <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-right" style="color: var(--text-muted);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingBranches as $branch)
                                        <tr style="border-bottom: 1px solid var(--border-soft);">
                                            <td class="px-4 sm:px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black flex-shrink-0"
                                                         style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                        {{ substr($branch->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <span class="text-sm font-bold whitespace-nowrap" style="color: var(--text-primary);">{{ $branch->name }}</span>
                                                        <p class="text-[10px] font-mono" style="color: var(--text-muted);">{{ $branch->code ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">{{ $branch->tenant->name ?? 'N/A' }}</td>
                                            <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">{{ $branch->address ?? 'N/A' }}</td>
                                            <td class="px-4 sm:px-6 py-4 text-sm" style="color: var(--text-muted);">
                                                <div class="flex flex-col whitespace-nowrap">
                                                    <span>{{ $branch->created_at->format('M d, Y') }}</span>
                                                    <span class="text-[10px]" style="color: var(--text-muted);">{{ $branch->created_at->diffForHumans() }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 text-right">
                                                <div class="flex justify-end gap-2 flex-wrap">
                                                    <button wire:click="viewDetails('branch', {{ $branch->id }})"
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                        <i class="fa fa-eye mr-1"></i> View
                                                    </button>
                                                    <button wire:click="approveBranch({{ $branch->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="approveBranch({{ $branch->id }})"
                                                            wire:confirm="Approve this branch? An email will be sent."
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50 whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                        <i class="fa fa-check mr-1"></i> Approve
                                                    </button>
                                                    <button wire:click="openRejectModal('branch', {{ $branch->id }})"
                                                            wire:loading.attr="disabled"
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50 whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                        <i class="fa fa-times mr-1"></i> Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-16">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                                     style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                    <i class="fa fa-check-circle text-3xl" style="color: var(--ok);"></i>
                                </div>
                                <p class="text-sm" style="color: var(--text-muted);">All caught up! No pending branch applications.</p>
                                <p class="text-xs mt-1" style="color: var(--text-muted);">All branches have been reviewed 🎉</p>
                            </div>
                        </div>
                    @endif

                    @if($pendingBranches->count() > 0)
                        <div class="px-6 py-3 flex justify-between items-center flex-wrap gap-2"
                             style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                            <span class="text-xs" style="color: var(--text-muted);">
                                <i class="fa-regular fa-clock mr-1"></i> {{ $pendingBranches->count() }} pending review(s)
                            </span>
                            <span class="text-xs font-semibold" style="color: var(--accent);">
                                <i class="fa-regular fa-bell mr-1"></i> Action required
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============================================================
                 HISTORY
                 ============================================================ --}}
            @if($activeTab === 'history')
                <div class="space-y-6">

                    {{-- Filters --}}
                    <div class="card p-4 shadow-sm">
                        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 sm:gap-4">
                            <div class="relative flex-1 min-w-0">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                                <input type="text"
                                       wire:model.live.debounce.300ms="historySearch"
                                       placeholder="Search history..."
                                       class="w-full text-sm rounded-xl pl-9 pr-4 py-2.5 outline-none transition">
                            </div>
                            <select wire:model.live="historyFilter"
                                    class="text-sm rounded-xl px-4 py-2.5 outline-none transition cursor-pointer sm:w-48">
                                <option value="all">All History</option>
                                <option value="approved">✅ Approved</option>
                                <option value="rejected">❌ Rejected</option>
                            </select>
                        </div>
                    </div>

                    {{-- Tenant History --}}
                    <div class="card overflow-hidden shadow-sm">
                        <div class="p-5 flex justify-between items-center flex-wrap gap-3" style="border-bottom: 1px solid var(--border-soft);">
                            <h2 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                                <i class="fa fa-building" style="color: var(--info);"></i>
                                Tenant History
                                <span class="text-xs font-normal" style="color: var(--text-muted);">(Approved & Rejected)</span>
                            </h2>
                            <span class="text-xs" style="color: var(--text-muted);">Total: {{ $historyTenants->count() }}</span>
                        </div>

                        @if($historyTenants->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left min-w-[800px]">
                                    <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                                        <tr>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Tenant</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Owner</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Status</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Date</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Reason</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-right" style="color: var(--text-muted);">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historyTenants as $tenant)
                                            @php $owner = $tenant->users->first(); @endphp
                                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                                <td class="px-4 sm:px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black flex-shrink-0"
                                                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                                            {{ substr($tenant->name, 0, 1) }}
                                                        </div>
                                                        <span class="text-sm font-bold whitespace-nowrap" style="color: var(--text-primary);">{{ $tenant->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">{{ $owner->name ?? 'N/A' }}</td>
                                                <td class="px-4 sm:px-6 py-4">
                                                    @if($tenant->status === 'active')
                                                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold whitespace-nowrap"
                                                              style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                            <i class="fa-solid fa-check-circle text-[8px]"></i> Approved
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold whitespace-nowrap"
                                                              style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                            <i class="fa-solid fa-times-circle text-[8px]"></i> Rejected
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">
                                                    {{ $tenant->approved_at ? $tenant->approved_at->format('M d, Y h:i A') : 'N/A' }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-sm max-w-xs truncate" style="color: var(--text-muted);">
                                                    {{ $tenant->rejection_reason ?? '—' }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-right">
                                                    <button wire:click="viewDetails('tenant', {{ $tenant->id }})"
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                        <i class="fa fa-eye mr-1"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                                         style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                        <i class="fa fa-history text-3xl" style="color: var(--text-muted);"></i>
                                    </div>
                                    <p class="text-sm" style="color: var(--text-muted);">No tenant history found.</p>
                                    <p class="text-xs mt-1" style="color: var(--text-muted);">Approved and rejected tenants will appear here</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Branch History --}}
                    <div class="card overflow-hidden shadow-sm">
                        <div class="p-5 flex justify-between items-center flex-wrap gap-3" style="border-bottom: 1px solid var(--border-soft);">
                            <h2 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                                <i class="fa fa-store" style="color: var(--info);"></i>
                                Branch History
                                <span class="text-xs font-normal" style="color: var(--text-muted);">(Approved & Rejected)</span>
                            </h2>
                            <span class="text-xs" style="color: var(--text-muted);">Total: {{ $historyBranches->count() }}</span>
                        </div>

                        @if($historyBranches->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-left min-w-[800px]">
                                    <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                                        <tr>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Branch</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Tenant</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Status</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Date</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Reason</th>
                                            <th class="px-4 sm:px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-right" style="color: var(--text-muted);">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historyBranches as $branch)
                                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                                <td class="px-4 sm:px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black flex-shrink-0"
                                                             style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                            {{ substr($branch->name, 0, 1) }}
                                                        </div>
                                                        <div>
                                                            <span class="text-sm font-bold whitespace-nowrap" style="color: var(--text-primary);">{{ $branch->name }}</span>
                                                            <p class="text-[10px] font-mono" style="color: var(--text-muted);">{{ $branch->code ?? 'N/A' }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">{{ $branch->tenant->name ?? 'N/A' }}</td>
                                                <td class="px-4 sm:px-6 py-4">
                                                    @if($branch->approval_status === 'approved')
                                                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold whitespace-nowrap"
                                                              style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                            <i class="fa-solid fa-check-circle text-[8px]"></i> Approved
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold whitespace-nowrap"
                                                              style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                            <i class="fa-solid fa-times-circle text-[8px]"></i> Rejected
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap" style="color: var(--text-muted);">
                                                    {{ $branch->approved_at ? $branch->approved_at->format('M d, Y h:i A') : 'N/A' }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-sm max-w-xs truncate" style="color: var(--text-muted);">
                                                    {{ $branch->rejection_reason ?? '—' }}
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-right">
                                                    <button wire:click="viewDetails('branch', {{ $branch->id }})"
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition whitespace-nowrap"
                                                            style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                        <i class="fa fa-eye mr-1"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                                         style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                        <i class="fa fa-history text-3xl" style="color: var(--text-muted);"></i>
                                    </div>
                                    <p class="text-sm" style="color: var(--text-muted);">No branch history found.</p>
                                    <p class="text-xs mt-1" style="color: var(--text-muted);">Approved and rejected branches will appear here</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        {{-- ============================================================
             REJECT MODAL
             ============================================================ --}}
        @if($showRejectModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="background-color: rgba(0,0,0,0.7);"
                 wire:click.self="closeRejectModal">
                <div class="admin-root card w-full max-w-lg shadow-2xl overflow-hidden max-h-[90vh] flex flex-col"
                     style="border-color: color-mix(in srgb, var(--bad) 30%, transparent);">

                    <div class="px-6 py-4 flex items-center justify-between flex-shrink-0"
                         style="border-bottom: 1px solid color-mix(in srgb, var(--bad) 20%, transparent);">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                 style="background-color: color-mix(in srgb, var(--bad) 20%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 40%, transparent);">
                                <i class="fa fa-times-circle" style="color: var(--bad);"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm" style="color: var(--text-primary);">Reject {{ ucfirst($rejectType) }}</h3>
                                <p class="text-[11px]" style="color: var(--text-muted);">The applicant will receive this reason by email</p>
                            </div>
                        </div>
                        <button wire:click="closeRejectModal" class="transition" style="color: var(--text-muted);">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest mb-2" style="color: var(--text-muted);">
                                Reason for rejection <span style="color: var(--bad);">*</span>
                            </label>
                            <textarea wire:model="rejectionReason"
                                      rows="5"
                                      maxlength="1000"
                                      placeholder="Explain clearly why this application is being rejected..."
                                      class="w-full text-sm rounded-xl px-4 py-3 outline-none transition resize-none"></textarea>

                            @error('rejectionReason')
                                <p class="mt-2 text-xs flex items-center gap-1" style="color: var(--bad);">
                                    <i class="fa fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror

                            <p class="mt-2 text-[10px] text-right" style="color: var(--text-muted);">
                                {{ strlen($rejectionReason) }} / 1000
                            </p>
                        </div>

                        <div class="p-3 rounded-lg flex gap-2"
                             style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                            <i class="fa fa-info-circle mt-0.5" style="color: var(--warn);"></i>
                            <p class="text-[11px] leading-relaxed" style="color: var(--text-muted);">
                                This reason will be sent <strong style="color: var(--text-primary);">directly to the applicant's email</strong> via Gmail. Be professional and specific.
                            </p>
                        </div>
                    </div>

                    <div class="px-6 py-4 flex justify-end gap-2 flex-shrink-0"
                         style="border-top: 1px solid color-mix(in srgb, var(--bad) 20%, transparent); background-color: var(--bg-inner);">
                        <button wire:click="closeRejectModal"
                                class="px-4 py-2 text-xs font-bold rounded-lg transition"
                                style="background-color: var(--bg-elevated); color: var(--text-muted);">
                            Cancel
                        </button>
                        <button wire:click="confirmReject"
                                wire:loading.attr="disabled"
                                wire:target="confirmReject"
                                class="px-4 py-2 text-xs font-bold rounded-lg transition disabled:opacity-50 flex items-center gap-2"
                                style="background-color: var(--bad); color: var(--bg-primary);">
                            <i class="fa fa-paper-plane" wire:loading.remove wire:target="confirmReject"></i>
                            <i class="fa fa-spinner fa-spin" wire:loading wire:target="confirmReject"></i>
                            Send Rejection Email
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================
             DETAILS MODAL
             ✅ The Approve/Reject footer only shows for PENDING records.
             ✅ Historical (approved/rejected) records show a status badge.
             ============================================================ --}}
        @if($showDetailsModal && $detailsRecord)
            @php
                // Determine current status of the record
                $isTenant = $detailsType === 'tenant';
                $currentStatus = $isTenant
                    ? $detailsRecord->status
                    : $detailsRecord->approval_status;
                $isPending = $currentStatus === 'pending';
            @endphp

            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="background-color: rgba(0,0,0,0.7);"
                 wire:click.self="closeDetailsModal">
                <div class="admin-root card w-full max-w-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col"
                     style="border-color: color-mix(in srgb, var(--accent) 30%, transparent);">

                    <div class="px-6 py-4 flex items-center justify-between flex-shrink-0"
                         style="border-bottom: 1px solid var(--border-soft);">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                 style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 40%, transparent);">
                                <i class="fa fa-{{ $isTenant ? 'building' : 'store' }}" style="color: var(--accent);"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm" style="color: var(--text-primary);">{{ $detailsRecord->name }}</h3>
                                <p class="text-[11px]" style="color: var(--text-muted);">{{ ucfirst($detailsType) }} application details</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            {{-- ✅ Status badge in header --}}
                            @if($currentStatus === 'active' || $currentStatus === 'approved')
                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                    <i class="fa-solid fa-check-circle text-[8px]"></i> Approved
                                </span>
                            @elseif($currentStatus === 'rejected')
                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                    <i class="fa-solid fa-times-circle text-[8px]"></i> Rejected
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                      style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                                    <i class="fa-solid fa-clock text-[8px]"></i> Pending
                                </span>
                            @endif

                            <button wire:click="closeDetailsModal" class="transition" style="color: var(--text-muted);">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 overflow-y-auto flex-1 space-y-4">
                        @if($isTenant)
                            @php $owner = $detailsRecord->users->first(); @endphp

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Business Name</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->name }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Business Type</p>
                                    <p class="text-sm" style="color: var(--text-primary);">
                                        @if($detailsRecord->business_type === 'other')
                                            Other — {{ $detailsRecord->business_type_other ?? 'N/A' }}
                                        @else
                                            {{ ucwords(str_replace('_', ' ', $detailsRecord->business_type ?? 'N/A')) }}
                                        @endif
                                    </p>
                                </div>

                                <div class="inner p-4 md:col-span-2">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Business Address</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->business_address ?? 'N/A' }}</p>
                                </div>

                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Owner Name</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->owner_name ?? $owner->name ?? 'N/A' }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Owner Phone</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->owner_phone ?? 'N/A' }}</p>
                                </div>

                                <div class="inner p-4 md:col-span-2">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Owner Email</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $owner->email ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="inner p-4" style="border-color: color-mix(in srgb, var(--accent) 30%, transparent);">
                                <p class="text-[10px] uppercase tracking-widest font-bold mb-3" style="color: var(--text-muted);">Owner Valid ID</p>

                                @if($detailsRecord->owner_id_path)
                                    @php $isPdf = \Illuminate\Support\Str::endsWith(strtolower($detailsRecord->owner_id_path), '.pdf'); @endphp

                                    @if($isPdf)
                                        <a href="{{ Storage::url($detailsRecord->owner_id_path) }}" target="_blank"
                                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition"
                                           style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                            <i class="fa fa-file-pdf"></i> Open PDF
                                        </a>
                                    @else
                                        <img src="{{ Storage::url($detailsRecord->owner_id_path) }}"
                                             alt="Owner ID"
                                             class="max-h-96 w-auto rounded-lg cursor-pointer"
                                             style="border: 1px solid var(--border-soft);"
                                             onclick="window.open(this.src, '_blank')">
                                        <p class="text-[10px] mt-2" style="color: var(--text-muted);">
                                            <i class="fa fa-info-circle mr-1"></i> Click image to view full size
                                        </p>
                                    @endif
                                @else
                                    <p class="text-xs italic" style="color: var(--text-muted);">No ID uploaded</p>
                                @endif
                            </div>
                        @else
                            {{-- BRANCH DETAILS --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Branch Name</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->name }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Branch Code</p>
                                    <p class="text-sm font-mono" style="color: var(--text-primary);">{{ $detailsRecord->code ?? 'N/A' }}</p>
                                </div>
                                <div class="inner p-4 md:col-span-2">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Street Address</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->address ?? 'N/A' }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Barangay</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->barangay ?? 'N/A' }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">City</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->city ?? 'N/A' }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Province</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->province ?? 'N/A' }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Contact Number</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->contact_number ?? 'N/A' }}</p>
                                </div>
                                <div class="inner p-4">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Tenant</p>
                                    <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->tenant->name ?? 'N/A' }}</p>
                                </div>
                            </div>

                            {{-- Business Info --}}
                            <div class="p-4 rounded-xl" style="background-color: color-mix(in srgb, var(--accent) 5%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                <p class="text-[10px] uppercase tracking-widest font-bold mb-3 flex items-center gap-2" style="color: var(--accent);">
                                    <i class="fa fa-building"></i> Business Registration Information
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Registered Business Name</p>
                                        <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->manager_name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">DTI/SEC Number</p>
                                        <p class="text-sm font-mono" style="color: var(--text-primary);">{{ $detailsRecord->dti_sec_number ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">BIR TIN</p>
                                        <p class="text-sm font-mono" style="color: var(--text-primary);">{{ $detailsRecord->bir_tin ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Mayor's Permit Number</p>
                                        <p class="text-sm font-mono" style="color: var(--text-primary);">{{ $detailsRecord->mayors_permit_number ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Business Documents --}}
                            <div class="p-4 rounded-xl" style="background-color: color-mix(in srgb, var(--info) 5%, transparent); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                <p class="text-[10px] uppercase tracking-widest font-bold mb-3 flex items-center gap-2" style="color: var(--info);">
                                    <i class="fa fa-file-contract"></i> Business Documents
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                                    @php
                                        $docs = [
                                            ['label' => "Mayor's/Business Permit", 'icon' => 'fa-file-shield', 'color' => 'var(--accent)', 'path' => 'business_permit_path'],
                                            ['label' => 'DTI/SEC Registration', 'icon' => 'fa-file-signature', 'color' => 'var(--info)', 'path' => 'dti_sec_registration_path'],
                                            ['label' => 'BIR Certificate 2303', 'icon' => 'fa-receipt', 'color' => 'var(--ok)', 'path' => 'bir_certificate_path'],
                                            ['label' => 'Barangay Clearance', 'icon' => 'fa-flag', 'color' => 'var(--violet)', 'path' => 'barangay_clearance_path'],
                                            ['label' => 'Sanitary/Health Permit', 'icon' => 'fa-hand-holding-medical', 'color' => 'var(--bad)', 'path' => 'sanitary_permit_path'],
                                            ['label' => 'Fire Safety Certificate', 'icon' => 'fa-fire-extinguisher', 'color' => 'var(--warn)', 'path' => 'fire_safety_path'],
                                        ];
                                    @endphp

                                    @foreach($docs as $doc)
                                        <div class="inner p-3">
                                            <p class="text-[10px] uppercase tracking-widest font-bold mb-2" style="color: var(--text-muted);">
                                                <i class="fa {{ $doc['icon'] }} mr-1" style="color: {{ $doc['color'] }};"></i> {{ $doc['label'] }}
                                            </p>
                                            @if($detailsRecord->{$doc['path']})
                                                @php $isPdf = \Illuminate\Support\Str::endsWith(strtolower($detailsRecord->{$doc['path']}), '.pdf'); @endphp
                                                @if($isPdf)
                                                    <a href="{{ Storage::url($detailsRecord->{$doc['path']}) }}" target="_blank"
                                                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-[11px] font-bold transition"
                                                       style="background-color: color-mix(in srgb, {{ $doc['color'] }} 10%, transparent); color: {{ $doc['color'] }}; border: 1px solid color-mix(in srgb, {{ $doc['color'] }} 30%, transparent);">
                                                        <i class="fa fa-file-pdf"></i> Open PDF
                                                    </a>
                                                @else
                                                    <img src="{{ Storage::url($detailsRecord->{$doc['path']}) }}"
                                                         class="max-h-48 w-full object-cover rounded-lg cursor-pointer"
                                                         style="border: 1px solid var(--border-soft);"
                                                         onclick="window.open(this.src, '_blank')">
                                                @endif
                                            @else
                                                <p class="text-xs italic" style="color: var(--text-muted);">Not uploaded</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Manager Info --}}
                            <div class="p-4 rounded-xl" style="background-color: color-mix(in srgb, var(--violet) 5%, transparent); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent);">
                                <p class="text-[10px] uppercase tracking-widest font-bold mb-3 flex items-center gap-2" style="color: var(--violet);">
                                    <i class="fa fa-user-tie"></i> Manager Information
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Manager Name</p>
                                        <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->manager->name ?? $detailsRecord->manager_name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] uppercase tracking-widest font-bold mb-1" style="color: var(--text-muted);">Manager Email</p>
                                        <p class="text-sm" style="color: var(--text-primary);">{{ $detailsRecord->manager->email ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Manager ID --}}
                            @if($detailsRecord->manager_id_path)
                                <div class="inner p-4" style="border-color: color-mix(in srgb, var(--accent) 30%, transparent);">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-3" style="color: var(--text-muted);">
                                        <i class="fa fa-id-card mr-1" style="color: var(--bad);"></i> Manager Valid ID
                                    </p>
                                    @php $midPdf = \Illuminate\Support\Str::endsWith(strtolower($detailsRecord->manager_id_path), '.pdf'); @endphp
                                    @if($midPdf)
                                        <a href="{{ Storage::url($detailsRecord->manager_id_path) }}" target="_blank"
                                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition"
                                           style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                            <i class="fa fa-file-pdf"></i> Open PDF
                                        </a>
                                    @else
                                        <img src="{{ Storage::url($detailsRecord->manager_id_path) }}"
                                             class="max-h-96 w-auto rounded-lg cursor-pointer"
                                             style="border: 1px solid var(--border-soft);"
                                             onclick="window.open(this.src, '_blank')">
                                    @endif
                                </div>
                            @endif

                            {{-- Location Photo --}}
                            @if($detailsRecord->location_photo_path)
                                <div class="inner p-4" style="border-color: color-mix(in srgb, var(--info) 30%, transparent);">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-3" style="color: var(--text-muted);">
                                        <i class="fa fa-camera mr-1" style="color: var(--info);"></i> Location Photo
                                    </p>
                                    <img src="{{ Storage::url($detailsRecord->location_photo_path) }}"
                                         class="max-h-96 w-auto rounded-lg cursor-pointer"
                                         style="border: 1px solid var(--border-soft);"
                                         onclick="window.open(this.src, '_blank')">
                                </div>
                            @endif

                            {{-- Legacy Permit --}}
                            @if($detailsRecord->permit_path)
                                <div class="inner p-4" style="border-color: color-mix(in srgb, var(--ok) 30%, transparent);">
                                    <p class="text-[10px] uppercase tracking-widest font-bold mb-3" style="color: var(--text-muted);">
                                        <i class="fa fa-file-shield mr-1" style="color: var(--ok);"></i> Legacy Permit
                                    </p>
                                    @php $permitPdf = \Illuminate\Support\Str::endsWith(strtolower($detailsRecord->permit_path), '.pdf'); @endphp
                                    @if($permitPdf)
                                        <a href="{{ Storage::url($detailsRecord->permit_path) }}" target="_blank"
                                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition"
                                           style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                            <i class="fa fa-file-pdf"></i> Open PDF
                                        </a>
                                    @else
                                        <img src="{{ Storage::url($detailsRecord->permit_path) }}"
                                             class="max-h-96 w-auto rounded-lg cursor-pointer"
                                             style="border: 1px solid var(--border-soft);"
                                             onclick="window.open(this.src, '_blank')">
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- ============================================================
                         FOOTER — ✅ CONDITIONAL
                         Only shows Approve/Reject for PENDING records.
                         Historical records show a status message instead.
                         ============================================================ --}}
                    <div class="px-6 py-4 flex justify-end gap-2 flex-wrap flex-shrink-0"
                         style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <button wire:click="closeDetailsModal"
                                class="px-4 py-2 text-xs font-bold rounded-lg transition"
                                style="background-color: var(--bg-elevated); color: var(--text-muted);">
                            Close
                        </button>

                        @if($isPending)
                            <button wire:click="openRejectModal('{{ $detailsType }}', {{ $detailsRecord->id }})"
                                    class="px-4 py-2 text-xs font-bold rounded-lg transition"
                                    style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                <i class="fa fa-times mr-1"></i> Reject
                            </button>
                            <button wire:click="approveAndClose('{{ $detailsType }}', {{ $detailsRecord->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="approveAndClose"
                                    class="px-4 py-2 text-xs font-bold rounded-lg transition disabled:opacity-50"
                                    style="background-color: var(--ok); color: var(--bg-primary);">
                                <i class="fa fa-check mr-1"></i> Approve
                            </button>
                        @elseif($currentStatus === 'active' || $currentStatus === 'approved')
                            <span class="px-4 py-2 text-xs font-bold rounded-lg"
                                  style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                <i class="fa fa-check-circle mr-1"></i> Already Approved
                            </span>
                        @elseif($currentStatus === 'rejected')
                            <span class="px-4 py-2 text-xs font-bold rounded-lg"
                                  style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                <i class="fa fa-times-circle mr-1"></i> Already Rejected
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </main>
</div>