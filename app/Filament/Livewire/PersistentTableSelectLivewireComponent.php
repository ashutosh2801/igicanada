<?php

namespace App\Filament\Livewire;

use Filament\Forms\Components\TableSelect\Livewire\TableSelectLivewireComponent;
use Filament\Tables\Table;

class PersistentTableSelectLivewireComponent extends TableSelectLivewireComponent
{
    public function mount(): void
    {
        $this->paginators['page'] = (int) session()->get($this->getPersistenceKey() . '.page', 1);

        $this->mountInteractsWithTable();
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->persistSearchInSession()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->persistColumnSearchesInSession();
    }

    public function updatedPage($page, $pageName = null): void
    {
        if ($pageName === null || $pageName === $this->getTablePaginationPageName()) {
            session()->put($this->getPersistenceKey() . '.page', (int) ($page ?? 1));
        }
    }

    protected function getPersistenceKey(): string
    {
        $tableConfiguration = base64_decode($this->tableConfiguration);

        return 'table_select.' . md5($tableConfiguration);
    }
}