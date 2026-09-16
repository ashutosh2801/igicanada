<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_account_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => 'correct-password',
            'approval_status' => 'approved',
            'account_type' => 'wholesale',
        ]);

        $this->post('/login', [
            'email' => 'BUYER@example.com',
            'password' => 'correct-password',
        ])->assertRedirect('/account');

        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_pending_reseller_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'pending@example.com',
            'password' => 'correct-password',
            'approval_status' => 'pending',
            'account_type' => 'wholesale',
        ]);

        $this->post('/login', [
            'email' => 'pending@example.com',
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unverified_wholesale_account_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => 'correct-password',
            'approval_status' => 'approved',
            'account_type' => 'wholesale',
            'email_verified_at' => null,
        ]);

        $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_retail_account_cannot_sign_in_to_wholesale_website(): void
    {
        User::factory()->create([
            'email' => 'retail@example.com',
            'password' => 'correct-password',
            'approval_status' => 'approved',
            'account_type' => 'customer',
        ]);

        $this->post('/login', [
            'email' => 'retail@example.com',
            'password' => 'correct-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_reset_activates_migrated_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'legacy@example.com',
            'password' => 'unusable-random-password',
            'approval_status' => 'approved',
            'account_type' => 'wholesale',
            'must_reset_password' => true,
        ]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect('/login');

        $user->refresh();
        $this->assertFalse($user->must_reset_password);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_account_dashboard_requires_authentication(): void
    {
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_retail_account_cannot_access_reseller_dashboard(): void
    {
        $user = User::factory()->create([
            'account_type' => 'customer',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($user)->get('/account')->assertForbidden();
    }

    public function test_admin_login_does_not_block_the_storefront_login_page(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);

        Auth::guard('admin')->setUser($admin);
        $this->get('/admin')->assertSuccessful();
        $this->assertSame('admin', auth()->getDefaultDriver());

        $this->get('/login')->assertSuccessful();
        $this->get('/forgot-password')->assertSuccessful();
    }

    public function test_admin_and_wholesale_storefront_logins_coexist_in_the_same_session(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
        $customer = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($customer)->get('/catalogue')->assertInertia(fn (Assert $page) => $page
            ->component('Catalogue/Index')
            ->where('auth.user.id', $customer->id));

        Auth::guard('admin')->setUser($admin);
        $this->get('/admin')->assertSuccessful();

        Auth::shouldUse('web');

        $this->assertSame($customer->id, auth()->id());
        $this->assertSame($admin->id, auth('admin')->id());

        $this->get('/catalogue')->assertInertia(fn (Assert $page) => $page
            ->component('Catalogue/Index')
            ->where('auth.user.id', $customer->id));
    }
}
