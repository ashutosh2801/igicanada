<?php

namespace App\Filament\Resources\StandardShippingRates;

use App\Filament\Resources\StandardShippingRates\Pages\CreateStandardShippingRate;
use App\Filament\Resources\StandardShippingRates\Pages\EditStandardShippingRate;
use App\Filament\Resources\StandardShippingRates\Pages\ListStandardShippingRates;
use App\Models\StandardShippingRate;
use App\Support\AdminStorefront;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

use function Filament\Support\original_request;

class StandardShippingRateResource extends Resource
{
    protected static ?string $model = StandardShippingRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Shipping charges';

    protected static ?string $modelLabel = 'standard shipping charge';

    protected static ?string $pluralModelLabel = 'Standard shipping charges';

    /** @return array<string, string> */
    public static function countries(): array
    {
        return [
            'CA' => 'Canada',
            'US' => 'USA',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminStorefront::apply(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_channel')
                ->label('Website')
                ->options([
                    'wholesale' => 'IGI Canada',
                    'retail' => 'Leather Wallets',
                    'walletsandbelts' => 'Wallets and Belts',
                ])
                ->required()
                ->visible(fn (): bool => AdminStorefront::current() === 'all')
                ->dehydratedWhenHidden()
                ->default(fn (): string => match (AdminStorefront::current()) {
                    'retail' => 'retail',
                    'walletsandbelts' => 'walletsandbelts',
                    default => 'wholesale',
                }),
            Select::make('country')
                ->options(static::countries())
                ->required()
                ->native(false)
                ->default(static fn (): ?string => static::requestedCountry()),
            TextInput::make('name')
                ->label('Slab label')
                ->placeholder('Up to $100')
                ->required()
                ->maxLength(100),
            TextInput::make('min_order_amount')
                ->label('Minimum order amount')
                ->numeric()
                ->prefix('$')
                ->minValue(0)
                ->step(0.01)
                ->required()
                ->rules([
                    static function (Get $get, ?StandardShippingRate $record): Closure {
                        return static function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                            $max = $get('max_order_amount');

                            if (is_numeric($max) && (float) $max < (float) $value) {
                                $fail('Maximum order amount must be greater than or equal to the minimum amount.');

                                return;
                            }

                            if ($get('is_active') === false) {
                                return;
                            }

                            $country = $get('country');

                            if (! filled($country)) {
                                return;
                            }

                            $channel = $get('sales_channel') ?? 'wholesale';
                            $max ??= $value;

                            $overlap = StandardShippingRate::query()
                                ->where('sales_channel', $channel)
                                ->where('country', $country)
                                ->where('is_active', true)
                                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                ->where('min_order_amount', '<=', (float) $max)
                                ->where('max_order_amount', '>=', (float) $value)
                                ->exists();

                            if ($overlap) {
                                $fail('This amount range overlaps another active rate for the selected website and country.');
                            }
                        };
                    },
                ]),
            TextInput::make('max_order_amount')
                ->label('Maximum order amount')
                ->numeric()
                ->prefix('$')
                ->minValue(0)
                ->step(0.01)
                ->required(),
            TextInput::make('charge')
                ->label('Shipping charge')
                ->numeric()
                ->prefix('$')
                ->minValue(0)
                ->step(0.01)
                ->required(),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('min_order_amount', 'asc')
            ->columns([
                TextColumn::make('sales_channel')
                    ->label('Website')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'retail' => 'Leather Wallets',
                        'walletsandbelts' => 'Wallets and Belts',
                        default => 'IGI Canada',
                    })
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('name')->label('Slab')->searchable(),
                TextColumn::make('min_order_amount')->label('Minimum')->money('CAD')->sortable(),
                TextColumn::make('max_order_amount')->label('Maximum')->money('CAD')->sortable(),
                TextColumn::make('charge')->label('Charge')->money('CAD')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('sales_channel')->label('Website')->options([
                    'wholesale' => 'IGI Canada',
                    'retail' => 'Leather Wallets',
                    'walletsandbelts' => 'Wallets and Belts',
                ])->visible(fn (): bool => AdminStorefront::current() === 'all'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStandardShippingRates::route('/'),
            'create' => CreateStandardShippingRate::route('/create'),
            'edit' => EditStandardShippingRate::route('/{record}/edit'),
        ];
    }

    protected static function requestedCountry(): ?string
    {
        $country = strtoupper(trim((string) original_request()->query('country')));

        return array_key_exists($country, static::countries()) ? $country : null;
    }
}
