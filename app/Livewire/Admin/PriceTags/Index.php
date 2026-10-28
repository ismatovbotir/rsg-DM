<?php

namespace App\Livewire\Admin\PriceTags;

use App\Livewire\AdminPage;
use App\Models\PriceCheck;
use App\Models\Printer;
use App\Services\PriceTagPrinter;

/** Queue of price tags that operators asked to print. */
class Index extends AdminPage
{
    /** Send the queue to the price-tag printer over the LAN. */
    public function send(PriceTagPrinter $printing): void
    {
        $printer = Printer::priceTag();

        if (! $printer) {
            $this->fail('Narx yorliqlari printeri tanlanmagan (Printerlar bo‘limi).');

            return;
        }

        try {
            $count = $printing->printQueued($printer);
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->ok("{$count} ta yorliq {$printer->name} printeriga yuborildi.");
    }

    public function markPrinted(PriceTagPrinter $printing): void
    {
        $count = PriceCheck::queued()->update(['printed_at' => now()]);
        $printing->syncDocumentStatus();

        $this->ok("{$count} ta yorliq chop etilgan deb belgilandi.");
    }

    public function remove(int $id): void
    {
        PriceCheck::findOrFail($id)->delete();
        $this->ok('Ro‘yxatdan olib tashlandi.');
    }

    public function render()
    {
        return $this->page('livewire.admin.pricetags.index', [
            'queue' => PriceCheck::queued()->with('item', 'user')->latest()->get(),
            'printer' => Printer::priceTag(),
        ]);
    }
}