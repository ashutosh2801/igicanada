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
                ->label(fn (): string => match ($this->record->sales_channel) {
                    'retail' => 'View Leather Wallets',
                    'walletsandbelts' => 'View Wallets and Belts',
                    default => 'View IGI Canada',
                })
                ->url(fn (): string => match ($this->record->sales_channel) {
                    'retail' => route('retail.home'),
                    'walletsandbelts' => 'https://'.config('storefronts.brands.walletsandbelts.domain'),
                    default => route('home'),
                })
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return match ($this->record->sales_channel) {
            'retail' => 'Leather Wallets homepage',
            'walletsandbelts' => 'Wallets and Belts homepage',
            default => 'IGI Canada homepage',
        };
    }
}
