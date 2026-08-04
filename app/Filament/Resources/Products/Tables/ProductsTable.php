<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                ImageColumn::make('primary_image_url')
                    ->label('Image')
                    ->state(fn (Product $record): ?string => $record->primaryImageUrl())
                    ->square()
                    ->imageSize(56)
                    ->checkFileExistence(false),
                TextColumn::make('name')
                    ->label('Title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(),
                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants'),
                TextColumn::make('weight_kg')
                    ->label('Weight kg')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Is active')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Published at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('legacy_id')
                    ->label('Legacy ID')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visibility')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('visibility')
                    ->options([
                        'wholesale' => 'Wholesale products',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
