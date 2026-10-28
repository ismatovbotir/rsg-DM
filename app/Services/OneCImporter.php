<?php

namespace App\Services;

use App\Models\Counterparty;
use App\Models\Group;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\ItemPrice;
use App\Models\ItemStock;
use App\Models\PriceType;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Writes one row of Adines master data for the inbound API (Adines pushes).
 * Adines ids are our ids (groups, items, partners, shops, price types); barcodes and units refer to items/units by uuid.
 * Rows are upserted by their natural key.
 */
class OneCImporter
{
    public const ENTITIES = ['groups', 'warehouses', 'price-types', 'items', 'barcodes', 'item-prices', 'item-stocks', 'partners'];

    /** Validate and save a row. Returns true when created, false when updated; throws RuntimeException with a readable message. */
    public function import(string $entity, mixed $row): bool
    {
        $validator = Validator::make(is_array($row) ? $row : [], $this->rules($entity));

        if ($validator->fails()) {
            throw new RuntimeException($validator->errors()->first());
        }

        return $this->{'import'.str_replace('-', '', ucwords($entity, '-'))}($validator->validated());
    }

    /** Rows keyed by their original index, parents before children, so a group never arrives before its parent. */
    public function order(string $entity, array $rows): array
    {
        if ($entity !== 'groups') {
            return $rows;
        }

        $indexOf = []; // Adines id => row index
        foreach ($rows as $i => $row) {
            if (is_array($row) && isset($row['id'])) {
                $indexOf[$row['id']] = $i;
            }
        }

        $ordered = [];
        $pending = $rows;

        while ($pending) {
            $before = count($pending);

            foreach ($pending as $i => $row) {
                $parent = is_array($row) ? ($row['parent_id'] ?? null) : null;
                $parentIndex = $parent ? ($indexOf[$parent] ?? null) : null;
                $waiting = $parentIndex !== null && $parentIndex !== $i && isset($pending[$parentIndex]);

                if (! $waiting) {
                    $ordered[$i] = $row;
                    unset($pending[$i]);
                }
            }

            if (count($pending) === $before) { // cycle: let the rest fail on their own
                return $ordered + $pending;
            }
        }

        return $ordered;
    }

    private function rules(string $entity): array
    {
        $unit = fn (string $k) => [
            $k => 'nullable|array', "{$k}.uuid" => "required_with:{$k}|string|max:36", "{$k}.name" => "required_with:{$k}|string|max:50",
            "{$k}.code" => 'nullable|string|max:50', "{$k}.qty" => 'nullable|numeric|min:0', "{$k}.weight" => 'nullable|numeric|min:0',
            "{$k}.width" => 'nullable|numeric|min:0', "{$k}.length" => 'nullable|numeric|min:0', "{$k}.height" => 'nullable|numeric|min:0',
            "{$k}.is_stackable" => 'nullable|boolean', "{$k}.shape" => 'nullable|integer',
        ];

        return match ($entity) {
            'groups' => [
                'id' => 'required|integer|min:1', 'name' => 'required|string|max:255',
                'parent_id' => 'nullable|integer|min:0', 'is_active' => 'nullable|boolean',
            ],
            'warehouses' => ['id' => 'required|integer|min:1', 'name' => 'required|string|max:255', 'price_id' => 'nullable|integer|min:0'],
            'price-types' => ['id' => 'required|integer|min:1', 'name' => 'required|string|max:255'],
            'items' => [
                'id' => 'required|integer|min:1', 'uuid' => 'nullable|string|max:36', 'group_id' => 'nullable|integer|min:0',
                'name' => 'required|string|max:255', 'article' => 'nullable|string|max:50', 'life' => 'nullable|integer|min:0|max:36500',
                'gtin' => 'nullable|array', 'is_weight' => 'nullable|boolean', 'is_active' => 'nullable|boolean',
            ] + $unit('unit_main') + $unit('unit_order') + $unit('unit_report'),
            'barcodes' => ['product_id' => 'required|string|max:36', 'unit_id' => 'nullable|string|max:36', 'barcode' => 'required|string|max:50', 'active' => 'nullable|boolean'],
            'item-prices' => ['item_id' => 'required|integer|min:1', 'price_id' => 'required|integer|min:1', 'value' => 'required|numeric|min:0'],
            'item-stocks' => [
                'shop_id' => 'required|integer|min:1', 'item_id' => 'required|integer|min:1', 'qty' => 'required|numeric',
                'cost' => 'nullable|numeric', 'stock_date' => 'nullable|date',
            ],
            'partners' => [
                'id' => 'required|integer|min:1', 'uuid' => 'nullable|string|max:36', 'type' => 'nullable|in:contragent,employee',
                'name' => 'required|string|max:255', 'full_name' => 'nullable|string|max:255', 'inn' => 'nullable|string|max:20',
                'phone' => 'nullable|string|max:255', 'address' => 'nullable|string|max:500',
                'is_vat_payer' => 'nullable|boolean', 'is_active' => 'nullable|boolean',
            ],
        };
    }

