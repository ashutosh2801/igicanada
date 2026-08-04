<?php

namespace App\Filament\Resources\MediaFolders\Pages;

use App\Filament\Resources\MediaFolders\MediaFolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaFolders extends ListRecords
{
    protected static string $resource = MediaFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
