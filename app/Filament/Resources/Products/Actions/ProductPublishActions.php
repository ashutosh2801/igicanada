<?php

namespace App\Filament\Resources\Products\Actions;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ProductPublishActions
{
    public static function toggleRowAction(): Action
    {
        return self::makeToggleAction();
    }

    public static function togglePageAction(Product $record): Action
    {
        return self::makeToggleAction()->record($record);
    }

    public static function bulkUnpublishAction(): BulkAction
    {
        return BulkAction::make('unpublishFromAllWebsites')
            ->label('Unpublish from all websites')
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Unpublish selected products?')
            ->modalDescription('Selected products will be hidden instantly on BOTH the wholesale and retail websites. Prices, stock and images stay untouched, so it is perfect for a short break like holidays. Publish them again whenever you are back.')
            ->modalSubmitActionLabel('Unpublish selected products')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $count = $records->filter(function (Product $product): bool {
                    if (! $product->is_active) {
                        return false;
                    }

                    $product->is_active = false;
                    $product->save();

                    return true;
                })->count();

                Notification::make()
                    ->title($count === 1 ? '1 product unpublished' : "{$count} products unpublished")
                    ->body('Hidden from the wholesale and retail websites. Publish them again whenever you are back.')
                    ->warning()
                    ->send();
            });
    }

    public static function bulkPublishAction(): BulkAction
    {
        return BulkAction::make('publishToAllWebsites')
            ->label('Publish to all websites')
            ->icon('heroicon-o-eye')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Publish selected products?')
            ->modalDescription('Selected products will go live again on every website they are assigned to. Inactive products become visible on the storefronts immediately.')
            ->modalSubmitActionLabel('Publish selected products')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $count = $records->filter(function (Product $product): bool {
                    if ($product->is_active) {
                        return false;
                    }

                    $product->is_active = true;
                    $product->save();

                    return true;
                })->count();

                Notification::make()
                    ->title($count === 1 ? '1 product published' : "{$count} products published")
                    ->body('Live on the wholesale and retail websites again.')
                    ->success()
                    ->send();
            });
    }

    private static function makeToggleAction(): Action
    {
        return Action::make('togglePublish')
            ->label(fn (Product $record): string => $record->is_active ? 'Unpublish' : 'Publish')
            ->icon(fn (Product $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
            ->color(fn (Product $record): string => $record->is_active ? 'warning' : 'success')
            ->requiresConfirmation(fn (Product $record): bool => (bool) $record->is_active)
            ->modalHeading(fn (Product $record): string => $record->is_active
                ? 'Unpublish from all websites?'
                : 'Publish to all websites?')
            ->modalDescription(fn (Product $record): string => $record->is_active
                ? '"'.$record->name.'" will be hidden instantly on BOTH the wholesale and retail websites. Prices, stock and images stay untouched, so it is perfect for a short break like holidays. You can publish it again at any time.'
                : '"'.$record->name.'" will go live again on every website it is assigned to.')
            ->modalSubmitActionLabel(fn (Product $record): string => $record->is_active ? 'Unpublish' : 'Publish')
            ->action(function (Product $record): void {
                $product = $record;
                $wasActive = (bool) $product->is_active;

                $product->is_active = ! $wasActive;
                $product->save();

                $notification = Notification::make()
                    ->title($wasActive ? 'Product unpublished' : 'Product published')
                    ->body($product->name.' is now '.($wasActive ? 'hidden from' : 'live on').' the wholesale and retail websites.');

                if ($wasActive) {
                    $notification->warning();
                } else {
                    $notification->success();
                }

                $notification->send();
            });
    }
}