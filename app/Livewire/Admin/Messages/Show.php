<?php

namespace App\Livewire\Admin\Messages;

use App\Livewire\AdminPage;
use App\Models\Message;
use Livewire\Attributes\Locked;

class Show extends AdminPage
{
    #[Locked]
    public int $recordId;

    public function mount(int $record): void
    {
        $this->recordId = Message::findOrFail($record)->id;
    }

    public function render()
    {
        return $this->page('livewire.admin.messages.show', ['message' => Message::with('recipients.user')->findOrFail($this->recordId)]);
    }
}