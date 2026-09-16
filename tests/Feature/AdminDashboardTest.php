<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_analytics_and_recent_activity(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertSuccessful()
            ->assertSeeText('Revenue this month')
            ->assertSeeText('Orders this month')
            ->assertSeeText('Orders requiring action')
            ->assertSeeText('New enquiries')
            ->assertSeeText('Sales trend')
            ->assertSeeText('Recent orders')
            ->assertSeeText('Recent enquiries')
            ->assertDontSeeText('Filament version');
    }
}
