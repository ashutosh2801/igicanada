<?php

namespace App\Console\Commands;

use App\Models\ContentPage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

#[Signature('legacy:import-pages {--dry-run : Validate source pages without writing}')]
#[Description('Safely import Yii content pages into the review queue')]
class ImportLegacyPages extends Command
{
    public function handle(): int
    {
        try {
            $legacy = DB::connection('legacy');
            if (! $legacy->getSchemaBuilder()->hasTable('igi_pages')) {
                $this->error('Missing legacy table: igi_pages');

                return self::FAILURE;
            }

            $pages = $legacy->table('igi_pages')->where('status', 1)->orderBy('id')->get();
            $this->info("Found {$pages->count()} active legacy pages. They will remain unpublished pending review.");

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            foreach ($pages as $page) {
                $existing = ContentPage::where('legacy_id', $page->id)->first();
                if ($existing) {
                    continue;
                }

                $slug = $this->availableSlug($page);
                $title = trim((string) ($page->title ?: $page->name)) ?: 'Legacy page '.$page->id;
                $plainText = trim(preg_replace('/\s+/', ' ', strip_tags((string) $page->content)) ?? '');

                ContentPage::create([
                    'legacy_id' => $page->id,
                    'sales_channel' => 'wholesale',
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => Str::limit($plainText, 240),
                    'body_html' => (string) $page->content,
                    'meta_title' => trim((string) $page->site_title) ?: null,
                    'meta_description' => trim((string) $page->site_description) ?: null,
                    'status' => 'review_required',
                    'is_legal' => $this->isLegal($page),
                    'published_at' => null,
                ]);
            }

            $this->info('Legacy pages are present in the review queue. Existing reviewed pages were preserved.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Page import failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function availableSlug(object $page): string
    {
        $base = Str::slug((string) ($page->slug ?: $page->title ?: $page->name)) ?: 'legacy-page-'.$page->id;

        return ContentPage::forChannel('wholesale')->where('slug', $base)->exists() ? $base.'-legacy-'.$page->id : $base;
    }

    private function isLegal(object $page): bool
    {
        $value = Str::lower(implode(' ', [
            (string) $page->slug,
            (string) $page->title,
            (string) $page->name,
        ]));

        return Str::contains($value, ['privacy', 'return', 'tax', 'duties', 'shipping', 'terms', 'payment']);
    }
}
