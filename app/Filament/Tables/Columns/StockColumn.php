<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\TextInputColumn;

class StockColumn extends TextInputColumn
{
    public function toEmbeddedHtml(): string
    {
        return view('filament.tables.columns.stock', [
            'state' => $this->getState(),
            'recordKey' => $this->getRecordKey(),
            'name' => $this->getName(),
        ])->render();
    }
}
