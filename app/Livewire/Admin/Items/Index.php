<?php

namespace App\Livewire\Admin\Items;

use App\Livewire\AdminPage;
use App\Models\DocumentLine;
use App\Models\Item;
use Livewire\WithPagination;

class Index extends AdminPage
{
    use WithPagination;

    public string $q = '';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return $this->page('livewire.admin.items.index', [
            'items' => Item::with('group')->search($this->q)->orderBy('name')->simplePaginate(30),
        ]);
    }
}
