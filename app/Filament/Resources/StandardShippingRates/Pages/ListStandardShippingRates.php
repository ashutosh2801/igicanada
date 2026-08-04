<?php

namespace App\Filament\Resources\StandardShippingRates\Pages;

use App\Filament\Resources\StandardShippingRates\StandardShippingRateResource;
use App\Models\StandardShippingRate;
use App\Services\StandardShippingRateManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;

class ListStandardShippingRates extends ListRecords
{
    protected static string $resource = StandardShippingRateResource::class;

    protected ?string $subheading = 'Use Bulk edit to update multiple Canada and USA slabs, then save all changes together.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkEdit')
                ->label('Bulk edit charges')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('Bulk edit Standard Shipping Charges')
                ->modalDescription('Nothing is saved until you click Save changes.')
                ->modalWidth(Width::SevenExtraLarge)
                ->modalSubmitActionLabel('Save changes')
                ->fillForm(fn (): array => ['rates' => $this->filteredRates()])
                ->schema([
                    View::make('filament.forms.shipping-rate-headings'),
                    Repeater::make('rates')
                        ->label('Shipping slabs')
                        ->extraAttributes(['class' => 'shipping-rates-zebra'])
                        ->schema([
                            Hidden::make('id')->required(),
                            Select::make('country')->label('Country')->hiddenLabel()->options(['CA' => 'Canada', 'US' => 'USA'])->required(),
                            TextInput::make('name')->label('Slab')->hiddenLabel()->required()->maxLength(100),
                            TextInput::make('min_order_amount')->label('Minimum')->hiddenLabel()->numeric()->prefix('$')->minValue(0)->step(0.01)->required(),
                            TextInput::make('max_order_amount')->label('Maximum')->hiddenLabel()->numeric()->prefix('$')->minValue(0)->step(0.01)->required(),
                            TextInput::make('charge')->label('Charge')->hiddenLabel()->numeric()->prefix('$')->minValue(0)->step(0.01)->required(),
                            Toggle::make('is_active')->label('Active')->hiddenLabel()->required(),
                        ])
                        ->columns(6)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data): void {
                    app(StandardShippingRateManager::class)->update($data['rates']);
                    Notification::make()->title('Shipping charges saved')->success()->send();
                }),
            CreateAction::make(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function filteredRates(): array
    {
        return ($this->getFilteredSortedTableQuery() ?? StandardShippingRate::query())
            ->get()
            ->map->only(['id', 'country', 'name', 'min_order_amount', 'max_order_amount', 'charge', 'is_active'])
            ->values()
            ->all();
    }
}
