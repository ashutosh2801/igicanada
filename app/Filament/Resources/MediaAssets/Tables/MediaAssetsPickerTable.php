<?php

namespace App\Filament\Resources\MediaAssets\Tables;

use App\Models\MediaAsset;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaAssetsPickerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(view('filament.forms.media-picker-header'))
            ->columns([
                Stack::make([
                    ImageColumn::make('preview_url')
                        ->label('Image')
                        ->state(fn (MediaAsset $record): string => $record->disk === 'public'
                            ? ltrim($record->path, '/')
                            : $record->url())
                        ->disk('public')
                        ->square()
                        ->imageSize(150)
                        ->checkFileExistence(false),
                    TextColumn::make('display_name')
                        ->label('Image name')
                        ->searchable(['title', 'filename'])
                        ->extraAttributes(['class' => 'media-picker-name'])
                        ->wrap(),
                ])->alignment(Alignment::Center)->space(2),
            ])
            ->contentGrid([
                'sm' => 2,
                'lg' => 3,
                'xl' => 6,
            ])
            ->recordClasses('media-picker-record')
            ->paginationPageOptions([24, 48, 96])
            ->defaultPaginationPageOption(24)
            ->defaultSort('created_at', 'desc');
    }
}
