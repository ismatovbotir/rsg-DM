<?php

namespace App\Livewire\Admin\Documents;

use App\Livewire\AdminPage;
use App\Models\Document;
use App\Models\Printer;
use App\Services\OneCClient;
use App\Services\PriceTagPrinter;
use Livewire\Attributes\Locked;

class Show extends AdminPage
{
    #[Locked]
    public int $recordId;

    public function mount(int $record): void
    {
        $document = Document::findOrFail($record);
        $types = auth()->user()->adminDocumentTypes();
        abort_unless($types === null || in_array($document->type, $types, true), 403);
        $this->recordId = $document->id;
    }

    /** Print the tags of a price check on the price-tag printer. */
    public function print(PriceTagPrinter $printing): void
    {
        $document = Document::findOrFail($this->recordId);
        abort_unless($document->isPriceCheck() && ! $document->isDraft(), 404);

        $printer = Printer::priceTag();

        if (! $printer) {
            $this->fail('Narx yorliqlari printeri tanlanmagan (Printerlar bo‘limi).');

            return;
        }

        try {
            $count = $printing->printDocument($document, $printer);
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->ok($count ? "{$count} ta yorliq {$printer->name} printeriga yuborildi." : 'Chop etiladigan yorliq yo‘q.');
    }

    public function sync(OneCClient $onec): void
    {
        $document = Document::findOrFail($this->recordId);

        if (! $document->syncs()) {
            $this->fail('Bu hujjat turi Adines ga yuborilmaydi.');

            return;
        }

        if ($document->isDraft()) {
            $this->fail('Qoralamani Adines ga yuborib bo‘lmaydi.');

            return;
        }

        try {
            $ok = $onec->pushDocument($document);
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $ok ? $this->ok('Adines ga yuborildi.') : $this->fail('Xato: '.$document->fresh()->onec_error);
    }

    public function render()
    {
        return $this->page('livewire.admin.documents.show', ['doc' => Document::with('lines.item', 'user')->findOrFail($this->recordId)]);
    }
}