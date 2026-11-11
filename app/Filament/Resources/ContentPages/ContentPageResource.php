<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Models\ContentPage;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class ContentPageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Content pages';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return AdminStorefront::apply(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_channel')->label('Website')->options([
                'wholesale' => 'IGI Canada wholesale',
                'retail' => 'Leather Wallets retail',
            ])->required()
                ->visible(fn (): bool => AdminStorefront::current() === 'all')
                ->dehydratedWhenHidden()
                ->default(fn (): string => AdminStorefront::current() === 'retail' ? 'retail' : 'wholesale'),
            TextInput::make('title')->required()->maxLength(255),
            // Slugs are unique per website (DB index: sales_channel + slug), so the
            // same slug — e.g. "privacy-policy" — may exist on both storefronts.
            TextInput::make('slug')->required()->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get, ?ContentPage $record): Unique => $rule->where(
                        'sales_channel',
                        $get('sales_channel')
                            ?: $record?->sales_channel
                            ?: (AdminStorefront::current() === 'retail' ? 'retail' : 'wholesale'),
                    ),
                ),
            Select::make('status')->options([
                'draft' => 'Draft',
                'review_required' => 'Review required',
                'published' => 'Published',
            ])->required()->default('draft'),
            Toggle::make('is_legal')->label('Legal, policy, tax or payment content'),
            Textarea::make('excerpt')->rows(3)->columnSpanFull(),
            RichEditor::make('body_html')->label('Page content')->columnSpanFull(),
            TextInput::make('meta_title')->label('SEO title')->maxLength(255),
            Textarea::make('meta_description')->label('SEO description')->rows(3),
            TextInput::make('legacy_id')->label('Legacy ID')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('sales_channel')
                    ->label('Website')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'retail' ? 'Leather Wallets' : 'IGI Canada')
                    ->color(fn (string $state): string => $state === 'retail' ? 'warning' : 'info')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'published' => 'success',
                    'review_required' => 'warning',
                    default => 'gray',
                }),
                IconColumn::make('is_legal')->label('Policy')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('sales_channel')->label('Website')->options([
                    'wholesale' => 'IGI Canada',
                    'retail' => 'Leather Wallets',
                ])->visible(fn (): bool => AdminStorefront::current() === 'all'),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'review_required' => 'Review required',
                    'published' => 'Published',
                ]),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View page')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->visible(fn (ContentPage $record): bool => $record->status === 'published')
                    ->url(fn (ContentPage $record): string => static::publicUrl($record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    /**
     * Public storefront URL for a page. Wholesale pages live at /page/{slug}
     * on IGI Canada; retail pages live at /policies/{slug} on the retail domain.
     */
    public static function publicUrl(ContentPage $page): string
    {
        return $page->sales_channel === 'retail'
            ? route('retail.pages.show', $page->slug)
            : route('pages.show', $page->slug);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentPages::route('/'),
            'create' => CreateContentPage::route('/create'),
            'edit' => EditContentPage::route('/{record}/edit'),
        ];
    }
}
