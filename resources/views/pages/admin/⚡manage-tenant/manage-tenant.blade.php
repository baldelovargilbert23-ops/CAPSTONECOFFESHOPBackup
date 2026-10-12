{{-- resources/views/pages/admin/⚡manage-tenant/manage-tenant.blade.php --}}
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
        .admin-root input, .admin-root select {
            background-color: var(--bg-inner);
            color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .admin-root input::placeholder { color: var(--text-muted); }
        .admin-root input:focus, .admin-root select:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
    </style>
    @endonce

    <main class="admin-root relative min-h-screen font-sans transition-colors duration-300 overflow-x-hidden"
          style="background-color: var(--bg-primary); color: var(--text-primary);">

        {{-- ============ HEADER BAR ============ --}}
        <div class="sticky top-0 inset-x-0 z-20 backdrop-blur-sm transition-colors"
             style="background-color: color-mix(in srgb, var(--bg-elevated) 95%, transparent); border-bottom: 1px solid var(--border-soft);">
            <div class="flex items-center justify-between py-3 px-4 sm:px-6 lg:px-8 flex-wrap gap-3">
                <h1 class="text-lg font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <i class="fa-solid fa-store" style="color: var(--accent);"></i>
                    Manage Tenants & Branches
                </h1>
                <div class="flex items-center gap-2 text-xs px-3 py-1 rounded-full"
                     style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                    <i class="fa-solid fa-database"></i>
                    <span>Management Console</span>
                </div>
            </div>
        </div>

        {{-- ============ FLASH MESSAGES ============ --}}
        @if(session()->has('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 pt-4">
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     class="p-3 rounded-xl text-sm font-medium flex items-center justify-between gap-3"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                    <span><i class="fa fa-check-circle mr-2"></i>{{ session('success') }}</span>
                    <button @click="show = false" class="opacity-60 hover:opacity-100"><i class="fa fa-times text-xs"></i></button>
                </div>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 pt-4">
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                     class="p-3 rounded-xl text-sm font-medium flex items-center justify-between gap-3"
                     style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                    <span><i class="fa fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
                    <button @click="show = false" class="opacity-60 hover:opacity-100"><i class="fa fa-times text-xs"></i></button>
                </div>
            </div>
        @endif

        @if(session()->has('warning'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 pt-4">
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                     class="p-3 rounded-xl text-sm font-medium flex items-center justify-between gap-3"
                     style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                    <span><i class="fa fa-triangle-exclamation mr-2"></i>{{ session('warning') }}</span>
                    <button @click="show = false" class="opacity-60 hover:opacity-100"><i class="fa fa-times text-xs"></i></button>
                </div>
            </div>
        @endif

        <section class="w-full pt-8 px-4 sm:px-6 md:px-8 pb-12 max-w-7xl mx-auto">

            {{-- ============ QUICK METRICS ============ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">

                <div class="card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                            <i class="fa-solid fa-check-circle text-lg" style="color: var(--ok);"></i>
                        </div>
                        <div>
                            <h2 class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">Approved</h2>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">
                                {{ $stats['tenants']['approved'] + $stats['branches']['approved'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <i class="fa-solid fa-clock text-lg" style="color: var(--accent);"></i>
                        </div>
                        <div>
                            <h2 class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">Pending</h2>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">
                                {{ $stats['tenants']['pending'] + $stats['branches']['pending'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                            <i class="fa-solid fa-ban text-lg" style="color: var(--bad);"></i>
                        </div>
                        <div>
                            <h2 class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">Rejected</h2>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">
                                {{ $stats['tenants']['rejected'] + $stats['branches']['rejected'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--info) 10%, transparent); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                            <i class="fa-solid fa-building text-lg" style="color: var(--info);"></i>
                        </div>
                        <div>
                            <h2 class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">Total Tenants</h2>
                            <p class="text-2xl font-black" style="color: var(--text-primary);">{{ $stats['tenants']['total'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ TABS ============ --}}
            <div class="card p-1 mb-6 shadow-sm">
                <div class="flex gap-1">
                    <button wire:click="switchTab('tenants')"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-lg transition-all duration-300"
                            style="{{ $activeTab === 'tenants'
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'color: var(--text-muted);' }}">
                        <i class="fa fa-building"></i>
                        Tenants
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
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold rounded-lg transition-all duration-300"
                            style="{{ $activeTab === 'branches'
                                ? 'background-color: var(--accent); color: var(--bg-primary);'
                                : 'color: var(--text-muted);' }}">
                        <i class="fa fa-store"></i>
                        Branches
                        @if($stats['branches']['pending'] > 0)
                            <span class="ml-1 px-2 py-0.5 rounded-full text-[10px]"
                                  style="{{ $activeTab === 'branches'
                                      ? 'background-color: rgba(0,0,0,0.2); color: var(--bg-primary);'
                                      : 'background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);' }}">
                                {{ $stats['branches']['pending'] }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- ============ SEARCH & FILTERS ============ --}}
            <div class="card p-4 mb-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               placeholder="{{ $activeTab === 'tenants' ? 'Search tenants by name or email...' : 'Search branches by name, code, or tenant...' }}"
                               class="w-full text-sm rounded-xl pl-9 pr-4 py-2.5 outline-none transition">
                    </div>

                    @if($activeTab === 'tenants')
                        <select wire:model.live="statusFilter"
                                class="text-sm rounded-xl px-4 py-2.5 outline-none transition cursor-pointer">
                            <option value="">All Status</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="active">✅ Active</option>
                            <option value="rejected">❌ Rejected</option>
                        </select>
                    @else
                        <select wire:model.live="statusFilter"
                                class="text-sm rounded-xl px-4 py-2.5 outline-none transition cursor-pointer">
                            <option value="">All Status</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="approved">✅ Approved</option>
                            <option value="rejected">❌ Rejected</option>
                        </select>
                    @endif

                    <select wire:model.live="dateFilter"
                            class="text-sm rounded-xl px-4 py-2.5 outline-none transition cursor-pointer">
                        <option value="">All Dates</option>
                        <option value="month">📅 This Month</option>
                        <option value="year">📆 This Year</option>
                    </select>

                    @if($search || $statusFilter || $dateFilter)
                        <button wire:click="resetFilters"
                                class="px-4 py-2.5 rounded-xl font-bold transition-all flex items-center gap-2 text-sm"
                                style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                            <i class="fa-solid fa-times"></i> Clear
                        </button>
                    @endif

                    <button wire:click="export"
                            class="px-5 py-2.5 rounded-xl font-bold transition-all flex items-center gap-2"
                            style="background-color: var(--accent); color: var(--bg-primary);">
                        <i class="fa-solid fa-download"></i>
                        Export CSV
                    </button>
                </div>
            </div>

            {{-- ============ TENANT TABLE ============ --}}
            @if($activeTab === 'tenants')
                <div class="card overflow-hidden shadow-sm">
                    <div class="p-5 flex justify-between items-center flex-wrap gap-3" style="border-bottom: 1px solid var(--border-soft);">
                        <h2 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                            <i class="fa fa-building" style="color: var(--accent);"></i>
                            All Tenants
                            @if($stats['tenants']['pending'] > 0)
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                    {{ $stats['tenants']['pending'] }} Pending
                                </span>
                            @endif
                        </h2>
                        <span class="text-xs" style="color: var(--text-muted);">Showing: {{ $tenants->count() }}</span>
                    </div>

                    @if($tenants->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left min-w-[900px]">
                                <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Tenant</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Owner</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Branches</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Requested</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-right" style="color: var(--text-muted);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tenants as $tenant)
                                        @php $owner = $tenant->users->first(); @endphp
                                        <tr style="border-bottom: 1px solid var(--border-soft);">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black"
                                                         style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                                        {{ substr($tenant->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $tenant->name }}</p>
                                                        @if($tenant->code)
                                                            <p class="text-[10px] font-mono" style="color: var(--text-muted);">{{ $tenant->code }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">{{ $owner->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                      style="background-color: color-mix(in srgb, var(--info) 10%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                    <i class="fa fa-store"></i>
                                                    {{ $tenant->branches_count }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($tenant->status === 'active')
                                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                                          style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                        <i class="fa-solid fa-circle text-[8px]"></i> Active
                                                    </span>
                                                @elseif($tenant->status === 'pending')
                                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                                          style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                                                        <i class="fa-solid fa-clock text-[8px]"></i> Pending
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                                          style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                        <i class="fa-solid fa-ban text-[8px]"></i> Rejected
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">
                                                <div class="flex flex-col">
                                                    <span>{{ $tenant->created_at->format('M d, Y') }}</span>
                                                    <span class="text-[10px]" style="color: var(--text-muted);">{{ $tenant->created_at->diffForHumans() }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end gap-2 flex-wrap">
                                                    @if($tenant->status === 'pending')
                                                        <button wire:click="approveTenant({{ $tenant->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="approveTenant({{ $tenant->id }})"
                                                                wire:confirm="Approve this tenant?"
                                                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50"
                                                                style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                            <i class="fa fa-check mr-1"></i> Approve
                                                        </button>
                                                        <button wire:click="rejectTenant({{ $tenant->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="rejectTenant({{ $tenant->id }})"
                                                                wire:confirm="Are you sure you want to reject this tenant?"
                                                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50"
                                                                style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                            <i class="fa fa-times mr-1"></i> Reject
                                                        </button>
                                                    @endif
                                                    <button wire:click="showDeleteTenantModal({{ $tenant->id }})"
                                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition"
                                                            style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                        <i class="fa fa-trash mr-1"></i> Delete
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
                                    <i class="fa fa-building text-3xl" style="color: var(--text-muted);"></i>
                                </div>
                                <p class="text-sm" style="color: var(--text-muted);">
                                    @if($search || $statusFilter || $dateFilter)
                                        No tenants match your filters.
                                    @else
                                        No tenants found.
                                    @endif
                                </p>
                                @if($search || $statusFilter || $dateFilter)
                                    <button wire:click="resetFilters"
                                            class="mt-3 px-4 py-2 rounded-xl text-xs font-bold transition"
                                            style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                        <i class="fa fa-times mr-1"></i> Clear filters
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($tenants->count() > 0)
                        <div class="px-6 py-3 flex justify-between items-center"
                             style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                            <span class="text-xs" style="color: var(--text-muted);">
                                <i class="fa-regular fa-clock mr-1"></i> {{ $tenants->count() }} tenant(s) shown
                            </span>
                            <span class="text-xs font-semibold" style="color: var(--accent);">
                                <i class="fa-regular fa-bell mr-1"></i> {{ $stats['tenants']['pending'] }} pending approval
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============ BRANCH TABLE ============ --}}
            @if($activeTab === 'branches')
                <div class="card overflow-hidden shadow-sm">
                    <div class="p-5 flex justify-between items-center flex-wrap gap-3" style="border-bottom: 1px solid var(--border-soft);">
                        <h2 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                            <i class="fa fa-store" style="color: var(--accent);"></i>
                            All Branches
                            @if($stats['branches']['pending'] > 0)
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                    {{ $stats['branches']['pending'] }} Pending
                                </span>
                            @endif
                        </h2>
                        <span class="text-xs" style="color: var(--text-muted);">Showing: {{ $branches->count() }}</span>
                    </div>

                    @if($branches->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left min-w-[1000px]">
                                <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                                    <tr>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Branch</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Tenant</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Manager</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Approval</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Requested</th>
                                        <th class="px-6 py-4 text-[10px] font-bold uppercase tracking-widest text-right" style="color: var(--text-muted);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($branches as $branch)
                                        <tr style="border-bottom: 1px solid var(--border-soft);">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black"
                                                         style="background-color: color-mix(in srgb, var(--info) 20%, transparent); color: var(--info); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                                                        {{ substr($branch->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <span class="text-sm font-bold" style="color: var(--text-primary);">{{ $branch->name }}</span>
                                                        <p class="text-[10px] font-mono" style="color: var(--text-muted);">{{ $branch->code ?? 'N/A' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">{{ $branch->tenant->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                @if($branch->manager)
                                                    <span style="color: var(--text-primary);">{{ $branch->manager->name }}</span>
                                                @else
                                                    <span style="color: var(--text-muted);">Not Assigned</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                                      style="background-color: color-mix(in srgb, {{ $branch->is_active ? 'var(--ok)' : 'var(--bad)' }} 10%, transparent);
                                                             color: {{ $branch->is_active ? 'var(--ok)' : 'var(--bad)' }};
                                                             border: 1px solid color-mix(in srgb, {{ $branch->is_active ? 'var(--ok)' : 'var(--bad)' }} 30%, transparent);">
                                                    <i class="fa-solid fa-circle text-[8px]"></i>
                                                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($branch->approval_status === 'approved')
                                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                                          style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                        <i class="fa-solid fa-check-circle text-[8px]"></i> Approved
                                                    </span>
                                                @elseif($branch->approval_status === 'pending')
                                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                                          style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                                                        <i class="fa-solid fa-clock text-[8px]"></i> Pending
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold"
                                                          style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                        <i class="fa-solid fa-ban text-[8px]"></i> Rejected
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm" style="color: var(--text-muted);">
                                                <div class="flex flex-col">
                                                    <span>{{ $branch->created_at->format('M d, Y') }}</span>
                                                    <span class="text-[10px]" style="color: var(--text-muted);">{{ $branch->created_at->diffForHumans() }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end gap-2 flex-wrap">
                                                    @if($branch->approval_status === 'pending')
                                                        <button wire:click="approveBranch({{ $branch->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="approveBranch({{ $branch->id }})"
                                                                wire:confirm="Approve this branch?"
                                                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50"
                                                                style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                                                            <i class="fa fa-check mr-1"></i> Approve
                                                        </button>
                                                        <button wire:click="rejectBranch({{ $branch->id }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="rejectBranch({{ $branch->id }})"
                                                                wire:confirm="Are you sure you want to reject this branch?"
                                                                class="px-3 py-1.5 rounded-lg text-[11px] font-bold transition disabled:opacity-50"
                                                                style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); color: var(--bad); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                                            <i class="fa fa-times mr-1"></i> Reject
                                                        </button>
                                                    @endif
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
                                    <i class="fa fa-store text-3xl" style="color: var(--text-muted);"></i>
                                </div>
                                <p class="text-sm" style="color: var(--text-muted);">
                                    @if($search || $statusFilter || $dateFilter)
                                        No branches match your filters.
                                    @else
                                        No branches found.
                                    @endif
                                </p>
                                @if($search || $statusFilter || $dateFilter)
                                    <button wire:click="resetFilters"
                                            class="mt-3 px-4 py-2 rounded-xl text-xs font-bold transition"
                                            style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                        <i class="fa fa-times mr-1"></i> Clear filters
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($branches->count() > 0)
                        <div class="px-6 py-3 flex justify-between items-center"
                             style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                            <span class="text-xs" style="color: var(--text-muted);">
                                <i class="fa-regular fa-clock mr-1"></i> {{ $branches->count() }} branch(es) shown
                            </span>
                            <span class="text-xs font-semibold" style="color: var(--accent);">
                                <i class="fa-regular fa-bell mr-1"></i> {{ $stats['branches']['pending'] }} pending approval
                            </span>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </main>

    {{-- ============ DELETE TENANT MODAL ============ --}}
    @if($showDeleteModal && $tenantToDelete)
        <div class="fixed inset-0 z-[200] flex items-center justify-center p-4">
            <div class="absolute inset-0 backdrop-blur-sm" style="background-color: rgba(0,0,0,0.8);" wire:click="closeDeleteModal"></div>

            <div class="admin-root card w-full max-w-lg relative z-10 shadow-2xl"
                 style="border-color: color-mix(in srgb, var(--bad) 40%, transparent);">

                <div class="p-6" style="border-bottom: 1px solid color-mix(in srgb, var(--bad) 20%, transparent); background-color: color-mix(in srgb, var(--bad) 10%, transparent);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                                 style="background-color: color-mix(in srgb, var(--bad) 20%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                                <i class="fa-solid fa-trash text-xl" style="color: var(--bad);"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-black" style="color: var(--text-primary);">Delete Tenant</h3>
                                <p class="text-xs" style="color: var(--text-muted);">{{ $tenantToDelete->name }}</p>
                            </div>
                        </div>
                        <button wire:click="closeDeleteModal" class="transition" style="color: var(--text-muted);">
                            <i class="fa-solid fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="rounded-xl p-4"
                         style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent);">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation mt-0.5" style="color: var(--bad);"></i>
                            <div>
                                <p class="text-sm font-bold" style="color: var(--bad);">⚠️ This action cannot be undone!</p>
                                <p class="text-xs mt-1" style="color: var(--text-muted);">
                                    Deleting this tenant will permanently remove:
                                </p>
                                <ul class="text-xs mt-1 space-y-1 list-disc list-inside" style="color: var(--text-muted);">
                                    <li>{{ $tenantDeleteStats['branches'] ?? 0 }} branch(es)</li>
                                    <li>{{ $tenantDeleteStats['orders'] ?? 0 }} order(s)</li>
                                    <li>{{ $tenantDeleteStats['users'] ?? 0 }} user(s)</li>
                                    <li>All associated products, inventory, and settings</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider block mb-1" style="color: var(--text-muted);">
                            Type <span class="font-bold" style="color: var(--bad);">DELETE</span> to confirm
                        </label>
                        <input type="text"
                               wire:model="deleteConfirmation"
                               wire:keydown.enter="deleteTenant"
                               class="w-full text-sm rounded-xl px-4 py-3 outline-none transition"
                               placeholder="Type DELETE here...">
                        @error('deleteConfirmation')
                            <p class="text-xs mt-1" style="color: var(--bad);">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3 mt-4">
                        <button wire:click="closeDeleteModal"
                                class="flex-1 px-4 py-2.5 rounded-xl font-bold transition"
                                style="background-color: var(--bg-inner); color: var(--text-primary);">
                            Cancel
                        </button>
                        <button wire:click="deleteTenant"
                                wire:loading.attr="disabled"
                                wire:target="deleteTenant"
                                class="flex-1 px-4 py-2.5 rounded-xl font-bold transition disabled:opacity-50"
                                style="background-color: var(--bad); color: var(--bg-primary);">
                            <span wire:loading.remove wire:target="deleteTenant">Delete Permanently</span>
                            <span wire:loading wire:target="deleteTenant"><i class="fa fa-spinner fa-spin mr-1"></i> Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>