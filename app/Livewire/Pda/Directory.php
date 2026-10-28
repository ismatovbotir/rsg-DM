<?php

namespace App\Livewire\Pda;

use App\Livewire\PdaPage;
use App\Models\Item;
use Livewire\Attributes\Url;

/** PDA item lookup: scan or type a barcode / code / name. */
class Directory extends PdaPage
{
    #[Url]
    public string $q = '';

    /** Submitting the scan bar: the model is synced with the request, the list is rebuilt in render(). */
    public function search(): void
    {
        $this->q = trim($this->q);
    }

    public function render()
    {
        $q = trim($this->q);

        return $this->page('livewire.pda.directory', [
            'items' => filled($q) ? Item::active()->withSum('stocks', 'qty')->search($q)->orderBy('name')->limit(50)->get() : collect(),
        ]);
    }
}