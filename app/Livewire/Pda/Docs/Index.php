<?php

namespace App\Livewire\Pda\Docs;

use App\Livewire\PdaPage;
use App\Livewire\Pda\Concerns\AuthorizesDocuments;
use App\Models\Document;
use App\Models\Group;
use App\Models\PriceType;
use App\Models\Warehouse;
use Livewire\Attributes\Locked;

/** Documents of one type (own documents; admins see all). */
class Index extends PdaPage
{
    use AuthorizesDocuments;

    #[Locked]
    public string $type = '';

    // price check start screen
    public string $scope = 'group';

    public string $group_id = '';

    public string $warehouse_id = '';

    public string $price_type_id = '';

    public function mount(string $type): void
    {
        $this->assertType($type);
        $this->type = $type;
        $this->warehouse_id = (string) (auth()->user()->shopId() ?? (Warehouse::active()->count() === 1 ? Warehouse::active()->value('id') : ''));
    }

    /** Price check: one tap from the start screen creates the document for the chosen scope (shop and price list come from the user). */
    public function startCheck()
    {
        $this->assertType($this->type);
        abort_unless($this->type === 'pricecheck', 404);

        $user = auth()->user();
        $allowed = $user->accessibleWarehouseIds();
        $shop = $this->warehouse_id !== '' ? (int) $this->warehouse_id : $user->shopId();
        $allowed && ! in_array($shop, $allowed, true) && $shop = $user->shopId() ?? $allowed[0]; // only warehouses the user has access to

        $types = $user->accessiblePriceTypeIds();
        $priceType = ($this->price_type_id !== '' ? (int) $this->price_type_id : null)
            ?? ($shop === $user->shopId() ? $user->shopPriceTypeId() : null)
            ?? Warehouse::active()->find($shop)?->price_type_id ?? (PriceType::active()->count() === 1 ? PriceType::active()->value('id') : null);
        $types && $priceType && ! in_array($priceType, $types, true) && $priceType = $user->shopPriceTypeId() ?? $types[0];

        $this->validate([
            'scope' => 'required|in:all,group,free',
            'group_id' => 'required_if:scope,group|nullable|exists:groups,id',
        ], ['group_id.required_if' => 'Guruhni tanlang.'], ['group_id' => 'Guruh']);

        if ($this->scope !== 'free' && ! $shop) {
            $this->addError('warehouse_id', 'Do‘konni (omborni) tanlang.');

            return null;
        }

        if (! $priceType) {
            $this->addError('price_type_id', 'Narx turini tanlang.');

            return null;
        }

        $doc = Document::create([
            'type' => 'pricecheck', 'user_id' => $user->id, 'doc_date' => now()->toDateString(),
            'warehouse_id' => $shop, 'price_type_id' => $priceType,
            'scope' => $this->scope, 'group_id' => $this->scope === 'group' ? (int) $this->group_id : null,
        ]);

        return $this->redirect(route('docs.show', $doc->id));
    }

    public function render()
    {
        $this->assertType($this->type);

        $docs = Document::where('type', $this->type)
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->where('user_id', auth()->id()))
            ->withCount('lines')
            ->latest()
            ->limit(50)
            ->get();

        $check = $this->type === 'pricecheck';

        return $this->page('livewire.pda.docs.index', [
            'title' => Document::types()[$this->type],
            'docs' => $docs,
            'check' => $check,
            'progress' => $check ? $docs->filter->isDraft()->mapWithKeys(fn ($d) => [$d->id => $d->checkProgress()]) : collect(),
            'groups' => $check ? Group::with('parent.parent.parent')->where('is_active', true)->orderBy('name')->get()->sortBy('path') : collect(),
            'shop' => $check ? auth()->user()->warehouse : null,
            // the shop / price list is a pick when the user has several (or none fixed), otherwise it is shown as text
            'warehouses' => $check && count(auth()->user()->accessibleWarehouseIds() ?? [0, 0]) !== 1 ? Warehouse::active()->when(auth()->user()->accessibleWarehouseIds(), fn ($q, $ids) => $q->whereIn('id', $ids))->orderBy('name')->get() : collect(),
            'priceTypes' => $check && count(auth()->user()->accessiblePriceTypeIds() ?? [0, 0]) !== 1 ? PriceType::active()->when(auth()->user()->accessiblePriceTypeIds(), fn ($q, $ids) => $q->whereIn('id', $ids))->orderBy('name')->get() : collect(),
        ]);
    }
}