<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Components\ModalTableSelect;
use App\Filament\Resources\MediaAssets\Tables\MediaAssetsPickerTable;
use App\Models\MediaAsset;
use App\Support\AdminStorefront;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Product name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old): void {
                        $slug = (string) $get('slug');
                        if (blank($slug) || $slug === Str::slug((string) $old)) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                RichEditor::make('description')
                    ->label('Product description')
                    ->columnSpanFull()
                    ->helperText('The same name, slug and description are shown on both websites.'),
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('categories')
                            ->relationship(
                                'categories',
                                'name',
                                modifyQueryUsing: fn ($query) => AdminStorefront::applyVisibility($query),
                            )
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->extraFieldWrapperAttributes(['class' => 'product-sku-sticky'])
                            ->default(fn (Get $get): ?string => filled($get('name'))
                                ? 'SKU-'.strtoupper(Str::slug($get('name'), '-'))
                                : null)
                            ->placeholder('SKU-'.strtoupper(Str::slug('Product name', '-'))),
                        TextInput::make('weight_kg')
                            ->numeric(),
                    ]),
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        ModalTableSelect::make('primary_media_asset_id')
                    ->label('Primary image')
                    ->relationship('primaryMedia', 'title')
                    ->getOptionLabelFromRecordUsing(fn (MediaAsset $record): HtmlString => self::selectedImageThumbnail($record))
                    ->tableConfiguration(MediaAssetsPickerTable::class)
                    ->live()
                    ->extraAttributes(['class' => 'primary-image-thumbnail-select'])
                    ->selectAction(fn (Action $action): Action => $action
                        ->label('Open media library and select primary image')
                        ->icon('heroicon-o-photo')
                        ->button()
                        ->color('primary')
                        ->modalIcon('heroicon-o-photo')
                        ->modalIconColor('primary')
                        ->modalHeading('Select primary image')
                        ->modalDescription('Search the media library, select one image, then click Use selected image.')
                        ->modalSubmitActionLabel('Use selected image')
                        ->modalWidth(Width::ScreenTwoExtraLarge)
                        ->extraModalWindowAttributes(['class' => 'media-library-popup'])
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->closeModalByClickingAway(false)
                        ->slideOver(false))
                    ->placeholder('No primary image selected')
                    ->helperText('Open the Media Library popup to select one reusable image.'),
                        ModalTableSelect::make('mediaAssets')
                    ->label('Product images (select multiple)')
                    ->relationship('mediaAssets', 'title')
                    ->getOptionLabelFromRecordUsing(fn (MediaAsset $record): HtmlString => self::selectedImageThumbnail($record))
                    ->multiple()
                    ->live()
                    ->extraAttributes(['class' => 'product-images-thumbnail-select'])
                    ->tableConfiguration(MediaAssetsPickerTable::class)
                    ->selectAction(fn (Action $action): Action => $action
                        ->label('Open media library and select images')
                        ->icon('heroicon-o-photo')
                        ->button()
                        ->color('primary')
                        ->modalIcon('heroicon-o-photo')
                        ->modalIconColor('primary')
                        ->modalHeading('Select product images')
                        ->modalDescription('Search the media library, tick multiple images, then click Use selected images.')
                        ->modalSubmitActionLabel('Use selected images')
                        ->modalWidth(Width::ScreenTwoExtraLarge)
                        ->extraModalWindowAttributes(['class' => 'media-library-popup'])
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->closeModalByClickingAway(false)
                        ->slideOver(false))
                    ->placeholder('No gallery images selected')
                    ->helperText('Open the Media Library popup to view thumbnails and select multiple images.'),
                    ]),
                Select::make('visibility')
                    ->options([
                        'wholesale' => 'Wholesale only',
                        'retail' => 'Retail only',
                        'both' => 'Retail and wholesale',
                    ])
                    ->required()
                    ->live()
                    ->visible(fn (): bool => AdminStorefront::current() === 'all')
                    ->dehydratedWhenHidden()
                    ->default('both'),
                Toggle::make('is_active')
                    ->default(true),
                Section::make('Variants')
                    ->icon('heroicon-o-swatch')
                    ->description('Add colour variants, sizes, and pricing for each website. Variants with a price show on the storefronts.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('variants')
                            ->relationship()
                            ->addable(false)
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                Select::make('color')
                                                    ->label('Color label')
                                                    ->options([
                                                        'Black' => 'Black',
                                                        'Brown' => 'Brown',
                                                        'Tan' => 'Tan',
                                                    ])
                                                    ->searchable()
                                                    ->native(false)
                                                    ->live()
                                                    ->getOptionLabelUsing(fn (?string $state): ?string => $state)
                                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                        $colorCodes = [
                                                            'black' => '#000000',
                                                            'brown' => '#8B4513',
                                                            'tan' => '#D2B48C',
                                                        ];

                                                        if (isset($colorCodes[strtolower((string) $state)])) {
                                                            $set('color_code', $colorCodes[strtolower((string) $state)]);
                                                        }
                                                    })
                                                    ->createOptionForm([
                                                        TextInput::make('custom_color')
                                                            ->label('Custom color')
                                                            ->required()
                                                            ->maxLength(100),
                                                    ])
                                                    ->createOptionUsing(fn (array $data): string => $data['custom_color'])
                                                    ->createOptionModalHeading('Add custom color'),
                                                ModalTableSelect::make('image_ids')
                                                    ->label('Colour images')
                                                    ->multiple()
                                                    ->placeholder('No colour images selected')
                                                    ->getOptionLabelsUsing(fn (array $values): array => MediaAsset::query()
                                                        ->whereIn('id', $values)
                                                        ->get()
                                                        ->mapWithKeys(fn (MediaAsset $asset): array => [(string) $asset->id => self::selectedImageThumbnail($asset)])
                                                        ->all())
                                                    ->tableConfiguration(MediaAssetsPickerTable::class)
                                                    ->tableSelect(fn (TableSelect $select): TableSelect => $select->relationshipName('mediaAssets'))
                                                    ->helperText('Optional. These images show on the product page when this colour is selected.')
                                                    ->selectAction(fn (Action $action): Action => $action
                                                        ->label('Open media library and select colour images')
                                                        ->icon('heroicon-o-photo')
                                                        ->button()
                                                        ->color('primary')
                                                        ->modalIcon('heroicon-o-photo')
                                                        ->modalIconColor('primary')
                                                        ->modalHeading('Select colour images')
                                                        ->modalDescription('Search the media library, tick the images for this colour, then click Use selected images.')
                                                        ->modalSubmitActionLabel('Use selected images')
                                                        ->modalWidth(Width::ScreenTwoExtraLarge)
                                                        ->extraModalWindowAttributes(['class' => 'media-library-popup'])
                                                        ->stickyModalHeader()
                                                        ->stickyModalFooter()
                                                        ->closeModalByClickingAway(false)
                                                        ->slideOver(false)),
                                            ])
                                            ->columnSpan(1),
                                        Grid::make(1)
                                            ->schema([
                                                ColorPicker::make('color_code')
                                                    ->label('Color code')
                                                    ->hex(),
                                                TextInput::make('retail_price')->numeric()->prefix('$')->minValue(0.01)
                                                    ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                                                    ->dehydratedWhenHidden(),
                                                TextInput::make('retail_compare_at_price')
                                                    ->label('Retail compare-at price')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->minValue(0.01)
                                                    ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                                                    ->dehydratedWhenHidden(),
                                                TextInput::make('stock_quantity')->numeric()->integer()->minValue(0)->default(0),
                                                TextInput::make('wholesale_minimum_quantity')->numeric()->default(1)->minValue(1)
                                                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                                    ->dehydratedWhenHidden(),
                                                TextInput::make('wholesale_price')->numeric()->prefix('$')->minValue(0.01)
                                                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                                    ->dehydratedWhenHidden(),
                                                TextInput::make('wholesale_compare_at_price')
                                                    ->label('Base W/S price')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->minValue(0.01)
                                                    ->helperText('Optional. Shown struck-through as the original price next to the sale price.')
                                                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                                    ->dehydratedWhenHidden(),
                                            ])
                                            ->columnSpan(1),
                                        Grid::make(1)
                                            ->schema([
                                                Select::make('sizes')
                                                    ->label('Available sizes')
                                                    ->multiple()
                                                    ->reorderable()
                                                    ->searchable()
                                                    ->preload()
                                                    ->native(false)
                                                    ->options(self::sizeOptions())
                                                    ->getOptionLabelsUsing(fn (array $values): array => collect($values)
                                                        ->mapWithKeys(fn (string $value): array => [$value => $value])
                                                        ->all())
                                                    ->createOptionForm([
                                                        TextInput::make('custom_size')
                                                            ->label('Custom size')
                                                            ->placeholder('e.g. OS-46')
                                                            ->required()
                                                            ->maxLength(30),
                                                    ])
                                                    ->createOptionUsing(fn (array $data): string => strtoupper(trim($data['custom_size'])))
                                                    ->createOptionModalHeading('Add custom size')
                                                    ->helperText('Select sizes for this colour, then drag the selected size chips into the order customers should see. Add custom sizes such as OS-46, OS-48, or OS-50.'),
                                                TextInput::make('stock_threshold')
                                                    ->label('Low-stock threshold')
                                                    ->numeric()
                                                    ->integer()
                                                    ->minValue(0)
                                                    ->default(10)
                                                    ->helperText('Notify the admin when stock falls to or below this number.'),
                                                Toggle::make('is_available_retail')
                                                    ->label('Retail')
                                                    ->default(true)
                                                    ->visible(fn (): bool => AdminStorefront::showsRetailFields())
                                                    ->dehydratedWhenHidden(),
                                                Toggle::make('is_available_wholesale')
                                                    ->label('Wholesale')
                                                    ->default(true)
                                                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                                                    ->dehydratedWhenHidden(),
                                                Toggle::make('is_active')->default(true),
                                            ])
                                            ->columnSpan(1),
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                        Actions::make([
                            Action::make('addVariant')
                                ->label('Add to variants')
                                ->button()
                                ->color('gray')
                                ->action(function (Get $get, Set $set): void {
                                    $variants = $get('variants');

                                    if (! is_array($variants)) {
                                        $variants = [];
                                    }

                                    $variants[(string) Str::uuid()] = [
                                        'image_ids' => [],
                                        'sizes' => [],
                                        'stock_quantity' => 0,
                                        'stock_threshold' => 10,
                                        'wholesale_minimum_quantity' => 1,
                                        'is_available_retail' => true,
                                        'is_available_wholesale' => true,
                                        'is_active' => true,
                                    ];

                                    $set('variants', $variants);
                                }),
                            Action::make('cloneVariant')
                                ->label('Clone variant')
                                ->icon('heroicon-o-square-2-stack')
                                ->button()
                                ->color('gray')
                                ->disabled(fn (Get $get): bool => blank($get('variants')))
                                ->action(function (Get $get, Set $set): void {
                                    $variants = $get('variants');

                                    if (! is_array($variants) || $variants === []) {
                                        return;
                                    }

                                    $lastKey = array_key_last($variants);
                                    $variants[(string) Str::uuid()] = $variants[$lastKey];

                                    $set('variants', $variants);
                                }),
                        ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<string, string> */
    private static function sizeOptions(): array
    {
        $letterSizes = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'One Size'];
        $numericSizes = array_map('strval', range(1, 60));

        return collect([...$letterSizes, ...$numericSizes])
            ->mapWithKeys(fn (string $size) => [$size => $size])
            ->all();
    }

    private static function selectedImageThumbnail(MediaAsset $asset): HtmlString
    {
        $url = e($asset->url());
        $name = e($asset->display_name);

        return new HtmlString(<<<HTML
            <img src="{$url}" alt="{$name}" title="{$name}" style="width:3.5rem;height:3.5rem;border-radius:0.5rem;object-fit:cover;display:block" />
            <span class="media-badge-name">{$name}</span>
        HTML);
    }
}
