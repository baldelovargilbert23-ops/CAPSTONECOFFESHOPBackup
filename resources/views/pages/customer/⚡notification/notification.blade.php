<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.customer')] class extends Component
{
    #[Url(as: 'filter')]
    public string $filter = 'all';   // all | unread | read

    public int $limit = 15;

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->limit = 15;
    }

    public function loadMore(): void
    {
        $this->limit += 15;
    }

    public function markAsRead(string $id): void
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $this->dispatch('notifications-updated', count: Auth::user()->unreadNotifications()->count());
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();

        $this->dispatch('notifications-updated', count: 0);
        session()->flash('success', 'All notifications marked as read.');
    }

    public function deleteNotification(string $id): void
    {
        Auth::user()->notifications()->where('id', $id)->delete();
        $this->dispatch('notifications-updated', count: Auth::user()->unreadNotifications()->count());
        session()->flash('success', 'Notification deleted.');
    }

    public function clearAll(): void
    {
        Auth::user()->notifications()->delete();
        $this->dispatch('notifications-updated', count: 0);
        session()->flash('success', 'All notifications cleared.');
    }

    public function render()
    {
        $query = Auth::user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->latest()->take($this->limit)->get();
        $totalCount    = Auth::user()->notifications()->count();
        $unreadCount   = Auth::user()->unreadNotifications()->count();

      return view('pages.customer.⚡notification.notification', [
            'notifications' => $notifications,
            'totalCount'    => $totalCount,
            'unreadCount'   => $unreadCount,
            'hasMore'       => $notifications->count() >= $this->limit && $notifications->count() < $totalCount,
        ]);
    }
};
?>
<div class="min-h-screen bg-[#f5f0e8] dark:bg-[#0a1f1a] font-sans text-[#1a3a3a] dark:text-[#f0ebe0] transition-colors duration-300">

    {{-- Ambient background --}}
    <div class="fixed inset-0 pointer-events-none opacity-40 dark:opacity-25"
         style="background:
             radial-gradient(circle at 10% 15%, color-mix(in srgb, #c8965f 22%, transparent), transparent 42%),
             radial-gradient(circle at 90% 15%, color-mix(in srgb, #16a34a 18%, transparent), transparent 42%);
         ">
    </div>

   <main class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10 space-y-4 sm:space-y-5">
        {{-- Flash messages --}}
        @if(session()->has('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                 x-transition
                 class="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 rounded-2xl text-sm font-medium flex items-center gap-3">
                <i class="fa fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Header --}}
        <div class="bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 shadow-lg p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/20 dark:border-[#d4a574]/20 flex items-center justify-center flex-shrink-0">
                        <i class="fa fa-bell text-[#c8965f] dark:text-[#d4a574] text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#1a3a3a] dark:text-[#f0ebe0]">
                            Notifications
                        </h1>
                        <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-0.5">
                            {{ $totalCount }} total
                            @if($unreadCount > 0)
                                · <span class="text-[#c8965f] dark:text-[#d4a574] font-bold">{{ $unreadCount }} unread</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($unreadCount > 0 || $totalCount > 0)
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($unreadCount > 0)
                            <button wire:click="markAllAsRead"
                                    class="px-3.5 py-2 text-xs font-bold rounded-xl text-[#c8965f] dark:text-[#d4a574] bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/30 dark:border-[#d4a574]/30 hover:bg-[#c8965f]/20 transition-all flex items-center gap-1.5">
                                <i class="fa fa-check-double text-[10px]"></i>
                                Mark all read
                            </button>
                        @endif
                        @if($totalCount > 0)
                            <button wire:click="clearAll"
                                    wire:confirm="Delete ALL notifications? This cannot be undone."
                                    class="px-3.5 py-2 text-xs font-bold rounded-xl text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 bg-[#1a3a3a]/5 dark:bg-[#c8965f]/5 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:text-rose-500 hover:border-rose-500/40 transition-all flex items-center gap-1.5">
                                <i class="fa fa-trash text-[10px]"></i>
                                Clear all
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Filter tabs --}}
            <div class="mt-5 pt-4 border-t border-[#1a3a3a]/5 dark:border-[#c8965f]/10">
                <div class="inline-flex items-center gap-1 p-1 rounded-xl bg-[#1a3a3a]/5 dark:bg-[#c8965f]/5 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                    @foreach([
                        'all'    => 'All',
                        'unread' => 'Unread',
                        'read'   => 'Read',
                    ] as $key => $label)
                        <button wire:click="setFilter('{{ $key }}')"
                                class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all
                                    {{ $filter === $key
                                        ? 'bg-[#c8965f] dark:bg-[#d4a574] text-[#f5f0e8] dark:text-[#0a1f1a] shadow-sm'
                                        : 'text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 hover:text-[#c8965f] dark:hover:text-[#d4a574]' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Notifications list --}}
        <div class="space-y-2.5">
            @forelse($notifications as $notif)
                @php
                    $isUnread = is_null($notif->read_at);
                    $data = $notif->data ?? [];
                    $title = $data['title'] ?? 'Notification';
                    $message = $data['message'] ?? '';
                    $url = $data['url'] ?? null;
                @endphp

                <div class="group bg-[#faf6ef] dark:bg-[#0f2922] rounded-2xl border {{ $isUnread ? 'border-[#c8965f]/40 dark:border-[#d4a574]/40' : 'border-[#1a3a3a]/10 dark:border-[#c8965f]/15' }} overflow-hidden transition-all hover:shadow-lg hover:shadow-[#c8965f]/5 hover:-translate-y-0.5">

                    <div class="flex items-start gap-3 p-4 sm:p-5">
                        {{-- Icon --}}
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                                    {{ $isUnread ? 'bg-[#c8965f]/10 dark:bg-[#d4a574]/10 border border-[#c8965f]/30 dark:border-[#d4a574]/30' : 'bg-[#1a3a3a]/5 dark:bg-[#c8965f]/5 border border-[#1a3a3a]/10 dark:border-[#c8965f]/15' }}">
                            <i class="fa fa-bell text-sm {{ $isUnread ? 'text-[#c8965f] dark:text-[#d4a574]' : 'text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40' }}"></i>
                        </div>

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3 mb-1">
                                <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                    <h3 class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0] leading-snug truncate">
                                        {{ $title }}
                                    </h3>
                                    @if($isUnread)
                                        <span class="text-[9px] font-black px-1.5 py-0.5 rounded-full bg-[#c8965f]/15 dark:bg-[#d4a574]/15 text-[#c8965f] dark:text-[#d4a574] border border-[#c8965f]/30 dark:border-[#d4a574]/30 flex-shrink-0 uppercase tracking-wider">
                                            New
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-[#1a3a3a]/40 dark:text-[#f0ebe0]/40 whitespace-nowrap flex-shrink-0 font-mono">
                                    {{ $notif->created_at->diffForHumans() }}
                                </span>
                            </div>

                            @if($message)
                                <p class="text-xs text-[#1a3a3a]/70 dark:text-[#f0ebe0]/70 leading-relaxed mb-2">
                                    {{ $message }}
                                </p>
                            @endif

                            {{-- Actions --}}
                            <div class="flex items-center gap-3 mt-3">
                                @if($url)
                                    <a href="{{ $url }}"
                                       wire:click="markAsRead('{{ $notif->id }}')"
                                       class="text-[11px] font-bold text-[#c8965f] dark:text-[#d4a574] hover:opacity-80 transition-opacity uppercase tracking-wider flex items-center gap-1">
                                        <i class="fa fa-arrow-right text-[9px]"></i> View
                                    </a>
                                @endif

                                @if($isUnread)
                                    <button wire:click="markAsRead('{{ $notif->id }}')"
                                            class="text-[11px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 hover:text-emerald-500 transition-colors uppercase tracking-wider flex items-center gap-1">
                                        <i class="fa fa-check text-[9px]"></i> Mark read
                                    </button>
                                @endif

                                <button wire:click="deleteNotification('{{ $notif->id }}')"
                                        wire:confirm="Delete this notification?"
                                        class="text-[11px] font-bold text-[#1a3a3a]/50 dark:text-[#f0ebe0]/50 hover:text-rose-500 transition-colors uppercase tracking-wider ml-auto flex items-center gap-1">
                                    <i class="fa fa-trash text-[9px]"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-[#faf6ef] dark:bg-[#0f2922] rounded-3xl border-2 border-dashed border-[#1a3a3a]/10 dark:border-[#c8965f]/15">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-[#1a3a3a]/5 dark:bg-[#c8965f]/10 border border-[#1a3a3a]/10 dark:border-[#c8965f]/20 flex items-center justify-center">
                        <i class="fa fa-bell text-3xl text-[#1a3a3a]/20 dark:text-[#f0ebe0]/20"></i>
                    </div>
                    <p class="text-sm font-bold text-[#1a3a3a] dark:text-[#f0ebe0]">
                        @if($filter === 'unread')
                            No unread notifications
                        @elseif($filter === 'read')
                            No read notifications
                        @else
                            No notifications yet
                        @endif
                    </p>
                    <p class="text-xs text-[#1a3a3a]/60 dark:text-[#f0ebe0]/60 mt-1">
                        Order updates and rewards will appear here
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Load more --}}
        @if($hasMore)
            <div class="text-center pt-2">
                <button wire:click="loadMore"
                        wire:loading.attr="disabled"
                        class="px-6 py-3 text-xs font-bold rounded-xl bg-[#faf6ef] dark:bg-[#0f2922] text-[#1a3a3a] dark:text-[#f0ebe0] border border-[#1a3a3a]/10 dark:border-[#c8965f]/15 hover:border-[#c8965f]/40 dark:hover:border-[#d4a574]/40 hover:text-[#c8965f] dark:hover:text-[#d4a574] transition-all">
                    <span wire:loading.remove wire:target="loadMore">
                        <i class="fa fa-arrow-down text-[10px] mr-1"></i> Load more
                    </span>
                    <span wire:loading wire:target="loadMore">
                        <i class="fa fa-spinner fa-spin text-[10px] mr-1"></i> Loading...
                    </span>
                </button>
            </div>
        @endif

    </main>
</div>