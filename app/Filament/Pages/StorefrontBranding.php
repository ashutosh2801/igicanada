<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class StorefrontBranding extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?string $navigationLabel = 'Storefront branding';

    protected static ?string $title = 'Storefront branding';

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadSettings(AdminStorefront::current() === 'all' ? 'retail' : AdminStorefront::current());
    }

    public function loadSettings(string $channel): void
    {
        $settings = HomepageSetting::firstOrCreate(['sales_channel' => $channel]);

        $this->form->fill($settings->only([
            'brand_name',
            'logo_path',
            'logo_alt',
            'favicon_path',
            'announcement_text',
            'default_meta_title',
            'default_meta_description',
            'og_title',
            'og_description',
            'og_image_path',
            'twitter_card',
            'twitter_title',
            'twitter_description',
            'twitter_image_path',
        ]));

        $this->data['sales_channel'] = $channel;
    }

    public function form(Schema $schema): Schema
    {
        $storefrontOptions = array_filter(
            AdminStorefront::options(),
            fn (string $key): bool => $key !== 'all',
            ARRAY_FILTER_USE_KEY,
        );

        return $schema
            ->components([
                Section::make('Storefront')->schema([
                    Select::make('sales_channel')
                        ->label('Storefront')
                        ->options($storefrontOptions)
                        ->live()
                        ->afterStateUpdated(fn (string $state): null => $this->loadSettings($state))
                        ->helperText('Switch between IGI Canada, Leather Wallets and Wallets and Belts to manage each website’s logo, favicon and meta settings.'),
                ])->columns(2)->columnSpanFull(),
                Section::make('Branding')->schema([
                    TextInput::make('brand_name')->label('Store name')->required()->maxLength(255),
                    FileUpload::make('logo_path')->label('Logo')->image()->disk('public')->directory('storefront/branding'),
                    TextInput::make('logo_alt')->label('Logo alternative text')->maxLength(255),
                    FileUpload::make('favicon_path')->label('Favicon')->image()->disk('public')->directory('storefront/branding')->helperText('Upload a square PNG, ICO or WebP image.'),
                    TextInput::make('announcement_text')->label('Announcement bar text')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
                Section::make('Meta and social')->schema([
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
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save branding')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->alignment(Alignment::Start),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $channel = $data['sales_channel'];

        unset($data['sales_channel']);

        HomepageSetting::updateOrCreate(['sales_channel' => $channel], $data);

        Notification::make()->title('Branding saved')->success()->send();
    }
}
