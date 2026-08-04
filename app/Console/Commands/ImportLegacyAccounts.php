<?php

namespace App\Console\Commands;

use App\Models\CustomerAddress;
use App\Models\PriceTier;
use App\Models\ResellerProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

#[Signature('legacy:import-accounts {--dry-run : Validate the source and show counts without writing}')]
#[Description('Import legacy wholesale accounts without carrying forward insecure passwords or tokens')]
class ImportLegacyAccounts extends Command
{
    public function handle(): int
    {
        try {
            $legacy = DB::connection('legacy');
            $tables = ['igi_users', 'igi_user_details', 'igi_user_address', 'igi_wholesale_discount', 'igi_locations'];

            foreach ($tables as $table) {
                if (! $legacy->getSchemaBuilder()->hasTable($table)) {
                    $this->error("Missing legacy table: {$table}");

                    return self::FAILURE;
                }
            }

            $this->table(['Legacy record type', 'Rows'], [
                ['Price tiers', $legacy->table('igi_wholesale_discount')->count()],
                ['Wholesale accounts', $legacy->table('igi_users')->where('role', 5)->count()],
                ['Saved addresses', $legacy->table('igi_user_address')->count()],
            ]);

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            DB::transaction(function () use ($legacy): void {
                $tierIds = [];
                foreach ($legacy->table('igi_wholesale_discount')->orderBy('id')->get() as $source) {
                    $tier = PriceTier::updateOrCreate(['legacy_id' => $source->id], [
                        'name' => trim($source->name) ?: 'Legacy tier '.$source->id,
                        'discount_percentage' => max(0, min(100, (float) $source->discount)),
                        'is_active' => true,
                    ]);
                    $tierIds[(int) $source->id] = $tier->id;
                }

                $locations = $legacy->table('igi_locations')->pluck('name', 'id');
                $legacy->table('igi_users')
                    ->where('role', 5)
                    ->orderBy('id')
                    ->chunkById(100, function ($rows) use ($legacy, $locations, $tierIds): void {
                        foreach ($rows as $source) {
                            $details = $legacy->table('igi_user_details')
                                ->where('user_id', $source->id)
                                ->orderBy('id')
                                ->first();
                            $user = User::firstOrNew(['legacy_id' => $source->id]);
                            $originalEmail = trim((string) $source->email);

                            $user->fill([
                                'name' => $this->accountName($details?->name, $source->username, $originalEmail, (int) $source->id),
                                'legacy_username' => $this->nullableString($source->username),
                                'email' => $this->safeUniqueEmail($originalEmail, (int) $source->id, $user->exists ? $user->id : null),
                                'legacy_email' => $this->nullableString($originalEmail),
                                'account_type' => 'wholesale',
                                'approval_status' => $this->approvalStatus((int) $source->status),
                                'legacy_role_id' => (int) $source->role,
                                'price_tier_id' => $tierIds[(int) $source->discount_level] ?? null,
                                'must_reset_password' => true,
                            ]);

                            if (! $user->exists) {
                                $user->password = Str::random(64);
                                $user->created_at = $this->legacyDate($source->created);
                            }

                            $user->save();

                            if ($details) {
                                ResellerProfile::updateOrCreate(['user_id' => $user->id], [
                                    'company' => $this->nullableString($details->company),
                                    'alternate_email' => $this->nullableString($details->alternet_email),
                                    'phone' => $this->nullableString($details->telephone),
                                    'mobile' => $this->nullableString($details->mobile),
                                    'fax' => $this->nullableString($details->fax_no),
                                    'tax_number' => $this->nullableString($details->hst_no),
                                    'business_number' => $this->nullableString($details->business_no),
                                    'area' => $this->nullableString($details->area),
                                    'legacy_country_id' => $details->country ?: null,
                                    'legacy_province_value' => $this->nullableString($details->province),
                                    'profile_photo' => $this->nullableString($details->profile_photo),
                                ]);

                                if ($this->hasAddress($details)) {
                                    CustomerAddress::updateOrCreate([
                                        'user_id' => $user->id,
                                        'type' => 'primary',
                                        'legacy_id' => null,
                                    ], $this->addressData($details, $locations));
                                }
                            }

                            foreach ($legacy->table('igi_user_address')->where('user_id', $source->id)->orderBy('id')->get() as $address) {
                                CustomerAddress::updateOrCreate(['legacy_id' => $address->id], [
                                    'user_id' => $user->id,
                                    'type' => 'shipping',
                                    ...$this->addressData($address, $locations),
                                ]);
                            }
                        }
                    }, 'id');
            });

            $this->info(sprintf(
                'Imported %d wholesale accounts, %d price tiers, %d profiles, and %d addresses.',
                User::where('account_type', 'wholesale')->whereNotNull('legacy_id')->count(),
                PriceTier::count(),
                ResellerProfile::count(),
                CustomerAddress::count(),
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Account import failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function safeUniqueEmail(string $email, int $legacyId, ?int $currentUserId): string
    {
        $normalized = Str::lower(trim($email));
        $isValid = filter_var($normalized, FILTER_VALIDATE_EMAIL) !== false;
        $isTaken = $isValid && User::query()
            ->where('email', $normalized)
            ->when($currentUserId, fn ($query) => $query->whereKeyNot($currentUserId))
            ->exists();

        return $isValid && ! $isTaken
            ? $normalized
            : "legacy-{$legacyId}@invalid.igicanada.local";
    }

    private function accountName(?string $name, ?string $username, string $email, int $legacyId): string
    {
        return $this->nullableString($name)
            ?? $this->nullableString($username)
            ?? $this->nullableString($email)
            ?? "Legacy Wholesale Account {$legacyId}";
    }

    private function approvalStatus(int $status): string
    {
        return match ($status) {
            1 => 'approved',
            0 => 'pending',
            default => 'suspended',
        };
    }

    private function legacyDate(?string $value): ?Carbon
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function hasAddress(object $source): bool
    {
        return collect(['address', 'city', 'province', 'country', 'zipcode', 'telephone', 'mobile'])
            ->contains(fn (string $field) => $this->nullableString($source->{$field} ?? null) !== null);
    }

    private function addressData(object $source, $locations): array
    {
        $countryId = (int) ($source->country ?? 0);
        $provinceValue = $this->nullableString($source->province ?? null);
        $provinceId = ctype_digit((string) $provinceValue) ? (int) $provinceValue : null;

        return [
            'address_line1' => $this->nullableString($source->address ?? null),
            'city' => $this->nullableString($source->city ?? null),
            'province' => $provinceId ? ($locations[$provinceId] ?? $provinceValue) : $provinceValue,
            'country' => $countryId ? ($locations[$countryId] ?? null) : null,
            'postal_code' => $this->nullableString($source->zipcode ?? null),
            'telephone' => $this->nullableString($source->telephone ?? null),
            'mobile' => $this->nullableString($source->mobile ?? null),
            'legacy_country_id' => $countryId ?: null,
        ];
    }
}
