<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Notifications\OrderStatusChanged;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Throwable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invoice')
                ->label('Download PDF')
                ->url(fn () => route('orders.invoice', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['discount_total'] = max(0, (float) ($data['discount_total'] ?? 0));
        $data['shipping_total'] = max(0, (float) ($data['shipping_total'] ?? 0));
        $data['tax_total'] = max(0, (float) ($data['tax_total'] ?? 0));
        $data['total'] = max(0, (float) $this->record->subtotal - $data['discount_total'] + $data['shipping_total'] + $data['tax_total']);

        if (($data['status'] ?? null) === 'quoted' && ! $this->record->quoted_at) {
            $data['quoted_at'] = now();
        }
        if (($data['payment_status'] ?? null) === 'paid' && ! $this->record->paid_at) {
            $data['paid_at'] = now();
        }
        if (($data['status'] ?? null) === 'shipped' && ! $this->record->shipped_at) {
            $data['shipped_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->sales_channel === 'retail'
            && $this->record->status === 'cancelled'
            && ! $this->record->inventory_released_at) {
            DB::transaction(function (): void {
                $order = $this->record->newQuery()->lockForUpdate()->findOrFail($this->record->id);
                if ($order->inventory_released_at) {
                    return;
                }
                $order->load('items');
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        $item->variant()->increment('stock_quantity', $item->quantity);
                    }
                }
                $order->update(['inventory_released_at' => now()]);
                $this->record->refresh();
            });
        }

        if (! $this->record->wasChanged(['status', 'payment_status', 'tracking_number', 'total'])) {
            return;
        }

        $email = $this->record->user?->email;
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || str_ends_with($email, '@invalid.igicanada.local')) {
            return;
        }

        try {
            $this->record->user?->notify(new OrderStatusChanged($this->record));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
