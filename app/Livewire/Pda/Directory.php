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

        // a scan that names exactly one item opens its card; anything else lists the matches
        if ($this->q !== '' && ($hit = Item::resolve($this->q))) {
            $this->redirect(route('item', $hit[0]->id));
        }
    }

    public function render()
    {
        $q = trim($this->q);

        return $this->page('livewire.pda.directory', [
            'items' => filled($q) ? $this->shopItems($q) : collect(),
        ]);
    }

    /** Items with the stock and price of the user's shop (all shops / base price for a user not tied to one). */
    private function shopItems(string $q)
    {
        $user = auth()->user();
        $items = Item::active()
            ->withSum(['stocks as stocks_sum_qty' => fn ($s) => $s->when($user->accessibleWarehouseIds(), fn ($w, $ids) => $w->whereIn('warehouse_id', $ids))], 'qty')
            ->search($q)->orderBy('name')->limit(50)->get();

        return Item::withShopPrices($items, $user->shopPriceTypeId());
    }
}
