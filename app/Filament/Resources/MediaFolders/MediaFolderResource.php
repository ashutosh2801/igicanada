<?php

namespace App\Filament\Resources\MediaFolders;

use App\Filament\Resources\MediaFolders\Pages\CreateMediaFolder;
use App\Filament\Resources\MediaFolders\Pages\EditMediaFolder;
use App\Filament\Resources\MediaFolders\Pages\ListMediaFolders;
use App\Models\MediaFolder;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MediaFolderResource extends Resource
{
    protected static ?string $model = MediaFolder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Media folders';

    protected static string|UnitEnum|null $navigationGroup = 'Media';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->unique(ignoreRecord: true)->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('assets_count')->counts('assets')->label('Images')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaFolders::route('/'),
            'create' => CreateMediaFolder::route('/create'),
            'edit' => EditMediaFolder::route('/{record}/edit'),
        ];
    }
}
