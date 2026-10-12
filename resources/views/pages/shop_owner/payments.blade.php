<?php

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.shop_owner')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $method = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingMethod(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    private function paymentQuery(): Builder
    {
        return Payment::query()
            ->when($this->method !== '', fn (Builder $query) => $query->where('method', $this->method))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('paid_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('paid_at', '<=', $this->dateTo))
            ->when(trim($this->search) !== '', function (Builder $query) {
                $needle = '%' . trim($this->search) . '%';
                $query->where(function (Builder $searchQuery) use ($needle) {
                    $searchQuery->where('reference_no', 'like', $needle)
                        ->orWhereHas('order', fn (Builder $orderQuery) => $orderQuery->where('order_number', 'like', $needle));
                });
            });
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'method', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $payments = $this->paymentQuery()
            ->with(['order.user', 'branch'])
            ->latest('paid_at')
            ->paginate(12);

        $statsQuery = $this->paymentQuery();
        $stats = [
            'amount' => (float) (clone $statsQuery)->sum('amount'),
            'count' => (int) (clone $statsQuery)->count(),
            'cash' => (float) (clone $statsQuery)->where('method', 'cash')->sum('amount'),
        ];

        return view('pages.shop_owner.payments', compact('payments', 'stats'));
    }
};
?>

<div class="min-w-0 px-3 py-5 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color: var(--accent);">Store finances</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl" style="color: var(--text-primary);">Payments</h1>
                <p class="mt-2 text-sm" style="color: var(--text-muted);">Review recorded payments, payment methods, and order references.</p>
            </div>
            <a href="{{ route('owner.analytics') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-semibold" style="border: 1px solid var(--border-soft); color: var(--text-primary);">View analytics</a>
        </header>

        <section class="grid gap-3 sm:grid-cols-3" aria-label="Payment summary">
            <article class="rounded-2xl p-4 sm:p-5" style="background: var(--bg-elevated); border: 1px solid var(--border-soft);">
                <p class="text-xs font-semibold" style="color: var(--text-muted);">Collected in this view</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($stats['amount'], 2) }}</p>
            </article>
            <article class="rounded-2xl p-4 sm:p-5" style="background: var(--bg-elevated); border: 1px solid var(--border-soft);">
                <p class="text-xs font-semibold" style="color: var(--text-muted);">Payment records</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">{{ number_format($stats['count']) }}</p>
            </article>
            <article class="rounded-2xl p-4 sm:p-5" style="background: var(--bg-elevated); border: 1px solid var(--border-soft);">
                <p class="text-xs font-semibold" style="color: var(--text-muted);">Cash collected in this view</p>
                <p class="mt-2 text-2xl font-black" style="color: var(--text-primary);">₱{{ number_format($stats['cash'], 2) }}</p>
            </article>
        </section>

        <section class="space-y-4 rounded-2xl p-4 sm:p-5" style="background: var(--bg-elevated); border: 1px solid var(--border-soft);">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(16rem,1fr)_12rem_12rem_12rem_auto] xl:items-end">
                <label class="block min-w-0">
                    <span class="mb-1 block text-xs font-semibold" style="color: var(--text-muted);">Search order or reference</span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Order number or reference" class="min-h-11 w-full min-w-0 rounded-xl px-3 text-sm outline-none" style="background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-soft);">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold" style="color: var(--text-muted);">Method</span>
                    <select wire:model.live="method" class="min-h-11 w-full rounded-xl px-3 text-sm outline-none" style="background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-soft);">
                        <option value="">All methods</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="grab_pay">GrabPay</option>
                        <option value="card">Card</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold" style="color: var(--text-muted);">From</span>
                    <input type="date" wire:model.live="dateFrom" class="min-h-11 w-full rounded-xl px-3 text-sm outline-none" style="background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-soft);">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold" style="color: var(--text-muted);">To</span>
                    <input type="date" wire:model.live="dateTo" class="min-h-11 w-full rounded-xl px-3 text-sm outline-none" style="background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-soft);">
                </label>
                <button type="button" wire:click="clearFilters" class="min-h-11 rounded-xl px-4 text-sm font-semibold" style="color: var(--text-muted); border: 1px solid var(--border-soft);">Clear</button>
            </div>

            <div class="overflow-x-auto rounded-xl" style="border: 1px solid var(--border-soft);">
                <table class="w-full min-w-[760px] border-collapse text-left text-sm">
                    <thead style="background: var(--bg-secondary); color: var(--text-muted);">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Order</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Paid by</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Location</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Method</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Reference</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Amount</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Paid at</th>
                        </tr>
                    </thead>
                    <tbody style="color: var(--text-primary);">
                        @forelse ($payments as $payment)
                            <tr wire:key="payment-{{ $payment->id }}" class="border-t" style="border-color: var(--border-soft);">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $payment->order?->order_number ? '#' . $payment->order->order_number : '—' }}</td>
                                <td class="px-4 py-3">{{ $payment->order?->user?->name ?? 'Guest' }}</td>
                                <td class="px-4 py-3">{{ $payment->branch?->name ?? 'Main store' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $payment->getMethodLabel() }}</td>
                                <td class="max-w-48 truncate px-4 py-3 font-mono text-xs" title="{{ $payment->reference_no }}">{{ $payment->reference_no ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold">₱{{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs" style="color: var(--text-muted);">{{ $payment->paid_at?->format('M j, Y · g:i A') ?? 'Not recorded' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center" style="color: var(--text-muted);">No payments match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $payments->links() }}</div>
        </section>
    </div>
</div>
