<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Tenant $tenant) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Coffee Shop tenant account is approved ☕')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line("Great news! Your tenant **{$this->tenant->name}** has been approved.")
            ->line('You can now log in and manage your branches, menu, and orders.')
            ->action('Log in now', url('/login'))
            ->line('Welcome to Coffee Shop!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'status'    => 'approved',
        ];
    }
}