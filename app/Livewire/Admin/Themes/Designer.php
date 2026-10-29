<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\AdminPage;
use App\Models\CheckerTheme;
use App\Models\Item;
use App\Models\PriceType;
use App\Support\CheckerDesign;
use App\Support\CheckerItem;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

/**
 * Editor of a price checker theme with a live preview: every change shows at once, "Saqlash" publishes it
 * (terminals using the theme reload within a few seconds). The preview shows the idle, result, not-found and pairing (auth) screens.
 */
class Designer extends AdminPage
{
    use WithFileUploads;

    #[Locked]
    public ?int $themeId = null;

    public string $name = '';

    public array $design = [];

    public string $state = 'result'; // preview: idle | result | error | auth

    public string $sample = '';

    public bool $portrait = false; // preview of a terminal that is physically upright

    public $bgUpload = null;

    public $logoUpload = null;

    /** @var array<int, mixed> idle-screen banner pictures being uploaded */
    public array $bannerUploads = [];

    public function mount(?int $record = null): void
    {
        $theme = $record ? CheckerTheme::findOrFail($record) : null;
        $this->themeId = $theme?->id;
        $this->name = $theme?->name ?? '';
        $this->design = $theme?->screen ?? CheckerDesign::merge(null);
    }

    public function updatedBgUpload(): void
    {
        $this->design['bg_image'] = $this->storeImage($this->bgUpload);
        $this->bgUpload = null;
    }

    public function updatedLogoUpload(): void
    {
        $this->design['logo'] = $this->storeImage($this->logoUpload);
        $this->logoUpload = null;
    }

    /** Idle screen slideshow: up to 8 pictures. */
    public function updatedBannerUploads(): void
    {
        foreach ($this->bannerUploads as $file) {
            if (count($this->design['banners'] ?? []) >= 8) {
                break;
            }
            $this->design['banners'][] = $this->storeImage($file);
        }
        $this->bannerUploads = [];
    }

    public function removeBanner(int $index): void
    {
        unset($this->design['banners'][$index]);
        $this->design['banners'] = array_values($this->design['banners'] ?? []);
    }

    private function storeImage($file): string
    {
        validator(['f' => $file], ['f' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096'], [], ['f' => 'Rasm'])->validate();

        return $file->storeAs('checker', Str::random(24).'.'.$file->extension(), 'uploads');
    }

    public function removeImage(string $key): void
    {
        if (in_array($key, ['bg_image', 'logo'], true)) {
            $this->design[$key] = '';
        }
    }

    public function save()
    {
        $this->validate(['name' => 'required|string|max:100'], [], ['name' => 'Nomi']);
        $design = CheckerDesign::sanitize($this->design);

        if ($this->themeId) {
            CheckerTheme::findOrFail($this->themeId)->update(['name' => $this->name, 'design' => $design]);
            $this->design = $design;
            $this->ok('Dizayn saqlandi. Terminallar bir necha soniyada yangilanadi.');

            return;
        }

        $theme = CheckerTheme::create(['name' => $this->name, 'design' => $design, 'is_default' => ! CheckerTheme::exists()]);
        session()->flash('status', 'Dizayn saqlandi.');

        return $this->redirect(route('admin.themes.edit', $theme->id));
    }

    /** One of the 20 ready colour sets. */
    public function applyPalette(string $key): void
    {
        if ($colors = CheckerDesign::palette($key)) {
            $this->design = array_replace($this->design, $colors);
        }
    }

    public function resetDefaults(): void
    {
        $this->design = CheckerDesign::merge(null);
        $this->notice = null;
    }

    /** Take another theme as a starting point. */
    public function copyFrom(int $id): void
    {
        $this->design = CheckerTheme::findOrFail($id)->screen;
        $this->notice = null;
    }

    public function updated($property): void
    {
        if (! str_starts_with($property, 'bgUpload') && ! str_starts_with($property, 'logoUpload') && ! str_starts_with($property, 'bannerUploads')) {
            $this->notice = null;
        }
    }

    public function render()
    {
        $safe = CheckerDesign::sanitize($this->design); // the preview only ever gets validated values
        $items = Item::active()->with('group')->orderBy('name')->limit(25)->get();
        $picked = $items->firstWhere('id', (int) $this->sample) ?? $items->first();

        $item = $picked
            ? CheckerItem::payload($picked, $safe, null)
            : ['found' => true, 'name' => 'Namuna tovar nomi', 'unit' => 'dona', 'code' => '1001', 'article' => 'ART-1', 'barcode' => '4780000000011', 'group' => 'Guruh', 'image' => '', 'price_text' => '12 500', 'old_price_text' => '', 'promo' => false, 'second_price_text' => '', 'stock' => ['state' => '', 'text' => '']];

        // sample values for the optional blocks, so the admin sees them even when the item has no promo / stock
        if ($safe['old_price_type_id'] && $item['old_price_text'] === '') {
            $item['old_price_text'] = CheckerItem::money(((float) preg_replace('/\D/', '', $item['price_text'])) * 1.2);
        }
        if ($safe['second_price_type_id'] && $item['second_price_text'] === '') {
            $item['second_price_text'] = CheckerItem::money(((float) preg_replace('/\D/', '', $item['price_text'])) * 0.95);
        }
        if ($safe['show_stock'] && $item['stock']['state'] === '') {
            $item['stock'] = ['state' => 'ok', 'text' => 'Mavjud'];
        }

        return $this->page('livewire.admin.themes.designer', [
            'theme' => $this->themeId ? CheckerTheme::find($this->themeId) : null,
            'safe' => $safe,
            'items' => $items,
            'others' => CheckerTheme::where('id', '!=', $this->themeId ?? 0)->orderBy('name')->get(),
            'priceTypes' => PriceType::active()->orderBy('name')->get(),
            'item' => $item,
        ]);
    }
}
