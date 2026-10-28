<?php

namespace App\Livewire\Pda;

use App\Livewire\PdaPage;
use App\Models\Document;
use App\Models\MessageRecipient;
use App\Models\Task;

/** PDA main menu: operations with draft / task counters, bell with unread messages, urgent message banner. */
class Menu extends PdaPage
{
    public function render()
    {
        $userId = auth()->id();
        $tasks = Task::openFor($userId)->count();

        // "Vazifalarim" only appears while the user has open tasks. Hardware keys are bound to the
        // operation (config 'hotkey'), not the position, so hiding a row never shifts the others.
        $items = collect(config('menu.items'))
            ->reject(fn ($i) => $i['key'] === 'tasks' && $tasks === 0)
            ->filter(fn ($i) => auth()->user()->canAccess($i['key'])) // per-user access set by the admin
            ->values();

        return $this->page('livewire.pda.menu', [
            'items' => $items,
            'badges' => [
                'tasks' => $tasks,
                ...Document::where('user_id', $userId)->where('status', Document::DRAFT)
                    ->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type')->all(),
            ],
            'unread' => MessageRecipient::where('user_id', $userId)->whereNull('read_at')->count(),
            'urgent' => MessageRecipient::with('message')->where('user_id', $userId)->whereNull('read_at')
                ->whereHas('message', fn ($q) => $q->where('priority', 'urgent'))->get(),
        ]);
    }
}