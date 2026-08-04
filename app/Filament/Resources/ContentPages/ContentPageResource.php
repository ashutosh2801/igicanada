<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Models\ContentPage;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContentPageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
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
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'review_required' => 'Review required',
                    'published' => 'Published',
                ]),
            ])
            ->recordActions([EditAction::make()]);
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
