<?php

namespace App\Livewire\Admin;

use App\Livewire\AdminPage;
use Livewire\WithPagination;

/**
 * A searchable, paginated admin list of one model with a "new" button and an edit link per row.
 * Subclasses describe the model, columns and routes; the generic view renders it.
 */
abstract class CrudIndex extends AdminPage
{
    use WithPagination;

    public string $q = '';

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    abstract protected function model(): string;

    abstract protected function title(): string;

    /** [[label, attribute-or-closure, 'num'?], ...] */
    abstract protected function columns(): array;

    /** ['edit' => route name of the view-only card, 'empty' => 'Yo‘q.'] (no "new" button: the data comes from Adines) */
    abstract protected function routes(): array;

    protected function searchable(): ?string
    {
        return null; // placeholder text of the search box, or null for no search
    }

    protected function query()
    {
        return $this->model()::query()->orderBy('name');
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->query();
        $this->searchable() && $query->search($this->q);

        return $this->page('livewire.admin.crud-index', [
            'title' => $this->title(),
            'columns' => $this->columns(),
            'routes' => $this->routes(),
            'searchPlaceholder' => $this->searchable(),
            'rows' => $query->simplePaginate(30),
            'hasStatus' => in_array('is_active', (new ($this->model()))->getFillable(), true),
        ]);
    }
}
