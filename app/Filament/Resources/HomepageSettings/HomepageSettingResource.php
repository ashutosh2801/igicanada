<?php

namespace App\Filament\Resources\HomepageSettings;

use App\Filament\Resources\HomepageSettings\Pages\EditHomepageSetting;
use App\Filament\Resources\HomepageSettings\Pages\ListHomepageSettings;
use App\Models\Category;
use App\Models\HomepageSetting;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HomepageSettingResource extends Resource
{
    protected static ?string $model = HomepageSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Website settings';

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?string $modelLabel = 'homepage';

    public static function getEloquentQuery(): Builder
    {
        return AdminStorefront::apply(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Website')->schema([
                TextInput::make('sales_channel')
                    ->label('Sales channel')
                    ->formatStateUsing(fn (string $state): string => $state === 'retail' ? 'Leather Wallets · Retail' : 'IGI Canada · Wholesale')
                    ->disabled()
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
            ])->columnSpanFull(),
            Section::make('Brand and header')->schema([
                TextInput::make('brand_name')->required()->maxLength(255),
                FileUpload::make('logo_path')->label('Logo')->image()->disk('public')->directory('storefront/branding'),
                TextInput::make('logo_alt')->label('Logo alternative text')->required(),
                FileUpload::make('favicon_path')->label('Favicon')->image()->disk('public')->directory('storefront/branding')->helperText('Upload a square PNG, ICO or WebP image.'),
                TextInput::make('announcement_text')->columnSpanFull(),
                Toggle::make('show_category_menu')
                    ->label('Show category mega menu')
                    ->default(true)
                    ->visible(fn (?HomepageSetting $record): bool => self::showsWholesaleFields($record))
                    ->dehydratedWhenHidden(),
                TextInput::make('category_menu_label')
                    ->label('Category menu label')
                    ->default('All categories')
                    ->visible(fn (?HomepageSetting $record): bool => self::showsWholesaleFields($record))
                    ->dehydratedWhenHidden(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Hero')->schema([
                TextInput::make('hero_eyebrow'),
                TextInput::make('hero_title')->required()->columnSpanFull(),
                Textarea::make('hero_description')->rows(4)->columnSpanFull(),
                FileUpload::make('hero_image_paths')
                    ->label('Hero slider images')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->maxFiles(8)
                    ->disk('public')
                    ->directory('storefront/hero')
                    ->helperText('Upload up to 8 images and drag them into display order.'),
                TextInput::make('hero_slider_interval')
                    ->label('Slide duration (seconds)')
                    ->numeric()
                    ->minValue(3)
                    ->maxValue(15)
                    ->default(5)
                    ->required(),
                TextInput::make('hero_primary_label'),
                TextInput::make('hero_primary_url')->placeholder('/shop or /catalogue'),
                TextInput::make('hero_secondary_label'),
                TextInput::make('hero_secondary_url')->placeholder('/shop or /wholesale/apply'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Search and social metadata')->schema([
                TextInput::make('default_meta_title')->label('Default browser and SEO title')->required()->columnSpanFull(),
                Textarea::make('default_meta_description')->label('Default meta description')->rows(3)->columnSpanFull(),
                TextInput::make('og_title')->label('Open Graph title'),
                Textarea::make('og_description')->label('Open Graph description')->rows(3),
                FileUpload::make('og_image_path')->label('Open Graph image')->image()->disk('public')->directory('storefront/social'),
                Select::make('twitter_card')->options([
                    'summary_large_image' => 'Large image card',
                    'summary' => 'Summary card',
                ])->default('summary_large_image')->required(),
                TextInput::make('twitter_title')->label('X / Twitter title'),
                Textarea::make('twitter_description')->label('X / Twitter description')->rows(3),
                FileUpload::make('twitter_image_path')->label('X / Twitter image')->image()->disk('public')->directory('storefront/social'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Products section')->schema([
                TextInput::make('catalogue_eyebrow'),
                TextInput::make('catalogue_title')->required(),
                Textarea::make('catalogue_description')->rows(3)->columnSpanFull(),
                Select::make('featured_category_ids')
                    ->label('Featured categories')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn () => AdminStorefront::applyVisibility(Category::query())->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->helperText('Leave empty to automatically show active categories for the selected website.')
                    ->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('New arrivals products')->schema([
                Toggle::make('show_new_arrivals')->label('Show new arrivals on homepage')->default(true),
                TextInput::make('new_arrivals_count')->label('Number of products')->numeric()->minValue(4)->maxValue(12)->default(8)->required(),
                TextInput::make('new_arrivals_eyebrow')->label('Small heading'),
                TextInput::make('new_arrivals_title')->label('Section title')->required(),
                Textarea::make('new_arrivals_description')->label('Description')->rows(3)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make('Footer')->schema([
                Textarea::make('footer_description')->rows(3)->columnSpanFull(),
                TextInput::make('footer_address')->columnSpanFull(),
                TextInput::make('footer_phone'),
                TextInput::make('footer_email')->email(),
                TextInput::make('footer_copyright')->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sales_channel')
                    ->label('Website')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'retail' ? 'Leather Wallets' : 'IGI Canada')
                    ->color(fn (string $state): string => $state === 'retail' ? 'warning' : 'info')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('brand_name')->label('Storefront'),
                TextColumn::make('hero_title')->limit(70),
                TextColumn::make('updated_at')->dateTime(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomepageSettings::route('/'),
            'edit' => EditHomepageSetting::route('/{record}/edit'),
        ];
    }

    private static function showsWholesaleFields(?HomepageSetting $record): bool
    {
        return ($record?->sales_channel ?? AdminStorefront::current()) !== 'retail';
    }
}
