<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Tables\Columns\DirectImageColumn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RetailProductActivationService;
use App\Support\AdminStorefront;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                    ->label(fn (): string => AdminStorefront::current() === 'all' ? 'Wholesale name' : 'Product name')
                    ->width('13.5rem')
                    ->sortable()
                    ->searchable()
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                    ->toggleable(),
                TextColumn::make('retail_name')
                    ->label(fn (): string => AdminStorefront::current() === 'all' ? 'Retail name' : 'Product name')
                    ->width('13.5rem')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Retail name not set')
                    ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                    ->toggleable()
                    ->toggledHiddenByDefault(fn (): bool => AdminStorefront::current() !== 'retail'),
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
                TextColumn::make('available_stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
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
                    ->toggleable(),
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
                                    TextInput::make('retail_price')
                                        ->label('Retail price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->minValue(0)
                                        ->step('any')
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
                                        'retail_price' => $variant->retail_price,
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
                                }

                                if (AdminStorefront::showsRetailFields()) {
                                    $variant->retail_price = $row['retail_price'];
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
