<?php

namespace App\Livewire\Admin;

use App\Livewire\AdminPage;
use Livewire\Attributes\Locked;

/**
 * Create / edit form of one model, rendered from a field list.
 * Field: key => ['label' => .., 'type' => text|number|checkbox, 'rules' => .., 'attrs' => [html attributes], 'default' => ..].
 */
abstract class CrudForm extends AdminPage
{
    #[Locked]
    public ?int $recordId = null;

    public array $data = [];

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    abstract protected function model(): string;

    abstract protected function titles(): array; // ['new' => .., 'edit' => ..]

    abstract protected function fields(): array;

    abstract protected function indexRoute(): string;

    protected function messages(): array
    {
        return [];
    }

    protected function saved(): string
    {
        return 'Saqlandi.';
    }

    public function mount(?int $record = null): void
    {
        $model = $record ? $this->model()::findOrFail($record) : null;
        $this->recordId = $model?->id;

        foreach ($this->fields() as $key => $field) {
            $isBool = $field['type'] === 'checkbox';
            $default = $field['default'] ?? ($isBool ? true : '');
            $this->data[$key] = $model ? ($model->{$key} ?? ($isBool ? false : '')) : $default;
        }
    }

    public function save()
    {
        $fields = $this->fields();
        $rules = collect($fields)->mapWithKeys(fn ($f, $key) => ["data.{$key}" => $f['rules']])->all();
        $names = collect($fields)->mapWithKeys(fn ($f, $key) => ["data.{$key}" => $f['label']])->all();
        $clean = $this->validate($rules, $this->messages(), $names)['data'];

        $values = collect($clean)->map(fn ($v, $key) => $fields[$key]['type'] === 'checkbox' ? (bool) $v : (($v === '' || $v === null) ? null : $v))->all();

        $this->recordId ? $this->model()::findOrFail($this->recordId)->update($values) : $this->model()::create($values);

        session()->flash('status', $this->saved());

        return $this->redirect(route($this->indexRoute()));
    }

    public function render()
    {
        return $this->page('livewire.admin.crud-form', [
            'title' => $this->titles()[$this->recordId ? 'edit' : 'new'],
            'fields' => $this->fields(),
            'back' => route($this->indexRoute()),
        ]);
    }
}
