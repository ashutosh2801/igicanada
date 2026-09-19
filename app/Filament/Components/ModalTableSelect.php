<?php

namespace App\Filament\Components;

use Filament\Forms\Components\ModalTableSelect as BaseModalTableSelect;
use Filament\Forms\Components\TableSelect as FilamentTableSelect;

class ModalTableSelect extends BaseModalTableSelect
{
    public function getTableSelect(): TableSelect
    {
        $select = TableSelect::make('selection')
            ->label($this->getLabel())
            ->hiddenLabel()
            ->tableConfiguration($this->getTableConfiguration())
            ->relationshipName($this->getRelationshipName())
            ->multiple($this->isMultiple())
            ->maxItems($this->getMaxItems())
            ->tableArguments($this->getTableArguments());

        if ($this->modifyTableSelectUsing) {
            $select = $this->evaluate(
                $this->modifyTableSelectUsing,
                namedInjections: [
                    'select' => $select,
                    'tableSelect' => $select,
                ],
                typedInjections: [
                    TableSelect::class => $select,
                    FilamentTableSelect::class => $select,
                ],
            ) ?? $select;
        }

        return $select;
    }
}