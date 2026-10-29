<?php

namespace App\Livewire\Admin\Counterparties;

use App\Livewire\Admin\CrudIndex;
use App\Models\Counterparty;

class Index extends CrudIndex
{
    protected function model(): string
    {
        return Counterparty::class;
    }

    protected function title(): string
    {
        return 'Kontragentlar';
    }

    protected function columns(): array
    {
        return [['Nomi', 'name'], ['STIR', 'stir']];
    }

    protected function routes(): array
    {
        return ['edit' => 'admin.counterparties.edit', 'empty' => 'Kontragentlar yo‘q.'];
    }

    protected function searchable(): ?string
    {
        return 'Nomi yoki STIR';
    }
}
