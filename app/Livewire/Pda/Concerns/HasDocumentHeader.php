<?php

namespace App\Livewire\Pda\Concerns;

use App\Models\Counterparty;
use App\Models\Document;
use App\Models\PriceType;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;

/**
 * Header form of a document (create page and the burger menu of the items screen).
 * The counterparty may be typed instead of picked: a STIR number or an exact name is resolved to its id on save.
 */
trait HasDocumentHeader
{
    /** @var array<string, string> field name => value (ids for dropdown / picker fields) */
    public array $header = [];

    public string $cp_q = '';

    public bool $cp_list = false;

    protected function fillHeader(string $type, array $values): void
    {
        $warehouses = Warehouse::active()->get();
        $priceTypes = PriceType::active()->get();

        $shop = auth()->user()->shopId();

        foreach (Document::headerFieldsFor($type) as $name => $field) {
            $value = $values[$name] ?? null;

            // a user tied to a shop works from it: the (source) warehouse defaults to that shop and stays inside the accessible ones
            if ($name === 'warehouse_id' && $shop && ! in_array((int) $value, auth()->user()->accessibleWarehouseIds() ?? [], true)) {
                $value = $shop;
            }

            // a single warehouse / price list is preselected
            if (blank($value) && $field['type'] === 'warehouse' && $warehouses->count() === 1) {
                $value = $warehouses->first()->id;
            }
            if (blank($value) && $field['type'] === 'pricetype' && ($shopType = auth()->user()->shopPriceTypeId())) {
                $value = $shopType; // the price list of the user's shop
            }
            if (blank($value) && $field['type'] === 'pricetype' && $priceTypes->count() === 1) {
                $value = $priceTypes->first()->id;
            }

            $this->header[$name] = $value === null ? '' : (string) $value;
        }

        if (filled($this->header['counterparty_id'] ?? null)) {
            $this->cp_q = (string) Counterparty::find($this->header['counterparty_id'])?->name;
        }
    }

    /** Typing in the picker clears the picked id; the typed text is resolved on save. */
    public function updatedCpQ(): void
    {
        $this->header['counterparty_id'] = '';
        $this->cp_list = mb_strlen(trim($this->cp_q)) >= 2;
    }

    public function toggleCpList(): void
    {
        $this->cp_list = ! $this->cp_list;
    }

    public function pickCounterparty(int $id): void
    {
        $counterparty = Counterparty::active()->findOrFail($id);
        $this->header['counterparty_id'] = (string) $counterparty->id;
        $this->cp_q = $counterparty->name;
        $this->cp_list = false;
    }

    protected function counterpartyResults()
    {
        return $this->cp_list
            ? Counterparty::active()->search(trim($this->cp_q))->orderBy('name')->limit(30)->get()
            : collect();
    }

    /** Validated header values for the type; throws a ValidationException shown on the screen. */
    protected function validatedHeader(string $type, bool $editing = false): array
    {
        $data = $this->header;

        if (! $editing && blank($data['counterparty_id'] ?? null) && filled($typed = trim($this->cp_q))) {
            $digits = preg_replace('/\D/', '', $typed);

            $match = Counterparty::active()->where(function ($w) use ($typed, $digits) {
                $w->where('name', $typed);
                if ($digits !== '') {
                    $w->orWhere('stir', $digits);
                }
            })->first();

            if ($match) {
                $data['counterparty_id'] = $match->id;
            }
        }

        // the source warehouse of a shop user is always their shop, whatever the browser sent; the destination is free
        $allowed = auth()->user()->accessibleWarehouseIds();
        if ($allowed && array_key_exists('warehouse_id', Document::headerFieldsFor($type)) && ! in_array((int) ($data['warehouse_id'] ?? 0), $allowed, true)) {
            $data['warehouse_id'] = (string) (auth()->user()->shopId() ?? $allowed[0]); // string like the form values, so "different" compares properly
        }

        $allowedTypes = auth()->user()->accessiblePriceTypeIds();
        if ($allowedTypes && filled($data['price_type_id'] ?? null) && ! in_array((int) $data['price_type_id'], $allowedTypes, true)) {
            $data['price_type_id'] = (string) (auth()->user()->shopPriceTypeId() ?? $allowedTypes[0]);
        }

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        return Validator::make($data, Document::headerRules($type, $editing), Document::validationMessages(), Document::headerLabels($type))->validate();
    }

    protected function headerViewData(): array
    {
        $user = auth()->user();
        $ids = $user->accessibleWarehouseIds();

        return [
            'warehouses' => Warehouse::active()->orderBy('name')->get(), // destination: free pick
            'sourceWarehouses' => Warehouse::active()->when($ids, fn ($q) => $q->whereIn('id', $ids))->orderBy('name')->get(),
            'lockedWarehouse' => $ids && count($ids) === 1 ? $ids[0] : null, // one accessible warehouse = fixed source
            'priceTypes' => PriceType::active()->when($user->accessiblePriceTypeIds(), fn ($q, $t) => $q->whereIn('id', $t))->orderBy('name')->get(),
            'cpResults' => $this->counterpartyResults(),
        ];
    }
}