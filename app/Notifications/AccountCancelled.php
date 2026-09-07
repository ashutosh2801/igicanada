<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCancelled extends Notification
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

        $template = EmailTemplate::byName('non-acceptance-mail-to-user', $channel) ?: EmailTemplate::byName('non-acceptance-mail-to-user', 'wholesale');

        if ($template) {
            return (new MailMessage)
                ->subject($template->renderSubject([
                    'name' => $this->user->name,
                ]))
                ->view('mail.raw-template', [
                    'content' => $template->render([
                        'name' => $this->user->name,
                    ]),
                ]);
        }

        return (new MailMessage)
            ->subject('Your IGI Canada wholesale application was not accepted')
            ->greeting('Hello '.$this->user->name.',')
            ->line('Thank you for your interest in a wholesale account at www.igicanada.ca.')
            ->line('Your application was not accepted at this time.')
            ->line('You may send your inquiries to info@igicanada.ca or call 905-625-8831 for wholesale dealership information.');
    }
}
