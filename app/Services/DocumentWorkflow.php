<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Document;
use App\Models\PriceCheck;
use App\Models\Printer;
use App\Models\Task;

/** What happens to a document when it is completed, and the price tag printing of a completed price check. */
class DocumentWorkflow
{
    /**
     * Complete a draft. Returns an error message, or null when the document was completed.
     * Price check: every verdict becomes a PriceCheck row ("print" ones wait in the admin tag queue) and the tags are printed.
     */
    public function finish(Document $document, ?Device $device = null): ?string
    {
        if (! $document->lines()->exists()) {
            return 'Hujjat bo‘sh: avval tovar skanerlang.';
        }

        $document->update([
            'status' => Document::COMPLETED,
            'completed_at' => now(),
            'onec_status' => $document->syncs() ? Document::SYNC_PENDING : Document::SYNC_NONE,
            'print_count' => $document->isPriceCheck() ? $document->lines()->where('check_action', PriceCheck::PRINT)->count() : 0,
        ]);

        if ($document->isPriceCheck()) {
            foreach ($document->lines as $line) {
                $alreadyQueued = $line->check_action === PriceCheck::PRINT
                    && PriceCheck::queued()->where('item_id', $line->item_id)->exists();

                if (! $alreadyQueued) {
                    PriceCheck::create([
                        'item_id' => $line->item_id, 'user_id' => $document->user_id, 'document_id' => $document->id,
                        'price' => $line->price, 'action' => $line->check_action ?? PriceCheck::CONFIRMED,
                    ]);
                }
            }

            $this->printTagsQuietly($document, $device);
        }

        // A task is done once none of its linked documents is still a draft.
        foreach ($document->tasks as $task) {
            if (! $task->documents()->where('status', Document::DRAFT)->exists()) {
                $task->update(['status' => Task::DONE, 'completed_at' => now()]);
            }
        }

        return null;
    }

    /** The PDA's own printer when it has one, else the price-tag printer. */
    public function printerFor(?Device $device): ?Printer
    {
        return $device?->printer?->is_active ? $device->printer : Printer::priceTag();
    }

    /** Best effort: if the printer is down the tags simply stay in the admin print queue. */
    private function printTagsQuietly(Document $document, ?Device $device): void
    {
        $printer = $this->printerFor($device);

        if (! $printer || $printer->isBluetooth()) {
            return; // a Bluetooth printer is driven from the phone: the Chop etish button hands the labels over
        }

        try {
            app(PriceTagPrinter::class)->printDocument($document->fresh(), $printer);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Print button of a completed price check. Returns [message, isError]. */
    public function printDocument(Document $document, ?Device $device): array
    {
        $printer = $this->printerFor($device);

        if (! $printer) {
            return ['Printer tanlanmagan: administrator «Printerlar» bo‘limida sozlashi kerak.', true];
        }

        try {
            $count = app(PriceTagPrinter::class)->printDocument($document, $printer);
        } catch (\Throwable $e) {
            return [$e->getMessage(), true];
        }

        return [$count ? __(':count ta yorliq :printer printeriga yuborildi.', ['count' => $count, 'printer' => $printer->name]) : __('Chop etiladigan yorliq yo‘q.'), false];
    }
}
