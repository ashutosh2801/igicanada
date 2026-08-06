<?php

namespace App\Filament\Resources\NavigationItems;

use App\Filament\Resources\NavigationItems\Pages\CreateNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\EditNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\ListNavigationItems;
use App\Models\NavigationItem;
use App\Support\AdminStorefront;
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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class NavigationItemResource extends Resource
{
    protected static ?string $model = NavigationItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = 'Menus';

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

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
                ])
                ->required()
                ->visible(fn (): bool => AdminStorefront::current() === 'all')
                ->dehydratedWhenHidden()
                ->default(fn (): string => AdminStorefront::current() === 'retail' ? 'retail' : 'wholesale'),
            Select::make('location')->options([
                'header' => 'Header menu',
                'footer' => 'Footer menu',
            ])->required(),
            TextInput::make('label')->required()->maxLength(100),
            TextInput::make('url')
                ->required()
                ->placeholder(fn (): string => AdminStorefront::current() === 'retail' ? '/shop' : '/catalogue')
                ->helperText('Use a path for the selected website or a full https:// URL.'),
            TextInput::make('sort_order')->numeric()->default(0)->minValue(0)->required(),
            Toggle::make('opens_new_tab')->label('Open in new tab'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sales_channel')
                    ->label('Website')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'retail' ? 'Leather Wallets' : 'IGI Canada')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('location')->badge()->sortable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('url')->limit(50),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('opens_new_tab')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('sales_channel')->label('Website')->options([
                    'wholesale' => 'IGI Canada',
                    'retail' => 'Leather Wallets',
                ])->visible(fn (): bool => AdminStorefront::current() === 'all'),
                SelectFilter::make('location')->options([
                    'header' => 'Header menu',
                    'footer' => 'Footer menu',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigationItems::route('/'),
            'create' => CreateNavigationItem::route('/create'),
            'edit' => EditNavigationItem::route('/{record}/edit'),
        ];
    }
}
