<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Components\ModalTableSelect;
use App\Filament\Resources\MediaAssets\Tables\MediaAssetsPickerTable;
use App\Models\MediaAsset;
use App\Support\AdminStorefront;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent category / submenu')
                    ->relationship(
                        'parent',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => AdminStorefront::applyVisibility($query),
                    )
                    ->searchable()
                    ->preload()
                    ->helperText('Leave empty for a top-level menu. Choose a parent for level 2 or level 3.'),
                TextInput::make('legacy_id')
                    ->label('Legacy ID')
                    ->disabled()
                    ->numeric()
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('visibility')
                    ->label('Website visibility')
                    ->options([
                        'wholesale' => 'IGI Canada only',
                        'retail' => 'Leather Wallets only',
                        'both' => 'Both websites',
                    ])
                    ->required()
                    ->visible(fn (): bool => AdminStorefront::current() === 'all')
                    ->dehydratedWhenHidden()
                    ->default(fn (): string => AdminStorefront::current() === 'all' ? 'both' : AdminStorefront::current()),
                ModalTableSelect::make('image_media_asset_id')
                    ->label('Image')
                    ->relationship('imageMediaAsset', 'title')
                    ->getOptionLabelFromRecordUsing(fn (MediaAsset $record): HtmlString => self::selectedImageThumbnail($record))
                    ->tableConfiguration(MediaAssetsPickerTable::class)
                    ->live()
                    ->extraAttributes(['class' => 'category-image-thumbnail-select'])
                    ->selectAction(fn (Action $action): Action => $action
                        ->label('Open media library and select image')
                        ->icon('heroicon-o-photo')
                        ->button()
                        ->color('primary')
                        ->modalIcon('heroicon-o-photo')
                        ->modalIconColor('primary')
                        ->modalHeading('Select category image')
                        ->modalDescription('Search the media library, select one image, then click Use selected image.')
                        ->modalSubmitActionLabel('Use selected image')
                        ->modalWidth(Width::ScreenTwoExtraLarge)
                        ->extraModalWindowAttributes(['class' => 'media-library-popup'])
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->closeModalByClickingAway(false)
                        ->slideOver(false))
                    ->placeholder('No image selected')
                    ->helperText('Open the Media Library popup to select one reusable image. First upload your picture under Media → Media library, then select it here.'),
                TextInput::make('position')
                    ->label('Menu order')
                    ->required()
                    ->numeric()
                    ->helperText('Lower numbers appear first within the same menu level.')
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true),
            ]);
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
