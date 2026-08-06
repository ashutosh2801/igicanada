<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function getEloquentQuery(): Builder
    {
        return AdminStorefront::apply(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order_number')->disabled(),
            TextInput::make('sales_channel')
                ->label('Sales channel')
                ->disabled()
                ->visible(fn (): bool => AdminStorefront::current() === 'all'),
            TextInput::make('user.name')->label('Customer')->disabled(),
            TextInput::make('customer_email')->label('Customer email')->disabled(),
            Select::make('status')->options([
                'awaiting_quote' => 'Awaiting quote',
                'quoted' => 'Quoted',
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ])->required(),
            Select::make('payment_status')->options([
                'unpaid' => 'Unpaid',
                'pending' => 'Pending',
                'paid' => 'Paid',
                'refunded' => 'Refunded',
            ])->required(),
            TextInput::make('subtotal')->numeric()->prefix('$')->disabled(),
            TextInput::make('discount_total')->numeric()->prefix('$')->required(),
            TextInput::make('shipping_method')->disabled(),
            TextInput::make('shipping_service')->disabled(),
            TextInput::make('shipping_total')->numeric()->prefix('$')->required(),
            TextInput::make('tax_total')->numeric()->prefix('$')->required(),
            TextInput::make('total')->numeric()->prefix('$')->disabled(),
            TextInput::make('tracking_number')->maxLength(255),
            Textarea::make('customer_notes')->disabled()->columnSpanFull(),
            Repeater::make('items')
                ->relationship()
                ->schema([
                    TextInput::make('product_name')->disabled(),
                    TextInput::make('option')->disabled(),
                    TextInput::make('quantity')->disabled(),
                    TextInput::make('unit_price')->prefix('$')->disabled(),
                    TextInput::make('line_total')->prefix('$')->disabled(),
                ])
                ->disabled()
                ->columnSpanFull(),
            Repeater::make('payments')
                ->relationship()
                ->schema([
                    TextInput::make('provider')->disabled(),
                    TextInput::make('provider_order_id')->label('Provider order')->disabled(),
                    TextInput::make('provider_capture_id')->label('Capture')->disabled(),
                    TextInput::make('amount')->prefix('$')->disabled(),
                    TextInput::make('currency')->disabled(),
                    TextInput::make('status')->disabled(),
                ])
                ->disabled()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
