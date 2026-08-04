<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use Inertia\Inertia;
use Inertia\Response;

class ContentPageController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $page = ContentPage::published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('Pages/Show', [
            'page' => [
                'title' => $page->title,
                'excerpt' => $page->excerpt,
                'bodyHtml' => $page->body_html,
                'metaTitle' => $page->meta_title,
                'metaDescription' => $page->meta_description,
                'updatedAt' => $page->updated_at?->toDateString(),
            ],
        ]);
    }
}
