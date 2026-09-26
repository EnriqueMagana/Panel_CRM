<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $inviterName,
        public string $roleName,
        public ?string $note,
        public string $acceptUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Invitación a '.config('app.name'))
            ->greeting('Te invitaron a '.config('app.name'))
            ->line("{$this->inviterName} te invitó a colaborar en ".config('app.name').'.')
            ->line("Rol asignado: {$this->roleName}.");

        if ($this->note) {
            $message->line($this->note);
        }

        return $message
            ->action('Aceptar invitación y crear contraseña', $this->acceptUrl)
            ->line('El enlace vence en '.config('auth.passwords.users.expire').' minutos y solo se puede usar una vez.');
    }
}
