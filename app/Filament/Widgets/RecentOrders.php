<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\AdminStorefront;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent orders')
            ->description('Latest orders for '.AdminStorefront::label())
            ->query(AdminStorefront::apply(Order::query())->with('user')->latest('placed_at')->limit(5))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('sales_channel')
                    ->label('Channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->title())
                    ->color(fn (string $state): string => $state === 'retail' ? 'warning' : 'info')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->limit(22),
                TextColumn::make('total')
                    ->money('CAD'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                TextColumn::make('placed_at')
                    ->label('Placed')
                    ->since()
                    ->tooltip(fn (Order $record): string => $record->placed_at->format('M j, Y g:i A')),
            ])
            ->headerActions([
                Action::make('allOrders')
                    ->label('View all orders')
                    ->icon(Heroicon::ArrowRight)
                    ->url(OrderResource::getUrl('index')),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
