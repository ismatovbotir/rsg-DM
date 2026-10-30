<?php

namespace App\Livewire\Pda\Docs;

use App\Livewire\Pda\Concerns\AuthorizesDocuments;
use App\Livewire\PdaPage;
use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\Item;
use App\Models\PriceCheck;
use Livewire\Attributes\Locked;

/**
 * Item entry window: qty, price and production date (optional); for a price check the shelf-price confirmation.
 * A line is unique per item + production date (date may be empty): saving the same pair again adds the quantity.
 */
class Entry extends PdaPage
{
    use AuthorizesDocuments;

    #[Locked]
    public int $recordId;

    #[Locked]
    public int $itemId;

    /** The line being corrected (opened from the item list); 0 = adding. */
    #[Locked]
    public int $lineId = 0;

    public string $qty = '1';

    public string $price = '';

    public string $production_date = '';

    public string $shelf_price = '';

    public function mount(int $record, int $item): void
    {
        $document = Document::findOrFail($record);
        $itemModel = Item::findOrFail($item);
        $this->authorizeEntry($document, $itemModel);

        $this->recordId = $document->id;
        $this->itemId = $itemModel->id;
        $this->qty = (string) (max((float) request()->query('qty', 1), 0.001) + 0);
        $this->price = (string) ($itemModel->priceFor($document->effectivePriceTypeId()) + 0);

        // ?line=ID: change qty / price of a line already in the list
        if (! $document->isPriceCheck() && $document->isDraft() && ($line = $document->lines()->where('item_id', $itemModel->id)->find((int) request()->query('line')))) {
            $this->lineId = $line->id;
            $this->qty = (string) ($line->qty + 0);
            $this->price = (string) ($line->price + 0);
            $this->production_date = $line->production_date?->toDateString() ?? '';
        }
    }

    public function save()
    {
        $document = Document::findOrFail($this->recordId);
        $item = Item::findOrFail($this->itemId);
        $this->authorizeEntry($document, $item);

        if ($document->isPriceCheck()) {
            return $this->saveShelfPrice($document, $item);
        }

        $rules = [];
        $document->canEditQty() && $rules['qty'] = 'required|numeric|min:0.001|max:99999999';
        $document->canEditPrice() && $rules['price'] = 'required|numeric|min:0|max:9999999999';
        $document->tracksProduction() && $rules['production_date'] = 'nullable|date|before_or_equal:today';

        $data = $this->validate($rules, [
            'required' => __(':attribute kiritilishi shart.'), 'numeric' => __(':attribute raqam bo‘lishi kerak.'),
            'min' => __(':attribute juda kichik.'), 'date' => __(':attribute noto‘g‘ri sana.'),
            'before_or_equal' => __(':attribute kelajakda bo‘lishi mumkin emas.'),
        ], ['qty' => __('Miqdor'), 'price' => __('Narx'), 'production_date' => __('Ishlab chiqarilgan sana')]);

        $date = ($data['production_date'] ?? '') ?: null;

        if ($this->lineId && ($edited = $document->lines()->where('item_id', $item->id)->find($this->lineId))) {
            // correcting: the qty replaces the old one; a date that already has its own line merges into it
            $other = $document->lines()->where('item_id', $item->id)->whereKeyNot($edited->id)
                ->when($date, fn ($q) => $q->whereDate('production_date', $date), fn ($q) => $q->whereNull('production_date'))->first();
            $qty = $document->canEditQty() ? (float) $data['qty'] : 1;
            $target = $other ?? $edited;
            $target->qty = $other ? $other->qty + $qty : $qty;
            $target->price = $data['price'] ?? $edited->price;
            $target->production_date = $date;
            $target->expiry_date = $date && $item->shelf_life_days !== null ? \Carbon\Carbon::parse($date)->addDays($item->shelf_life_days) : null;
            $target->save();
            $other && $edited->delete();

            return $this->redirect(route('docs.show', $document->id));
        }

        $line = $document->lines()->where('item_id', $item->id)
            ->when($date, fn ($q) => $q->whereDate('production_date', $date), fn ($q) => $q->whereNull('production_date'))
            ->first() ?? new DocumentLine(['document_id' => $document->id, 'item_id' => $item->id, 'qty' => 0]);

        $line->qty = $document->canEditQty() ? $line->qty + $data['qty'] : 1;
        $line->price = $data['price'] ?? ($line->exists ? $line->price : $item->priceFor($document->effectivePriceTypeId()));
        $line->production_date = $date;
        // Expiry follows from the shelf life (days) that Adines sends for the item.
        $line->expiry_date = $date && $item->shelf_life_days !== null ? \Carbon\Carbon::parse($date)->addDays($item->shelf_life_days) : null;
        $line->save();

        session()->flash('status', "{$item->name}: ".($line->qty + 0));

        return $this->redirect(route('docs.show', $document->id));
    }

