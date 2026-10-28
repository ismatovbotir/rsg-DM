<?php

namespace App\Livewire\Admin\Exchange;

use App\Livewire\AdminPage;
use App\Models\ExchangeLog;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** What Adines has pushed: one row per call with counts and the first row errors. Refreshes itself. */
class Index extends AdminPage
{
    use WithPagination;

    #[Url]
    public string $entity = '';

    #[Url]
    public bool $errors = false;

    public function updated($property): void
    {
        in_array($property, ['entity', 'errors'], true) && $this->resetPage();
    }

    public function render()
    {
        $logs = ExchangeLog::query()
            ->when($this->entity !== '', fn ($q) => $q->where('entity', $this->entity))
            ->when($this->errors, fn ($q) => $q->where('errors_count', '>', 0))
            ->latest('id')
            ->simplePaginate(30);

        return $this->page('livewire.admin.exchange.index', [
            'logs' => $logs,
            'entities' => ExchangeLog::distinct()->orderBy('entity')->pluck('entity'),
        ]);
    }
}