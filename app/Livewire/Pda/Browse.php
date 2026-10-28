<?php

namespace App\Livewire\Pda;

use App\Livewire\Pda\Concerns\AuthorizesDocuments;
use App\Livewire\PdaPage;
use App\Models\Document;
use App\Models\Item;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

/**
 * "Tanlash": a full page item picker (not a modal: on a small PDA a modal competes with the on-screen keyboard and
 * the hardware Back key). Opened from a document it continues to the entry page, from the directory it shows the item.
 */
class Browse extends PdaPage
{
    use AuthorizesDocuments;

    /** 0 = the directory */
    #[Locked]
    public int $recordId = 0;

    #[Url]
    public string $q = '';

    public function mount(?int $record = null): void
    {
        if ($record) {
            $this->authorizeEdit(Document::findOrFail($record));
            $this->recordId = $record;
        }
    }

    public function pick(int $itemId)
    {
        $item = Item::active()->findOrFail($itemId);

        if ($this->recordId) {
            $document = Document::findOrFail($this->recordId);
            $this->authorizeEdit($document);

            return $this->redirect(route('docs.entry', ['record' => $document->id, 'item' => $item->id]));
        }

        return $this->redirect(route('directory', ['q' => $item->code]));
    }

    public function render()
    {
        $q = trim($this->q);
        $document = $this->recordId ? Document::with('lines')->find($this->recordId) : null;

        return $this->page('livewire.pda.browse', [
            'title' => $document ? 'Tovar tanlash' : 'Ma’lumotnomalar — tanlash',
            'back' => $document ? route('docs.show', $document->id) : route('directory'),
            'items' => Item::active()->withSum('stocks', 'qty')->search($q)->orderBy('name')->limit(50)->get(),
            'added' => $document ? $document->lines->groupBy('item_id')->map->sum('qty') : collect(),
        ]);
    }
}