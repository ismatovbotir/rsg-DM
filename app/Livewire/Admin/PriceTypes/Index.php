<?php

namespace App\Livewire\Admin\PriceTypes;

use App\Livewire\Admin\CrudIndex;
use App\Models\PriceType;

class Index extends CrudIndex
{
    protected function model(): string
    {
        return PriceType::class;
    }

    protected function title(): string
    {
        return 'Narx turlari';
    }

    protected function columns(): array
    {
        return [['Kod (Adines)', 'code'], ['Nomi', 'name']];
    }

    protected function routes(): array
    {
        return ['edit' => 'admin.pricetypes.edit', 'empty' => 'Narx turlari yo‘q.'];
    }
}
