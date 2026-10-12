<?php

namespace App\Notifications;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Branch $branch) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your branch is approved 🎉')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("Your branch **{$this->branch->name}** ({$this->branch->code}) has been approved.")
            ->line('You can now start accepting orders at this location.')
            ->action('Go to dashboard', url('/dashboard'))
            ->line('Good luck with your new branch!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'branch_id' => $this->branch->id,
            'status'    => 'approved',
        ];
    }
}