<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentPage extends CreateRecord
{
    protected static string $resource = ContentPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['published_at'] = ($data['status'] ?? null) === 'published' ? now() : null;

        return $data;
    }
}
