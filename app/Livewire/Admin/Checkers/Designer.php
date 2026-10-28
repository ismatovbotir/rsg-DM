<?php

namespace App\Livewire\Admin\Checkers;

use App\Models\Item;
use App\Models\PriceChecker;
use App\Support\CheckerDesign;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Live editor of the price checker screen: every change is shown in the preview at once, "Saqlash" publishes it to the terminal. */
class Designer extends Component
{
    #[Locked]
    public int $checkerId;

    public array $design = [];

    public string $state = 'result'; // preview: idle | result | error

    public string $sample = '';

    public bool $portrait = false; // preview of a terminal that is physically upright

    public ?string $notice = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function mount(int $checkerId): void
    {
        $this->checkerId = $checkerId;
        $this->design = PriceChecker::findOrFail($checkerId)->screen;
    }

    public function save(): void
    {
        $checker = PriceChecker::findOrFail($this->checkerId);
        $checker->update(['design' => CheckerDesign::sanitize($this->design)]);
        $this->design = $checker->screen;
        $this->notice = 'Dizayn saqlandi. Terminal bir necha soniyada yangilanadi.';
    }

    public function resetDefaults(): void
    {
        $this->design = CheckerDesign::DEFAULTS;
        $this->notice = null;
    }

    /** Take the design of another checker as a starting point. */
    public function copyFrom(int $id): void
    {
        $this->design = PriceChecker::findOrFail($id)->screen;
        $this->notice = null;
    }

    public function updated(): void
    {
        $this->notice = null;
    }

    public function render()
    {
        $checker = PriceChecker::findOrFail($this->checkerId);
        $items = Item::active()->orderBy('name')->limit(25)->get();
        $picked = $items->firstWhere('id', (int) $this->sample) ?? $items->first();
        $price = $picked ? (float) $picked->priceFor($checker->price_type_id) : 12500;

        return view('livewire.admin.checkers.designer', [
            'checker' => $checker,
            'safe' => CheckerDesign::sanitize($this->design), // the preview only ever gets validated values
            'items' => $items,
            'others' => PriceChecker::where('id', '!=', $checker->id)->orderBy('name')->get(),
            'item' => [
                'name' => $picked?->name ?? 'Namuna tovar nomi',
                'price_text' => number_format($price, 0, '.', ' '),
                'unit' => $picked?->unit ?? 'dona',
                'code' => (string) ($picked?->code ?? '1001'),
            ],
        ]);
    }
}
