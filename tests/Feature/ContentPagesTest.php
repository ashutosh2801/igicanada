<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('legacy');

        Schema::connection('legacy')->create('igi_pages', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('site_title')->nullable();
            $table->string('site_keyword')->nullable();
            $table->text('site_description')->nullable();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('slug')->nullable();
            $table->integer('status');
        });

        DB::connection('legacy')->table('igi_pages')->insert([
            [
                'id' => 14,
                'site_title' => 'Privacy',
                'site_keyword' => 'privacy',
                'site_description' => 'Old privacy copy',
                'name' => 'Privacy Policy',
                'title' => 'Privacy Policy',
                'content' => '<h2 onclick="steal()">Privacy</h2><script>alert(1)</script><form action="https://bad.example"><input name="card"></form><p>Safe text <a href="javascript:alert(1)">bad link</a></p>',
                'slug' => 'privacy-policy',
                'status' => 1,
            ],
            [
                'id' => 15,
                'site_title' => 'Duplicate',
                'site_keyword' => null,
                'site_description' => null,
                'name' => 'Second policy',
                'title' => 'Second policy',
                'content' => '<p>Second page</p>',
                'slug' => 'privacy-policy',
                'status' => 1,
            ],
        ]);
    }

    public function test_it_safely_imports_pages_to_review_queue_and_is_idempotent(): void
    {
        $this->artisan('legacy:import-pages')->assertSuccessful();

        $page = ContentPage::where('legacy_id', 14)->firstOrFail();
        $this->assertSame('review_required', $page->status);
        $this->assertTrue($page->is_legal);
        $this->assertStringNotContainsString('<script', $page->body_html);
        $this->assertStringNotContainsString('<form', $page->body_html);
        $this->assertStringNotContainsString('onclick', $page->body_html);
        $this->assertStringNotContainsString('javascript:', $page->body_html);
        $this->assertStringContainsString('Safe text', $page->body_html);
        $this->assertDatabaseHas('content_pages', ['legacy_id' => 15, 'slug' => 'privacy-policy-legacy-15']);

        $page->update(['title' => 'Reviewed title', 'status' => 'published', 'published_at' => now()]);
        $this->artisan('legacy:import-pages')->assertSuccessful();

        $this->assertSame(2, ContentPage::forChannel('wholesale')->count());
        $this->assertDatabaseHas('content_pages', ['legacy_id' => 14, 'title' => 'Reviewed title', 'status' => 'published']);
    }

    public function test_dry_run_does_not_write_pages(): void
    {
        $this->artisan('legacy:import-pages --dry-run')->assertSuccessful();

        $this->assertSame(0, ContentPage::forChannel('wholesale')->count());
    }

    public function test_only_published_pages_are_public(): void
    {
        ContentPage::create([
            'title' => 'About us',
            'slug' => 'about-us',
            'body_html' => '<p>Our story</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
        ContentPage::create([
            'title' => 'Draft',
            'slug' => 'draft',
            'body_html' => '<p>Not ready</p>',
            'status' => 'review_required',
        ]);

        $this->get('/page/about-us')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pages/Show')
                ->where('page.title', 'About us'));
        $this->get('/page/draft')->assertNotFound();
    }
}
