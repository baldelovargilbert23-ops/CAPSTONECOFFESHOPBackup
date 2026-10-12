<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on your Coffee Shop tenant application')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("Thank you for applying to register **{$this->tenant->name}**.")
            ->line('Unfortunately, your application was **not approved**.')
            ->line('**Reason from the admin:**')
            ->line('> ' . $this->reason)
            ->line('If you believe this is a mistake, please reply to this email and we will review it.')
            ->salutation('Regards, Coffee Shop Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'status'    => 'rejected',
            'reason'    => $this->reason,
        ];
    }
}