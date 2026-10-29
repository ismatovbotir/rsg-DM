<?php

namespace App\Livewire\Pda;

use App\Livewire\Pda\Concerns\AuthorizesDocuments;
use App\Livewire\PdaPage;
use App\Models\Document;
use App\Models\Item;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

/**
 * "Tanlash": a full page item picker (not a modal: on a small PDA a modal competes with the on-screen keyboard and
 * the hardware Back key). Opened from a document it continues to the entry page, from the directory it shows the item.
 */
class Browse extends PdaPage
{
    use AuthorizesDocuments;

    /** 0 = the directory */
    #[Locked]
    public int $recordId = 0;

    #[Url]
    public string $q = '';

    /** Price check: only the items of the scope that are not checked yet. */
    #[Url]
    public bool $remaining = false;

    public function mount(?int $record = null): void
    {
        if ($record) {
            $this->authorizeEdit(Document::findOrFail($record));
            $this->recordId = $record;
        }
    }

    public function pick(int $itemId)
    {
        $item = Item::active()->findOrFail($itemId);

        if ($this->recordId) {
            $document = Document::findOrFail($this->recordId);
            $this->authorizeEdit($document);

            return $this->redirect(route('docs.entry', ['record' => $document->id, 'item' => $item->id]));
        }

        return $this->redirect(route('item', $item->id));
    }

    public function render()
    {
        $q = trim($this->q);
        $document = $this->recordId ? Document::with('lines')->find($this->recordId) : null;

        return $this->page('livewire.pda.browse', [
            'title' => $this->remaining ? 'Qolgan tovarlar' : ($document ? 'Tovar tanlash' : 'Mahsulotlar — tanlash'),
            'back' => $document ? route('docs.show', $document->id) : route('directory'),
            'items' => $this->remaining && $document?->hasCheckList() ? $this->remainingItems($document, $q) : $this->shopItems($q),
            'added' => $document ? $document->lines->groupBy('item_id')->map->sum('qty') : collect(),
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

    private function remainingItems(Document $document, string $q)
    {
        $items = $document->checkItemsQuery()->whereNotIn('items.id', $document->lines()->select('item_id'))
            ->withSum(['stocks as stocks_sum_qty' => fn ($s) => $s->where('warehouse_id', $document->warehouse_id)], 'qty')
            ->search($q)->orderBy('name')->limit(50)->get();

        return Item::withShopPrices($items, $document->effectivePriceTypeId());
    }
}
