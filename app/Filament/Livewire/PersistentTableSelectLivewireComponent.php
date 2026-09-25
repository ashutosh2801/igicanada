<?php

namespace App\Filament\Livewire;

use Filament\Forms\Components\TableSelect\Livewire\TableSelectLivewireComponent;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class PersistentTableSelectLivewireComponent extends TableSelectLivewireComponent
{
    /**
     * @return array<int>
     */
    public function selectedMediaAssetIds(): array
    {
        return collect(Arr::wrap($this->state))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter()
            ->values()
            ->all();
    }

    public function removeSelectedImage(int $id): void
    {
        if (is_array($this->state)) {
            $this->state = collect($this->state)
                ->reject(fn (mixed $value): bool => (int) $value === $id)
                ->values()
                ->all();

            return;
        }

        if ((int) $this->state === $id) {
            $this->state = null;
        }
    }

    /**
     * Persists a drag-and-drop reordering of the selected images so the
     * selection order (and therefore their display position) follows the
     * order set in the picker.
     *
     * @param  array<int|string>  $orderedIds
     */
    public function reorderSelectedImages(array $orderedIds): void
    {
        if (! is_array($this->state)) {
            return;
        }

        $this->state = collect($orderedIds)
            ->map(fn (mixed $value): int => (int) $value)
            ->filter()
            ->values()
            ->all();
    }

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