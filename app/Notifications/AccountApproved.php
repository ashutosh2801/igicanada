<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification
{
    use Queueable;

    public function __construct(protected User $user) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $channel = $this->user->account_type === 'retail' ? 'retail' : 'wholesale';

        $template = EmailTemplate::byName('active-mail-to-user', $channel) ?: EmailTemplate::byName('active-mail-to-user', 'wholesale');

        if ($template) {
            return (new MailMessage)
                ->subject($template->renderSubject([
                    'name' => $this->user->name,
                ]))
                ->view('mail.raw-template', [
                    'content' => $template->render([
                        'name' => $this->user->name,
                        'username' => $this->user->legacy_username ?: $this->user->email,
                        'password' => '',
                        'login_link' => route('login'),
                    ]),
                ]);
        }

        return (new MailMessage)
            ->subject('Your IGI Canada wholesale account has been activated')
            ->greeting('Hello '.$this->user->name.',')
            ->line('Your wholesale account at www.igicanada.ca has now been activated.')
            ->line('You can now access our wholesale prices.')
            ->action('Sign in', route('login'));
    }
}
