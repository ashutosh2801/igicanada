<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\Actions\ProductPublishActions;
use App\Filament\Tables\Columns\DirectImageColumn;
use App\Filament\Tables\Columns\StockColumn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RetailProductActivationService;
use App\Support\AdminStorefront;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
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
                    ->searchable()
                    ->toggleable(),
                DirectImageColumn::make('primary_image_url')
                    ->label('Image')
                    ->state(fn (Product $record): ?string => $record->primaryImageUrl())
                    ->square()
                    ->imageSize(56)
                    ->checkFileExistence(false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Product name')
                    ->width('14rem')
                    ->wrap()
                    ->limit(60)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable()
                    ->toggleable(),
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
                    ->visible(fn (): bool => AdminStorefront::current() === 'all')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->toggleable(),
                IconColumn::make('wholesale_ready_variants_count')
                    ->label('Wholesale ready')
                    ->boolean()
                    ->state(fn (Product $record): bool => (int) $record->wholesale_ready_variants_count > 0)
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('minimum_wholesale_price')
                    ->label('Wholesale from')
                    ->money('CAD')
                    ->placeholder('Not priced')
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                IconColumn::make('retail_ready_variants_count')
                    ->label('Retail ready')
                    ->boolean()
                    ->state(fn (Product $record): bool => (int) $record->retail_ready_variants_count > 0)
                    ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('minimum_retail_price')
                    ->label('Retail from')
                    ->money('CAD')
                    ->placeholder('Not priced')
                    ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                StockColumn::make('available_stock_quantity')
                    ->label('Stock')
                    ->width('6rem')
                    ->rules(['integer', 'min:0'])
                    ->toggleable()
                    ->updateStateUsing(function (Product $record, mixed $state): void {
                        $qty = max(0, (int) $state);
                        $record->variants()
                            ->where('is_active', true)
                            ->update(['stock_quantity' => $qty]);
                    }),
                TextColumn::make('weight_kg')
                    ->label('Weight kg')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Is active')
                    ->boolean()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('published_at')
                    ->label('Published at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('legacy_id')
                    ->label('Legacy ID')
                    ->numeric()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
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
                SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('categories', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('stock_status')
                    ->label('Stock status')
                    ->options([
                        'in_stock' => 'In stock',
                        'low_stock' => 'Low stock (≤ 10)',
                        'out_of_stock' => 'Out of stock',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'in_stock' => $query->whereHas('variants', fn (Builder $query): Builder => $query->where('stock_quantity', '>', 0)),
                            'out_of_stock' => $query->where(fn (Builder $query): Builder => $query
                                ->whereDoesntHave('variants')
                                ->orWhereDoesntHave('variants', fn (Builder $query): Builder => $query->where('stock_quantity', '>', 0))),
                            'low_stock' => $query->whereHas('variants', fn (Builder $query): Builder => $query
                                ->where('stock_quantity', '>', 0)
                                ->where('stock_quantity', '<=', 10)),
                            default => $query,
                        };
                    }),
                Filter::make('price')
                    ->label(fn (): string => AdminStorefront::current() === 'retail' ? 'Retail price range' : 'Wholesale price range')
                    ->schema([
                        TextInput::make('min')->label('Min')->numeric()->prefix('$')->minValue(0)->placeholder('0'),
                        TextInput::make('max')->label('Max')->numeric()->prefix('$')->minValue(0)->placeholder('500'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['min']) && blank($data['max'])) {
                            return $query;
                        }

                        $column = AdminStorefront::current() === 'retail' ? 'retail_price' : 'wholesale_price';

                        $query->whereHas('variants', function (Builder $query) use ($data, $column): Builder {
                            if (filled($data['min'])) {
                                $query->where($column, '>=', $data['min']);
                            }

                            if (filled($data['max'])) {
                                $query->where($column, '<=', $data['max']);
                            }

                            return $query;
                        });

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['min'] ?? null)) {
                            $indicators[] = "Min {$data['min']}";
                        }

                        if (filled($data['max'] ?? null)) {
                            $indicators[] = "Max {$data['max']}";
                        }

                        return $indicators;
                    }),
                TernaryFilter::make('published')
                    ->label('Published')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('published_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('published_at'),
                    ),
                TernaryFilter::make('has_variants')
                    ->label('Has variants')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('variants'),
                        false: fn (Builder $query): Builder => $query->doesntHave('variants'),
                    ),
            ])
            ->recordActions([
                Action::make('changeVisibility')
                    ->label('Websites')
                    ->icon('heroicon-o-globe-alt')
                    ->color('primary')
                    ->modalHeading(fn (Product $record): string => "Websites for {$record->name}")
                    ->modalDescription('Switch this product between the wholesale and retail storefronts, or show it on both. New products default to both websites.')
                    ->schema([
                        Radio::make('visibility')
                            ->label('Where should this product be shown?')
                            ->options(self::visibilityOptions())
                            ->default(fn (Product $record): string => $record->visibility)
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $record->update(['visibility' => $data['visibility']]);

                        Notification::make()
                            ->title('Website updated')
                            ->body($record->name.' is now shown on '.self::visibilityLabel($data['visibility']).'.')
                            ->success()
                            ->send();
                    }),
                ProductPublishActions::toggleRowAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                Action::make('saveStockUpdates')
                    ->label('Save stock')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->disabled(fn (HasTable $livewire): bool => blank($livewire->pendingStockUpdates))
                    ->action(function (HasTable $livewire): void {
                        $updated = 0;

                        foreach ($livewire->pendingStockUpdates as $productId => $quantity) {
                            $product = Product::query()->find((int) $productId);

                            if (! $product) {
                                continue;
                            }

                            $product->variants()
                                ->where('is_active', true)
                                ->update(['stock_quantity' => max(0, (int) $quantity)]);
                            $updated++;
                        }

                        $livewire->pendingStockUpdates = [];

                        Notification::make()
                            ->title('Stock saved')
                            ->body("Updated stock for {$updated} product(s).")
                            ->success()
                            ->send();
                    }),
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
                    BulkAction::make('updatePrices')
                        ->label('Update prices')
                        ->icon('heroicon-o-banknotes')
                        ->color('primary')
                        ->schema([
                            Select::make('price_type')
                                ->label('Price to update')
                                ->options(function (): array {
                                    $options = [];

                                    if (AdminStorefront::showsWholesaleFields()) {
                                        $options['wholesale'] = 'Wholesale price';
                                    }

                                    if (AdminStorefront::showsRetailFields()) {
                                        $options['retail'] = 'Retail price';
                                    }

                                    if (AdminStorefront::current() === 'all') {
                                        $options['both'] = 'Both prices';
                                    }

                                    return $options;
                                })
                                ->default(fn (): string => AdminStorefront::current() === 'retail' ? 'retail' : 'wholesale')
                                ->required()
                                ->live(),
                            Select::make('operation')
                                ->label('Operation')
                                ->options([
                                    'set' => 'Set to value',
                                    'increase_percent' => 'Increase by %',
                                    'decrease_percent' => 'Decrease by %',
                                    'increase_amount' => 'Increase by fixed amount',
                                    'decrease_amount' => 'Decrease by fixed amount',
                                ])
                                ->default('set')
                                ->required()
                                ->live(),
                            TextInput::make('value')
                                ->label(fn (Get $get): string => in_array($get('operation'), ['increase_percent', 'decrease_percent'], true)
                                    ? 'Percent'
                                    : 'Amount (CAD)')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step('any'),
                        ])
                        ->modalSubmitActionLabel('Apply price update')
                        ->action(function (Collection $records, array $data): void {
                            $columns = match ($data['price_type']) {
                                'retail' => ['retail_price'],
                                'both' => ['wholesale_price', 'retail_price'],
                                default => ['wholesale_price'],
                            };

                            $value = (float) $data['value'];
                            $operation = $data['operation'];
                            $priceUpdates = 0;

                            foreach ($records as $product) {
                                foreach ($product->variants as $variant) {
                                    foreach ($columns as $column) {
                                        $current = (float) $variant->{$column};

                                        $variant->{$column} = match ($operation) {
                                            'increase_percent' => round($current * (1 + $value / 100), 2),
                                            'decrease_percent' => round($current * (1 - $value / 100), 2),
                                            'increase_amount' => round($current + $value, 2),
                                            'decrease_amount' => max(0, round($current - $value, 2)),
                                            default => round($value, 2),
                                        };

                                        $priceUpdates++;
                                    }

                                    $variant->save();
                                }
                            }

                            Notification::make()
                                ->title('Prices updated')
                                ->body("{$priceUpdates} price values updated across {$records->count()} products.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('updateStock')
                        ->label('Update stock')
                        ->icon('heroicon-o-squares-2x2')
                        ->color('warning')
                        ->schema([
                            Select::make('operation')
                                ->label('Operation')
                                ->options([
                                    'set' => 'Set stock to',
                                    'increase' => 'Add stock',
                                    'decrease' => 'Remove stock',
                                ])
                                ->default('set')
                                ->required()
                                ->live(),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->integer(),
                        ])
                        ->modalSubmitActionLabel('Apply stock update')
                        ->action(function (Collection $records, array $data): void {
                            $variantCount = 0;

                            foreach ($records as $product) {
                                foreach ($product->variants as $variant) {
                                    $current = (int) $variant->stock_quantity;

                                    $variant->stock_quantity = match ($data['operation']) {
                                        'increase' => $current + (int) $data['quantity'],
                                        'decrease' => max(0, $current - (int) $data['quantity']),
                                        default => (int) $data['quantity'],
                                    };

                                    $variant->save();
                                    $variantCount++;
                                }
                            }

                            Notification::make()
                                ->title('Stock updated')
                                ->body("{$variantCount} variants updated across {$records->count()} products.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('editSelected')
                        ->label('Edit selected products')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->schema([
                            Repeater::make('variants')
                                ->label('Selected product variants')
                                ->helperText('Type the new wholesale/retail price and stock for each variant, then save.')
                                ->schema([
                                    Hidden::make('id'),
                                    TextInput::make('label')
                                        ->label('Product / variant')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(2),
                                    TextInput::make('wholesale_price')
                                        ->label('Wholesale price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->minValue(0)
                                        ->step('any')
                                        ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                        ->dehydratedWhenHidden(),
                                    TextInput::make('wholesale_compare_at_price')
                                        ->label('Wholesale original price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->minValue(0)
                                        ->step('any')
                                        ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                        ->dehydratedWhenHidden(),
                                    TextInput::make('wholesale_minimum_quantity')
                                        ->label('Min qty')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(1)
                                        ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                        ->dehydratedWhenHidden(),
                                    Toggle::make('is_available_wholesale')
                                        ->label('Wholesale')
                                        ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                        ->dehydratedWhenHidden(),
                                    TextInput::make('retail_price')
                                        ->label('Retail price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->minValue(0)
                                        ->step('any')
                                        ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                                        ->dehydratedWhenHidden(),
                                    TextInput::make('retail_compare_at_price')
                                        ->label('Retail compare-at price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->minValue(0)
                                        ->step('any')
                                        ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                                        ->dehydratedWhenHidden(),
                                    Toggle::make('is_available_retail')
                                        ->label('Retail')
                                        ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                                        ->dehydratedWhenHidden(),
                                    TextInput::make('stock_quantity')
                                        ->label('Stock')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(0),
                                ])
                                ->default(fn (HasTable $livewire): array => collect($livewire->getSelectedTableRecords())
                                    ->flatMap(fn (Product $product): array => $product->variants->map(fn ($variant): array => [
                                        'id' => $variant->getKey(),
                                        'label' => trim($product->name.($variant->color ? ' · '.$variant->color : '')),
                                        'wholesale_price' => $variant->wholesale_price,
                                        'wholesale_compare_at_price' => $variant->wholesale_compare_at_price,
                                        'wholesale_minimum_quantity' => $variant->wholesale_minimum_quantity,
                                        'is_available_wholesale' => (bool) $variant->is_available_wholesale,
                                        'retail_price' => $variant->retail_price,
                                        'retail_compare_at_price' => $variant->retail_compare_at_price,
                                        'is_available_retail' => (bool) $variant->is_available_retail,
                                        'stock_quantity' => $variant->stock_quantity,
                                    ])->all())
                                    ->all())
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->columns(fn (): int => AdminStorefront::current() === 'all' ? 5 : 4)
                                ->columnSpanFull(),
                        ])
                        ->modalHeading('Edit selected products')
                        ->modalDescription('Prices and stock below apply per variant.')
                        ->modalSubmitActionLabel('Save changes')
                        ->action(function (array $data): void {
                            $updated = 0;

                            foreach ($data['variants'] ?? [] as $row) {
                                $variant = ProductVariant::find($row['id'] ?? null);

                                if (! $variant) {
                                    continue;
                                }

                                if (AdminStorefront::showsWholesaleFields()) {
                                    $variant->wholesale_price = $row['wholesale_price'];
                                    $variant->wholesale_compare_at_price = filled($row['wholesale_compare_at_price'] ?? null)
                                        ? $row['wholesale_compare_at_price']
                                        : null;
                                    $variant->wholesale_minimum_quantity = filled($row['wholesale_minimum_quantity'] ?? null)
                                        ? $row['wholesale_minimum_quantity']
                                        : $variant->wholesale_minimum_quantity;
                                    $variant->is_available_wholesale = $row['is_available_wholesale'] ?? $variant->is_available_wholesale;
                                }

                                if (AdminStorefront::showsRetailFields()) {
                                    $variant->retail_price = $row['retail_price'];
                                    $variant->retail_compare_at_price = filled($row['retail_compare_at_price'] ?? null)
                                        ? $row['retail_compare_at_price']
                                        : null;
                                    $variant->is_available_retail = $row['is_available_retail'] ?? $variant->is_available_retail;
                                }

                                $variant->stock_quantity = $row['stock_quantity'];
                                $variant->save();
                                $updated++;
                            }

                            Notification::make()
                                ->title('Products updated')
                                ->body("{$updated} variants updated.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('setVisibility')
                        ->label('Change websites')
                        ->icon('heroicon-o-globe-alt')
                        ->color('primary')
                        ->modalHeading('Change websites for selected products')
                        ->modalDescription('Set every selected product to one storefront or both. New products default to both websites.')
                        ->schema([
                            Radio::make('visibility')
                                ->label('Where should these products be shown?')
                                ->options(self::visibilityOptions())
                                ->default('both')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (Product $product): ?bool => $product->update(['visibility' => $data['visibility']]));

                            Notification::make()
                                ->title('Websites updated')
                                ->body($records->count().' products are now shown on '.self::visibilityLabel($data['visibility']).'.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    ProductPublishActions::bulkUnpublishAction(),
                    ProductPublishActions::bulkPublishAction(),
                    DeleteBulkAction::make()
                        ->modalHeading('Delete selected products?')
                        ->modalDescription('This permanently deletes the selected product(s) and all of their variants. This cannot be undone. Consider disabling a product instead if you only want to hide it from your website.'),
                ]),
            ]);
    }

    /** @return array<string, string> */
    private static function visibilityOptions(): array
    {
        return [
            'wholesale' => 'Wholesale only',
            'retail' => 'Retail only',
            'both' => 'Retail and wholesale',
        ];
    }

    private static function visibilityLabel(string $visibility): string
    {
        return self::visibilityOptions()[$visibility] ?? $visibility;
    }
}
