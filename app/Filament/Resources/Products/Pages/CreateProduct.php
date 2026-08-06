<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Support\AdminStorefront;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (AdminStorefront::current() === 'retail' && blank($data['name'] ?? null)) {
            $data['name'] = $data['retail_name'];
        }

        return $data;
    }
}
