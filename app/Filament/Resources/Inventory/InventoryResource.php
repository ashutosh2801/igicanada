<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ListInventory;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Tables\Columns\DirectImageColumn;
use App\Filament\Tables\Columns\StockColumn;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class InventoryResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?string $modelLabel = 'variant';

    protected static ?string $pluralModelLabel = 'Inventory';

    protected static ?string $slug = 'inventory';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('product', fn (Builder $query): Builder => AdminStorefront::applyProductVisibility($query))
            ->with(['product.images.mediaAsset', 'product.primaryMedia']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('stock_quantity', 'asc')
            ->extremePaginationLinks()
            ->columns([
                DirectImageColumn::make('product_image_url')
                    ->label('Image')
                    ->state(fn (ProductVariant $record): ?string => $record->product?->primaryImageUrl() ?: $record->product?->images->first()?->url())
                    ->square()
                    ->imageSize(48)
                    ->extraImgAttributes([
                        'class' => 'block rounded-md bg-white object-contain object-center p-1',
                        'style' => 'width:48px;height:48px;object-fit:contain;object-position:center;',
                    ])
                    ->checkFileExistence(false)
                    ->toggleable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->width('14rem')
                    ->wrap()
                    ->limit(60)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('variant_label')
                    ->label('Variant')
                    ->state(fn (ProductVariant $record): string => $record->optionLabel())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where(fn (Builder $query): Builder => $query
                            ->where('color', 'like', "%{$search}%")
                            ->orWhere('size', 'like', "%{$search}%"))),
                StockColumn::make('stock_quantity')
                    ->label('Stock')
                    ->width('6rem')
                    ->rules(['integer', 'min:0'])
                    ->sortable(),
                TextInputColumn::make('stock_threshold')
                    ->label('Low-stock at')
                    ->type('number')
                    ->rules(['integer', 'min:0'])
                    ->width('6rem')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ProductVariant $record): string => self::stockStatus($record))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'Out of stock',
                        'low_stock' => 'Low stock',
                        default => 'In stock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label('Stock status')
                    ->options([
                        'in_stock' => 'In stock',
                        'low_stock' => 'Low stock (at threshold)',
                        'out_of_stock' => 'Out of stock',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'in_stock' => $query->whereColumn('stock_quantity', '>', 'stock_threshold'),
                        'low_stock' => $query->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'stock_threshold'),
                        'out_of_stock' => $query->where('stock_quantity', '<=', 0),
                        default => $query,
                    }),
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('product.categories', fn (Builder $query): Builder => $query->whereKey($data['value']))
                        : $query),
                TernaryFilter::make('is_active')
                    ->label('Active variants')
                    ->default(true),
            ])
            ->recordActions([
                Action::make('editProduct')
                    ->label('Product')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (ProductVariant $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id])),
            ])
            ->toolbarActions([
                Action::make('saveStockUpdates')
                    ->label('Save stock')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->disabled(fn (HasTable $livewire): bool => blank($livewire->pendingStockUpdates))
                    ->action(function (HasTable $livewire): void {
                        $updated = 0;

                        ProductVariant::query()
                            ->whereKey(array_keys($livewire->pendingStockUpdates))
                            ->get()
                            ->each(function (ProductVariant $variant) use ($livewire, &$updated): void {
                                $variant->stock_quantity = max(0, (int) $livewire->pendingStockUpdates[$variant->getKey()]);
                                $variant->save();
                                $updated++;
                            });

                        $livewire->pendingStockUpdates = [];
                        $livewire->dispatch('stock-updates-saved');

                        Notification::make()
                            ->title('Stock saved')
                            ->body("Updated stock for {$updated} variant(s).")
                            ->success()
                            ->send();
                    }),
                BulkActionGroup::make([
                    BulkAction::make('editVariantStock')
                        ->label('Set stock per variant')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->modalHeading('Set stock per variant')
                        ->modalDescription('Type a separate stock quantity for each selected variant, then save.')
                        ->modalSubmitActionLabel('Save stock')
                        ->fillForm(fn (Collection $records): array => ['variants' => self::variantStockRows($records)])
                        ->schema([self::variantStockRepeater()])
                        ->action(fn (array $data) => self::saveVariantStock($data['variants'] ?? []))
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
                                ->required(),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->integer(),
                        ])
                        ->modalSubmitActionLabel('Apply stock update')
                        ->action(function (Collection $records, array $data): void {
                            $quantity = (int) $data['quantity'];

                            $records->each(function (ProductVariant $variant) use ($data, $quantity): void {
                                $current = (int) $variant->stock_quantity;

                                $variant->stock_quantity = match ($data['operation']) {
                                    'increase' => $current + $quantity,
                                    'decrease' => max(0, $current - $quantity),
                                    default => $quantity,
                                };

                                $variant->save();
                            });

                            Notification::make()
                                ->title('Stock updated')
                                ->body("{$records->count()} variants updated.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('updateThreshold')
                        ->label('Set low-stock threshold')
                        ->icon('heroicon-o-bell-alert')
                        ->schema([
                            TextInput::make('stock_threshold')
                                ->label('Low-stock threshold')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->integer(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            ProductVariant::query()
                                ->whereKey($records->modelKeys())
                                ->update(['stock_threshold' => (int) $data['stock_threshold']]);

                            Notification::make()
                                ->title('Threshold updated')
                                ->body("{$records->count()} variants updated.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /**
     * Repeater with one stock input per variant. Shared by the inventory bulk
     * action and the per-product stock action on the products table.
     */
    public static function variantStockRepeater(): Repeater
    {
        return Repeater::make('variants')
            ->hiddenLabel()
            ->schema([
                Hidden::make('id'),
                TextInput::make('label')
                    ->label('Product / variant')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(2),
                TextInput::make('stock_quantity')
                    ->label('Stock')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
                TextInput::make('stock_threshold')
                    ->label('Low-stock at')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required(),
            ])
            ->columns(4)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->columnSpanFull();
    }

    /**
     * @param  iterable<ProductVariant>  $variants
     * @return array<int, array<string, mixed>>
     */
    public static function variantStockRows(iterable $variants): array
    {
        return collect($variants)
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->getKey(),
                'label' => trim(($variant->product?->name ?? '').' · '.$variant->optionLabel().($variant->sku ? " ({$variant->sku})" : ''), ' ·'),
                'stock_quantity' => (int) $variant->stock_quantity,
                'stock_threshold' => (int) $variant->stock_threshold,
            ])
            ->values()
            ->all();
    }

    /** @param  array<int|string, array<string, mixed>>  $rows */
    public static function saveVariantStock(array $rows): void
    {
        $rows = collect($rows)->keyBy(fn (array $row): int => (int) ($row['id'] ?? 0));
        $updated = 0;

        ProductVariant::query()
            ->whereKey($rows->keys()->filter()->all())
            ->get()
            ->each(function (ProductVariant $variant) use ($rows, &$updated): void {
                $row = $rows->get($variant->getKey());

                $variant->stock_quantity = max(0, (int) $row['stock_quantity']);
                $variant->stock_threshold = max(0, (int) ($row['stock_threshold'] ?? $variant->stock_threshold));

                if ($variant->isDirty()) {
                    $variant->save();
                    $updated++;
                }
            });

        Notification::make()
            ->title('Stock saved')
            ->body("Updated {$updated} variant(s).")
            ->success()
            ->send();
    }

    public static function stockStatus(ProductVariant $variant): string
    {
        $stock = (int) $variant->stock_quantity;

        return match (true) {
            $stock <= 0 => 'out_of_stock',
            $stock <= (int) $variant->stock_threshold => 'low_stock',
            default => 'in_stock',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventory::route('/'),
        ];
    }
}
