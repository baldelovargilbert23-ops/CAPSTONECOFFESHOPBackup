<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification
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
            ->subject('Your VictoriasHub verification code')
            ->greeting('Hi ' . $notifiable->name . '!')
            ->line('Use the code below to verify your email address:')
            ->line('')
            ->line('# ' . $this->code)
            ->line('')
            ->line('This code expires in **15 minutes**.')
            ->line('If you didn\'t create this account, you can safely ignore this email.')
            ->salutation('— The VictoriasHub Team');
    }
}