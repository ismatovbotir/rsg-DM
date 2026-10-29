<?php

namespace App\Livewire\Admin\Warehouses;

use App\Livewire\Admin\CrudForm;
use App\Models\ItemStock;
use App\Models\Warehouse;

/** Warehouse / shop card (view only, data comes from Adines). */
class Form extends CrudForm
{
    protected function model(): string
    {
        return Warehouse::class;
    }

    protected function listTitle(): string
    {
        return 'Omborlar';
    }

    protected function indexRoute(): string
    {
        return 'admin.warehouses.index';
    }

    protected function fields(): array
    {
        return [
            ['Nomi', 'name'],
            ['Kod (Adines)', 'code'],
            ['Narx turi', fn ($w) => $w->priceType?->name],
        ];
    }

    protected function figures($record): array
    {
        return [
            ['Kod', $record->code ?: '—'],
            ['Narx turi', $record->priceType?->name ?? '—'],
            ['Qoldiq qatorlari', ItemStock::where('warehouse_id', $record->id)->count()],
            ['Hujjatlar', \App\Models\Document::where('warehouse_id', $record->id)->count()],
        ];
    }
}
