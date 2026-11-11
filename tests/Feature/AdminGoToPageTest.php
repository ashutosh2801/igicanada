<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminGoToPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_go_to_page_dropdown_calls_goto_page_without_livewire_parsing_error(): void
    {
        $this->actingAs(User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]), 'admin');
        Filament::setCurrentPanel('admin');

        foreach (range(1, 120) as $i) {
            Product::create(['name' => "Product {$i}", 'slug' => "product-{$i}", 'visibility' => 'both', 'is_active' => true]);
        }

        Livewire::test(ListProducts::class)
            ->assertSeeHtml('x-on:change="$wire.gotoPage(parseInt($event.target.value, 10)')
            ->assertDontSeeHtml('wire:change="gotoPage(Number(')
            ->call('gotoPage', 2, 'page')
            ->assertHasNoErrors()
            ->assertSet('paginators.page', 2);
    }
}
