<?php

namespace App\Livewire\Admin\Items;

use App\Livewire\AdminPage;
use App\Models\Group;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\ItemPrice;
use App\Models\PriceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/** Item card: main data, barcode table, prices per price list, stock balances (tabs). */
class Form extends AdminPage
{
    #[Locked]
    public ?int $recordId = null;

    public string $code = '';

    public string $article = '';

    public string $barcode = '';

    public string $name = '';

    public string $unit = 'dona';

    public string $group_id = '';

    public string $price = '0';

    public string $vat_rate = '0';

    public string $shelf_life_days = '';

    public bool $is_weighed = false;

    public bool $is_active = true;

    /** @var list<array{barcode: string, qty_in_pack: string|float, is_active: bool}> */
    public array $barcodes = [];

    /** @var array<int, string> price_type_id => price (empty = base price) */
    public array $prices = [];

    public function mount(?int $record = null): void
    {
        $item = $record ? Item::with('barcodes', 'prices')->findOrFail($record) : null;
        $this->recordId = $item?->id;

        if ($item) {
            $this->fill([
                'code' => $item->code, 'article' => (string) $item->article, 'barcode' => (string) $item->barcode, 'name' => $item->name,
                'unit' => $item->unit, 'group_id' => (string) $item->group_id, 'price' => (string) ($item->price + 0), 'vat_rate' => (string) ($item->vat_rate + 0),
                'shelf_life_days' => (string) $item->shelf_life_days, 'is_weighed' => $item->is_weighed, 'is_active' => $item->is_active,
            ]);
            $this->barcodes = $item->barcodes->map(fn ($b) => ['barcode' => $b->barcode, 'qty_in_pack' => (string) ($b->qty_in_pack + 0), 'is_active' => $b->is_active])->all();
        }

        foreach (PriceType::active()->orderBy('name')->get() as $type) {
            $this->prices[$type->id] = $item ? (string) ($item->prices->firstWhere('price_type_id', $type->id)?->price ?? '') : '';
        }
    }

    public function addBarcode(): void
    {
        $this->barcodes[] = ['barcode' => '', 'qty_in_pack' => '1', 'is_active' => true];
    }

    public function removeBarcode(int $index): void
    {
        unset($this->barcodes[$index]);
        $this->barcodes = array_values($this->barcodes);
    }

    public function save()
    {
        $data = $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('items', 'code')->ignore($this->recordId)],
            'article' => 'nullable|string|max:50',
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('items', 'barcode')->ignore($this->recordId)],
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'price' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'shelf_life_days' => 'nullable|integer|min:0|max:36500',
            'group_id' => 'nullable|exists:groups,id',
            'is_weighed' => 'boolean',
            'is_active' => 'boolean',
        ], [], ['code' => 'Kod', 'name' => 'Nomi', 'unit' => 'Birlik', 'price' => 'Narx', 'barcode' => 'Shtrix-kod']);

        $data = collect($data)->map(fn ($v) => $v === '' ? null : $v)->all();
        $data['vat_rate'] ??= 0;

        try {
            DB::transaction(function () use ($data) {
                $item = $this->recordId ? tap(Item::findOrFail($this->recordId))->update($data) : Item::create($data);
                $this->syncBarcodes($item);
                $this->syncPrices($item);
            });
        } catch (ValidationException $e) {
            throw $e; // shown next to the form; nothing was saved
        }

        session()->flash('status', $this->recordId ? 'Tovar saqlandi.' : 'Tovar qo‘shildi.');

        return $this->redirect(route('admin.items.index'));
    }

    /** Prices per price list; an empty value removes the row (falls back to the base price). */
    private function syncPrices(Item $item): void
    {
        foreach (PriceType::pluck('id') as $typeId) {
            $value = $this->prices[$typeId] ?? null;

            if ($value === null || $value === '') {
                ItemPrice::where(['item_id' => $item->id, 'price_type_id' => $typeId])->delete();
            } elseif (is_numeric($value) && $value >= 0) {
                ItemPrice::updateOrCreate(['item_id' => $item->id, 'price_type_id' => $typeId], ['price' => $value]);
            } else {
                throw ValidationException::withMessages(['prices' => 'Narx noto‘g‘ri kiritilgan.']);
            }
        }
    }

    /**
     * The barcode table: rows of barcode / qty_in_pack (a case of 12 books 12) / active.
     * Blank rows are ignored; a barcode already used by another item or twice in the table is rejected.
     */
    private function syncBarcodes(Item $item): void
    {
        $rows = [];

        foreach ($this->barcodes as $row) {
            $code = trim((string) ($row['barcode'] ?? ''));

            if ($code === '') {
                continue;
            }

            $qty = trim((string) ($row['qty_in_pack'] ?? '')) === '' ? 1 : $row['qty_in_pack'];

            if (! is_numeric($qty) || $qty <= 0) {
                throw ValidationException::withMessages(['barcodes' => "Noto‘g‘ri miqdor: {$code}"]);
            }

            $taken = ItemBarcode::where('barcode', $code)->where('item_id', '!=', $item->id)->exists()
                || Item::where('barcode', $code)->where('id', '!=', $item->id)->exists();

            if ($taken || $code === $item->barcode || isset($rows[$code])) {
                throw ValidationException::withMessages(['barcodes' => "Shtrix-kod band yoki takrorlangan: {$code}"]);
            }

            $rows[$code] = ['qty_in_pack' => (float) $qty, 'is_active' => ! empty($row['is_active'])];
        }

        $item->barcodes()->delete();
        foreach ($rows as $code => $values) {
            $item->barcodes()->create(['barcode' => $code] + $values);
        }
    }

    public function render()
    {
        $item = $this->recordId ? Item::find($this->recordId) : null;

        return $this->page('livewire.admin.items.form', [
            'groups' => Group::with('parent.parent.parent')->orderBy('name')->get()->sortBy('path'),
            'priceTypes' => PriceType::active()->orderBy('name')->get(),
            'stocks' => $item
                ? $item->stocks()->join('warehouses', 'warehouses.id', '=', 'item_stocks.warehouse_id')->orderBy('warehouses.name')->get(['warehouses.name', 'item_stocks.qty', 'item_stocks.cost', 'item_stocks.stock_date'])
                : collect(),
            'item' => $item,
        ]);
    }
}
