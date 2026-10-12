<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your VictoriasHub password reset code')
            ->greeting('Hi ' . $notifiable->name . '!')
            ->line('We received a request to reset your password.')
            ->line('')
            ->line('# ' . $this->code)
            ->line('')
            ->line('This code expires in **15 minutes**.')
            ->line('If you didn\'t request a password reset, you can safely ignore this email — your password won\'t change.')
            ->salutation('— The VictoriasHub Team');
    }
}