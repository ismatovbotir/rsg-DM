<?php

namespace App\Livewire\Admin\Groups;

use App\Livewire\AdminPage;
use App\Models\Group;
use Livewire\WithPagination;

/** Item groups (the tree comes from Adines): the list with the price checker colour scheme of each. */
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
        $groups = Group::with('parent.parent.parent')->withCount('items')
            ->when(filled($this->q), fn ($q) => $q->where('name', 'like', '%'.trim($this->q).'%'))
            ->orderBy('name')->simplePaginate(40);

        return $this->page('livewire.admin.groups.index', ['rows' => $groups]);
    }
}
