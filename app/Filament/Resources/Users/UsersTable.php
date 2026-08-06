<?php

namespace App\Filament\Resources\Users;

use App\Models\User;
use App\Support\AdminStorefront;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(fn ($query) => $query->with(['resellerProfile', 'priceTier']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('account_type')
                    ->label('Channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->title())
                    ->color(fn (string $state): string => $state === 'retail' ? 'warning' : 'info')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('resellerProfile.company')
                    ->label('Company')
                    ->searchable()
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextColumn::make('email')->searchable(),
                TextColumn::make('email_verified_at')->label('Email verified')->dateTime()->placeholder('Not verified'),
                TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        default => 'danger',
                    })
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextColumn::make('priceTier.name')
                    ->label('Price tier')
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('account_type')->label('Channel')->options([
                    'wholesale' => 'Wholesale',
                    'retail' => 'Retail',
                ])->visible(fn (): bool => AdminStorefront::current() === 'all'),
                SelectFilter::make('approval_status')->options([
                    'pending' => 'Pending review',
                    'approved' => 'Approved',
                    'suspended' => 'Suspended',
                ])->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->disabled(fn (User $record) => ! $record->hasVerifiedEmail())
                    ->tooltip(fn (User $record) => $record->hasVerifiedEmail() ? null : 'The business must verify its email before approval.')
                    ->visible(fn (User $record) => $record->account_type === 'wholesale' && $record->approval_status !== 'approved')
                    ->action(fn (User $record) => $record->update([
                        'approval_status' => 'approved',
                        'approved_at' => now(),
                    ])),
                Action::make('suspend')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->approval_status === 'approved' && $record->account_type !== 'admin')
                    ->action(fn (User $record) => $record->update([
                        'approval_status' => 'suspended',
                        'approved_at' => null,
                    ])),
                EditAction::make(),
            ]);
    }
}