    // Each import* returns true when the row was created, false when updated.

    private function importGroups(array $d): bool
    {
        $parentId = null;

        if (! empty($d['parent_id'])) {
            if ((int) $d['parent_id'] === (int) $d['id']) {
                throw new RuntimeException('A group cannot be its own parent.');
            }

            $parentId = Group::whereKey($d['parent_id'])->value('id') ?? throw new RuntimeException("Unknown parent group: {$d['parent_id']}");
        }

        return $this->upsert(Group::class, ['id' => $d['id']], [
            'name' => $d['name'], 'parent_id' => $parentId, 'is_active' => $d['is_active'] ?? true,
        ]);
    }

    // Shops = warehouses; price_id is the shop's price list.
    private function importWarehouses(array $d): bool
    {
        return $this->upsert(Warehouse::class, ['id' => $d['id']], [
            'code' => (string) $d['id'], 'name' => $d['name'], 'price_type_id' => ($d['price_id'] ?? 0) ?: null, 'is_active' => true,
        ]);
    }

    private function importPriceTypes(array $d): bool
    {
        return $this->upsert(PriceType::class, ['id' => $d['id']], ['code' => (string) $d['id'], 'name' => $d['name'], 'is_active' => true]);
    }

    private function importItems(array $d): bool
    {
        $code = (string) $d['id'];

        if (Item::where('code', $code)->where('id', '!=', $d['id'])->exists()) {
            throw new RuntimeException("Code {$code} belongs to another item.");
        }

        if (! empty($d['uuid']) && Item::where('uuid', $d['uuid'])->where('id', '!=', $d['id'])->exists()) {
            throw new RuntimeException("uuid {$d['uuid']} belongs to another item.");
        }

        if (! empty($d['group_id']) && ! Group::whereKey($d['group_id'])->exists()) {
            throw new RuntimeException("Unknown group: {$d['group_id']}");
        }

        // gtin: a list of barcodes (plain strings, or objects with "barcode")
        $gtin = collect($d['gtin'] ?? [])
            ->map(fn ($g) => trim((string) (is_array($g) ? ($g['barcode'] ?? $g['gtin'] ?? '') : $g)))
            ->filter()->unique()->values();

        foreach ($gtin as $barcode) {
            if (Item::where('barcode', $barcode)->where('id', '!=', $d['id'])->exists()
                || ItemBarcode::where('barcode', $barcode)->where('item_id', '!=', $d['id'])->exists()) {
                throw new RuntimeException("Barcode {$barcode} belongs to another item.");
            }
        }

        $units = [];
        foreach (['unit_main', 'unit_order', 'unit_report'] as $k) {
            $units[$k] = ! empty($d[$k]) ? $this->unit($d[$k]) : null;
        }

        $item = Item::firstOrNew(['id' => $d['id']]);
        $created = ! $item->exists;

        $item->fill([
            'code' => $code,
            'uuid' => $d['uuid'] ?? $item->uuid,
            'name' => $d['name'],
            'article' => $d['article'] ?? null,
            'group_id' => ($d['group_id'] ?? 0) ?: null,
            'is_weighed' => $d['is_weight'] ?? false,
            'is_active' => $d['is_active'] ?? true,
            'unit' => $units['unit_main']?->name ?? $item->unit ?? 'dona',
            'price' => $item->price ?? 0,
        ] + (array_key_exists('life', $d) ? ['shelf_life_days' => $d['life'] ?: null] : []));

        foreach ($units as $k => $unit) {
            $unit && $item->{$k.'_id'} = $unit->id;
        }

        $item->save();

        foreach ($gtin as $barcode) {
            $this->upsert(ItemBarcode::class, ['item_id' => $item->id, 'barcode' => $barcode], ['qty_in_pack' => 1, 'is_active' => true]);
        }

        return $created;
    }

