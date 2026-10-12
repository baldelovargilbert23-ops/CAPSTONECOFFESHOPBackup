<section class="space-y-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Your account</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white sm:text-3xl">Notifications</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Order updates and shop announcements for your account.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-900 dark:bg-amber-300/15 dark:text-amber-200">
                {{ $unreadCount }} unread
            </span>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" wire:loading.attr="disabled"
                        class="min-h-10 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    Mark all read
                </button>
            @endif
            @if($notifications->total() > 0)
                <button wire:click="clearAll" wire:confirm="Clear all your notifications?"
                        class="min-h-10 rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/40">
                    Clear all
                </button>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap gap-2" role="group" aria-label="Filter notifications">
        @foreach(['all' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $value => $label)
            <button wire:click="$set('filter', '{{ $value }}')"
                    @class([
                        'min-h-10 rounded-xl px-4 py-2 text-sm font-semibold transition',
                        'bg-slate-900 text-white dark:bg-amber-300 dark:text-slate-950' => $filter === $value,
                        'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800' => $filter !== $value,
                    ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($notifications as $notification)
            @php
                $data = is_array($notification->data) ? $notification->data : [];
                $isUnread = $notification->read_at === null;
            @endphp
            <article wire:key="notification-{{ $notification->id }}"
                     class="rounded-2xl border p-4 shadow-sm sm:p-5 {{ $isUnread ? 'border-amber-300 bg-amber-50/70 dark:border-amber-800 dark:bg-amber-950/20' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }}">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isUnread ? 'bg-amber-200 text-amber-950 dark:bg-amber-300/20 dark:text-amber-200' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}" aria-hidden="true">
                        <i class="fa fa-bell"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h2 class="font-bold text-slate-900 dark:text-white">{{ $data['title'] ?? 'Notification' }}</h2>
                            <time class="text-xs text-slate-500 dark:text-slate-400" datetime="{{ $notification->created_at?->toIso8601String() }}">
                                {{ $notification->created_at?->diffForHumans() }}
                            </time>
                        </div>
                        @if(filled($data['message'] ?? null))
                            <p class="mt-1 whitespace-pre-line break-words text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $data['message'] }}</p>
                        @endif
                        <div class="mt-3 flex justify-end gap-3">
                            @if($isUnread)
                                <button wire:click="markAsRead('{{ $notification->id }}')"
                                        class="min-h-9 rounded-lg px-3 text-xs font-bold text-amber-800 hover:bg-amber-100 dark:text-amber-200 dark:hover:bg-amber-900/40">
                                    Mark read
                                </button>
                            @endif
                            <button wire:click="deleteNotification('{{ $notification->id }}')"
                                    wire:confirm="Delete this notification?"
                                    class="min-h-9 rounded-lg px-3 text-xs font-bold text-slate-500 hover:bg-slate-100 hover:text-rose-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-rose-300">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <i class="fa fa-bell text-xl"></i>
                </span>
                <h2 class="mt-4 font-bold text-slate-900 dark:text-white">No notifications here</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">New updates will show up here.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
</section>
