<?php

namespace App\Livewire\Admin\Documents;

use App\Livewire\AdminPage;
use App\Models\Document;
use App\Services\OneCClient;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Index extends AdminPage
{
    use WithPagination;

    #[Url]
    public string $type = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $sync = '';

    public function updated($property): void
    {
        in_array($property, ['type', 'status', 'sync'], true) && $this->resetPage();
    }

    public function syncAll(OneCClient $onec): void
    {
        try {
            [$sent, $failed] = $onec->pushPending();
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());

            return;
        }

        $this->ok("Yuborildi: {$sent}, xato: {$failed}.");
    }

    public function render()
    {
        $docs = Document::with('user')
            ->withCount('lines')
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->sync !== '', fn ($q) => $q->where('onec_status', $this->sync))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->latest()
            ->simplePaginate(30);

        return $this->page('livewire.admin.documents.index', ['docs' => $docs]);
    }
}