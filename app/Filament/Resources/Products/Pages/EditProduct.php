<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Actions\ProductPublishActions;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected bool $closeAfterSave = false;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('saveAndClose')
                ->label('Save and close')
                ->action(function (): void {
                    $this->saveAndClose();
                }),
            $this->getCancelFormAction(),
        ];
    }

    public function saveAndClose(): void
    {
        $this->closeAfterSave = true;
        $this->save();
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->closeAfterSave
            ? ProductResource::getUrl('index')
            : parent::getRedirectUrl();
    }

    protected function getHeaderActions(): array
    {
        return [
            ProductPublishActions::togglePageAction($this->record),
            DeleteAction::make(),
        ];
    }
}
