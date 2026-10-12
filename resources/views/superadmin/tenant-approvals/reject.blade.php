@extends('layouts.admin')

@section('content')
<div class="min-h-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-2xl space-y-5">
        <a href="{{ route('admin.tenant-approvals') }}" class="inline-flex min-h-10 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back to tenant approvals</a>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-rose-700 dark:text-rose-300">Application review</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Reject {{ $tenant->name }}</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Give a clear reason so the owner understands what needs attention.</p>
            <form method="POST" action="{{ route('admin.tenant-approvals.reject', $tenant) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="rejection_reason" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-100">Reason</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="5" minlength="10" maxlength="500" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.tenant-approvals') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancel</a>
                    <button type="submit" class="min-h-11 rounded-xl bg-rose-700 px-4 py-2 text-sm font-bold text-white hover:bg-rose-800">Confirm rejection</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
