@extends('layouts.admin')

@section('content')
<div class="min-h-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Super Admin</p>
                <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white sm:text-3xl">Tenant approvals</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Review a coffee shop before it becomes available to customers.</p>
            </div>
            <a href="{{ route('admin.branch-approvals') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">Review branch requests</a>
        </div>

        @foreach(['success' => 'emerald', 'info' => 'blue', 'error' => 'rose'] as $key => $tone)
            @if(session($key))
                <div role="status" class="rounded-xl border border-{{ $tone }}-200 bg-{{ $tone }}-50 px-4 py-3 text-sm font-semibold text-{{ $tone }}-900 dark:border-{{ $tone }}-900 dark:bg-{{ $tone }}-950/40 dark:text-{{ $tone }}-200">{{ session($key) }}</div>
            @endif
        @endforeach

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'total' => 'All tenants'] as $key => $label)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($stats[$key]) }}</p>
                </div>
            @endforeach
        </div>

        @if($pendingTenants->isNotEmpty())
            <form method="POST" action="{{ route('admin.tenant-approvals.bulk-approve') }}" class="space-y-3">
                @csrf
                <div class="flex justify-end">
                    <button type="submit" class="min-h-10 rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Approve selected</button>
                </div>
                @foreach($pendingTenants as $tenant)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <label class="flex min-h-10 shrink-0 items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                <input type="checkbox" name="tenant_ids[]" value="{{ $tenant->id }}" class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                Select
                            </label>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $tenant->name }}</h2>
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-900 dark:bg-amber-300/15 dark:text-amber-200">Pending review</span>
                                </div>
                                <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                                    <div><dt class="inline text-slate-500 dark:text-slate-400">Email: </dt><dd class="inline break-all text-slate-800 dark:text-slate-200">{{ $tenant->email }}</dd></div>
                                    <div><dt class="inline text-slate-500 dark:text-slate-400">Contact: </dt><dd class="inline text-slate-800 dark:text-slate-200">{{ $tenant->contact_number ?: 'Not provided' }}</dd></div>
                                    <div><dt class="inline text-slate-500 dark:text-slate-400">Owner: </dt><dd class="inline text-slate-800 dark:text-slate-200">{{ $tenant->owner_name ?: $tenant->users->first()?->name ?: 'Not provided' }}</dd></div>
                                    <div><dt class="inline text-slate-500 dark:text-slate-400">Submitted: </dt><dd class="inline text-slate-800 dark:text-slate-200">{{ $tenant->created_at?->format('M j, Y') }}</dd></div>
                                    <div class="sm:col-span-2"><dt class="inline text-slate-500 dark:text-slate-400">Address: </dt><dd class="inline text-slate-800 dark:text-slate-200">{{ $tenant->address ?: 'Not provided' }}</dd></div>
                                </dl>
                            </div>
                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                <button type="submit" formaction="{{ route('admin.tenant-approvals.approve', $tenant) }}" class="min-h-10 rounded-xl bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Approve</button>
                                <a href="{{ route('admin.tenant-approvals.reject-form', $tenant) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/40">Reject</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </form>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                <h2 class="font-bold text-slate-900 dark:text-white">No tenant applications waiting</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">New shop applications will appear here.</p>
            </div>
        @endif
    </div>
</div>
@endsection
