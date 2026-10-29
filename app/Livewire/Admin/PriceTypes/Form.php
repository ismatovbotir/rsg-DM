<?php

namespace App\Livewire\Admin\PriceTypes;

use App\Livewire\Admin\CrudForm;
use App\Models\ItemPrice;
use App\Models\PriceType;

/** Price type card (view only, data comes from Adines). */
class Form extends CrudForm
{
    protected function model(): string
    {
        return PriceType::class;
    }

    protected function listTitle(): string
    {
        return 'Narx turlari';
    }

    protected function indexRoute(): string
    {
        return 'admin.pricetypes.index';
    }

    protected function fields(): array
    {
        return [['Nomi', 'name'], ['Kod (Adines)', 'code']];
    }

    protected function figures($record): array
    {
        return [['Kod', $record->code ?: '—'], ['Narxlar soni', ItemPrice::where('price_type_id', $record->id)->count()]];
    }
}
