<?php

namespace Tests\Feature;

use App\Filament\Resources\ContentPages\ContentPageResource;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Models\ContentPage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentPageAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel('admin');
    }

    public function test_publishing_from_edit_page_saves_status(): void
    {
        $page = ContentPage::create([
            'sales_channel' => 'wholesale',
            'title' => 'About us',
            'slug' => 'about-us',
            'body_html' => '<p>Hello</p>',
            'status' => 'draft',
        ]);

        Livewire::test(EditContentPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['status' => 'published'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertActionVisible('view');

        $page->refresh();
        $this->assertSame('published', $page->status);
        $this->assertNotNull($page->published_at);
        $this->get('/page/about-us')->assertOk();
    }

    public function test_same_slug_on_both_websites_can_be_published_and_viewed(): void
    {
        $wholesale = ContentPage::create([
            'sales_channel' => 'wholesale', 'title' => 'Privacy', 'slug' => 'privacy-policy',
            'body_html' => '<p>W</p>', 'status' => 'review_required',
        ]);
        $retail = ContentPage::updateOrCreate(
            ['sales_channel' => 'retail', 'slug' => 'privacy-policy'],
            ['title' => 'Privacy', 'body_html' => '<p>R</p>', 'status' => 'review_required'],
        );

        foreach ([$wholesale, $retail] as $page) {
            Livewire::test(EditContentPage::class, ['record' => $page->getRouteKey()])
                ->fillForm(['status' => 'published'])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('published', $page->refresh()->status);
        }

        $this->assertSame(route('pages.show', 'privacy-policy'), ContentPageResource::publicUrl($wholesale));
        $this->assertSame(route('retail.pages.show', 'privacy-policy'), ContentPageResource::publicUrl($retail));
        $this->get(ContentPageResource::publicUrl($retail))->assertOk();
    }

    public function test_slug_still_unique_within_one_website(): void
    {
        ContentPage::create([
            'sales_channel' => 'wholesale', 'title' => 'A', 'slug' => 'taken', 'status' => 'draft',
        ]);
        $other = ContentPage::create([
            'sales_channel' => 'wholesale', 'title' => 'B', 'slug' => 'free', 'status' => 'draft',
        ]);

        Livewire::test(EditContentPage::class, ['record' => $other->getRouteKey()])
            ->fillForm(['slug' => 'taken'])
            ->call('save')
            ->assertHasFormErrors(['slug' => 'unique']);
    }
}
