<?php

namespace Tests\Feature;

use App\Models\PriceTier;
use App\Models\User;
use App\Notifications\VerifyWholesaleEmail;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResellerApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_reseller_application_form(): void
    {
        $this->get('/wholesale/apply')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Apply')
            ->where('signedInAccount', null));
    }

    public function test_signed_in_admin_can_open_application_page_without_guest_redirect(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($admin)->get('/wholesale/apply')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Apply')
            ->where('signedInAccount.destinationLabel', 'Open admin panel')
            ->where('signedInAccount.destinationUrl', '/admin'));
    }

    public function test_pending_reseller_gets_an_account_status_page(): void
    {
        $reseller = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($reseller)->get('/account/status')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->component('Account/Status')
            ->where('account.approvalStatus', 'pending'));
    }

    public function test_business_can_submit_a_pending_reseller_application(): void
    {
        Notification::fake();
        $tier = PriceTier::create(['name' => 'Level 1', 'discount_percentage' => 0, 'is_active' => true]);

        $response = $this->post('/wholesale/apply', [
            'name' => 'Jordan Buyer',
            'email' => 'BUYER@example.com',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'company' => 'Main Street Goods',
            'phone' => '416-555-0100',
            'business_number' => 'BN-100',
            'tax_number' => 'HST-200',
            'address' => '10 King Street',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
        ]);

        $response->assertRedirect('/wholesale/application-received');
        $response->assertSessionHas('verification_email', 'buyer@example.com');
        $user = User::where('email', 'buyer@example.com')->firstOrFail();
        $this->assertSame('wholesale', $user->account_type);
        $this->assertSame('pending', $user->approval_status);
        $this->assertSame($tier->id, $user->price_tier_id);
        $this->assertSame('Main Street Goods', $user->resellerProfile->company);
        $this->assertSame('10 King Street', $user->addresses->first()->address_line1);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyWholesaleEmail::class);
    }

    public function test_signed_email_link_verifies_wholesale_application(): void
    {
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
            'email_verified_at' => null,
        ]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($url)
            ->assertRedirect('/wholesale/application-received')
            ->assertSessionHas('status', 'Email verified successfully. Your wholesale application is now waiting for administrator approval.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_invalid_email_verification_signature_is_rejected(): void
    {
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
            'email_verified_at' => null,
        ]);

        $this->get("/wholesale/verify-email/{$user->id}/".sha1($user->email))->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_duplicate_email_cannot_submit_an_application(): void
    {
        User::factory()->create(['email' => 'buyer@example.com']);

        $this->post('/wholesale/apply', [
            'name' => 'Jordan Buyer',
            'email' => 'buyer@example.com',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'company' => 'Main Street Goods',
            'phone' => '416-555-0100',
            'address' => '10 King Street',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_only_approved_admins_can_access_filament(): void
    {
        $panel = Filament::getPanel('admin');
        $reseller = User::factory()->make(['account_type' => 'wholesale', 'approval_status' => 'approved']);
        $admin = User::factory()->make(['account_type' => 'admin', 'approval_status' => 'approved']);

        $this->assertFalse($reseller->canAccessPanel($panel));
        $this->assertTrue($admin->canAccessPanel($panel));
    }
}
