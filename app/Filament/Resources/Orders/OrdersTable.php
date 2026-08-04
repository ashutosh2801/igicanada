<?php

namespace App\Filament\Resources\Orders;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Wholesale account')->searchable(),
                TextColumn::make('user.resellerProfile.company')->label('Company')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('total')->money('CAD')->sortable(),
                TextColumn::make('placed_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'awaiting_quote' => 'Awaiting quote',
                    'quoted' => 'Quoted',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('payment_status')->options([
                    'unpaid' => 'Unpaid',
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'refunded' => 'Refunded',
                ]),
            ])
            ->recordActions([EditAction::make()]);
    }
}
