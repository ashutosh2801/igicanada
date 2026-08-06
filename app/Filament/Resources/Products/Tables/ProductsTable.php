<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Tables\Columns\DirectImageColumn;
use App\Models\Product;
use App\Services\RetailProductActivationService;
use App\Support\AdminStorefront;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (AdminStorefront::showsRetailFields()) {
                    $query
                        ->withCount(['variants as retail_ready_variants_count' => fn (Builder $query): Builder => $query
                            ->where('is_active', true)
                            ->where('is_available_retail', true)
                            ->where('retail_price', '>', 0)
                            ->where('stock_quantity', '>', 0)])
                        ->withMin(['variants as minimum_retail_price' => fn (Builder $query): Builder => $query
                            ->where('is_active', true)], 'retail_price');
                }

                if (AdminStorefront::showsWholesaleFields()) {
                    $query
                        ->withCount(['variants as wholesale_ready_variants_count' => fn (Builder $query): Builder => $query
                            ->where('is_active', true)
                            ->where('is_available_wholesale', true)
                            ->where('wholesale_price', '>', 0)
                            ->where('stock_quantity', '>', 0)])
                        ->withMin(['variants as minimum_wholesale_price' => fn (Builder $query): Builder => $query
                            ->where('is_active', true)], 'wholesale_price');
                }

                return $query->withSum(['variants as available_stock_quantity' => fn (Builder $query): Builder => $query
                    ->where('is_active', true)], 'stock_quantity');
            })
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                DirectImageColumn::make('primary_image_url')
                    ->label('Image')
                    ->state(fn (Product $record): ?string => $record->primaryImageUrl())
                    ->square()
                    ->imageSize(56)
                    ->checkFileExistence(false),
                TextColumn::make('name')
                    ->label(fn (): string => AdminStorefront::current() === 'all' ? 'Wholesale name' : 'Product name')
                    ->sortable()
                    ->searchable()
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextColumn::make('retail_name')
                    ->label(fn (): string => AdminStorefront::current() === 'all' ? 'Retail name' : 'Product name')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Retail name not set')
                    ->visible(fn (): bool => AdminStorefront::showsRetailFields()),
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(),
                TextColumn::make('visibility')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'both' => 'Retail + wholesale',
                        'retail' => 'Retail',
                        default => 'Wholesale',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'both' => 'success',
                        'retail' => 'warning',
                        default => 'info',
                    })
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants'),
                IconColumn::make('wholesale_ready_variants_count')
                    ->label('Wholesale ready')
                    ->boolean()
                    ->state(fn (Product $record): bool => (int) $record->wholesale_ready_variants_count > 0)
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextColumn::make('minimum_wholesale_price')
                    ->label('Wholesale from')
                    ->money('CAD')
                    ->placeholder('Not priced')
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                IconColumn::make('retail_ready_variants_count')
                    ->label('Retail ready')
                    ->boolean()
                    ->state(fn (Product $record): bool => (int) $record->retail_ready_variants_count > 0)
                    ->visible(fn (): bool => AdminStorefront::showsRetailFields()),
                TextColumn::make('minimum_retail_price')
                    ->label('Retail from')
                    ->money('CAD')
                    ->placeholder('Not priced')
                    ->visible(fn (): bool => AdminStorefront::showsRetailFields()),
                TextColumn::make('available_stock_quantity')
                    ->label('Stock')
                    ->numeric(),
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
                        'wholesale' => 'Wholesale only',
                        'retail' => 'Retail only',
                        'both' => 'Retail and wholesale',
                    ])
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enableRetail')
                        ->label('Enable for retail')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Enable selected products for retail?')
                        ->modalDescription('Only active, in-stock variants with a valid retail price will be enabled. Wholesale-only products will become available on both websites.')
                        ->action(function (Collection $records, RetailProductActivationService $activation): void {
                            $products = 0;
                            $variants = 0;

                            foreach ($records as $product) {
                                $enabled = $activation->enable($product);
                                $variants += $enabled;
                                $products += $enabled > 0 ? 1 : 0;
                            }

                            Notification::make()
                                ->title("{$products} products enabled for retail")
                                ->body("{$variants} active, priced and in-stock variants are now retail-ready.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => AdminStorefront::showsRetailFields()),
                    BulkAction::make('disableRetail')
                        ->label('Disable retail availability')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, RetailProductActivationService $activation): void {
                            $variants = $records->sum(fn (Product $product): int => $activation->disable($product));

                            Notification::make()
                                ->title('Retail availability disabled')
                                ->body("{$variants} variants were removed from the retail storefront.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => AdminStorefront::showsRetailFields()),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
