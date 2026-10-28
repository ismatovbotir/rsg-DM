<?php

namespace App\Livewire\Admin\Labels;

use App\Livewire\AdminPage;
use App\Models\LabelTemplate;

class Index extends AdminPage
{
    public function makeDefault(int $id): void
    {
        $label = LabelTemplate::findOrFail($id);
        $label->makeDefault();
        $this->ok("«{$label->name}» narx yorliqlari uchun tanlandi.");
    }

    public function delete(int $id): void
    {
        LabelTemplate::findOrFail($id)->delete();
        $this->ok('Dizayn o‘chirildi.');
    }

    public function render()
    {
        return $this->page('livewire.admin.labels.index', ['templates' => LabelTemplate::orderBy('name')->get()]);
    }
}