    /** Price check, one tap: the shelf tag shows the system price ("OK") or not ("Wrong": a new tag is queued for the end of the check). */
    public function verdict(string $result)
    {
        abort_unless(in_array($result, ['ok', 'wrong'], true), 422);

        $document = Document::findOrFail($this->recordId);
        $item = Item::findOrFail($this->itemId);
        $this->authorizeEntry($document, $item);
        abort_unless($document->isPriceCheck(), 404);

        $system = (float) $item->priceFor($document->effectivePriceTypeId());
        $typed = trim($this->shelf_price);
        $ok = $result === 'ok';

        if (! $ok && $typed !== '' && ! is_numeric($typed)) {
            $this->addError('shelf_price', 'Narx raqam bo‘lishi kerak.');

            return;
        }

        $document->lines()->updateOrCreate(['item_id' => $item->id], [
            'qty' => 1, 'price' => $system,
            'shelf_price' => $ok ? $system : ($typed !== '' ? $typed : null),
            'check_action' => $ok ? PriceCheck::CONFIRMED : PriceCheck::PRINT,
        ]);

        session()->flash('status', $ok ? "{$item->name}: narx to‘g‘ri" : "{$item->name}: yorliq navbatga qo‘shildi");

        return $this->redirect(route('docs.show', $document->id));
    }

    /**
     * Price check confirmation: the operator types the price shown on the shelf tag.
     * Same as the system price -> confirmed; different -> the line goes to the price-tag print list.
     */
    private function saveShelfPrice(Document $document, Item $item)
    {
        $shelf = $this->validate(
            ['shelf_price' => 'required|numeric|min:0|max:9999999999'],
            ['required' => 'Javondagi narxni kiriting.', 'numeric' => 'Narx raqam bo‘lishi kerak.', 'min' => 'Narx noto‘g‘ri.']
        )['shelf_price'];

        $system = (float) $item->priceFor($document->effectivePriceTypeId());
        $matches = abs((float) $shelf - $system) < 0.005;

        $document->lines()->updateOrCreate(['item_id' => $item->id], [
            'qty' => 1, 'price' => $system, 'shelf_price' => $shelf,
            'check_action' => $matches ? PriceCheck::CONFIRMED : PriceCheck::PRINT,
        ]);

        session()->flash('status', $matches ? "{$item->name}: narx to‘g‘ri" : "{$item->name}: narx mos emas — yorliq chop etishga qo‘shildi");

        return $this->redirect(route('docs.show', $document->id));
    }

    public function render()
    {
        $document = Document::findOrFail($this->recordId);
        $item = Item::findOrFail($this->itemId);
        $expiry = null;

        if ($document->tracksProduction() && $this->production_date !== '' && $item->shelf_life_days !== null && strtotime($this->production_date)) {
            $expiry = \Carbon\Carbon::parse($this->production_date)->addDays($item->shelf_life_days)->format('d.m.Y');
        }

        return $this->page('livewire.pda.docs.entry', [
            'doc' => $document,
            'item' => $item,
            'priceSystem' => $item->priceFor($document->effectivePriceTypeId()) + 0,
            'lines' => $document->lines()->where('item_id', $item->id)->whereKeyNot($this->lineId)->orderBy('production_date')->get(),
            'stocks' => $item->stocks()->join('warehouses', 'warehouses.id', '=', 'item_stocks.warehouse_id')
                ->when(auth()->user()->accessibleWarehouseIds(), fn ($q, $ids) => $q->whereIn('item_stocks.warehouse_id', $ids)) // only the warehouses the user has access to
                ->orderBy('warehouses.name')->get(['warehouses.name', 'item_stocks.qty']),
            'expiry' => $expiry,
        ]);
    }
}