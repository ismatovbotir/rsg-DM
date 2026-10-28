<?php

namespace App\Livewire\Admin\Tasks;

use App\Livewire\AdminPage;
use App\Models\Task;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** Admin assigns tasks to operators; operators accept / decline / execute them on the PDA. */
class Index extends AdminPage
{
    use WithPagination;

    #[Url]
    public string $status = '';

    /** @var array<int, string> cancel reason typed per task row */
    public array $reasons = [];

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function cancel(int $id): void
    {
        $task = Task::findOrFail($id);

        if (! $task->isOpen()) {
            $this->fail('Faqat ochiq vazifani bekor qilish mumkin.');

            return;
        }

        $reason = trim((string) ($this->reasons[$id] ?? ''));

        if (mb_strlen($reason) > 255) {
            $this->fail('Sabab 255 belgidan oshmasin.');

            return;
        }

        $task->update(['status' => Task::CANCELLED, 'cancel_reason' => $reason !== '' ? $reason : null]);
        unset($this->reasons[$id]);
        $this->ok('Vazifa bekor qilindi.');
    }

    public function render()
    {
        $tasks = Task::with('assignee')
            ->withCount('documents')
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->simplePaginate(30);

        return $this->page('livewire.admin.tasks.index', ['tasks' => $tasks]);
    }
}