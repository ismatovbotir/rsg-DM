<?php

namespace App\Livewire\Pda\Docs;

use App\Livewire\Pda\Concerns\AuthorizesDocuments;
use App\Livewire\Pda\Concerns\HasDocumentHeader;
use App\Livewire\PdaPage;
use App\Models\Device;
use App\Models\Document;
use App\Models\DocumentLine;
use App\Models\Item;
use App\Models\PriceCheck;
use App\Services\DocumentWorkflow;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;

/**
 * Step 2 of a document: the items screen with the scan bar, inline qty / price editing, price check verdicts,
 * and the burger menu that holds the header with save / exit / cancel / complete.
 */
class Show extends PdaPage
{
    use AuthorizesDocuments, HasDocumentHeader;

    #[Locked]
    public int $recordId;

    public string $code = '';

    public function mount(int $record): void
    {
        $document = Document::findOrFail($record);
        $this->authorizeView($document);
        $this->recordId = $document->id;
        $this->fillHeader($document->type, $document->headerValues());
    }

    private function document(bool $forEdit = false): Document
    {
        $document = Document::findOrFail($this->recordId);
        $forEdit ? $this->authorizeEdit($document) : $this->authorizeView($document);

        return $document;
    }

    /** Scan bar: a scanner types the code and presses Enter. Every item goes through the entry page. */
    public function scan()
    {
        $document = $this->document(true);
        $code = trim($this->code);
        $this->code = '';

        if ($code === '') {
            return;
        }

        $resolved = Item::resolve($code);

        if (! $resolved) {
            $this->fail("Tovar topilmadi: {$code}");

            return;
        }

        [$item, $qty] = $resolved; // a pack barcode books its whole pack quantity

        return $this->redirect(route('docs.entry', ['record' => $document->id, 'item' => $item->id, 'qty' => $qty + 0]));
    }

    public function updateQty(int $lineId, string $value): void
    {
        $line = $this->line($lineId);
        $document = $this->document(true);
        abort_unless($document->canEditQty(), 403);

        validator(['qty' => $value], ['qty' => 'required|numeric|min:0.001|max:99999999'], [], ['qty' => 'Miqdor'])->validate();
        $line->update(['qty' => $value]);
    }

    public function updatePrice(int $lineId, string $value): void
    {
        $line = $this->line($lineId);
        $document = $this->document(true);
        abort_unless($document->canEditPrice(), 403);

        validator(['price' => $value], ['price' => 'required|numeric|min:0|max:9999999999'], [], ['price' => 'Narx'])->validate();
        $line->update(['price' => $value]);
    }

    /** Price check verdict for one line: price is correct, or queue a new price tag. */
    public function setCheck(int $lineId, string $action): void
    {
        $line = $this->line($lineId);
        abort_unless($this->document(true)->isPriceCheck(), 404);
        abort_unless(in_array($action, [PriceCheck::CONFIRMED, PriceCheck::PRINT], true), 422);

        $line->update(['check_action' => $action]);
    }

    public function removeLine(int $lineId): void
    {
        $this->line($lineId);
        $this->document(true);
        DocumentLine::where('document_id', $this->recordId)->whereKey($lineId)->delete();
    }

    private function line(int $lineId): DocumentLine
    {
        return DocumentLine::where('document_id', $this->recordId)->findOrFail($lineId);
    }

    /** Burger menu: save the header and leave, or save and complete. */
    public function saveHeader(string $action = 'draft')
    {
        $document = $this->document(true);

        try {
            $document->update($this->validatedHeader($document->type, editing: true));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('open-drawer'); // show the form again
            throw $e;
        }

        if ($action === 'complete') {
            $error = app(DocumentWorkflow::class)->finish($document->fresh(), Device::current());

            if ($error) {
                $this->addError('lines', $error);
                $this->dispatch('close-drawer');

                return;
            }

            session()->flash('status', "{$document->number} yakunlandi.");

            return $this->redirect(route('docs.index', $document->type));
        }

        session()->flash('status', "{$document->number}: qoralama saqlandi.");

        return $this->redirect(route('docs.index', $document->type));
    }

    /** Cancel = discard the draft and its lines. */
    public function cancelDocument()
    {
        $document = $this->document(true);
        $document->delete();

        session()->flash('status', "{$document->number} bekor qilindi.");

        return $this->redirect(route('docs.index', $document->type));
    }

    /** Print button of a completed price check: this PDA's printer, else the price-tag printer. */
    public function print(DocumentWorkflow $workflow): void
    {
        $document = $this->document();
        abort_unless($document->isPriceCheck() && ! $document->isDraft(), 404);

        [$message, $isError] = $workflow->printDocument($document, Device::current());
        $isError ? $this->fail($message) : $this->ok($message);
    }

    public function render()
    {
        $document = $this->document();

        return $this->page('livewire.pda.docs.show', [
            'doc' => $document->load('lines.item', 'counterparty', 'warehouse', 'warehouseTo', 'priceType'),
            'fields' => $document->headerFields(),
            'editable' => Arr::except($document->headerFields(), Document::LOCKED_AFTER_CREATE),
        ] + $this->headerViewData());
    }
}