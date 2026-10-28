<?php

namespace App\Livewire\Admin\Tasks;

use App\Livewire\AdminPage;
use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\Rule;

class Form extends AdminPage
{
    public string $title = '';

    public string $description = '';

    public string $assignee_id = '';

    public string $priority = 'normal';

    public string $due_at = '';

    public string $doc_type = '';

    public function save()
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'assignee_id' => ['required', Rule::exists('users', 'id')->where('is_active', true)],
            'priority' => 'required|in:low,normal,high',
            'due_at' => 'nullable|date',
            'doc_type' => ['nullable', Rule::in(array_keys(Document::types()))],
        ], [], ['title' => 'Vazifa nomi', 'assignee_id' => 'Ijrochi']);

        Task::create(collect($data)->map(fn ($v) => $v === '' ? null : $v)->all() + ['created_by' => auth()->id()]);

        session()->flash('status', 'Vazifa tayinlandi.');

        return $this->redirect(route('admin.tasks.index'));
    }

    public function render()
    {
        return $this->page('livewire.admin.tasks.form', ['operators' => User::operators()->orderBy('name')->get(), 'docTypes' => Document::types()]);
    }
}