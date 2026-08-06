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
                ->label(fn (): string => $this->record->sales_channel === 'retail' ? 'View Leather Wallets' : 'View IGI Canada')
                ->url(fn (): string => $this->record->sales_channel === 'retail' ? route('retail.home') : route('home'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->sales_channel === 'retail'
            ? 'Leather Wallets homepage'
            : 'IGI Canada homepage';
    }
}
