<?php

namespace App\Filament\Resources\MediaFolders\Pages;

use App\Filament\Resources\MediaFolders\MediaFolderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaFolder extends EditRecord
{
    protected static string $resource = MediaFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
