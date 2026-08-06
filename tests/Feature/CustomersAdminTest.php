<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($admin)
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

        $this->actingAs($admin)
            ->get("/admin/users/{$admin->id}/edit")
            ->assertNotFound();
    }
}
