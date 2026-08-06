<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use App\Models\ContactEnquiry;
use App\Support\AdminStorefront;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentEnquiries extends TableWidget
{
    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent enquiries')
            ->description('Latest messages for '.AdminStorefront::label())
            ->query(AdminStorefront::apply(ContactEnquiry::query())->latest()->limit(5))
            ->columns([
                TextColumn::make('sales_channel')
                    ->label('Channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->title())
                    ->color(fn (string $state): string => $state === 'retail' ? 'warning' : 'info')
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                TextColumn::make('name')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('subject')
                    ->placeholder('General enquiry')
                    ->limit(28),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title())
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'warning',
                        'resolved' => 'success',
                        'spam' => 'danger',
                        default => 'info',
                    }),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->tooltip(fn (ContactEnquiry $record): string => $record->created_at->format('M j, Y g:i A')),
            ])
            ->headerActions([
                Action::make('allEnquiries')
                    ->label('View all enquiries')
                    ->icon(Heroicon::ArrowRight)
                    ->url(ContactEnquiryResource::getUrl('index')),
            ])
            ->recordUrl(fn (ContactEnquiry $record): string => ContactEnquiryResource::getUrl('edit', ['record' => $record]))
            ->paginated(false);
    }
}
