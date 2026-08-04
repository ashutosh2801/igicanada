<?php

namespace App\Filament\Resources\ContactEnquiries\Pages;

use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use Filament\Resources\Pages\EditRecord;

class EditContactEnquiry extends EditRecord
{
    protected static string $resource = ContactEnquiryResource::class;
}
