<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditContentPage extends EditRecord
{
    protected static string $resource = ContentPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->visible(fn (): bool => $this->record->status === 'published')
                ->url(fn (): string => route('pages.show', $this->record->slug))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['published_at'] = ($data['status'] ?? null) === 'published'
            ? ($this->record->published_at ?: now())
            : null;

        return $data;
    }
}
