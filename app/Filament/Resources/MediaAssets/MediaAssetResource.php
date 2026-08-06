<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\MediaAssets\Pages\CreateMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Filament\Tables\Columns\DirectImageColumn;
use App\Models\MediaAsset;
use App\Support\StorefrontAsset;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use UnitEnum;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Media library';

    protected static ?string $modelLabel = 'image';

    protected static ?string $pluralModelLabel = 'media library';

    protected static string|UnitEnum|null $navigationGroup = 'Media';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('media-library')
                ->required()
                ->hidden(fn (?MediaAsset $record): bool => $record?->disk === 'legacy')
                ->columnSpanFull(),
            Placeholder::make('legacy_path')
                ->label('Legacy image path')
                ->content(fn (?MediaAsset $record): string => $record?->path ?? '—')
                ->visible(fn (?MediaAsset $record): bool => $record?->disk === 'legacy')
                ->columnSpanFull(),
            Hidden::make('disk')->default('public'),
            Select::make('media_folder_id')
                ->label('Folder')
                ->relationship('folder', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')->required()->unique('media_folders', 'name'),
                ]),
            TextInput::make('title')->maxLength(255),
            TextInput::make('alt_text')
                ->label('Alternative text')
                ->helperText('Describe the image for accessibility and SEO.')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['productImages', 'primaryProducts']))
            ->columns([
                DirectImageColumn::make('preview_url')
                    ->label('Image')
                    ->state(fn (MediaAsset $record): string => StorefrontAsset::directUrl($record->path) ?? ($record->disk === 'public'
                        ? ltrim($record->path, '/')
                        : $record->url()))
                    ->disk('public')
                    ->square()
                    ->imageSize(72)
                    ->checkFileExistence(false),
                TextColumn::make('display_name')
                    ->label('Title')
                    ->description(fn (MediaAsset $record): string => $record->filename)
                    ->searchable(['title', 'filename']),
                TextColumn::make('folder.name')
                    ->placeholder('Unfiled')
                    ->sortable(),
                TextColumn::make('dimensions')
                    ->state(fn (MediaAsset $record): string => $record->width && $record->height ? "{$record->width} × {$record->height}" : '—'),
                TextColumn::make('size_bytes')
                    ->label('File size')
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state) : '—'),
                TextColumn::make('usage')
                    ->state(fn (MediaAsset $record): int => $record->product_images_count + $record->primary_products_count)
                    ->label('Used by')
                    ->suffix(' product links'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('media_folder_id')
                    ->label('Folder')
                    ->relationship('folder', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (MediaAsset $record): bool => ($record->product_images_count + $record->primary_products_count) > 0)
                    ->tooltip(fn (MediaAsset $record): ?string => ($record->product_images_count + $record->primary_products_count) > 0 ? 'Remove this image from all products before deleting it.' : null),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaAssets::route('/'),
            'create' => CreateMediaAsset::route('/create'),
            'edit' => EditMediaAsset::route('/{record}/edit'),
        ];
    }
}
