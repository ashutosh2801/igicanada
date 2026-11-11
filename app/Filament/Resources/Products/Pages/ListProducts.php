<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Services\ProductDuplicator;
use App\Support\AdminStorefront;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Set;

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
            $this->copyProductAction(),
            CreateAction::make(),
        ];
    }

    private function copyProductAction(): Action
    {
        return Action::make('copyProduct')
            ->label('Copy product')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->modalHeading('Copy a product')
            ->modalDescription('Creates a new product with the same details, prices, variants, images and categories. The copy is UNPUBLISHED and every variant has 0 quantity, so nothing goes live until you review it.')
            ->modalSubmitActionLabel('Create copy')
            ->schema([
                Select::make('product_id')
                    ->label('Product to copy')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => ProductResource::getEloquentQuery()
                        ->where(fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                    ->live()
                    ->afterStateUpdated(fn ($state, Set $set) => $set(
                        'name',
                        ($name = Product::find($state)?->name) ? $name.' (Copy)' : null,
                    )),
                TextInput::make('name')
                    ->label('Name of the copy')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data, ProductDuplicator $duplicator): void {
                $copy = $duplicator->duplicate(Product::findOrFail($data['product_id']), $data['name']);

                Notification::make()
                    ->title('Product copied')
                    ->body("\"{$copy->name}\" was created as unpublished with 0 quantity. Review it, then publish when ready.")
                    ->success()
                    ->send();

                $this->redirect(ProductResource::getUrl('edit', ['record' => $copy]));
            });
    }
}