    private function unit(array $u): Unit
    {
        $unit = Unit::firstOrNew(['uuid' => $u['uuid']]);
        $unit->fill(array_intersect_key($u, array_flip(['code', 'name', 'qty', 'weight', 'width', 'length', 'height', 'is_stackable', 'shape'])))->save();

        return $unit;
    }

    // product_id / unit_id are uuids; a pack unit books its qty when scanned.
    private function importBarcodes(array $d): bool
    {
        $item = Item::where('uuid', $d['product_id'])->first() ?? throw new RuntimeException("Unknown item uuid: {$d['product_id']}");
        $unit = ! empty($d['unit_id']) ? Unit::where('uuid', $d['unit_id'])->first() : null;

        if (Item::where('barcode', $d['barcode'])->where('id', '!=', $item->id)->exists()
            || ItemBarcode::where('barcode', $d['barcode'])->where('item_id', '!=', $item->id)->exists()) {
            throw new RuntimeException("Barcode {$d['barcode']} belongs to another item.");
        }

        return $this->upsert(ItemBarcode::class, ['item_id' => $item->id, 'barcode' => $d['barcode']], [
            'qty_in_pack' => ($unit && $unit->qty > 0) ? $unit->qty : 1,
            'is_active' => $d['active'] ?? true,
        ]);
    }

    private function importItemPrices(array $d): bool
    {
        $item = Item::find($d['item_id']) ?? throw new RuntimeException("Unknown item: {$d['item_id']}");
        $type = PriceType::find($d['price_id']) ?? throw new RuntimeException("Unknown price type: {$d['price_id']}");

        return $this->upsert(ItemPrice::class, ['item_id' => $item->id, 'price_type_id' => $type->id], ['price' => $d['value']]);
    }

    // Adines sends the quantity multiplied by 1000.
    private function importItemStocks(array $d): bool
    {
        $item = Item::find($d['item_id']) ?? throw new RuntimeException("Unknown item: {$d['item_id']}");
        $warehouse = Warehouse::find($d['shop_id']) ?? throw new RuntimeException("Unknown shop: {$d['shop_id']}");

        return $this->upsert(ItemStock::class, ['item_id' => $item->id, 'warehouse_id' => $warehouse->id], [
            'qty' => $d['qty'] / 1000,
            'cost' => $d['cost'] ?? null,
            'stock_date' => $d['stock_date'] ?? null,
        ]);
    }

    private function importPartners(array $d): bool
    {
        $inn = $d['inn'] ?? null;
        $taken = filled($inn) && Counterparty::where('stir', $inn)->where('id', '!=', $d['id'])->exists(); // STIR is unique: a repeated one is not stored

        return $this->upsert(Counterparty::class, ['id' => $d['id']], [
            'uuid' => $d['uuid'] ?? null,
            'type' => $d['type'] ?? 'contragent',
            'name' => $d['name'],
            'full_name' => $d['full_name'] ?? null,
            'stir' => ($taken || blank($inn)) ? null : $inn,
            'phone' => $d['phone'] ?? null,
            'address' => $d['address'] ?? null,
            'is_vat_payer' => $d['is_vat_payer'] ?? false,
            'is_active' => $d['is_active'] ?? true,
        ]);
    }

    private function upsert(string $model, array $key, array $values): bool
    {
        $record = $model::firstOrNew($key);
        $created = ! $record->exists;
        $record->fill($values)->save();

        return $created;
    }
}
