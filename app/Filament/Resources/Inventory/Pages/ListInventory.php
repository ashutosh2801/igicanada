<?php

namespace App\Filament\Resources\Inventory\Pages;

use App\Filament\Resources\Inventory\InventoryResource;
use Filament\Resources\Pages\ListRecords;

class ListInventory extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected ?string $subheading = 'Type new stock for any variant and click Save stock, or select variants and use the bulk actions to set, add or remove stock together.';

    /** @var array<int|string, mixed> */
    public array $pendingStockUpdates = [];
}
