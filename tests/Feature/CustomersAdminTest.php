<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\AccountApproved;
use App\Notifications\AccountCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CustomersAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_resource_lists_retail_and_wholesale_accounts_but_not_admins(): void
    {
        $customer = User::factory()->create(['account_type' => 'wholesale']);
        $retailCustomer = User::factory()->create(['account_type' => 'retail']);
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);

        $listedIds = UserResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($listedIds->contains($customer->id));
        $this->assertTrue($listedIds->contains($retailCustomer->id));
        $this->assertFalse($listedIds->contains($admin->id));
    }

    public function test_admin_customers_page_uses_customers_label(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users')
            ->assertSuccessful()
            ->assertSee('Customers')
            ->assertDontSee('Wholesale accounts');
    }

    public function test_admin_account_cannot_be_opened_through_customers_resource(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/users/{$admin->id}/edit")
            ->assertNotFound();
    }

    public function test_approving_a_wholesale_application_sends_confirmation_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
        $applicant = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
            'email_verified_at' => now(),
        ]);

        EmailTemplate::where('name', 'active-mail-to-user')->delete();
        EmailTemplate::create([
            'name' => 'active-mail-to-user',
            'subject' => 'Account Activation Update',
            'content' => '<p>Dear {{name}},</p><p>Your account has been activated.</p><p>User: {{username}}</p><p><a href="{{login_link}}">Login</a></p>',
            'status' => 1,
            'sales_channel' => 'wholesale',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(ListUsers::class)
            ->callTableAction('approve', $applicant)
            ->assertHasNoActionErrors();

        $this->assertSame('approved', $applicant->fresh()->approval_status);
        $this->assertNotNull($applicant->fresh()->approved_at);

        Notification::assertSentTo(
            $applicant,
            AccountApproved::class,
            function (AccountApproved $notification) use ($applicant): bool {
                $mail = $notification->toMail($applicant);

                return str_contains($mail->viewData['content'], 'Your account has been activated')
                    && str_contains($mail->viewData['content'], $applicant->email);
            }
        );
    }

    public function test_rejecting_a_wholesale_application_sends_cancellation_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
        $applicant = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
        ]);

        EmailTemplate::where('name', 'non-acceptance-mail-to-user')->delete();
        EmailTemplate::create([
            'name' => 'non-acceptance-mail-to-user',
            'subject' => 'Application Status',
            'content' => '<p>Dear {{name}},</p><p>Your application was not accepted.</p>',
            'status' => 1,
            'sales_channel' => 'wholesale',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(ListUsers::class)
            ->callTableAction('reject', $applicant)
            ->assertHasNoActionErrors();

        $this->assertSame('rejected', $applicant->fresh()->approval_status);
        $this->assertNull($applicant->fresh()->approved_at);

        Notification::assertSentTo(
            $applicant,
            AccountCancelled::class,
            function (AccountCancelled $notification) use ($applicant): bool {
                $mail = $notification->toMail($applicant);

                return str_contains($mail->viewData['content'], 'Your application was not accepted.')
                    && str_contains($mail->viewData['content'], $applicant->name);
            }
        );
    }

    public function test_rejected_account_login_shows_cancellation_message(): void
    {
        $applicant = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'rejected',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $applicant->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wholesale_approval_uses_wholesale_template(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
        $applicant = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
        ]);

        EmailTemplate::where('name', 'active-mail-to-user')->delete();
        EmailTemplate::create([
            'name' => 'active-mail-to-user',
            'subject' => 'Wholesale Activation Update',
            'content' => '<p>https://www.igicanada.ca wholesale copy for {{name}}</p>',
            'status' => 1,
            'sales_channel' => 'wholesale',
        ]);
        EmailTemplate::create([
            'name' => 'active-mail-to-user',
            'subject' => 'Retail Activation Update',
            'content' => '<p>https://www.LeatherWallets.ca retail copy for {{name}}</p>',
            'status' => 1,
            'sales_channel' => 'retail',
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(ListUsers::class)
            ->callTableAction('approve', $applicant)
            ->assertHasNoActionErrors();

        Notification::assertSentTo(
            $applicant,
            AccountApproved::class,
            function (AccountApproved $notification) use ($applicant): bool {
                $mail = $notification->toMail($applicant);

                return str_contains($mail->viewData['content'], 'igicanada.ca wholesale copy')
                    && str_contains($mail->subject, 'Wholesale Activation Update');
            }
        );
    }

    public function test_retail_account_uses_retail_template_when_notified(): void
    {
        $retailCustomer = User::factory()->create([
            'account_type' => 'retail',
            'approval_status' => 'approved',
        ]);

        EmailTemplate::query()->delete();
        EmailTemplate::create([
            'name' => 'active-mail-to-user',
            'subject' => 'Wholesale Activation Update',
            'content' => '<p>https://www.igicanada.ca wholesale copy for {{name}}</p>',
            'status' => 1,
            'sales_channel' => 'wholesale',
        ]);
        EmailTemplate::create([
            'name' => 'active-mail-to-user',
            'subject' => 'Retail Activation Update',
            'content' => '<p>https://www.LeatherWallets.ca retail copy for {{name}}</p>',
            'status' => 1,
            'sales_channel' => 'retail',
        ]);

        $mail = (new AccountApproved($retailCustomer))->toMail($retailCustomer);

        $this->assertStringContainsString('LeatherWallets.ca retail copy', $mail->viewData['content']);
        $this->assertSame('Retail Activation Update', $mail->subject);

        $wholesale = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]);

        $mail = (new AccountApproved($wholesale))->toMail($wholesale);

        $this->assertStringContainsString('igicanada.ca wholesale copy', $mail->viewData['content']);
        $this->assertSame('Wholesale Activation Update', $mail->subject);
    }

    public function test_email_template_by_name_resolves_per_channel(): void
    {
        EmailTemplate::query()->delete();

        $wholesale = EmailTemplate::create([
            'name' => 'order-place-mail-to-user',
            'subject' => 'wholesale subject',
            'content' => '<p>wholesale body</p>',
            'status' => 1,
            'sales_channel' => 'wholesale',
        ]);
        $retail = EmailTemplate::create([
            'name' => 'order-place-mail-to-user',
            'subject' => 'retail subject',
            'content' => '<p>retail body</p>',
            'status' => 1,
            'sales_channel' => 'retail',
        ]);

        $this->assertSame($wholesale->id, EmailTemplate::byName('order-place-mail-to-user', 'wholesale')?->id);
        $this->assertSame($retail->id, EmailTemplate::byName('order-place-mail-to-user', 'retail')?->id);
    }
}
