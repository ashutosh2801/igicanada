<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Resources\MediaAssets\Tables\MediaAssetsPickerTable;
use App\Models\MediaAsset;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                RichEditor::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                Select::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
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
                    ->columnSpanFull()
                    ->helperText('Open the Media Library popup to view thumbnails and select multiple images.'),
                TextInput::make('sku')
                    ->label('SKU'),
                TextInput::make('weight_kg')
                    ->numeric(),
                Toggle::make('is_active')
                    ->default(true),
                DateTimePicker::make('published_at'),
                Repeater::make('variants')
                    ->relationship()
                    ->schema([
                        TextInput::make('sku')->label('SKU'),
                        TextInput::make('color')
                            ->label('Color label')
                            ->placeholder('e.g. Vintage Brown')
                            ->maxLength(100),
                        ColorPicker::make('color_code')
                            ->label('Color code')
                            ->hex(),
                        Select::make('sizes')
                            ->label('Available sizes')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(self::sizeOptions())
                            ->helperText('Select one or more sizes. Each selection appears here immediately.'),
                        TextInput::make('wholesale_price')->numeric()->prefix('$'),
                        TextInput::make('wholesale_minimum_quantity')->numeric()->default(1)->minValue(1),
                        TextInput::make('stock_quantity')->numeric()->default(0),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
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
            <img src="{$url}" alt="{$name}" title="{$name}" style="width:3.5rem;height:3.5rem;border-radius:0.5rem;object-fit:cover" />
        HTML);
    }

}
