<?php

namespace App\Console\Commands;

use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

#[Signature('legacy:import-orders {--dry-run : Validate source counts without writing}')]
#[Description('Import historical order masters and immutable line snapshots from Yii')]
class ImportLegacyOrders extends Command
{
    private int $archivedAccounts = 0;

    private int $missingLines = 0;

    public function handle(): int
    {
        try {
            $legacy = DB::connection('legacy');
            $tables = ['igi_order_place', 'igi_orders', 'igi_posts', 'igi_post_variation', 'igi_user_address'];
            foreach ($tables as $table) {
                if (! $legacy->getSchemaBuilder()->hasTable($table)) {
                    $this->error("Missing legacy table: {$table}");

                    return self::FAILURE;
                }
            }

            $this->table(['Legacy table', 'Rows'], collect($tables)->map(
                fn (string $table) => [$table, $legacy->table($table)->count()]
            )->all());

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            $legacy->table('igi_order_place')->orderBy('id')->chunkById(50, function ($masters) use ($legacy): void {
                foreach ($masters as $master) {
                    $this->importOrder($legacy, $master);
                }
            }, 'id');

            $this->info(sprintf(
                'Imported %d historical orders and %d line snapshots. Created %d suspended wholesale accounts; %d referenced lines were missing.',
                Order::whereNotNull('legacy_id')->count(),
                DB::table('order_items')->whereNotNull('legacy_id')->count(),
                $this->archivedAccounts,
                $this->missingLines,
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Order import failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function importOrder($legacy, object $master): void
    {
        $user = $this->resolveUser($legacy, $master);

        $lineIds = collect(explode(',', (string) $master->order_ids))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();
        $sourceLines = $legacy->table('igi_orders')->whereIn('id', $lineIds)->orderBy('id')->get();
        $this->missingLines += max(0, $lineIds->count() - $sourceLines->count());
        $computedSubtotal = $sourceLines->sum(fn ($line) => (float) $line->price * (int) $line->qty);
        $address = CustomerAddress::where('user_id', $user->id)->where('legacy_id', $master->shipping_id)->first();
        $sourceAddress = $legacy->table('igi_user_address')->where('id', $master->shipping_id)->first();
        $placedAt = $this->date($master->ondate) ?? now();

        DB::transaction(function () use ($legacy, $master, $user, $sourceLines, $computedSubtotal, $address, $sourceAddress, $placedAt): void {
            $order = Order::updateOrCreate(['legacy_id' => $master->id], [
                'legacy_shipping_id' => $master->shipping_id ?: null,
                'legacy_order_ids' => $master->order_ids ?: null,
                'order_number' => $this->orderNumber($master),
                'user_id' => $user->id,
                'customer_address_id' => $address?->id,
                'shipping_address' => $this->addressSnapshot($user, $address, $sourceAddress),
                'currency' => $this->currency($master->currency),
                'status' => $this->orderStatus((int) $master->status),
                'payment_status' => $master->type === 'paid' ? 'paid' : 'unpaid',
                'payment_method' => $master->type === 'invoice' ? 'invoice' : 'legacy',
                'shipping_method' => $master->shipping ?: null,
                'shipping_service' => $master->service ?: null,
                'payment_reference' => $master->token ?: null,
                'subtotal' => (float) $master->product_price > 0 ? $master->product_price : $computedSubtotal,
                'discount_total' => max(0, (float) $master->discount),
                'shipping_total' => max(0, (float) $master->shipping_price),
                'tax_total' => max(0, (float) $master->tax),
                'total' => max(0, (float) $master->price),
                'placed_at' => $placedAt,
                'paid_at' => $master->type === 'paid' ? $placedAt : null,
                'shipped_at' => (int) $master->status >= 3 && (int) $master->status <= 4 ? $placedAt : null,
                'tracking_number' => $master->track_no ?: null,
                'created_at' => $placedAt,
            ]);

            $order->items()->delete();
            foreach ($sourceLines as $line) {
                $sourceProduct = $legacy->table('igi_posts')->where('id', $line->product_id)->first();
                $sourceVariant = $legacy->table('igi_post_variation')->where('id', $line->varaition_id)->first();
                $targetProduct = Product::where('legacy_id', $line->product_id)->first();
                $targetVariant = ProductVariant::where('legacy_id', $line->varaition_id)->first();
                $unitPrice = (float) $line->price;

                $order->items()->create([
                    'product_variant_id' => $targetVariant?->id,
                    'legacy_id' => $line->id,
                    'legacy_product_id' => $line->product_id ?: null,
                    'legacy_variant_id' => $line->varaition_id ?: null,
                    'product_name' => $sourceProduct?->title ?: $targetProduct?->name ?: 'Legacy product '.$line->product_id,
                    'sku' => $sourceProduct?->product_code ?: $targetProduct?->sku,
                    'option' => $line->size ?: collect([$sourceVariant?->color, $sourceVariant?->size])->filter()->join(' · ') ?: null,
                    'quantity' => max(0, (int) $line->qty),
                    'unit_price' => $unitPrice,
                    'line_total' => round($unitPrice * (int) $line->qty, 2),
                    'created_at' => $this->date($line->createdon) ?? $placedAt,
                ]);
            }
        });
    }

    private function resolveUser($legacy, object $master): User
    {
        $user = User::where('legacy_id', $master->user_id)->first();
        if ($user) {
            return $user;
        }

        $details = $legacy->getSchemaBuilder()->hasTable('igi_user_details')
            ? $legacy->table('igi_user_details')->where('user_id', $master->user_id)->orderBy('id')->first()
            : null;
        $user = User::create([
            'legacy_id' => $master->user_id,
            'name' => trim((string) ($details?->name ?? '')) ?: 'Archived wholesale account '.$master->user_id,
            'email' => "archived-{$master->user_id}@invalid.igicanada.local",
            'password' => Str::random(64),
            'account_type' => 'wholesale',
            'approval_status' => 'suspended',
            'must_reset_password' => true,
            'created_at' => $this->date($master->ondate),
        ]);

        if ($details) {
            $user->resellerProfile()->create([
                'company' => $details->company ?: null,
                'alternate_email' => $details->alternet_email ?: null,
                'phone' => $details->telephone ?: null,
                'mobile' => $details->mobile ?: null,
                'fax' => $details->fax_no ?: null,
                'tax_number' => $details->hst_no ?: null,
                'business_number' => $details->business_no ?: null,
                'area' => $details->area ?: null,
                'legacy_country_id' => $details->country ?: null,
                'legacy_province_value' => $details->province ?: null,
                'profile_photo' => $details->profile_photo ?: null,
            ]);
        }

        $this->archivedAccounts++;

        return $user;
    }

    private function orderNumber(object $master): string
    {
        $base = 'LEGACY-'.($master->order_no ?: $master->id);
        $owner = Order::where('order_number', $base)->first();

        return ! $owner || (int) $owner->legacy_id === (int) $master->id
            ? $base
            : $base.'-'.$master->id;
    }

    private function orderStatus(int $status): string
    {
        return match ($status) {
            0 => 'awaiting_quote',
            1, 2 => 'processing',
            3 => 'shipped',
            4 => 'completed',
            5, 6 => 'cancelled',
            default => 'awaiting_quote',
        };
    }

    private function currency(?string $currency): string
    {
        $currency = strtoupper(trim((string) $currency));

        return preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'CAD';
    }

    private function addressSnapshot(User $user, ?CustomerAddress $address, ?object $source): array
    {
        return [
            'name' => $user->name,
            'company' => $user->resellerProfile?->company,
            'address' => $address?->address_line1 ?? $source?->address,
            'city' => $address?->city ?? $source?->city,
            'province' => $address?->province ?? $source?->province,
            'country' => $address?->country,
            'postal_code' => $address?->postal_code ?? $source?->zipcode,
            'phone' => $address?->telephone ?? $source?->telephone,
        ];
    }

    private function date(?string $value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
