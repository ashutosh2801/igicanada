<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Support\AdminStorefront;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getTableColumnsSessionKey(): string
    {
        // Reset the old column preferences once so the curated defaults apply to existing admins.
        return parent::getTableColumnsSessionKey().'_v3_'.AdminStorefront::current();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
