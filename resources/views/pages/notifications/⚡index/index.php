<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public function updatedFilter(): void
    {
        if (! in_array($this->filter, ['all', 'unread', 'read'], true)) {
            $this->filter = 'all';
        }

        $this->resetPage();
    }

    public function markAsRead(string $id): void
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();

        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications()->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function deleteNotification(string $id): void
    {
        Auth::user()->notifications()->whereKey($id)->delete();
    }

    public function clearAll(): void
    {
        Auth::user()->notifications()->delete();
        $this->resetPage();
    }

    public function render()
    {
        $query = Auth::user()->notifications()->latest();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return view('pages.notifications.⚡index.index', [
            'notifications' => $query->paginate(15),
            'unreadCount' => Auth::user()->unreadNotifications()->count(),
        ])->layout('layouts.notifications');
    }
};
