<?php

namespace App\Filament\Resources\StandardShippingRates\Pages;

use App\Filament\Resources\StandardShippingRates\StandardShippingRateResource;
use App\Models\StandardShippingRate;
use App\Services\StandardShippingRateManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListStandardShippingRates extends ListRecords
{
    protected static string $resource = StandardShippingRateResource::class;

    protected ?string $subheading = 'Use Bulk edit to update every slab for the country you are viewing, then save all changes together.';

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $tabs = [];

        foreach (StandardShippingRateResource::countries() as $code => $label) {
            $tabs[$code] = Tab::make($label)
                ->query(fn (Builder $query): Builder => $query->where('country', $code))
                ->badge(fn (): int => $this->countryRateCount($code));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkEdit')
                ->label('Bulk edit charges')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading(fn (): string => 'Bulk edit '.($this->activeCountryLabel()).' shipping charges')
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
                            Hidden::make('sales_channel')->required(),
                            Hidden::make('country')->required(),
                            TextInput::make('name')->label('Slab')->hiddenLabel()->required()->maxLength(100),
                            TextInput::make('min_order_amount')->label('Minimum')->hiddenLabel()->numeric()->prefix('$')->minValue(0)->step(0.01)->required(),
                            TextInput::make('max_order_amount')->label('Maximum')->hiddenLabel()->numeric()->prefix('$')->minValue(0)->step(0.01)->required(),
                            TextInput::make('charge')->label('Charge')->hiddenLabel()->numeric()->prefix('$')->minValue(0)->step(0.01)->required(),
                            Toggle::make('is_active')->label('Active')->hiddenLabel()->required(),
                        ])
                        ->columns(5)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data): void {
                    app(StandardShippingRateManager::class)->update($data['rates']);
                    Notification::make()->title('Shipping charges saved')->success()->send();
                }),
            Action::make('newCharge')
                ->label('Add standard shipping charge')
                ->icon('heroicon-m-plus')
                ->url(fn (): string => CreateStandardShippingRate::getUrl([
                    'country' => $this->activeTab,
                ])),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function filteredRates(): array
    {
        return ($this->getFilteredSortedTableQuery() ?? StandardShippingRate::query())
            ->get()
            ->map->only(['id', 'sales_channel', 'country', 'name', 'min_order_amount', 'max_order_amount', 'charge', 'is_active'])
            ->values()
            ->all();
    }

    private function countryRateCount(string $country): int
    {
        return StandardShippingRateResource::getEloquentQuery()
            ->where('country', $country)
            ->count();
    }

    private function activeCountryLabel(): string
    {
        return StandardShippingRateResource::countries()[$this->activeTab] ?? 'Standard';
    }
}
