<div class="min-h-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Super Admin</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white sm:text-3xl">Announcements</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">Send a plain-text announcement to every active customer, tenant, branch manager, cashier, barista, and admin account.</p>
        </div>

        @if(session('announcement_sent'))
            <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('announcement_sent') }}
            </div>
        @endif

        <form wire:submit="send" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Recipients</span>
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900 dark:bg-amber-300/15 dark:text-amber-200">{{ number_format($activeUsersCount) }} active accounts</span>
            </div>

            <div>
                <label for="announcement-title" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-100">Title</label>
                <input id="announcement-title" type="text" maxlength="100" wire:model="title" required
                       class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                       placeholder="For example: Holiday hours">
                @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="announcement-message" class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-100">Message</label>
                <textarea id="announcement-message" rows="5" maxlength="1000" wire:model="message" required
                          class="w-full resize-y rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                          placeholder="Write a clear update for everyone."></textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">HTML is removed and the message is shown as text. Maximum 1,000 characters.</p>
                @error('message') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <p class="text-xs text-slate-500 dark:text-slate-400">Each recipient gets a private, dismissible inbox notification.</p>
                <button type="submit" wire:loading.attr="disabled" wire:confirm="Send this announcement to all active accounts?"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 disabled:cursor-wait disabled:opacity-60 dark:bg-amber-300 dark:text-slate-950 dark:hover:bg-amber-200">
                    <i class="fa fa-paper-plane" wire:loading.remove wire:target="send" aria-hidden="true"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="send" aria-hidden="true"></i>
                    <span>Send announcement</span>
                </button>
            </div>
        </form>
    </div>
</div>
