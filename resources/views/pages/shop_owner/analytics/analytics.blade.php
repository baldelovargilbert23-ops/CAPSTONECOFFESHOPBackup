<div>
    <div wire:poll.30s="$refresh" class="hidden"></div>

    <main class="relative min-h-screen font-sans transition-colors duration-300"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

            {{-- Reusable SVG symbols --}}
            <svg width="0" height="0" style="position:absolute" aria-hidden="true">
                <defs>
                    <symbol id="ic-chart-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3v18h18"/>
                        <path d="M7 15l4-4 3 3 5-6"/>
                    </symbol>
                    <symbol id="ic-chart-pie" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                    </symbol>
                    <symbol id="ic-chart-bar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="20" x2="12" y2="10"/>
                        <line x1="18" y1="20" x2="18" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="16"/>
                    </symbol>
                    <symbol id="ic-peso" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 4h8a5 5 0 0 1 0 10H6"/>
                        <path d="M6 14h8"/>
                        <path d="M6 4v16"/>
                        <path d="M4 8h14"/>
                        <path d="M4 12h14"/>
                    </symbol>
                    <symbol id="ic-star" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linejoin="round">
                        <polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9"/>
                    </symbol>
                    <symbol id="ic-star-outline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round">
                        <polygon points="12 2 15 9 22 9.5 17 14.5 18.5 22 12 18 5.5 22 7 14.5 2 9.5 9 9"/>
                    </symbol>
                    <symbol id="ic-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </symbol>
                    <symbol id="ic-trophy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9H4a2 2 0 0 1-2-2V5h4"/>
                        <path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/>
                        <path d="M6 5h12v6a6 6 0 0 1-12 0z"/>
                        <path d="M10 21h4"/>
                        <path d="M12 17v4"/>
                    </symbol>
                    <symbol id="ic-comment" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </symbol>
                    <symbol id="ic-lightbulb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="9" y1="18" x2="15" y2="18"/>
                        <line x1="10" y1="22" x2="14" y2="22"/>
                        <path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>
                    </symbol>
                    <symbol id="ic-crown" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7l4 4 5-7 5 7 4-4v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    </symbol>
                    <symbol id="ic-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </symbol>
                    <symbol id="ic-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10 15 15 0 0 1 4-10z"/>
                    </symbol>
                    <symbol id="ic-shop" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l1-5h16l1 5"/>
                        <path d="M4 9v11a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9"/>
                        <path d="M9 22V12h6v10"/>
                    </symbol>
                    <symbol id="ic-cart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </symbol>
                    <symbol id="ic-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                    </symbol>
                </defs>
            </svg>

            {{-- HEADER --}}
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg"
                             style="background-color: var(--forest); color: var(--bg-primary);">
                            <svg class="w-5 h-5"><use href="#ic-chart-line"/></svg>
                        </div>
                        <h1 class="text-2xl font-black">Store Analytics</h1>
                    </div>
                    <p class="text-sm ml-1" style="color: var(--text-muted);">
                        Welcome back,
                        <span class="font-bold" style="color: var(--accent);">{{ explode(' ', auth()->user()->name)[0] }}</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold ml-2"
                              style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 20%, transparent);">
                            <svg class="w-2 h-2 animate-pulse"><use href="#ic-pulse"/></svg>
                            Live Analytics
                        </span>
                    </p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Location filter --}}
                    <div class="p-3 rounded-2xl flex items-center gap-3"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em]" style="color: var(--text-muted);">View</p>
                            <p class="text-sm font-bold">{{ $this->branchLabel }}</p>
                        </div>
                        <select wire:model.live="selectedBranch"
                                class="text-xs font-bold rounded-lg px-3 py-2 outline-none"
                                style="background-color: var(--bg-inner); border: 1px solid var(--border-soft); color: var(--text-primary);">
                            <option value="all">All Locations</option>
                            <option value="__own_store__">Your Shop</option>
                            @foreach($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Period filter --}}
                    <div class="p-3 rounded-2xl flex items-center gap-3"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em]" style="color: var(--text-muted);">Period</p>
                            <p class="text-sm font-bold">{{ $this->periodLabel }}</p>
                        </div>

                        <div class="flex items-center gap-1 rounded-xl p-1"
                             style="background-color: var(--bg-inner);">
                            @foreach([
                                'daily'   => 'Day',
                                'weekly'  => 'Week',
                                'monthly' => 'Month',
                                'yearly'  => 'Year',
                            ] as $key => $label)
                                <button type="button" wire:click="setPeriod('{{ $key }}')"
                                        class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                        style="{{ $period === $key
                                            ? 'background-color: var(--accent); color: var(--bg-primary);'
                                            : 'color: var(--text-muted);' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPI GRID --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                <div class="rounded-2xl p-6 shadow-lg" style="background-color: var(--forest); color: var(--bg-primary);">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-bold uppercase tracking-wider opacity-70">Revenue</h3>
                        <svg class="w-5 h-5 opacity-50"><use href="#ic-peso"/></svg>
                    </div>
                    <p class="text-3xl font-black tracking-tight">₱{{ number_format($this->todayRevenue, 2) }}</p>
                    <p class="text-[10px] opacity-60 mt-2">{{ $this->periodLabel }} — {{ $this->branchLabel }}</p>
                </div>

                <div class="rounded-2xl p-6" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">Avg Order Value</h3>
                        <svg class="w-5 h-5" style="color: var(--accent);"><use href="#ic-chart-bar"/></svg>
                    </div>
                    <p class="text-3xl font-black tracking-tight">₱{{ number_format($this->avgOrderValue, 2) }}</p>
                    <p class="text-[10px] mt-2" style="color: var(--text-muted);">Per transaction</p>
                </div>

                <div class="rounded-2xl p-6" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">Customer Rating</h3>
                        <svg class="w-5 h-5" style="color: var(--accent);"><use href="#ic-star"/></svg>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <p class="text-3xl font-black tracking-tight">{{ number_format($this->averageRating, 1) }}</p>
                        <span class="text-sm" style="color: var(--text-muted);">/ 5.0</span>
                    </div>
                    <div class="flex gap-0.5 mt-2" style="color: var(--accent);">
                        @for($i=1; $i<=5; $i++)
                            @if($i <= round($this->averageRating))
                                <svg class="w-3 h-3"><use href="#ic-star"/></svg>
                            @else
                                <svg class="w-3 h-3 opacity-30"><use href="#ic-star-outline"/></svg>
                            @endif
                        @endfor
                    </div>
                </div>

                <div class="rounded-2xl p-6" style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">Total Orders</h3>
                        <svg class="w-5 h-5" style="color: var(--accent);"><use href="#ic-cart"/></svg>
                    </div>
                    <p class="text-3xl font-black tracking-tight">{{ $this->totalOrders }}</p>
                    <p class="text-[10px] mt-2" style="color: var(--text-muted);">{{ $this->periodLabel }}</p>
                </div>
            </div>

            {{-- CHARTS ROW 1 --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section class="lg:col-span-2 rounded-2xl overflow-hidden"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="px-6 py-5 flex justify-between items-center flex-wrap gap-2"
                         style="border-bottom: 1px solid var(--border-soft);">
                        <h3 class="font-black text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" style="color: var(--accent);"><use href="#ic-chart-line"/></svg>
                            {{ $this->salesTrendTitle }}
                        </h3>
                        <span class="text-[10px] font-bold px-3 py-1 rounded-full"
                              style="background-color: var(--bg-inner); color: var(--text-muted);">
                            PHP / {{ $this->periodLabel }}
                        </span>
                    </div>
                    <div class="p-4">
                        <canvas id="revenueChart" class="w-full h-80" wire:ignore></canvas>
                    </div>
                </section>

                <section class="rounded-2xl overflow-hidden"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="px-6 py-5" style="border-bottom: 1px solid var(--border-soft);">
                        <h3 class="font-black text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" style="color: var(--accent);"><use href="#ic-chart-pie"/></svg>
                            Category Mix
                        </h3>
                    </div>
                    <div class="p-4">
                        <canvas id="categoryChart" class="w-full h-72" wire:ignore></canvas>
                        @if($this->categorySales->isEmpty())
                            <p class="text-center text-sm mt-4" style="color: var(--text-muted);">No sales data yet</p>
                        @endif
                    </div>
                </section>
            </div>

            {{-- CHARTS ROW 2 --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section class="rounded-2xl overflow-hidden"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="px-6 py-5" style="border-bottom: 1px solid var(--border-soft);">
                        <h3 class="font-black text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" style="color: var(--accent);"><use href="#ic-clock"/></svg>
                            Peak Hours
                        </h3>
                    </div>
                    <div class="p-4">
                        <canvas id="peakHoursChart" class="w-full h-72" wire:ignore></canvas>
                        <p class="text-center text-[10px] mt-3" style="color: var(--text-muted);">Most active ordering hours</p>
                    </div>
                </section>

                <section class="rounded-2xl overflow-hidden"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="px-6 py-5" style="border-bottom: 1px solid var(--border-soft);">
                        <h3 class="font-black text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" style="color: var(--accent);"><use href="#ic-trophy"/></svg>
                            Top Products
                        </h3>
                    </div>
                    <div class="p-4">
                        <canvas id="productChart" class="w-full h-72" wire:ignore></canvas>
                    </div>
                </section>
            </div>

            {{-- INSIGHTS --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <section class="rounded-2xl overflow-hidden"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="px-6 py-5" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h3 class="font-black flex items-center gap-2">
                            <svg class="w-4 h-4" style="color: var(--accent);"><use href="#ic-crown"/></svg>
                            Best Sellers
                        </h3>
                    </div>
                    <div>
                        @forelse($this->topProducts as $index => $product)
                            <div class="p-4 flex items-center justify-between" style="border-bottom: 1px solid var(--border-soft);">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-sm font-black w-6 flex-shrink-0" style="color: var(--text-muted);">#{{ $index + 1 }}</span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-sm truncate">{{ $product->name }}</p>
                                        <p class="text-[9px]" style="color: var(--text-muted);">{{ number_format($product->orders_count) }} units sold</p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold flex-shrink-0" style="color: var(--accent);">
                                    ₱{{ number_format($product->price ?? 0, 2) }}
                                </span>
                            </div>
                        @empty
                            <div class="p-8 text-center" style="color: var(--text-muted);">No products sold in this period</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl overflow-hidden"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="px-6 py-5" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h3 class="font-black flex items-center gap-2">
                            <svg class="w-4 h-4" style="color: var(--accent);"><use href="#ic-comment"/></svg>
                            Recent Feedback
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        @forelse($this->recentFeedback as $review)
                            <div class="p-3 rounded-xl"
                                 style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                <div class="flex items-center justify-between mb-2 gap-2">
                                    <span class="text-xs font-bold truncate">{{ $review['customer'] }}</span>
                                    <div class="flex gap-0.5 flex-shrink-0" style="color: var(--accent);">
                                        @for($i=1; $i<=5; $i++)
                                            @if($i <= $review['stars'])
                                                <svg class="w-2.5 h-2.5"><use href="#ic-star"/></svg>
                                            @else
                                                <svg class="w-2.5 h-2.5 opacity-30"><use href="#ic-star-outline"/></svg>
                                            @endif
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-xs italic" style="color: var(--text-muted);">
                                    "{{ \Illuminate\Support\Str::limit($review['comment'], 80) }}"
                                </p>
                            </div>
                        @empty
                            <p class="text-center py-6" style="color: var(--text-muted);">No feedback in this period</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl overflow-hidden"
                         style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="px-6 py-5" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                        <h3 class="font-black flex items-center gap-2">
                            <svg class="w-4 h-4" style="color: var(--accent);"><use href="#ic-lightbulb"/></svg>
                            Business Insights
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 20%, transparent); color: var(--ok);">
                                <svg class="w-3.5 h-3.5"><use href="#ic-chart-line"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold">Revenue Trend</p>
                                <p class="text-xs" style="color: var(--text-muted);">
                                    Daily average:
                                    <span class="font-bold" style="color: var(--accent);">₱{{ number_format($this->avgDailyRevenue, 2) }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent);">
                                <svg class="w-3.5 h-3.5"><use href="#ic-clock"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold">Peak Performance</p>
                                <p class="text-xs" style="color: var(--text-muted);">
                                    Best hour:
                                    <span class="font-bold" style="color: var(--accent);">{{ $this->peakHours['peakHour'] }}:00</span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background-color: color-mix(in srgb, var(--violet) 10%, transparent); border: 1px solid color-mix(in srgb, var(--violet) 20%, transparent); color: var(--violet);">
                                <svg class="w-3.5 h-3.5"><use href="#ic-star"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold">Rating Goal</p>
                                <p class="text-xs" style="color: var(--text-muted);">
                                    Need
                                    <span class="font-bold" style="color: var(--accent);">{{ number_format(max(0, 5 - $this->averageRating), 1) }}</span>
                                    stars to reach 5.0
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        initCharts();
        Livewire.hook('morphed', () => { destroyCharts(); initCharts(); });
        window.addEventListener('theme-changed', () => { destroyCharts(); initCharts(); });
    });

    let charts = {};

    function isDark() { return document.documentElement.classList.contains('dark'); }
    function themeColors() {
        const dark = isDark();
        return { text: dark ? '#a8a29e' : '#57534e', grid: dark ? '#292524' : '#e5e7eb' };
    }
    function destroyCharts() {
        Object.values(charts).forEach(chart => { if (chart) chart.destroy(); });
        charts = {};
    }

    function initCharts() {
        if (! window.Chart) return;
        const colors = themeColors();

        const revenueCanvas = document.getElementById('revenueChart');
        if (revenueCanvas && !charts.revenue) {
            const labels = @json($this->salesTrend->pluck('label')->toArray());
            const data   = @json($this->salesTrend->pluck('total')->toArray());

            charts.revenue = new Chart(revenueCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels.length ? labels : ['—'],
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: data.length ? data : [0],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#f59e0b',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { labels: { color: colors.text } } },
                    scales: {
                        y: { grid: { color: colors.grid }, ticks: { color: colors.text } },
                        x: { grid: { color: colors.grid }, ticks: { color: colors.text } }
                    }
                }
            });
        }

        const categoryCanvas = document.getElementById('categoryChart');
        const categoryData   = @json($this->categorySales);
        if (categoryCanvas && !charts.category && categoryData && categoryData.length > 0) {
            charts.category = new Chart(categoryCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: categoryData.map(i => i.label),
                    datasets: [{
                        data: categoryData.map(i => i.value),
                        backgroundColor: ['#f59e0b','#fbbf24','#10b981','#6b7280','#b45309','#eab308','#3b82f6','#ef4444','#8b5cf6','#ec4899'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { position: 'bottom', labels: { color: colors.text, font: { size: 10 } } } }
                }
            });
        }

        const peakCanvas = document.getElementById('peakHoursChart');
        const peak       = @json($this->peakHours);
        if (peakCanvas && !charts.peak && peak.labels && peak.labels.length) {
            charts.peak = new Chart(peakCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: peak.labels,
                    datasets: [{
                        label: 'Orders',
                        data: peak.data,
                        backgroundColor: '#f59e0b',
                        borderRadius: 8,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { labels: { color: colors.text } } },
                    scales: {
                        y: { grid: { color: colors.grid }, ticks: { color: colors.text } },
                        x: { grid: { display: false }, ticks: { color: colors.text } }
                    }
                }
            });
        }

        const productCanvas = document.getElementById('productChart');
        const productData   = @json($this->topProducts);
        if (productCanvas && !charts.product && productData && productData.length > 0) {
            charts.product = new Chart(productCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: productData.map(p => p.name.length > 15 ? p.name.substring(0, 12) + '...' : p.name),
                    datasets: [{
                        label: 'Units Sold',
                        data: productData.map(p => p.orders_count),
                        backgroundColor: '#f59e0b',
                        borderRadius: 8,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { labels: { color: colors.text } } },
                    scales: {
                        y: { grid: { color: colors.grid }, ticks: { color: colors.text }, beginAtZero: true },
                        x: { grid: { display: false }, ticks: { color: colors.text, font: { size: 10 } } }
                    }
                }
            });
        }
    }
    </script>
</div>