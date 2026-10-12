@auth
    @php($notificationUnreadCount = auth()->user()->unreadNotifications()->count())
    <a href="{{ route('notifications.index') }}"
       class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-amber-400 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-400 dark:hover:text-amber-300"
       aria-label="Notifications{{ $notificationUnreadCount ? ', ' . $notificationUnreadCount . ' unread' : '' }}"
       title="Notifications">
        <i class="fa fa-bell" aria-hidden="true"></i>
        @if($notificationUnreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-black leading-none text-white">
                {{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}
            </span>
        @endif
    </a>
@endauth
