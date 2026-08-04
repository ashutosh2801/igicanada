<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Notifications\ContactEnquiryReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactEnquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_contact_enquiry_is_stored_and_the_team_is_notified(): void
    {
        Notification::fake();
        config()->set('commerce.order_notification_email', 'sales@example.com');

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])->post('/contact', [
            'name' => 'Taylor Merchant',
            'email' => 'taylor@example.com',
            'phone' => '416-555-0100',
            'company' => 'Market Goods',
            'subject' => 'Wholesale account',
            'message' => 'Please tell me more about opening a wholesale account.',
            'website' => '',
        ]);

        $response->assertRedirect()->assertSessionHas('status');
        $enquiry = ContactEnquiry::firstOrFail();
        $this->assertSame('new', $enquiry->status);
        $this->assertNotSame('203.0.113.42', $enquiry->ip_hash);
        $this->assertSame(64, strlen((string) $enquiry->ip_hash));
        Notification::assertSentOnDemand(ContactEnquiryReceived::class);
    }

    public function test_the_honeypot_rejects_bot_submissions(): void
    {
        $this->post('/contact', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'This message is long enough.',
            'website' => 'https://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, ContactEnquiry::count());
    }
}
