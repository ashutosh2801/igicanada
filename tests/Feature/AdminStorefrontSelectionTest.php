<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\ContactEnquiry;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminStorefront;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStorefrontSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_only_offers_individual_websites(): void
    {
        $this->get('/admin/login')
            ->assertSuccessful()
            ->assertDontSeeText('Which website are you logging in for?')
            ->assertDontSeeText('All Stores')
            ->assertSeeText('IGI Canada · Wholesale')
            ->assertSeeText('Leather Wallets · Retail');
    }

    public function test_admin_selects_a_store_on_first_panel_visit_and_choice_is_remembered(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertRedirect(route('admin.storefront.select'));

        $this->get(route('admin.storefront.select'))
            ->assertSuccessful()
            ->assertSeeText('Which store would you like to manage?')
            ->assertSeeText('All Stores')
            ->assertSeeText('IGI Canada · Wholesale')
            ->assertSeeText('Leather Wallets · Retail');

        $this->post(route('admin.storefront.store'), [
            'sales_channel' => 'retail',
            'initial_selection' => true,
        ])->assertRedirect('/admin');

        $this->assertSame('retail', $admin->fresh()->admin_sales_channel);
        $this->assertSame('retail', session(AdminStorefront::SESSION_KEY));
        $this->get('/admin')->assertSuccessful()->assertSeeText('Leather Wallets · Retail');
    }

    public function test_remembered_store_opens_panel_without_asking_again(): void
    {
        $admin = $this->admin('wholesale');

        $this->actingAs($admin, 'admin')
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('name="sales_channel"', false)
            ->assertDontSeeText('Which website are you logging in for?')
            ->assertDontSeeText('All Stores')
            ->assertSeeText('IGI Canada · Wholesale');

        $this->assertSame('wholesale', session(AdminStorefront::SESSION_KEY));
    }

    public function test_selected_store_filters_shared_admin_resources(): void
    {
        $this->actingAs($this->admin('retail'), 'admin')
            ->withSession([AdminStorefront::SESSION_KEY => 'retail']);

        $retailProduct = $this->product('retail-product', 'retail');
        $bothProduct = $this->product('both-product', 'both');
        $this->product('wholesale-product', 'wholesale');

        $retailOrder = $this->order('RETAIL-1', 'retail');
        $this->order('WHOLESALE-1', 'wholesale');

        $retailCustomer = User::factory()->create(['account_type' => 'retail']);
        User::factory()->create(['account_type' => 'wholesale']);

        $retailEnquiry = $this->enquiry('retail@example.com', 'retail');
        $this->enquiry('wholesale@example.com', 'wholesale');

        $this->assertEqualsCanonicalizing(
            [$retailProduct->id, $bothProduct->id],
            ProductResource::getEloquentQuery()->pluck('id')->all(),
        );
        $this->assertSame([$retailOrder->id], OrderResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$retailCustomer->id], UserResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$retailEnquiry->id], ContactEnquiryResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_invalid_store_selection_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.storefront.store'), ['sales_channel' => 'unknown'])
            ->assertSessionHasErrors('sales_channel');
    }

    public function test_product_edit_record_binding_bypasses_visibility_filter(): void
    {
        $wholesaleOnly = $this->product('wholesale-product', 'wholesale');

        AdminStorefront::select('retail');

        $this->assertSame(
            [$wholesaleOnly->id],
            ProductResource::getRecordRouteBindingEloquentQuery()->where('products.id', $wholesaleOnly->id)->pluck('products.id')->all(),
        );
        $this->assertNull(ProductResource::getEloquentQuery()->find($wholesaleOnly->id));
    }

    public function test_email_templates_page_lists_both_websites(): void
    {
        $admin = $this->admin('all');

        $this->actingAs($admin, 'admin')
            ->withSession([AdminStorefront::SESSION_KEY => 'all'])
            ->get('/admin/email-templates')
            ->assertSuccessful()
            ->assertSee('Email templates')
            ->assertSee('IGI Canada')
            ->assertSee('Leather Wallets');
    }

    public function test_selected_store_filters_email_template_resource(): void
    {
        EmailTemplate::query()->delete();
        AdminStorefront::select('retail');

        $retailTemplate = EmailTemplate::create([
            'name' => 'active-mail-to-user',
            'subject' => 'Retail activation',
            'content' => '<p>retail</p>',
            'status' => 1,
            'sales_channel' => 'retail',
        ]);
        EmailTemplate::create([
            'name' => 'active-mail-to-user',
            'subject' => 'Wholesale activation',
            'content' => '<p>wholesale</p>',
            'status' => 1,
            'sales_channel' => 'wholesale',
        ]);

        $this->assertSame([$retailTemplate->id], EmailTemplateResource::getEloquentQuery()->pluck('id')->all());

        AdminStorefront::select('all');
        $this->assertSame(2, EmailTemplateResource::getEloquentQuery()->count());
    }

    public function test_valid_store_selection_switches_and_returns_to_same_page(): void
    {
        $admin = $this->admin('wholesale');
        $product = $this->product('shared-product', 'both');

        $this->actingAs($admin, 'admin')
            ->withSession([AdminStorefront::SESSION_KEY => 'wholesale'])
            ->from('/admin/products/'.$product->id.'/edit')
            ->post(route('admin.storefront.store'), ['sales_channel' => 'retail'])
            ->assertRedirect('/admin/products/'.$product->id.'/edit');

        $this->assertSame('retail', $admin->fresh()->admin_sales_channel);
        $this->assertSame('retail', session(AdminStorefront::SESSION_KEY));
    }

    private function admin(?string $channel = null): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => $channel,
        ]);
    }

    private function product(string $slug, string $visibility): Product
    {
        return Product::create([
            'name' => str($slug)->headline(),
            'slug' => $slug,
            'visibility' => $visibility,
            'is_active' => true,
        ]);
    }

    private function order(string $number, string $channel): Order
    {
        return Order::create([
            'order_number' => $number,
            'sales_channel' => $channel,
            'shipping_address' => [],
            'subtotal' => 10,
            'total' => 10,
            'placed_at' => now(),
        ]);
    }

    private function enquiry(string $email, string $channel): ContactEnquiry
    {
        return ContactEnquiry::create([
            'sales_channel' => $channel,
            'name' => 'Customer',
            'email' => $email,
            'message' => 'Question',
        ]);
    }
}
