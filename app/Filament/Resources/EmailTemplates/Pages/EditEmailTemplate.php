<?php

namespace App\Filament\Resources\EmailTemplates\Pages;

use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Support\AdminStorefront;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['sales_channel'] ?? null) === null) {
            $data['sales_channel'] = AdminStorefront::current() === 'retail' ? 'retail' : 'wholesale';
        }

        return $data;
    }
}
