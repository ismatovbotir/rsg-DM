<?php

namespace App\Livewire\Admin;

use App\Livewire\AdminPage;
use Livewire\Attributes\Locked;

/**
 * View-only card of one master-data record that comes from Adines (1C): header with status and actions,
 * key figures, then a table of fields. Nothing here can be changed, data is corrected in Adines.
 * Subclasses describe the model, the fields to show and optional key figures.
 */
abstract class CrudForm extends AdminPage
{
    #[Locked]
    public int $recordId;

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    abstract protected function model(): string;

    /** Name of the list ("Kontragentlar") for the breadcrumb. */
    abstract protected function listTitle(): string;

    abstract protected function indexRoute(): string;

    /** @return list<array{0: string, 1: string|\Closure}> [label, attribute or closure($record)] */
    abstract protected function fields(): array;

    /** Key figures above the table: [[label, value], ...]. */
    protected function figures($record): array
    {
        return [];
    }

    public function mount(int $record): void
    {
        $this->recordId = $this->model()::findOrFail($record)->id;
    }

    public function render()
    {
        $record = $this->model()::findOrFail($this->recordId);

        return $this->page('livewire.admin.crud-card', [
            'record' => $record,
            'listTitle' => $this->listTitle(),
            'back' => route($this->indexRoute()),
            'rows' => collect($this->fields())->map(fn ($f) => [$f[0], is_callable($f[1]) ? $f[1]($record) : $record->{$f[1]}]),
            'figures' => $this->figures($record),
            'hasStatus' => array_key_exists('is_active', $record->getAttributes()),
        ]);
    }
}
