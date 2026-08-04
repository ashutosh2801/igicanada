<?php

namespace App\Filament\Resources\HomepageSettings\Pages;

use App\Filament\Resources\HomepageSettings\HomepageSettingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditHomepageSetting extends EditRecord
{
    protected static string $resource = HomepageSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewStorefront')
                ->label('View homepage')
                ->url(route('home'))
                ->openUrlInNewTab(),
        ];
    }
}
