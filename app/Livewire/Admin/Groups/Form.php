<?php

namespace App\Livewire\Admin\Groups;

use App\Livewire\AdminPage;
use App\Models\Group;
use App\Support\CheckerDesign;
use Livewire\Attributes\Locked;

/** One item group. Name and place in the tree belong to Adines; here the price checker colour scheme is chosen. */
class Form extends AdminPage
{
    #[Locked]
    public int $recordId;

    /** '' = the colours of the price checker design (default), else a CheckerDesign::PALETTES key */
    public string $color_scheme = '';

    public function mount(int $record): void
    {
        $group = Group::findOrFail($record);
        $this->recordId = $group->id;
        $this->color_scheme = (string) $group->color_scheme;
    }

    public function choose(string $key): void
    {
        $this->color_scheme = $key === '' || isset(CheckerDesign::PALETTES[$key]) ? $key : $this->color_scheme;
    }

    public function save()
    {
        $this->validate(['color_scheme' => ['nullable', 'in:'.implode(',', array_keys(CheckerDesign::PALETTES))]], [], ['color_scheme' => 'Ranglar sxemasi']);
        Group::findOrFail($this->recordId)->update(['color_scheme' => $this->color_scheme ?: null]);
        session()->flash('status', 'Guruh saqlandi.');

        return $this->redirect(route('admin.groups.index'));
    }

    public function render()
    {
        $group = Group::with('parent.parent.parent')->withCount('items')->findOrFail($this->recordId);

        return $this->page('livewire.admin.groups.form', [
            'group' => $group,
            'inherited' => $group->parent?->effectiveColorScheme(),
        ]);
    }
}
