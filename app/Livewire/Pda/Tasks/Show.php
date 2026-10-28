<?php

namespace App\Livewire\Pda\Tasks;

use App\Livewire\PdaPage;
use App\Models\Task;
use Livewire\Attributes\Locked;

/** One task: accept / decline, start the linked document, or mark a simple task done. */
class Show extends PdaPage
{
    #[Locked]
    public int $recordId;

    public string $reason = '';

    public function mount(int $record): void
    {
        $this->recordId = $record;
        $this->task(); // 404 / 403 on load
    }

    private function task(): Task
    {
        $task = Task::findOrFail($this->recordId);
        abort_unless($task->assignee_id === auth()->id(), 403);

        return $task;
    }

    public function accept(): void
    {
        $task = $this->task();
        abort_unless($task->status === Task::NEW, 403);

        $task->update(['status' => Task::ACCEPTED, 'accepted_at' => now()]);
        $this->ok('Vazifa qabul qilindi.');
    }

    public function decline()
    {
        $task = $this->task();
        abort_unless($task->status === Task::NEW, 403);

        $this->validate(['reason' => 'required|string|max:255'], ['reason.required' => 'Rad etish sababini yozing.']);
        $task->update(['status' => Task::DECLINED, 'cancel_reason' => $this->reason]);

        session()->flash('status', 'Vazifa rad etildi.');

        return $this->redirect(route('tasks.index'));
    }

    /** Only for tasks that have no document to execute. */
    public function complete()
    {
        $task = $this->task();
        abort_unless(in_array($task->status, [Task::ACCEPTED, Task::IN_PROGRESS], true) && ! $task->doc_type, 403);

        $task->update(['status' => Task::DONE, 'completed_at' => now()]);

        session()->flash('status', 'Vazifa bajarildi.');

        return $this->redirect(route('tasks.index'));
    }

    public function render()
    {
        return $this->page('livewire.pda.tasks.show', ['task' => $this->task()->load('documents')]);
    }
}