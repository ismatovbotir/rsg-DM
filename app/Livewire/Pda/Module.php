<?php

namespace App\Livewire\Pda;

use App\Livewire\PdaPage;
use Livewire\Attributes\Locked;

/** /m/{key}: opens a menu item: a document list, a screen, or a "not ready yet" placeholder. */
class Module extends PdaPage
{
    #[Locked]
    public array $item = [];

    public function mount(string $key)
    {
        $item = collect(config('menu.items'))->merge(config('menu.side'))->firstWhere('key', $key);

        abort_if($item === null, 404);
        $side = collect(config('menu.side'))->contains('key', $key);
        abort_unless($side || auth()->user()->canAccess($key), 403, 'Bu bo‘limga ruxsat yo‘q.');

        if (! empty($item['doc'])) {
            return $this->redirect(route('docs.index', $key));
        }

        if (! empty($item['route'])) {
            return $this->redirect(route($item['route']));
        }

        $this->item = $item;
    }

    public function render()
    {
        return $this->page('livewire.pda.module');
    }
}