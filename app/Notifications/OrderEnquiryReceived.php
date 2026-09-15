<?php

namespace App\Notifications;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderEnquiryReceived extends Notification
{
    use Queueable;

    public function __construct(public ContactEnquiry $enquiry, public array $orderSummary) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Order enquiry: '.$this->enquiry->subject)
            ->line("From: {$this->enquiry->name} ({$this->enquiry->email})")
            ->line('Phone: '.($this->enquiry->phone ?: 'Not provided'))
            ->line('Message:')
            ->line($this->enquiry->message);

        if (! empty($this->orderSummary['items'])) {
            $mail->line('Proposed order:')
                ->line('Subtotal: $'.$this->orderSummary['subtotal'].' CAD');
            foreach ($this->orderSummary['items'] as $item) {
                $mail->line("• {$item['name']} ({$item['option']}) × {$item['quantity']} — \${$item['line_total']} CAD");
            }
            $mail->line('Estimated total: $'.$this->orderSummary['total'].' CAD');
        }

        return $mail->action('Review enquiry', url('/admin/contact-enquiries/'.$this->enquiry->id.'/edit'));
    }
}
