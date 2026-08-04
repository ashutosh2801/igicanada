<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['approval_status'] ?? null) === 'approved' && ! $this->record->approved_at) {
            $data['approved_at'] = now();
        }

        if (($data['approval_status'] ?? null) !== 'approved') {
            $data['approved_at'] = null;
        }

        return $data;
    }
}
