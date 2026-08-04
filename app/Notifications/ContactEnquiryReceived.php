<?php

namespace App\Notifications;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactEnquiryReceived extends Notification
{
    use Queueable;

    public function __construct(public ContactEnquiry $enquiry) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Website enquiry: '.($this->enquiry->subject ?: 'General enquiry'))
            ->line("From: {$this->enquiry->name} ({$this->enquiry->email})")
            ->line('Company: '.($this->enquiry->company ?: 'Not provided'))
            ->line($this->enquiry->message)
            ->action('Review enquiry', url('/admin/contact-enquiries/'.$this->enquiry->id.'/edit'));
    }
}
