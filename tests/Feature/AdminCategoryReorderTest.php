<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCategoryReorderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
    }

    public function test_drag_and_drop_reorder_updates_category_positions(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $wallets = Category::create(['name' => 'Wallets', 'slug' => 'wallets', 'position' => 1]);
        $bags = Category::create(['name' => 'Bags', 'slug' => 'bags', 'position' => 2]);
        $belts = Category::create(['name' => 'Belts', 'slug' => 'belts', 'position' => 3]);

        Livewire::test(ListCategories::class)
            ->call('reorderTable', [$belts->getKey(), $wallets->getKey(), $bags->getKey()]);

        $this->assertSame(1, $belts->fresh()->position);
        $this->assertSame(2, $wallets->fresh()->position);
        $this->assertSame(3, $bags->fresh()->position);
    }
}