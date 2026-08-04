<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'customer';

    protected static ?string $pluralModelLabel = 'Customers';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('account_type', 'wholesale');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('resellerProfile.company')->label('Company')->disabled(),
            Select::make('account_type')->options([
                'wholesale' => 'Customer',
            ])->disabled(),
            Select::make('approval_status')->options([
                'pending' => 'Pending review',
                'approved' => 'Approved',
                'suspended' => 'Suspended',
            ])->required(),
            Select::make('price_tier_id')->relationship('priceTier', 'name')->label('Price tier')->preload(),
            TextInput::make('resellerProfile.phone')->label('Phone')->disabled(),
            TextInput::make('legacy_id')->label('Legacy ID')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
