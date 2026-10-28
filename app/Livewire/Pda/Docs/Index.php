<?php

namespace App\Livewire\Pda\Docs;

use App\Livewire\PdaPage;
use App\Livewire\Pda\Concerns\AuthorizesDocuments;
use App\Models\Document;
use Livewire\Attributes\Locked;

/** Documents of one type (own documents; admins see all). */
class Index extends PdaPage
{
    use AuthorizesDocuments;

    #[Locked]
    public string $type = '';

    public function mount(string $type): void
    {
        $this->assertType($type);
        $this->type = $type;
    }

    public function render()
    {
        $this->assertType($this->type);

        $docs = Document::where('type', $this->type)
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->where('user_id', auth()->id()))
            ->withCount('lines')
            ->latest()
            ->limit(50)
            ->get();

        return $this->page('livewire.pda.docs.index', ['title' => Document::types()[$this->type], 'docs' => $docs]);
    }
}