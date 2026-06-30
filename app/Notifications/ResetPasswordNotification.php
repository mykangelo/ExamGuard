<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/reset-password?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset()));
        $expire = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reset your ExamGuard password')
            ->greeting('Hello!')
            ->line('We received a request to reset the password for your ExamGuard account.')
            ->action('Reset password', $url)
            ->line("This link expires in {$expire} minutes.")
            ->line('If you did not request a password reset, you can ignore this email.');
    }
}
