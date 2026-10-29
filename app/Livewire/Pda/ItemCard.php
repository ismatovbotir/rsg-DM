<?php

namespace App\Livewire\Pda;

use App\Livewire\PdaPage;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\PriceType;
use Livewire\Attributes\Locked;

/** PDA item card: one item in tabs (Asosiy, Narxlar, Qoldiq, Shtrix-kodlar). Opened by a scan or a pick in "Mahsulotlar". */
class ItemCard extends PdaPage
{
    #[Locked]
    public int $recordId;

    public string $code = '';

    public function mount(int $record): void
    {
        $this->recordId = Item::findOrFail($record)->id;
    }

    /** Scan the next item right on the card. */
    public function scan()
    {
        $code = trim($this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        if ($hit = Item::resolve($code)) {
            return $this->redirect(route('item', $hit[0]->id));
        }

        $this->fail("Topilmadi: {$code}");
    }

    public function render()
    {
        $user = auth()->user();
        $item = Item::with(['group.parent.parent', 'barcodes'])->findOrFail($this->recordId);
        $shopPriceType = $user->shopPriceTypeId();

        $prices = ItemPrice::where('item_id', $item->id)->pluck('price', 'price_type_id');
        $priceTypes = PriceType::orderBy('name')->get()->map(fn ($t) => (object) ['id' => $t->id, 'name' => $t->name, 'price' => $prices[$t->id] ?? null])->filter(fn ($t) => $t->price !== null)->values();

        $stocks = $item->stocks()->join('warehouses', 'warehouses.id', '=', 'item_stocks.warehouse_id')
            ->when($user->accessibleWarehouseIds(), fn ($q, $ids) => $q->whereIn('item_stocks.warehouse_id', $ids))
            ->orderBy('warehouses.name')->get(['warehouses.name', 'item_stocks.qty']);

        return $this->page('livewire.pda.item', [
            'item' => $item,
            'price' => $item->priceFor($shopPriceType) + 0,
            'shopPriceType' => $shopPriceType,
            'priceTypes' => $priceTypes,
            'stocks' => $stocks,
            'stockTotal' => $stocks->isEmpty() ? null : $stocks->sum('qty') + 0,
            'image' => \App\Support\CheckerDesign::imageUrl($item->image),
        ]);
    }
}
