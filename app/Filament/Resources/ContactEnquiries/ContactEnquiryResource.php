<?php

namespace App\Filament\Resources\ContactEnquiries;

use App\Filament\Resources\ContactEnquiries\Pages\EditContactEnquiry;
use App\Filament\Resources\ContactEnquiries\Pages\ListContactEnquiries;
use App\Models\ContactEnquiry;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ContactEnquiryResource extends Resource
{
    protected static ?string $model = ContactEnquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Contact enquiries';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return AdminStorefront::apply(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sales_channel')
                ->label('Channel')
                ->disabled()
                ->visible(fn (): bool => AdminStorefront::current() === 'all'),
            TextInput::make('name')->disabled(),
            TextInput::make('email')->disabled(),
            TextInput::make('company')
                ->disabled()
                ->visible(fn (?ContactEnquiry $record): bool => self::showsWholesaleFields($record)),
            TextInput::make('phone')->disabled(),
            TextInput::make('subject')->disabled()->columnSpanFull(),
            Textarea::make('message')->disabled()->rows(10)->columnSpanFull(),
            Select::make('status')->options([
                'new' => 'New',
                'in_progress' => 'In progress',
                'resolved' => 'Resolved',
                'spam' => 'Spam',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sales_channel')
                    ->label('Channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->title())
                    ->color(fn (string $state): string => $state === 'retail' ? 'warning' : 'info')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('subject')->limit(45),
                TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'new' => 'warning',
                    'resolved' => 'success',
                    'spam' => 'danger',
                    default => 'info',
                }),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('sales_channel')->label('Channel')->options([
                    'wholesale' => 'Wholesale',
                    'retail' => 'Retail',
                ])->visible(fn (): bool => AdminStorefront::current() === 'all'),
                SelectFilter::make('status')->options([
                    'new' => 'New',
                    'in_progress' => 'In progress',
                    'resolved' => 'Resolved',
                    'spam' => 'Spam',
                ]),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactEnquiries::route('/'),
            'edit' => EditContactEnquiry::route('/{record}/edit'),
        ];
    }

    private static function showsWholesaleFields(?ContactEnquiry $record): bool
    {
        return ($record?->sales_channel ?? AdminStorefront::current()) !== 'retail';
    }
}
