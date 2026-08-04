<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['visibility'] = 'wholesale';

        return $data;
    }
}
