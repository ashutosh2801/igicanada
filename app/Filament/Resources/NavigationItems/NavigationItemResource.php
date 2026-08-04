<?php

namespace App\Filament\Resources\NavigationItems;

use App\Filament\Resources\NavigationItems\Pages\CreateNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\EditNavigationItem;
use App\Filament\Resources\NavigationItems\Pages\ListNavigationItems;
use App\Models\NavigationItem;
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
use UnitEnum;

class NavigationItemResource extends Resource
{
    protected static ?string $model = NavigationItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = 'Menus';

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('location')->options([
                'header' => 'Header menu',
                'footer' => 'Footer menu',
            ])->required(),
            TextInput::make('label')->required()->maxLength(100),
            TextInput::make('url')->required()->placeholder('/catalogue')->helperText('Use a site path such as /catalogue or a full https:// URL.'),
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
                TextColumn::make('location')->badge()->sortable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('url')->limit(50),
                TextColumn::make('sort_order')->numeric()->sortable(),
                IconColumn::make('opens_new_tab')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
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
