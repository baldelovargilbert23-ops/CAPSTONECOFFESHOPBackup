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
        .tenant-root { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .tenant-root .card {
            background-color: var(--bg-elevated);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
        }
    </style>
    @endonce

    <div wire:poll.30s="$refresh" class="hidden"></div>

    <main class="tenant-root relative min-h-screen font-sans transition-colors duration-300"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-4 sm:space-y-6">

            {{-- HEADER --}}
            <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center"
                             style="background-color: var(--forest); color: var(--bg-primary);">
                            <i class="fa fa-store text-base sm:text-lg"></i>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black" style="color: var(--text-primary);">My Shop Dashboard</h1>
                    </div>
                    <p class="text-xs sm:text-sm ml-1" style="color: var(--text-muted);">
                        Welcome back, <span class="font-bold" style="color: var(--accent);">{{ $userFirstName }}</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-bold ml-2"
                              style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); color: var(--ok); border: 1px solid color-mix(in srgb, var(--ok) 20%, transparent);">
                            <i class="fa fa-circle text-[6px] animate-pulse"></i> Live
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-3 sm:gap-4 p-2 sm:p-3 rounded-2xl shadow-sm"
                     style="background-color: var(--bg-elevated); border: 1px solid var(--border-soft);">
                    <div class="text-right">
                        <p class="text-[8px] sm:text-[10px] font-black uppercase tracking-[0.2em]" style="color: var(--text-muted);">System Time</p>
                        <p id="system-time" class="text-xs sm:text-sm font-bold" style="color: var(--text-primary);">--</p>
                    </div>
                    <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl flex items-center justify-center"
                         style="background-color: var(--forest); color: var(--bg-primary);">
                        <i class="fa-regular fa-clock text-base sm:text-xl"></i>
                    </div>
                </div>
            </div>

            {{-- REVENUE CARD --}}
            <div class="rounded-2xl p-5 sm:p-7 shadow-sm"
                 style="background-color: var(--forest); color: var(--bg-primary);">
                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                        <div>
                            <p class="text-[10px] sm:text-xs font-bold uppercase tracking-[0.2em] opacity-70">My Shop Revenue</p>
                            <p class="text-[8px] sm:text-[9px] mt-1 opacity-50">From your shop's completed orders</p>
                        </div>
                        <div class="flex gap-1 sm:gap-2 p-1 rounded-xl" style="background-color: rgba(0,0,0,0.2);">
                            @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                                <button wire:click="setRevenuePeriod('{{ $key }}')"
                                        class="px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg text-[10px] sm:text-xs font-bold transition-all"
                                        style="{{ $revenuePeriod === $key
                                            ? 'background-color: var(--bg-primary); color: var(--text-primary);'
                                            : 'color: rgba(255,255,255,0.8);' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <span class="text-4xl sm:text-5xl font-black">₱{{ number_format($periodRevenue, 2) }}</span>
                        @if($revenueComparison['percentage'] != 0)
                            <span class="text-[10px] sm:text-xs font-bold px-2 py-1 rounded-lg"
                                  style="background-color: rgba(0,0,0,0.15); color: {{ $revenueComparison['percentage'] >= 0 ? 'var(--ok)' : 'var(--bad)' }};">
                                <i class="fa {{ $revenueComparison['percentage'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} mr-1"></i>
                                {{ abs($revenueComparison['percentage']) }}% vs previous period
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- STATS GRID --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

                <div class="card p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.15em]" style="color: var(--text-muted);">Active Orders</h3>
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 20%, transparent);">
                            <i class="fa fa-timer text-sm animate-pulse" style="color: var(--accent);"></i>
                        </div>
                    </div>
                    <p class="text-3xl sm:text-4xl font-black" style="color: var(--text-primary);">{{ $activeOrders }}</p>
                    <p class="text-[9px] sm:text-[10px] mt-2" style="color: var(--text-muted);">In your shop right now</p>
                </div>

                <div class="card p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.15em]" style="color: var(--text-muted);">My Branches</h3>
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 20%, transparent);">
                            <i class="fa fa-store text-sm" style="color: var(--ok);"></i>
                        </div>
                    </div>
                    <p class="text-3xl sm:text-4xl font-black" style="color: var(--text-primary);">{{ $totalBranches }}</p>
                    <p class="text-[9px] sm:text-[10px] mt-2" style="color: var(--text-muted);">Branches you own</p>
                </div>

                <div class="card p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.15em]" style="color: var(--text-muted);">My Staff</h3>
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--info) 10%, transparent); border: 1px solid color-mix(in srgb, var(--info) 20%, transparent);">
                            <i class="fa fa-users text-sm" style="color: var(--info);"></i>
                        </div>
                    </div>
                    <p class="text-3xl sm:text-4xl font-black" style="color: var(--text-primary);">{{ $totalStaff }}</p>
                    <p class="text-[9px] sm:text-[10px] mt-2" style="color: var(--text-muted);">Staff in your shop</p>
                </div>

                <div class="card p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.15em]" style="color: var(--text-muted);">My Orders</h3>
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background-color: color-mix(in srgb, var(--violet) 10%, transparent); border: 1px solid color-mix(in srgb, var(--violet) 20%, transparent);">
                            <i class="fa fa-receipt text-sm" style="color: var(--violet);"></i>
                        </div>
                    </div>
                    <p class="text-3xl sm:text-4xl font-black" style="color: var(--text-primary);">{{ $totalOrders }}</p>
                    <p class="text-[9px] sm:text-[10px] mt-2" style="color: var(--text-muted);">Completed in your shop</p>
                </div>
            </div>

            {{-- WEEKLY REVENUE CHART --}}
            <div class="card overflow-hidden shadow-sm">
                <div class="px-4 sm:px-6 py-4" style="border-bottom: 1px solid var(--border-soft);">
                    <h3 class="font-black text-base sm:text-lg flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-chart-line" style="color: var(--accent);"></i> My Weekly Revenue
                    </h3>
                    <p class="text-[10px] sm:text-xs mt-1" style="color: var(--text-muted);">Your shop's last 7 days</p>
                </div>
                <div class="p-4 sm:p-6">
                    @php
                        $maxRevenue = max(array_column($weeklyBreakdown, 'revenue') ?: [1]) ?: 1;
                    @endphp
                    <div class="flex items-end justify-between gap-2 h-48 sm:h-64">
                        @foreach($weeklyBreakdown as $day)
                            @php
                                $heightPct = max(4, ($day['revenue'] / $maxRevenue) * 100);
                            @endphp
                            <div class="flex-1 h-full flex flex-col items-center gap-2 group">
                                <div class="relative w-full flex-1 flex items-end">
                                    <div class="w-full rounded-lg transition-all duration-300 group-hover:opacity-80 relative"
                                         style="height: {{ $heightPct }}%; min-height: 4px; background-color: var(--accent);">
                                        <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-20"
                                             style="background-color: var(--forest); color: var(--bg-primary); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                            ₱{{ number_format($day['revenue'], 2) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] sm:text-xs font-bold" style="color: var(--text-primary);">{{ $day['day'] }}</p>
                                    <p class="text-[8px] sm:text-[9px]" style="color: var(--text-muted);">{{ $day['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- MAIN CONTENT GRID --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

                {{-- Live Orders --}}
                <div class="lg:col-span-2 card overflow-hidden shadow-sm">
                    <div class="px-4 sm:px-6 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2"
                         style="border-bottom: 1px solid var(--border-soft);">
                        <div>
                            <h3 class="font-black text-base sm:text-lg flex items-center gap-2" style="color: var(--text-primary);">
                                <i class="fa fa-clock" style="color: var(--accent);"></i> Live Orders
                            </h3>
                            <p class="text-[10px] sm:text-xs mt-1" style="color: var(--text-muted);">Your shop's recent orders</p>
                        </div>
                        <div class="flex items-center gap-1 text-[10px]" style="color: var(--text-muted);">
                            <i class="fa fa-circle text-[6px] animate-pulse" style="color: var(--ok);"></i>
                            <span>Auto-refresh: 30s</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs sm:text-sm min-w-[600px]">
                            <thead style="background-color: var(--bg-inner); border-bottom: 1px solid var(--border-soft);">
                                <tr class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider" style="color: var(--text-muted);">
                                    <th class="px-4 sm:px-6 py-3">Order #</th>
                                    <th class="px-4 sm:px-6 py-3">Time</th>
                                    <th class="px-4 sm:px-6 py-3">Amount</th>
                                    <th class="px-4 sm:px-6 py-3">Payment</th>
                                    <th class="px-4 sm:px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    @php
                                        $statusColor = match($order->status) {
                                            'completed' => 'var(--ok)',
                                            'brewing'   => 'var(--accent)',
                                            'preparing' => 'var(--info)',
                                            'ready'     => 'var(--info)',
                                            'cancelled' => 'var(--bad)',
                                            default     => 'var(--text-muted)',
                                        };
                                        $statusIcon = match($order->status) {
                                            'completed' => 'fa-check-circle',
                                            'brewing'   => 'fa-fire',
                                            'preparing' => 'fa-utensils',
                                            'ready'     => 'fa-box',
                                            'pending'   => 'fa-clock',
                                            'cancelled' => 'fa-ban',
                                            default     => 'fa-clock',
                                        };
                                        $paymentLabel = match($order->payment_method) {
                                            'cash'  => 'Cash',
                                            'gcash' => 'GCash',
                                            'card'  => 'Card',
                                            default => ucfirst($order->payment_method ?? '—'),
                                        };
                                    @endphp
                                    <tr style="border-bottom: 1px solid var(--border-soft);">
                                        <td class="px-4 sm:px-6 py-3 font-mono font-bold text-xs" style="color: var(--accent);">
                                            #{{ $order->order_number ?? str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 text-xs whitespace-nowrap" style="color: var(--text-muted);">
                                            @if($order->created_at->isToday())
                                                {{ $order->created_at->format('h:i A') }}
                                            @else
                                                {{ $order->created_at->format('M d, h:i A') }}
                                            @endif
                                        </td>
                                        <td class="px-4 sm:px-6 py-3 font-bold text-xs" style="color: var(--text-primary);">₱{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="px-4 sm:px-6 py-3 text-xs" style="color: var(--text-muted);">{{ $paymentLabel }}</td>
                                        <td class="px-4 sm:px-6 py-3">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase"
                                                  style="background-color: color-mix(in srgb, {{ $statusColor }} 10%, transparent); color: {{ $statusColor }};">
                                                <i class="fa {{ $statusIcon }} text-[9px]"></i> {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center" style="color: var(--text-muted);">
                                            <i class="fa fa-inbox text-3xl mb-2 block opacity-50"></i> No orders yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Popular Items + Feedback --}}
                <div class="card overflow-hidden shadow-sm">
                    <div class="p-4" style="border-bottom: 1px solid var(--border-soft);">
                        <h3 class="font-black flex items-center gap-2 mb-3" style="color: var(--text-primary);">
                            <i class="fa fa-chart-simple" style="color: var(--accent);"></i> My Popular Items
                        </h3>
                        <div class="space-y-3">
                            @forelse($topProducts as $index => $product)
                                <div class="flex items-center justify-between p-2 rounded-xl transition">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-xs flex-shrink-0"
                                             style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                            {{ $index + 1 }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-xs truncate" title="{{ $product->name }}" style="color: var(--text-primary);">
                                                {{ $product->name }}
                                            </p>
                                            <p class="text-[9px]" style="color: var(--text-muted);">{{ $product->orders_count ?? 0 }} units sold</p>
                                        </div>
                                    </div>
                                    <p class="font-bold text-xs flex-shrink-0 ml-2" style="color: var(--text-primary);">₱{{ number_format($product->price ?? 0, 2) }}</p>
                                </div>
                            @empty
                                <p class="text-center py-6 text-sm" style="color: var(--text-muted);">No products sold yet</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="p-4">
                        <h3 class="font-black flex items-center gap-2 mb-3" style="color: var(--text-primary);">
                            <i class="fa fa-comment-dots" style="color: var(--accent);"></i> My Latest Feedback
                        </h3>
                        <div class="space-y-3">
                            @forelse($recentFeedback as $feedback)
                                <div class="p-3 rounded-xl" style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black flex-shrink-0"
                                                 style="background-color: color-mix(in srgb, var(--accent) 20%, transparent); color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                                                {{ substr($feedback->user->name ?? 'U', 0, 1) }}
                                            </div>
                                            <span class="text-[10px] font-semibold truncate" title="{{ $feedback->user->name ?? 'Anonymous' }}" style="color: var(--text-primary);">
                                                {{ $feedback->user->name ?? 'Anonymous' }}
                                            </span>
                                        </div>
                                        <div class="flex gap-0.5 flex-shrink-0">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="fa fa-star text-[8px]"
                                                   style="color: {{ $i <= $feedback->rating ? 'var(--accent)' : 'color-mix(in srgb, var(--text-primary) 20%, transparent)' }};"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="text-[10px] italic truncate" title="{{ $feedback->comment }}" style="color: var(--text-muted);">
                                        "{{ $feedback->comment }}"
                                    </p>
                                    <p class="text-[8px] mt-1" style="color: var(--text-muted);">{{ $feedback->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="text-center py-6 text-sm" style="color: var(--text-muted);">No feedback yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- SUB-BRANCHES --}}
            @if($subBranches->isNotEmpty())
                <div class="card overflow-hidden shadow-sm">
                    <div class="px-4 sm:px-6 py-4" style="border-bottom: 1px solid var(--border-soft);">
                        <h3 class="font-black text-base sm:text-lg flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-code-branch" style="color: var(--accent);"></i> My Branches
                        </h3>
                        <p class="text-[10px] sm:text-xs mt-1" style="color: var(--text-muted);">Today's snapshot of your extra branches</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                        @foreach($subBranches as $branch)
                            <div class="rounded-xl p-4"
                                 style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-sm truncate" style="color: var(--text-primary);">
                                            {{ $branch['name'] }}
                                        </p>
                                        <p class="text-[10px] font-mono" style="color: var(--text-muted);">
                                            {{ $branch['code'] }}
                                        </p>
                                    </div>
                                    @if($branch['active_orders'] > 0)
                                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase flex-shrink-0"
                                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent);">
                                            {{ $branch['active_orders'] }} active
                                        </span>
                                    @endif
                                </div>

                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs">
                                        <span style="color: var(--text-muted);">Manager</span>
                                        <span class="font-semibold truncate ml-2" style="color: var(--text-primary);">
                                            {{ $branch['manager_name'] }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span style="color: var(--text-muted);">Revenue today</span>
                                        <span class="font-bold" style="color: var(--accent);">
                                            ₱{{ number_format($branch['today_revenue'], 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </main>

    <script>
        function updateSystemTime() {
            const now = new Date();
            const month = now.toLocaleString('default', { month: 'short' });
            const day = String(now.getDate()).padStart(2, '0');
            const year = now.getFullYear();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const formattedTime = `${month} ${day}, ${year} • ${hours}:${minutes} ${ampm}`;
            const timeElement = document.getElementById('system-time');
            if (timeElement) timeElement.textContent = formattedTime;
        }

        if (window.__systemTimeInterval) {
            clearInterval(window.__systemTimeInterval);
        }
        window.__systemTimeInterval = setInterval(updateSystemTime, 1000);
        updateSystemTime();
    </script>
</div>