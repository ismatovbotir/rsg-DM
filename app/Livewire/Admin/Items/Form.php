<?php

namespace App\Livewire\Admin\Items;

use App\Livewire\AdminPage;
use App\Models\Item;
use App\Models\PriceCheck;
use App\Models\PriceType;
use App\Models\Printer;
use App\Services\PriceTagPrinter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

/**
 * Item card, view only: the data comes from Adines (1C), nothing here can be changed.
 * The one local thing is the picture for the price checker screen, because Adines does not send pictures.
 */
class Form extends AdminPage
{
    use WithFileUploads;

    #[Locked]
    public int $recordId;

    public $photo = null;

    /** Copies for "Yorliq chop etish". */
    public int $copies = 1;

    public function mount(int $record): void
    {
        $this->recordId = Item::findOrFail($record)->id;
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'image|mimes:jpg,jpeg,png,webp|max:4096'], [], ['photo' => 'Rasm']);
        Item::findOrFail($this->recordId)->update(['image' => $this->photo->storeAs('items', Str::random(24).'.'.$this->photo->extension(), 'uploads')]);
        $this->photo = null;
        $this->ok('Rasm saqlandi.');
    }

    public function removePhoto(): void
    {
        Item::findOrFail($this->recordId)->update(['image' => null]);
        $this->ok('Rasm olib tashlandi.');
    }

    /** Print this item's price tag now on the price-tag printer (LAN: the server sends it). */
    public function printTag(PriceTagPrinter $printing): void
    {
        $this->validate(['copies' => 'required|integer|min:1|max:100'], [], ['copies' => 'Nusxa soni']);
        $printer = Printer::priceTag();

        if (! $printer) {
            $this->fail('Narx yorliqlari printeri tanlanmagan (Printerlar bo‘limi).');

            return;
        }

        if ($printer->isBluetooth()) {
            $this->fail('Narx yorliqlari printeri Bluetooth: uni serverdan emas, PDA dan chop eting yoki navbatga qo‘shing.');

            return;
        }

        $item = Item::findOrFail($this->recordId);

        try {
            $printing->send($printer, implode("
", array_fill(0, $this->copies, $printing->label($printer, $item))));
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->ok("{$this->copies} ta yorliq {$printer->name} printeriga yuborildi.");
    }

    /** Add the tag to the print queue ("Narx yorliqlari"), to be printed together with the rest. */
    public function queueTag(): void
    {
        $item = Item::findOrFail($this->recordId);

        if (PriceCheck::queued()->where('item_id', $item->id)->exists()) {
            $this->fail('Bu tovar yorlig‘i allaqachon navbatda.');

            return;
        }

        PriceCheck::create(['item_id' => $item->id, 'user_id' => auth()->id(), 'price' => $item->price, 'action' => PriceCheck::PRINT]);
        $this->ok('Yorliq chop etish navbatiga qo‘shildi.');
    }

    /** A user tied to a shop sees that shop's price list only. */
    private function visiblePriceTypes()
    {
        return PriceType::active()->when(auth()->user()?->accessiblePriceTypeIds(), fn ($q, $ids) => $q->whereIn('id', $ids))->orderBy('name')->get();
    }

    public function render()
    {
        $item = Item::with(['group.parent.parent', 'barcodes', 'prices'])->findOrFail($this->recordId);

        return $this->page('livewire.admin.items.form', [
            'item' => $item,
            'queued' => PriceCheck::queued()->where('item_id', $item->id)->exists(),
            'printer' => Printer::priceTag(),
            'priceTypes' => $this->visiblePriceTypes(),
            'stocks' => $item->stocks()->join('warehouses', 'warehouses.id', '=', 'item_stocks.warehouse_id')
                ->when(auth()->user()->accessibleWarehouseIds(), fn ($q, $ids) => $q->whereIn('item_stocks.warehouse_id', $ids))
                ->orderBy('warehouses.name')->get(['warehouses.name', 'item_stocks.qty', 'item_stocks.cost', 'item_stocks.stock_date']),
        ]);
    }
}
