<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PriceCheck;
use App\Models\Printer;
use RuntimeException;

/** Sends ZPL labels to a LAN printer over raw TCP (port 9100) and prints the queued price tags. */
class PriceTagPrinter
{
    /** Send raw data; throws RuntimeException with a readable message when the printer is unreachable. */
    public function send(Printer $printer, string $data): void
    {
        $socket = @fsockopen($printer->host, $printer->port, $errno, $error, 3);

        if (! $socket) {
            throw new RuntimeException("Printerga ulanib bo‘lmadi ({$printer->address}): {$error}");
        }

        stream_set_timeout($socket, 3);
        $written = @fwrite($socket, $data);
        fclose($socket);

        if ($written === false || $written < strlen($data)) {
            throw new RuntimeException("Printerga to‘liq yuborilmadi ({$printer->address}).");
        }
    }

    /** One 203 dpi label: name, barcode and the current price of the item. */
    public function label(Printer $printer, Item $item): string
    {
        if ($design = \App\Models\LabelTemplate::default()) {
            return app(ZplLabel::class)->render($design, $item);
        }

        $w = $printer->label_width_mm * 8; // dots at 203 dpi
        $h = $printer->label_height_mm * 8;
        $name = $this->clean(mb_substr($item->name, 0, 60));
        $price = number_format((float) $item->price, 0, '.', ' ');
        $code = $this->clean((string) ($item->barcode ?: $item->code));

        return "^XA^CI28^PW{$w}^LL{$h}\n"
            ."^FO16,16^FB".($w - 32).",2,0,L,0^A0N,28,28^FD{$name}^FS\n"
            ."^FO16,".(int) ($h * 0.38)."^A0N,64,64^FD{$price}^FS\n"
            ."^FO16,".(int) ($h * 0.70)."^BCN,50,Y,N,N^FD{$code}^FS\n"
            .'^XZ';
    }

    /** Print every queued tag on the printer and mark them printed. Returns how many were sent. */
    public function printQueued(Printer $printer): int
    {
        $checks = PriceCheck::queued()->with('item')->oldest()->get();

        if ($checks->isEmpty()) {
            return 0;
        }

        $this->send($printer, $checks->map(fn ($c) => $this->label($printer, $c->item))->implode("\n"));
        PriceCheck::whereIn('id', $checks->modelKeys())->update(['printed_at' => now()]);
        $this->syncDocumentStatus();

        return $checks->count();
    }

    /**
     * Print the tags of one price check document (its "print" lines) and mark the document printed.
     * Tags of the same items waiting in the queue are marked printed too, so nothing prints twice.
     */
    public function printDocument(\App\Models\Document $document, Printer $printer): int
    {
        $items = $document->lines()->where('check_action', PriceCheck::PRINT)->with('item')->get()->pluck('item');

        if ($items->isEmpty()) {
            return 0;
        }

        $this->send($printer, $items->map(fn ($item) => $this->label($printer, $item))->implode("\n"));

        PriceCheck::queued()->whereIn('item_id', $items->pluck('id'))->update(['printed_at' => now()]);
        $document->update(['printed_at' => now()]);

        return $items->count();
    }

    /** After the queue was printed or marked printed: documents with no tag left waiting count as printed. */
    public function syncDocumentStatus(): void
    {
        \App\Models\Document::where('type', 'pricecheck')->where('print_count', '>', 0)->whereNull('printed_at')->each(function (\App\Models\Document $doc) {
            $waiting = PriceCheck::queued()->where('document_id', $doc->id)->exists();
            $waiting || $doc->update(['printed_at' => now()]);
        });
    }

    public function testLabel(Printer $printer): string
    {
        $w = $printer->label_width_mm * 8;

        return "^XA^CI28^PW{$w}^FO16,24^A0N,36,36^FDTest: {$this->clean($printer->name)}^FS^FO16,80^A0N,28,28^FD".now()->format('d.m.Y H:i')."^FS^XZ";
    }

    /** ZPL field data must not contain the control characters ^ and ~. */
    private function clean(string $text): string
    {
        return str_replace(['^', '~', "\n", "\r"], ' ', $text);
    }
}
