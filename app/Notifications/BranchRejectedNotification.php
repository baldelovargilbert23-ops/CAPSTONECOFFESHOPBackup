<?php

namespace App\Notifications;

use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Branch $branch,
        public string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on your branch application')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("Your branch **{$this->branch->name}** ({$this->branch->code}) was not approved.")
            ->line('**Reason from the admin:**')
            ->line('> ' . $this->reason)
            ->line('You may resubmit after addressing the issue.')
            ->salutation('Regards, Coffee Shop Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'branch_id' => $this->branch->id,
            'status'    => 'rejected',
            'reason'    => $this->reason,
        ];
    }
}