<?php

namespace App\Livewire\Admin\Checkers;

use App\Livewire\AdminPage;
use App\Models\CheckerTheme;
use App\Models\PriceChecker;
use App\Models\PriceType;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Livewire\Attributes\Locked;

/** Create / edit a price checker. Creating generates its token; the QR tab shows it and can replace it. */
class Form extends AdminPage
{
    #[Locked]
    public ?int $checkerId = null;

    public string $name = '';

    public ?string $price_type_id = null;

    public ?string $theme_id = null;

    public bool $is_active = true;

    public function mount(?PriceChecker $checker = null): void
    {
        if ($checker?->exists) {
            $this->checkerId = $checker->id;
            $this->name = $checker->name;
            $this->price_type_id = $checker->price_type_id ? (string) $checker->price_type_id : null;
            $this->theme_id = $checker->theme_id ? (string) $checker->theme_id : null;
            $this->is_active = $checker->is_active;
        }
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string|max:100',
            'price_type_id' => 'nullable|exists:price_types,id',
            'theme_id' => 'nullable|exists:checker_themes,id',
            'is_active' => 'boolean',
        ]);
        $data['price_type_id'] = $data['price_type_id'] ?: null;
        $data['theme_id'] = $data['theme_id'] ?: null;

        if ($this->checkerId) {
            PriceChecker::findOrFail($this->checkerId)->update($data);
            $this->ok('Narx tekshirgich saqlandi.');

            return;
        }

        $checker = PriceChecker::create($data);
        $checker->regenerateToken();
        session()->flash('status', 'Narx tekshirgich qo‘shildi. Token yaratildi — QR kodni terminalda skanerlang.');

        return $this->redirect(route('admin.checkers.edit', $checker).'#qr');
    }

    /** New token + QR code; terminals that used the old token are signed out. */
    public function regenerate(): void
    {
        PriceChecker::findOrFail($this->checkerId)->regenerateToken();
        $this->ok('Yangi token yaratildi. Eski QR kod endi ishlamaydi.');
    }

    public function render()
    {
        $checker = $this->checkerId ? PriceChecker::find($this->checkerId) : null;
        $url = $checker?->activation_url;

        return $this->page('livewire.admin.checkers.form', [
            'checker' => $checker,
            'priceTypes' => PriceType::active()->orderBy('name')->get(),
            'themes' => CheckerTheme::orderBy('name')->get(),
            'url' => $url,
            'qr' => $url ? (new Writer(new ImageRenderer(new RendererStyle(320, 2), new SvgImageBackEnd())))->writeString($url) : null,
        ]);
    }
}
