<?php

namespace App\Livewire\Admin\Printers;

use App\Livewire\AdminPage;
use App\Models\Device;
use App\Models\Printer;
use App\Services\PriceTagPrinter;

/** LAN (ethernet) label printers. One of them prints the price tags; PDAs can be assigned their own. */
class Index extends AdminPage
{
    public function test(int $id, PriceTagPrinter $printing): void
    {
        $printer = Printer::findOrFail($id);

        try {
            $printing->send($printer, $printing->testLabel($printer));
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->ok("Sinov yorlig‘i yuborildi: {$printer->address}");
    }

    public function delete(int $id): void
    {
        Device::where('printer_id', $id)->update(['printer_id' => null]);
        Printer::findOrFail($id)->delete();
        $this->ok('Printer o‘chirildi.');
    }

    public function render()
    {
        return $this->page('livewire.admin.printers.index', [
            'printers' => Printer::orderBy('name')->get(),
            'deviceCounts' => Device::whereNotNull('printer_id')->selectRaw('printer_id, count(*) as n')->groupBy('printer_id')->pluck('n', 'printer_id'),
        ]);
    }
}