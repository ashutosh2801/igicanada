<?php

namespace App\Filament\Resources\StandardShippingRates;

use App\Filament\Resources\StandardShippingRates\Pages\CreateStandardShippingRate;
use App\Filament\Resources\StandardShippingRates\Pages\EditStandardShippingRate;
use App\Filament\Resources\StandardShippingRates\Pages\ListStandardShippingRates;
use App\Models\StandardShippingRate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StandardShippingRateResource extends Resource
{
    protected static ?string $model = StandardShippingRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Shipping charges';

    protected static ?string $modelLabel = 'standard shipping charge';

    protected static ?string $pluralModelLabel = 'Standard shipping charges';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('country')
                ->options(['CA' => 'Canada', 'US' => 'USA'])
                ->required()
                ->native(false),
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
                ->required(),
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
            ->defaultSort('country')
            ->columns([
                TextColumn::make('country')
                    ->formatStateUsing(fn (string $state): string => $state === 'CA' ? 'Canada' : 'USA')
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')->label('Slab')->searchable(),
                TextColumn::make('min_order_amount')->label('Minimum')->money('CAD')->sortable(),
                TextColumn::make('max_order_amount')->label('Maximum')->money('CAD')->sortable(),
                TextColumn::make('charge')->label('Charge')->money('CAD')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('country')->options(['CA' => 'Canada', 'US' => 'USA']),
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
}
