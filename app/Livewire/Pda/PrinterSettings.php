<?php

namespace App\Livewire\Pda;

use App\Livewire\PdaPage;
use App\Models\Device;
use App\Models\Printer;
use App\Services\DocumentWorkflow;
use App\Services\PriceTagPrinter;

/**
 * PDA "Printer": this device's own printer (type LAN with an IP, or Bluetooth picked from the paired devices),
 * or one of the printers the administrator keeps. LAN prints from the server, Bluetooth prints from the phone.
 */
class PrinterSettings extends PdaPage
{
    public string $kind = 'lan';

    public string $host = '';

    public string $port = '9100';

    public string $bt_name = '';

    public string $bt_address = '';

    public function mount(): void
    {
        $own = $this->ownPrinter();

        if ($own) {
            $this->kind = $own->kind;
            $this->host = (string) $own->host;
            $this->port = (string) ($own->port ?: 9100);
            $this->bt_name = (string) $own->bt_name;
            $this->bt_address = (string) $own->bt_address;
        }
    }

    private function ownPrinter(): ?Printer
    {
        $device = Device::current();

        return $device ? Printer::where('owner_device_id', $device->id)->first() : null;
    }

    /** Save this device's own printer and make it the device's printer. */
    public function saveOwn(): void
    {
        $device = Device::current();
        abort_unless($device, 404);

        $rules = ['kind' => 'required|in:lan,bluetooth'];
        $this->kind === 'lan'
            ? $rules += ['host' => ['required', 'max:100', 'regex:/^[A-Za-z0-9.\-]+$/'], 'port' => 'required|integer|between:1,65535']
            : $rules += ['bt_name' => 'required|max:100', 'bt_address' => 'nullable|max:100'];

        $this->validate($rules, [
            'required' => ':attribute kiritilishi shart.', 'regex' => ':attribute noto‘g‘ri.', 'integer' => ':attribute son bo‘lishi kerak.', 'between' => ':attribute noto‘g‘ri.',
        ], ['host' => 'IP manzil', 'port' => 'Port', 'bt_name' => 'Printer nomi']);

        $lan = $this->kind === 'lan';
        $printer = Printer::updateOrCreate(['owner_device_id' => $device->id], [
            'name' => ($device->name ?: 'PDA').' printeri',
            'kind' => $this->kind,
            'host' => $lan ? $this->host : '',
            'port' => $lan ? (int) $this->port : 9100,
            'bt_name' => $lan ? null : $this->bt_name,
            'bt_address' => $lan ? null : ($this->bt_address ?: null),
            'is_active' => true,
        ]);

        $device->update(['printer_id' => $printer->id]);
        $device->unsetRelation('printer');
        $this->ok('Printer saqlandi.');
    }

    public function choose(?int $id): void
    {
        $device = Device::current();
        abort_unless($device, 404);

        if ($id) {
            Printer::active()->shared()->findOrFail($id);
        }

        $device->update(['printer_id' => $id ?: null]);
        $device->unsetRelation('printer');
        $this->ok($id ? 'Printer tanlandi.' : 'Printer tanlovi olib tashlandi (umumiy yorliq printeri ishlatiladi).');
    }

    public function testPrint(DocumentWorkflow $workflow, PriceTagPrinter $printing): void
    {
        $printer = $workflow->printerFor(Device::current());

        if (! $printer) {
            $this->fail('Printer tanlanmagan.');

            return;
        }

        if ($printer->isBluetooth()) {
            $this->dispatch('bt-print', zpl: $printing->testLabel($printer), printer: $printer->name, address: (string) $printer->bt_address);
            $this->ok("Sinov yorlig‘i Bluetooth printerga yuborilmoqda ({$printer->name}).");

            return;
        }

        try {
            $printing->send($printer, $printing->testLabel($printer));
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->ok("Sinov yorlig‘i yuborildi: {$printer->name} ({$printer->address}).");
    }

    public function render()
    {
        $device = Device::current();

        return $this->page('livewire.pda.printer', [
            'device' => $device,
            'printers' => Printer::active()->shared()->orderBy('name')->get(),
            'current' => app(DocumentWorkflow::class)->printerFor($device),
            'own' => $this->ownPrinter(),
        ]);
    }
}
