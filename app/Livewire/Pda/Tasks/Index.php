<?php

namespace App\Livewire\Pda\Tasks;

use App\Livewire\PdaPage;
use App\Models\Task;

/** My tasks: open ones first (urgent on top), then the last few finished. */
class Index extends PdaPage
{
    public function render()
    {
        $mine = Task::where('assignee_id', auth()->id());

        return $this->page('livewire.pda.tasks.index', [
            'open' => (clone $mine)->whereIn('status', Task::OPEN)
                ->orderByRaw("case priority when 'high' then 0 when 'normal' then 1 else 2 end")
                ->orderBy('due_at')
                ->get(),
            'recent' => (clone $mine)->whereNotIn('status', Task::OPEN)->latest('updated_at')->limit(10)->get(),
        ]);
    }
}