<?php

namespace App\Livewire\Pages\Admin;

use App\Models\User;
use App\Notifications\SystemAnnouncement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public string $title = '';
    public string $message = '';
    public int $sentCount = 0;

    public function send(): void
    {
        abort_unless(Auth::user()?->role === 'admin', 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $title = trim((string) preg_replace('/\s+/u', ' ', strip_tags($validated['title'])));
        $message = trim(strip_tags($validated['message']));
        $message = str_replace(["\r\n", "\r"], "\n", $message);

        if ($title === '' || Str::length($title) > 100) {
            $this->addError('title', 'Enter a title using up to 100 characters.');
            return;
        }

        if ($message === '' || Str::length($message) > 1000) {
            $this->addError('message', 'Enter a message using up to 1,000 characters.');
            return;
        }

        $notification = new SystemAnnouncement($title, $message);
        $this->sentCount = 0;

        User::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($notification): void {
                DB::transaction(function () use ($users, $notification): void {
                    foreach ($users as $user) {
                        $user->notifyNow($notification);
                        $this->sentCount++;
                    }
                });
            });

        $this->reset(['title', 'message']);
        session()->flash('announcement_sent', "Announcement sent to {$this->sentCount} active accounts.");
    }

    public function render()
    {
        return view('pages.admin.⚡announcements.announcements', [
            'activeUsersCount' => User::query()->where('is_active', true)->count(),
        ]);
    }
};
