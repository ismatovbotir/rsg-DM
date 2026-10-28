<?php

namespace App\Livewire\Admin\Printers;

use App\Livewire\AdminPage;
use App\Models\Printer;
use Livewire\Attributes\Locked;

class Form extends AdminPage
{
    #[Locked]
    public ?int $recordId = null;

    public string $name = '';

    public string $host = '';

    public string $port = '9100';

    public string $label_width_mm = '58';

    public string $label_height_mm = '40';

    public bool $is_price_tag = false;

    public bool $is_active = true;

    public function mount(?int $record = null): void
    {
        if ($record) {
            $p = Printer::findOrFail($record);
            $this->recordId = $p->id;
            $this->fill([
                'name' => $p->name, 'host' => $p->host, 'port' => (string) $p->port, 'label_width_mm' => (string) $p->label_width_mm,
                'label_height_mm' => (string) $p->label_height_mm, 'is_price_tag' => $p->is_price_tag, 'is_active' => $p->is_active,
            ]);
        }
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string|max:100',
            'host' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'], // IP or LAN host name only
            'port' => 'required|integer|min:1|max:65535',
            'label_width_mm' => 'required|integer|min:20|max:200',
            'label_height_mm' => 'required|integer|min:15|max:300',
            'is_price_tag' => 'boolean',
            'is_active' => 'boolean',
        ], ['host.regex' => 'Manzil faqat IP yoki tarmoq nomi bo‘lishi kerak.'], ['name' => 'Nomi', 'host' => 'Manzil', 'port' => 'Port']);

        $priceTag = $data['is_price_tag'];
        $data['is_price_tag'] = false; // set through makePriceTag() so only one printer holds it

        $printer = $this->recordId ? tap(Printer::findOrFail($this->recordId))->update($data) : Printer::create($data);
        $priceTag && $printer->makePriceTag();

        session()->flash('status', $this->recordId ? 'Printer saqlandi.' : 'Printer qo‘shildi.');

        return $this->redirect(route('admin.printers.index'));
    }

    public function render()
    {
        return $this->page('livewire.admin.printers.form');
    }
}