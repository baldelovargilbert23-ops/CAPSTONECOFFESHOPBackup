{{-- resources/views/pages/admin/⚡dashboard/dashboard.blade.php --}}
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
            --cyan: #0891b2;
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
            --cyan: #22d3ee;
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

    <main class="admin-root relative min-h-screen font-sans transition-colors duration-300 overflow-x-hidden"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <section class="w-full pt-8 px-4 sm:px-6 md:px-8 pb-12 max-w-7xl mx-auto">

            {{-- HEADER --}}
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-widest uppercase px-3 py-1 rounded-full"
                              style="color: var(--accent); background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--accent);"></span>
                            Administrator
                        </span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight" style="color: var(--text-primary);">
                        Platform Overview
                    </h1>
                    <p class="text-sm mt-1" style="color: var(--text-muted);">
                        Real-time insights across <strong style="color: var(--text-primary);">{{ $stats->totalShops }}</strong> shops and <strong style="color: var(--text-primary);">{{ $stats->totalCustomers }}</strong> customers
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs rounded-full px-4 py-2 shadow-sm"
                     style="background-color: var(--bg-elevated); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                    <i class="fa-solid fa-satellite-dish" style="color: var(--ok);"></i>
                    <span class="font-semibold" style="color: var(--text-primary);">Live feed</span>
                    <span class="w-2 h-2 rounded-full" style="background-color: var(--ok);"></span>
                </div>
            </div>

            {{-- TIME FILTER --}}
            <div class="card p-4 mb-8 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <i class="fa-regular fa-calendar-alt" style="color: var(--accent);"></i>
                        </div>
                        <div>
                            <span class="font-bold text-sm block" style="color: var(--text-primary);">Performance Timeline</span>
                            <span class="text-[11px]" style="color: var(--text-muted);">{{ $currentPeriodLabel }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach([
                            ['key' => 'daily',   'icon' => 'fa-calendar-day', 'label' => 'Today'],
                            ['key' => 'monthly', 'icon' => 'fa-calendar-alt', 'label' => 'This Month'],
                            ['key' => 'yearly',  'icon' => 'fa-calendar',     'label' => 'This Year'],
                        ] as $range)
                            <button wire:click="setTimeRange('{{ $range['key'] }}')"
                                    class="px-3 sm:px-4 py-2 rounded-full text-xs font-bold transition-all duration-300"
                                    style="{{ $activeTimeRange === $range['key']
                                        ? 'background-color: var(--accent); color: var(--bg-primary);'
                                        : 'background-color: var(--bg-inner); color: var(--text-muted); border: 1px solid var(--border-soft);' }}">
                                <i class="fa-regular {{ $range['icon'] }} mr-1"></i> {{ $range['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- KPI CARDS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">

                {{-- Platform GTV --}}
                <div class="card p-6 shadow-sm hover:-translate-y-1 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <i class="fa-solid fa-coins text-lg" style="color: var(--accent);"></i>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold flex items-center gap-1"
                              style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent);">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span>
                            LIVE
                        </span>
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: var(--text-muted);">Platform GTV</p>
                    <h2 class="text-3xl font-black mb-1" style="color: var(--text-primary);">₱{{ number_format($stats->totalRevenue, 2) }}</h2>
                    <p class="text-[11px]" style="color: var(--text-muted);">Gross transaction value</p>
                </div>

                {{-- Tenants --}}
                <div class="card p-6 shadow-sm hover:-translate-y-1 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--info) 10%, transparent); border: 1px solid color-mix(in srgb, var(--info) 30%, transparent);">
                            <i class="fa-solid fa-store text-lg" style="color: var(--info);"></i>
                        </div>
                        @if($stats->pendingCount > 0)
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                  style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                                {{ $stats->pendingCount }} PENDING
                            </span>
                        @endif
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: var(--text-muted);">Partner Tenants</p>
                    <h2 class="text-3xl font-black mb-1" style="color: var(--text-primary);">{{ number_format($stats->totalShops) }}</h2>
                    <p class="text-[11px] font-bold" style="color: var(--accent);">
                        <i class="fa-solid fa-circle-check mr-1" style="color: var(--ok);"></i>
                        {{ $stats->activeShops }} active stores
                    </p>
                </div>

                {{-- Customers --}}
                <div class="card p-6 shadow-sm hover:-translate-y-1 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--violet) 10%, transparent); border: 1px solid color-mix(in srgb, var(--violet) 30%, transparent);">
                            <i class="fa-solid fa-users text-lg" style="color: var(--violet);"></i>
                        </div>
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: var(--text-muted);">Total Customers</p>
                    <h2 class="text-3xl font-black mb-1" style="color: var(--text-primary);">{{ number_format($stats->totalCustomers) }}</h2>
                    <p class="text-[11px]" style="color: var(--text-muted);">
                        <i class="fa-solid fa-bag-shopping mr-1" style="color: var(--violet);"></i>
                        {{ number_format($stats->totalTransactions) }} orders processed
                    </p>
                </div>

                {{-- Top Rating --}}
                <div class="card p-6 shadow-sm hover:-translate-y-1 transition-all duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <i class="fa-solid fa-star text-lg" style="color: var(--accent);"></i>
                        </div>
                        @php $topRated = collect($topRatedShops)->first(); @endphp
                        @if($topRated)
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                  style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                🏆 TOP
                            </span>
                        @endif
                    </div>
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: var(--text-muted);">Highest Rated</p>
                    @if($topRated)
                        <h2 class="text-3xl font-black mb-1" style="color: var(--accent);">{{ number_format($topRated['rating'], 1) }}<span class="text-lg">★</span></h2>
                        <p class="text-[11px] truncate" style="color: var(--text-muted);">{{ $topRated['name'] }} ({{ $topRated['review_count'] }} reviews)</p>
                    @else
                        <h2 class="text-3xl font-black mb-1" style="color: var(--text-muted);">—</h2>
                        <p class="text-[11px]" style="color: var(--text-muted);">No ratings yet</p>
                    @endif
                </div>
            </div>

            {{-- CHART + LEADERBOARD --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 mb-8">

                {{-- Main Chart --}}
                <div class="lg:col-span-8 card p-4 sm:p-6 shadow-sm overflow-hidden">
                    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
                        <div>
                            <h3 class="font-black flex items-center gap-2" style="color: var(--text-primary);">
                                <i class="fa-solid fa-chart-column" style="color: var(--accent);"></i> Platform Activity
                            </h3>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Registrations and revenue breakdown</p>
                        </div>
                        <div class="flex gap-3 text-[10px] font-bold flex-wrap">
                            <span class="flex items-center gap-1.5" style="color: var(--text-muted);">
                                <span class="w-2.5 h-2.5 rounded-full" style="background-color: var(--accent);"></span> TENANTS
                            </span>
                            <span class="flex items-center gap-1.5" style="color: var(--text-muted);">
                                <span class="w-2.5 h-2.5 rounded-full" style="background-color: var(--ok);"></span> CUSTOMERS
                            </span>
                            @if($activeTimeRange !== 'yearly')
                                <span class="flex items-center gap-1.5" style="color: var(--text-muted);">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: var(--cyan);"></span> REVENUE (₱K)
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="w-full min-w-0 h-56 sm:h-72 md:h-80 relative">
                        @if($activeTimeRange === 'daily')
                            <div class="flex items-end justify-between gap-1 sm:gap-2 h-full pb-2" style="border-bottom: 1px solid var(--border-soft);">
                                @foreach($dailyChartData as $day)
                                    @php
                                        $maxTenant = max(collect($dailyChartData)->max('tenants'), 1);
                                        $maxCustomer = max(collect($dailyChartData)->max('customers'), 1);
                                        $maxRevenueK = max(collect($dailyChartData)->max('revenue_k'), 1);
                                        $tHeight = ($day['tenants'] / $maxTenant) * 80;
                                        $cHeight = ($day['customers'] / $maxCustomer) * 80;
                                        $rHeight = ($day['revenue_k'] / $maxRevenueK) * 70;
                                    @endphp
                                    <div class="flex-1 min-w-0 relative group flex items-end justify-center gap-0.5 sm:gap-1.5 h-full">
                                        <div class="w-1.5 sm:w-3 rounded-t-md transition-all duration-500 hover:scale-x-110"
                                             style="height: {{ max($tHeight, 3) }}%; background-color: var(--accent);"></div>
                                        <div class="w-1.5 sm:w-3 rounded-t-md transition-all duration-500"
                                             style="height: {{ max($cHeight, 3) }}%; background-color: var(--ok);"></div>
                                        <div class="w-1.5 sm:w-3 rounded-t-md transition-all duration-500"
                                             style="height: {{ max($rHeight, 3) }}%; background-color: var(--cyan);"></div>
                                        <div class="absolute -top-12 scale-0 group-hover:scale-100 transition-all duration-200 text-[9px] font-medium px-2 py-1.5 rounded-lg z-50 whitespace-nowrap"
                                             style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid color-mix(in srgb, var(--accent) 50%, transparent);">
                                            <strong style="color: var(--accent);">{{ $day['label'] }}</strong><br>
                                            🏪 Tenants: {{ $day['tenants'] }}<br>
                                            👤 Customers: {{ $day['customers'] }}<br>
                                            💰 Revenue: ₱{{ number_format($day['revenue_k'] * 1000) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-4 text-[8px] sm:text-[9px] font-semibold uppercase tracking-wide px-1" style="color: var(--text-muted);">
                                @foreach($dailyChartData as $day)
                                    <span class="flex-1 min-w-0 text-center truncate">{{ $day['short_label'] }}</span>
                                @endforeach
                            </div>
                        @elseif($activeTimeRange === 'monthly')
                            <div class="flex items-end justify-between gap-0.5 sm:gap-1.5 h-full pb-2" style="border-bottom: 1px solid var(--border-soft);">
                                @foreach($monthlyChartData as $month)
                                    @php
                                        $maxTenant = max(collect($monthlyChartData)->max('tenants'), 1);
                                        $maxCustomer = max(collect($monthlyChartData)->max('customers'), 1);
                                        $maxRevenueK = max(collect($monthlyChartData)->max('revenue_k'), 1);
                                        $tHeight = ($month['tenants'] / $maxTenant) * 85;
                                        $cHeight = ($month['customers'] / $maxCustomer) * 85;
                                        $rHeight = ($month['revenue_k'] / $maxRevenueK) * 75;
                                    @endphp
                                    <div class="flex-1 min-w-0 relative group flex items-end justify-center gap-0.5 sm:gap-1 h-full">
                                        <div class="w-1 sm:w-2.5 rounded-t-md transition-all duration-500"
                                             style="height: {{ max($tHeight, 3) }}%; background-color: var(--accent);"></div>
                                        <div class="w-1 sm:w-2.5 rounded-t-md transition-all duration-500"
                                             style="height: {{ max($cHeight, 3) }}%; background-color: var(--ok);"></div>
                                        <div class="w-1 sm:w-2.5 rounded-t-md transition-all duration-500"
                                             style="height: {{ max($rHeight, 3) }}%; background-color: var(--cyan);"></div>
                                        <div class="absolute -top-12 scale-0 group-hover:scale-100 transition-all duration-200 text-[9px] font-medium px-2 py-1.5 rounded-lg z-50 whitespace-nowrap"
                                             style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid color-mix(in srgb, var(--accent) 50%, transparent);">
                                            <strong style="color: var(--accent);">{{ $month['month'] }}</strong><br>
                                            🏪 {{ $month['tenants'] }} | 👤 {{ $month['customers'] }}<br>
                                            💰 ₱{{ number_format($month['revenue_k'] * 1000) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-4 text-[8px] sm:text-[9px] font-semibold uppercase px-1" style="color: var(--text-muted);">
                                @foreach($monthlyChartData as $month)
                                    <span class="flex-1 min-w-0 text-center truncate">{{ $month['short'] }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-end justify-around gap-2 sm:gap-6 h-full pb-2" style="border-bottom: 1px solid var(--border-soft);">
                                @foreach($yearlyChartData as $yearData)
                                    @php
                                        $maxTenant = max(collect($yearlyChartData)->max('tenants'), 1);
                                        $maxCustomer = max(collect($yearlyChartData)->max('customers'), 1);
                                        $tHeight = ($yearData['tenants'] / $maxTenant) * 90;
                                        $cHeight = ($yearData['customers'] / $maxCustomer) * 90;
                                    @endphp
                                    <div class="flex-1 min-w-0 max-w-[80px] relative group flex items-end justify-center gap-1 sm:gap-2 h-full">
                                        <div class="w-2 sm:w-4 rounded-t-md transition-all duration-500"
                                             style="height: {{ max($tHeight, 5) }}%; background-color: var(--accent);"></div>
                                        <div class="w-2 sm:w-4 rounded-t-md transition-all duration-500"
                                             style="height: {{ max($cHeight, 5) }}%; background-color: var(--ok);"></div>
                                        <div class="absolute -top-12 scale-0 group-hover:scale-100 transition-all duration-200 text-[10px] font-medium px-2 py-1 rounded-lg z-50 whitespace-nowrap"
                                             style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid color-mix(in srgb, var(--accent) 50%, transparent);">
                                            <strong style="color: var(--accent);">{{ $yearData['year'] }}</strong><br>
                                            Tenants: {{ $yearData['tenants'] }}<br>
                                            Customers: {{ $yearData['customers'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-around mt-4 text-[9px] sm:text-[10px] font-semibold" style="color: var(--text-muted);">
                                @foreach($yearlyChartData as $yearData)
                                    <span class="flex-1 min-w-0 text-center truncate">{{ $yearData['year'] }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 text-center text-[10px] pt-3" style="color: var(--text-muted); border-top: 1px solid var(--border-soft);">
                        <i class="fa-regular fa-lightbulb" style="color: var(--accent);"></i> Hover over bars for details · Updated in real time
                    </div>
                </div>

                {{-- Sales Leaderboard --}}
                <div class="lg:col-span-4 card p-6 shadow-sm">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h3 class="font-black flex items-center gap-2" style="color: var(--text-primary);">
                                <i class="fa-solid fa-ranking-star" style="color: var(--accent);"></i> Sales Leaderboard
                            </h3>
                            <p class="text-[11px] mt-0.5" style="color: var(--text-muted);">This week's top performers</p>
                        </div>
                        <span class="text-[9px] px-2 py-0.5 rounded-full font-bold whitespace-nowrap"
                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            🔥 Trending
                        </span>
                    </div>
                    <div class="space-y-4">
                        @forelse($topWeeklyShops as $index => $shop)
                            <div class="flex items-center justify-between group p-2 rounded-xl transition"
                                 style="border-radius: 0.75rem;">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-sm font-black w-6 flex-shrink-0"
                                          style="color: {{ $index == 0 ? 'var(--accent)' : ($index == 1 ? 'var(--text-muted)' : 'var(--accent-bright)') }};">
                                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold truncate" style="color: var(--text-primary);">
                                            {{ $shop->name }}
                                        </p>
                                        <p class="text-[10px] font-bold uppercase tracking-tight" style="color: var(--accent);">
                                            ₱{{ number_format($shop->weekly_sales, 2) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if($index == 0) <span class="text-lg">👑</span>
                                    @elseif($index == 1) <span class="text-lg">🥈</span>
                                    @elseif($index == 2) <span class="text-lg">🥉</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10">
                                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
                                     style="background-color: var(--bg-inner);">
                                    <i class="fa-solid fa-chart-simple text-xl" style="color: var(--text-muted);"></i>
                                </div>
                                <p class="text-xs italic" style="color: var(--text-muted);">No sales recorded this week</p>
                            </div>
                        @endforelse
                    </div>

                    @if(count($topWeeklyShops) > 0)
                        <div class="mt-6 pt-4 flex justify-between items-center text-xs" style="border-top: 1px solid var(--border-soft);">
                            <span style="color: var(--text-muted);">
                                <i class="fa-regular fa-clock mr-1"></i> Week of {{ now()->startOfWeek()->format('M d') }}
                            </span>
                            <span class="font-bold" style="color: var(--ok);">
                                <i class="fa-solid fa-arrow-up mr-1"></i> {{ $weeklyGrowth }}% vs prev
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- TOP RATED SHOPS + QUALITY METRICS --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 mb-8">

                {{-- Highest Rated --}}
                <div class="card p-6 shadow-sm">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h3 class="font-black flex items-center gap-2" style="color: var(--text-primary);">
                                <i class="fa-solid fa-star" style="color: var(--accent);"></i> Highest Rated Shops
                            </h3>
                            <p class="text-[11px] mt-0.5" style="color: var(--text-muted);">Based on verified customer reviews</p>
                        </div>
                        <span class="text-[9px] px-2 py-0.5 rounded-full font-bold whitespace-nowrap"
                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            ⭐ Best Quality
                        </span>
                    </div>

                    @if(count($topRatedShops) > 0)
                        <div class="space-y-4">
                            @foreach($topRatedShops as $index => $shop)
                                <div class="flex items-center justify-between group p-2 rounded-xl transition">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <span class="text-sm font-black w-6 flex-shrink-0"
                                              style="color: {{ $index == 0 ? 'var(--accent)' : ($index == 1 ? 'var(--text-muted)' : 'var(--accent-bright)') }};">
                                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-bold truncate" style="color: var(--text-primary);">
                                                    {{ $shop['name'] }}
                                                </p>
                                                @if($index == 0) <span class="text-xs flex-shrink-0">👑</span> @endif
                                            </div>
                                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                                <div class="flex items-center gap-0.5">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= floor($shop['rating']))
                                                            <i class="fa-solid fa-star text-xs" style="color: var(--accent);"></i>
                                                        @elseif($i - 0.5 <= $shop['rating'])
                                                            <i class="fa-solid fa-star-half-stroke text-xs" style="color: var(--accent);"></i>
                                                        @else
                                                            <i class="fa-regular fa-star text-xs" style="color: color-mix(in srgb, var(--text-primary) 20%, transparent);"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                                <span class="text-xs font-bold" style="color: var(--accent);">{{ number_format($shop['rating'], 1) }}</span>
                                                <span class="text-[10px]" style="color: var(--text-muted);">({{ $shop['review_count'] }} reviews)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 flex-shrink-0">
                                        <div class="hidden sm:block w-20 sm:w-24 h-1.5 rounded-full overflow-hidden"
                                             style="background-color: color-mix(in srgb, var(--text-primary) 10%, transparent);">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                 style="width: {{ ($shop['rating'] / 5) * 100 }}%; background-color: var(--accent);"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
                                 style="background-color: var(--bg-inner);">
                                <i class="fa-regular fa-star text-xl" style="color: var(--text-muted);"></i>
                            </div>
                            <p class="text-xs italic" style="color: var(--text-muted);">No reviews recorded yet</p>
                        </div>
                    @endif

                    @if(count($ratingDistribution) > 0)
                        <div class="mt-6 pt-4" style="border-top: 1px solid var(--border-soft);">
                            <p class="text-[10px] font-bold uppercase tracking-widest mb-3 flex items-center gap-1.5" style="color: var(--text-muted);">
                                <i class="fa-solid fa-chart-simple" style="color: var(--accent);"></i> Rating Distribution
                            </p>
                            <div class="flex items-end justify-between gap-2 h-24">
                                @foreach($ratingDistribution as $shop)
                                    @php
                                        $height = ($shop['rating'] / 5) * 100;
                                        $barColors = ['var(--accent)', 'var(--ok)', 'var(--cyan)'];
                                        $emojis = ['👑', '🥈', '🥉'];
                                    @endphp
                                    <div class="flex-1 flex flex-col items-center group min-w-0">
                                        <div class="relative w-full flex flex-col items-center">
                                            <div class="absolute -top-8 scale-0 group-hover:scale-100 transition-all duration-200 text-[9px] font-medium px-2 py-1 rounded-lg z-50 whitespace-nowrap"
                                                 style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid color-mix(in srgb, var(--accent) 50%, transparent);">
                                                {{ $shop['name'] }}<br>
                                                {{ number_format($shop['rating'], 1) }}★ ({{ $shop['review_count'] }})
                                            </div>
                                            <div class="w-full rounded-t-lg transition-all duration-500 group-hover:scale-y-105"
                                                 style="height: {{ max($height, 10) }}%; background-color: {{ $barColors[$loop->index] ?? 'var(--accent)' }}; opacity: 0.85;"></div>
                                            <span class="text-[8px] mt-1 truncate max-w-full" style="color: var(--text-muted);">{{ substr($shop['name'], 0, 3) }}</span>
                                            <span class="text-[8px] font-bold" style="color: var(--accent);">{{ $emojis[$loop->index] ?? '' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Quality Metrics --}}
                <div class="card p-6 shadow-sm">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h3 class="font-black flex items-center gap-2" style="color: var(--text-primary);">
                                <i class="fa-solid fa-medal" style="color: var(--accent);"></i> Quality Metrics
                            </h3>
                            <p class="text-[11px] mt-0.5" style="color: var(--text-muted);">Platform-wide satisfaction snapshot</p>
                        </div>
                        <span class="text-[9px] px-2 py-0.5 rounded-full font-bold whitespace-nowrap"
                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            🏆 Elite
                        </span>
                    </div>

                    @if(count($topRatedShops) > 0)
                        <div class="space-y-3 mb-6">
                            @foreach($topRatedShops as $index => $shop)
                                @if($index < 3)
                                    <div class="p-4 rounded-xl transition-all duration-300"
                                         style="{{ $index == 0
                                            ? 'background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 40%, transparent);'
                                            : 'border: 1px solid var(--border-soft);' }}">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                                <div class="w-12 h-12 rounded-xl flex-shrink-0 flex items-center justify-center text-2xl"
                                                     style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); border: 2px solid color-mix(in srgb, var(--accent) 50%, transparent);">
                                                    {{ $index == 0 ? '👑' : ($index == 1 ? '🥈' : '🥉') }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold truncate" style="color: var(--text-primary);">{{ $shop['name'] }}</p>
                                                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                                        <div class="flex items-center gap-0.5">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= floor($shop['rating']))
                                                                    <i class="fa-solid fa-star text-[10px]" style="color: var(--accent);"></i>
                                                                @elseif($i - 0.5 <= $shop['rating'])
                                                                    <i class="fa-solid fa-star-half-stroke text-[10px]" style="color: var(--accent);"></i>
                                                                @else
                                                                    <i class="fa-regular fa-star text-[10px]" style="color: color-mix(in srgb, var(--text-primary) 20%, transparent);"></i>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                        <span class="text-xs font-bold" style="color: var(--accent);">{{ number_format($shop['rating'], 1) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right flex-shrink-0">
                                                <p class="text-[10px] font-semibold uppercase" style="color: var(--text-muted);">Reviews</p>
                                                <p class="text-lg font-black" style="color: var(--text-primary);">{{ $shop['review_count'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="pt-4 grid grid-cols-3 gap-3 text-center" style="border-top: 1px solid var(--border-soft);">
                            <div class="p-3 rounded-xl" style="background-color: var(--bg-inner);">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Top Rating</p>
                                <p class="text-lg font-black" style="color: var(--accent);">{{ $topRatedShops[0]['rating'] ?? 0 }}★</p>
                            </div>
                            <div class="p-3 rounded-xl" style="background-color: var(--bg-inner);">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Reviews</p>
                                <p class="text-lg font-black" style="color: var(--text-primary);">{{ collect($topRatedShops)->sum('review_count') }}</p>
                            </div>
                            <div class="p-3 rounded-xl" style="background-color: var(--bg-inner);">
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--text-muted);">Avg Rating</p>
                                <p class="text-lg font-black" style="color: var(--text-primary);">
                                    @php $avg = collect($topRatedShops)->avg('rating') ?? 0; @endphp
                                    {{ number_format($avg, 1) }}★
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-10">
                            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                                 style="background-color: var(--bg-inner);">
                                <i class="fa-regular fa-star text-3xl" style="color: var(--text-muted);"></i>
                            </div>
                            <p class="text-sm font-semibold" style="color: var(--text-muted);">No ratings yet</p>
                            <p class="text-xs mt-1" style="color: var(--text-muted);">Reviews will appear here once customers start rating</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- TENANT DIRECTORY --}}
            <div class="card overflow-hidden shadow-sm">
                <div class="p-5 flex flex-wrap justify-between items-center gap-3" style="border-bottom: 1px solid var(--border-soft);">
                    <div>
                        <h3 class="font-black flex items-center gap-2 flex-wrap" style="color: var(--text-primary);">
                            <i class="fa-solid fa-store" style="color: var(--accent);"></i> Tenant Directory
                            <span class="text-[10px] px-2 py-0.5 rounded-full"
                                  style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                Verified Merchants
                            </span>
                        </h3>
                        <p class="text-[11px] mt-0.5" style="color: var(--text-muted);">
                            @if($tenantSearch)
                                Search results for "{{ $tenantSearch }}"
                            @else
                                Top 5 by total transaction value
                            @endif
                        </p>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                        <input type="text"
                               wire:model.live.debounce.300ms="tenantSearch"
                               placeholder="Search all tenants..."
                               class="w-full text-sm rounded-xl pl-9 pr-4 py-2 outline-none transition">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px]">
                        <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                            <tr>
                                <th class="text-left px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Store Identity</th>
                                <th class="text-center px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Total GTV</th>
                                <th class="text-left px-6 py-4 text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted);">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filteredTenants as $tenant)
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-xs flex-shrink-0"
                                                 style="background-color: var(--forest); color: var(--bg-primary);">
                                                {{ substr($tenant->name, 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold truncate" style="color: var(--text-primary);">{{ $tenant->name }}</p>
                                                <p class="text-[10px] uppercase font-mono" style="color: var(--text-muted);">ID: #TN-{{ $tenant->id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-sm" style="color: var(--text-primary);">
                                        ₱{{ number_format($tenant->total_sales ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusColor = match($tenant->status) {
                                                'active' => 'var(--ok)',
                                                'pending' => 'var(--warn)',
                                                'suspended' => 'var(--bad)',
                                                default => 'var(--text-muted)',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                              style="background-color: color-mix(in srgb, {{ $statusColor }} 10%, transparent); color: {{ $statusColor }}; border: 1px solid color-mix(in srgb, {{ $statusColor }} 20%, transparent);">
                                            @if($tenant->status === 'active')
                                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--ok);"></span>
                                            @endif
                                            {{ $tenant->status ?? 'Active' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-14 h-14 rounded-full flex items-center justify-center mb-3"
                                                 style="background-color: var(--bg-inner);">
                                                <i class="fa-solid fa-store-slash text-xl" style="color: var(--text-muted);"></i>
                                            </div>
                                            <p class="text-sm font-semibold" style="color: var(--text-muted);">
                                                @if($tenantSearch)
                                                    No tenants match "{{ $tenantSearch }}"
                                                @else
                                                    No tenants found
                                                @endif
                                            </p>
                                            <p class="text-xs mt-1" style="color: var(--text-muted);">Try a different search term</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 flex flex-col sm:flex-row justify-between gap-2 text-xs"
                     style="border-top: 1px solid var(--border-soft); color: var(--text-muted);">
                    <span>
                        <i class="fa-regular fa-building mr-1" style="color: var(--accent);"></i>
                        Showing <strong style="color: var(--text-primary);">{{ $filteredTenants->count() }}</strong>
                        {{ $tenantSearch ? 'matching merchant(s)' : 'top merchant(s)' }}
                    </span>
                    <span class="font-semibold" style="color: var(--accent);">
                        <i class="fa-regular fa-chart-line mr-1"></i>
                        @if($tenantSearch)
                            Search results (by revenue)
                        @else
                            Top 5 by revenue
                        @endif
                    </span>
                </div>
            </div>
        </section>
    </main>
</div>  