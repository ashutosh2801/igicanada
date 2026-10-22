<?php

namespace App\Filament\Resources\EmailTemplates;

use App\Filament\Resources\EmailTemplates\Pages\CreateEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Models\EmailTemplate;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?string $navigationLabel = 'Email templates';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'email-templates';

    public static function getEloquentQuery(): Builder
    {
        return AdminStorefront::apply(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('sales_channel')
                ->label('Website')
                ->options(EmailTemplate::channelOptions())
                ->required()
                ->visible(fn (): bool => AdminStorefront::current() === 'all')
                ->dehydratedWhenHidden()
                ->default(fn (): string => AdminStorefront::current() === 'retail' ? 'retail' : 'wholesale'),
            TextInput::make('name')
                ->label('Template key')
                ->required()
                ->maxLength(200)
                ->helperText('Used as the lookup key, e.g. active-mail-to-user. Keep the same key for wholesale and retail so each website resolves its own copy.'),
            TextInput::make('subject')->required()->maxLength(500)->columnSpanFull(),
            RichEditor::make('content')
                ->label('Email content')
                ->columnSpanFull()
                ->helperText('Available placeholders: {{name}}, {{username}}, {{password}}, {{login_link}}, {{message}}, {{phone}}, {{type}}, {{edit_link}}'),
            Toggle::make('status')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sales_channel')
            ->columns([
                TextColumn::make('sales_channel')
                    ->label('Website')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'retail' ? 'Leather Wallets' : 'IGI Canada')
                    ->color(fn (string $state): string => $state === 'retail' ? 'warning' : 'info')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('subject')->limit(50)->searchable(),
                IconColumn::make('status')->boolean(),
            ])
            ->filters([
                SelectFilter::make('sales_channel')->label('Website')->options(EmailTemplate::channelOptions())
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
            ])
            ->recordActions([
                Action::make('copy')
                    ->label('Copy')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->action(function (EmailTemplate $record): void {
                        $baseName = trim((string) $record->name).'-copy';
                        $name = substr($baseName, 0, 200);
                        $suffix = 2;

                        while (EmailTemplate::query()
                            ->where('sales_channel', $record->sales_channel)
                            ->where('name', $name)
                            ->exists()) {
                            $suffixText = '-'.$suffix++;
                            $name = substr($baseName, 0, 200 - strlen($suffixText)).$suffixText;
                        }

                        $copy = $record->replicate();
                        $copy->name = $name;
                        $copy->save();

                        Notification::make()
                            ->title('Email template copied')
                            ->body("Created {$name}.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'create' => CreateEmailTemplate::route('/create'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
