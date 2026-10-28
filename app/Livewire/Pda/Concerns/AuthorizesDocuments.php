<?php

namespace App\Livewire\Pda\Concerns;

use App\Models\Document;
use App\Models\Item;

/** Who may see / change which document (the same rules the controller had). */
trait AuthorizesDocuments
{
    protected function assertType(string $type): void
    {
        abort_unless(array_key_exists($type, Document::types()), 404);
        abort_unless(auth()->user()->canAccess($type), 403, 'Bu bo‘limga ruxsat yo‘q.');
    }

    protected function authorizeView(Document $document): void
    {
        abort_unless(auth()->user()->isAdmin() || $document->user_id === auth()->id(), 403);
        abort_unless(auth()->user()->canAccess($document->type), 403, 'Bu bo‘limga ruxsat yo‘q.');
    }

    protected function authorizeEdit(Document $document): void
    {
        $this->authorizeView($document);
        abort_unless($document->isDraft(), 403, 'Yakunlangan hujjatni o‘zgartirib bo‘lmaydi.');
    }

    protected function authorizeEntry(Document $document, Item $item): void
    {
        $this->authorizeEdit($document);
        abort_unless($document->usesEntryWindow() && $item->is_active, 404);
    }
}