<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Tables\Columns\DirectImageColumn;
use App\Support\AdminStorefront;
use App\Support\StorefrontAsset;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.name')
                    ->label('Parent / submenu')
                    ->searchable(),
                TextColumn::make('legacy_id')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('visibility')
                    ->label('Website')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'both' => 'Both',
                        'retail' => 'Leather Wallets',
                        default => 'IGI Canada',
                    })
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                DirectImageColumn::make('image_path')
                    ->state(fn ($record): ?string => StorefrontAsset::directUrl($record->image_path) ?? $record->image_path)
                    ->disk('public')
                    ->checkFileExistence(false),
                TextColumn::make('position')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('position');
    }
}
