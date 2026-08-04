<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent category / submenu')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Leave empty for a top-level menu. Choose a parent for level 2 or level 3.'),
                TextInput::make('legacy_id')
                    ->label('Legacy ID')
                    ->disabled()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('categories'),
                TextInput::make('position')
                    ->label('Menu order')
                    ->required()
                    ->numeric()
                    ->helperText('Lower numbers appear first within the same menu level.')
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
