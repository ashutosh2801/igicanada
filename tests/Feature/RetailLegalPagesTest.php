<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RetailLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_retail_legal_templates_require_review_before_they_are_public(): void
    {
        $this->assertDatabaseHas('content_pages', [
            'sales_channel' => 'retail',
            'slug' => 'privacy-policy',
            'status' => 'review_required',
        ]);

        $this->get('https://leatherwallets.ca/policies/privacy-policy')->assertNotFound();
    }

    public function test_approved_retail_legal_pages_appear_on_the_retail_site_only(): void
    {
        ContentPage::query()
            ->where('sales_channel', 'retail')
            ->where('slug', 'privacy-policy')
            ->update(['status' => 'published', 'published_at' => now()]);

        $this->get('https://leatherwallets.ca/policies/privacy-policy')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pages/Show')
                ->where('page.title', 'Privacy Policy')
                ->where('retailStorefront.legalNavigation.0.label', 'Privacy Policy'));

        $this->get('https://igicanada.ca/page/privacy-policy')->assertNotFound();
    }
}
