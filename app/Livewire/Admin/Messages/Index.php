<?php

namespace App\Livewire\Admin\Messages;

use App\Livewire\AdminPage;
use App\Models\Message;
use Livewire\WithPagination;

class Index extends AdminPage
{
    use WithPagination;

    public function render()
    {
        $messages = Message::withCount([
            'recipients',
            'recipients as read_count' => fn ($q) => $q->whereNotNull('read_at'),
        ])->latest()->simplePaginate(30);

        return $this->page('livewire.admin.messages.index', ['messages' => $messages]);
    }
}