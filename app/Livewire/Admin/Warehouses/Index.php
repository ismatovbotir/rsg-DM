<?php

namespace App\Livewire\Admin\Warehouses;

use App\Livewire\Admin\CrudIndex;
use App\Models\Warehouse;

class Index extends CrudIndex
{
    protected function model(): string
    {
        return Warehouse::class;
    }

    protected function title(): string
    {
        return 'Omborlar';
    }

    protected function columns(): array
    {
        return [['Kod (Adines)', 'code'], ['Nomi', 'name']];
    }

    protected function routes(): array
    {
        return ['create' => 'admin.warehouses.create', 'edit' => 'admin.warehouses.edit', 'newLabel' => '+ Yangi ombor', 'empty' => 'Omborlar yo‘q.'];
    }
}
