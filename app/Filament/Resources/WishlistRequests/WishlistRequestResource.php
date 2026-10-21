<?php

namespace App\Filament\Resources\WishlistRequests;

use App\Filament\Resources\WishlistRequests\Pages\EditWishlistRequest;
use App\Filament\Resources\WishlistRequests\Pages\ListWishlistRequests;
use App\Models\WishlistRequest;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WishlistRequestResource extends Resource
{
    protected static ?string $model = WishlistRequest::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;
    protected static string|UnitEnum|null $navigationGroup = 'Contact enquiries';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Wishlist requests';

    public static function getEloquentQuery(): Builder
    {
        return AdminStorefront::apply(parent::getEloquentQuery()->with(['product', 'variant']));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('product.name')->label('Product')->disabled(),
            TextInput::make('variant.color')->label('Colour')->disabled(),
            TextInput::make('variant.size')->label('Size')->disabled(),
            TextInput::make('sales_channel')->label('Channel')->disabled(),
            TextInput::make('name')->disabled(),
            TextInput::make('email')->disabled(),
            Select::make('status')->options(['new' => 'New', 'contacted' => 'Contacted', 'resolved' => 'Resolved'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('sales_channel')->label('Channel')->badge(),
            TextColumn::make('product.name')->label('Product')->searchable(),
            TextColumn::make('variant.color')->label('Colour')->placeholder('Standard'),
            TextColumn::make('variant.size')->label('Size')->placeholder('All selected sizes'),
            TextColumn::make('email')->placeholder('No email supplied'),
            TextColumn::make('status')->badge(),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('sales_channel')->options(['retail' => 'Retail', 'wholesale' => 'Wholesale']),
            SelectFilter::make('status')->options(['new' => 'New', 'contacted' => 'Contacted', 'resolved' => 'Resolved']),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListWishlistRequests::route('/'), 'edit' => EditWishlistRequest::route('/{record}/edit')];
    }
}
