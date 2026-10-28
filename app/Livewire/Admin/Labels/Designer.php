<?php

namespace App\Livewire\Admin\Labels;

use App\Livewire\AdminPage;
use App\Models\Item;
use App\Models\LabelTemplate;
use App\Models\Printer;
use App\Services\PriceTagPrinter;
use App\Services\ZplLabel;
use Livewire\Attributes\Locked;

/** Price tag designer: place and rotate elements on a label, rotate the label, see the ZPL, send a test print. */
class Designer extends AdminPage
{
    #[Locked]
    public ?int $recordId = null;

    public string $name = '';

    public string $width_mm = '58';

    public string $height_mm = '40';

    public string $rotation = '0';

    public string $dpi = '203';

    public string $font = '0';

    /** @var list<array<string, mixed>> */
    public array $elements = [];

    public ?string $printer_id = null;

    public ?string $item_id = null;

    public function mount(?int $record = null): void
    {
        $label = $record ? LabelTemplate::findOrFail($record) : null;
        $this->recordId = $label?->id;
        $this->elements = $label?->elements ?? LabelTemplate::starter();

        if ($label) {
            $this->fill([
                'name' => $label->name, 'width_mm' => (string) ($label->width_mm + 0), 'height_mm' => (string) ($label->height_mm + 0),
                'rotation' => (string) $label->rotation, 'dpi' => (string) $label->dpi, 'font' => $label->font,
            ]);
        }
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string|max:100',
            'width_mm' => 'required|numeric|min:15|max:200',
            'height_mm' => 'required|numeric|min:10|max:300',
            'rotation' => 'required|in:0,90,180,270',
            'dpi' => 'required|in:203,300,600',
            'font' => ['required', 'string', 'max:60', 'regex:/^(0|[A-Za-z]:[\w.\-]+)$/'],
            'elements' => 'array',
        ], ['font.regex' => 'Shrift «0» yoki printerdagi fayl nomi bo‘lishi kerak (masalan E:ARIAL.TTF).'], ['name' => 'Nomi', 'font' => 'Shrift']);

        $data['elements'] = LabelTemplate::sanitize($data['elements']);
        $data['rotation'] = (int) $data['rotation'];
        $data['dpi'] = (int) $data['dpi'];

        if ($this->recordId) {
            LabelTemplate::findOrFail($this->recordId)->update($data);
            $this->elements = $data['elements'];
            $this->ok('Dizayn saqlandi.');

            return;
        }

        $label = LabelTemplate::create($data);
        LabelTemplate::count() === 1 && $label->makeDefault();
        session()->flash('status', 'Dizayn saqlandi.');

        return $this->redirect(route('admin.labels.edit', $label->id));
    }

    /** Send the saved design with a real item to a printer. */
    public function test(PriceTagPrinter $printing, ZplLabel $zpl): void
    {
        $this->validate(['printer_id' => 'required|exists:printers,id', 'item_id' => 'required|exists:items,id']);

        try {
            $printing->send(Printer::findOrFail($this->printer_id), $zpl->render(LabelTemplate::findOrFail($this->recordId), Item::findOrFail($this->item_id)));
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->ok('Sinov yorlig‘i yuborildi.');
    }

    public function render()
    {
        $samples = Item::active()->orderBy('name')->limit(30)->get();
        $sample = $samples->first() ?? new Item(['name' => 'Namuna tovar', 'code' => '1001', 'barcode' => '4780000000011', 'unit' => 'dona', 'price' => 12500]);

        // ZPL of the design as it is right now (unsaved changes included), for the first sample item.
        $current = new LabelTemplate([
            'width_mm' => (float) $this->width_mm ?: 58, 'height_mm' => (float) $this->height_mm ?: 40, 'rotation' => (int) $this->rotation,
            'dpi' => in_array((int) $this->dpi, [203, 300, 600], true) ? (int) $this->dpi : 203, 'font' => $this->font,
            'elements' => LabelTemplate::sanitize($this->elements),
        ]);

        return $this->page('livewire.admin.labels.designer', [
            'samples' => $samples,
            'printers' => Printer::active()->orderBy('name')->get(),
            'sampleData' => ($samples->isEmpty() ? collect([$sample]) : $samples)->map(fn ($i) => [
                'id' => $i->id, 'name' => $i->name, 'price' => (float) $i->price, 'code' => (string) $i->code,
                'article' => (string) $i->article, 'barcode' => (string) ($i->barcode ?: $i->code), 'unit' => (string) $i->unit,
            ])->values(),
            'zpl' => $current->elements ? app(ZplLabel::class)->render($current, $sample) : null,
        ]);
    }
}