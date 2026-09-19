<?php

namespace App\Filament\Components;

use App\Filament\Livewire\PersistentTableSelectLivewireComponent;
use Filament\Forms\Components\TableSelect as BaseTableSelect;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Livewire\Livewire;

class TableSelect extends BaseTableSelect
{
    public function toEmbeddedHtml(): string
    {
        $extraAttributes = $this->getExtraAttributes();
        $id = $this->getId();
        $statePath = $this->getStatePath();

        $properties = [
            'isDisabled' => $this->isDisabled(),
            'maxSelectableRecords' => $this->getMaxItems(),
            'model' => $this->getModel(),
            'record' => $this->getRecord(),
            'relationshipName' => $this->getRelationshipName(),
            'shouldIgnoreRelatedRecords' => $this->shouldIgnoreRelatedRecords(),
            'tableConfiguration' => base64_encode($this->getTableConfiguration()),
            'tableArguments' => $this->getTableArguments(),
            $this->applyStateBindingModifiers('wire:model') => $statePath,
        ];

        $livewireHtml = Livewire::mount(PersistentTableSelectLivewireComponent::class, $properties, $this->getLivewireKey());

        $attributes = (new FilamentComponentAttributeBag)
            ->merge([
                'aria-labelledby' => "{$id}-label",
                'id' => $id,
                'role' => 'group',
            ], escape: false)
            ->merge($extraAttributes, escape: false);

        return $this->wrapEmbeddedHtml('<div ' . $attributes->toHtml() . '>' . $livewireHtml . '</div>', labelTag: 'div');
    }
}