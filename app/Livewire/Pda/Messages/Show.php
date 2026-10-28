<?php

namespace App\Livewire\Pda\Messages;

use App\Livewire\PdaPage;
use App\Models\Message;
use App\Models\MessageRecipient;
use Livewire\Attributes\Locked;

/** Reading a message marks it read for this user. */
class Show extends PdaPage
{
    #[Locked]
    public int $recordId;

    public function mount(int $record): void
    {
        $recipient = MessageRecipient::where('message_id', $record)->where('user_id', auth()->id())->firstOrFail();
        $recipient->read_at ??= now();
        $recipient->save();

        $this->recordId = $record;
    }

    public function render()
    {
        return $this->page('livewire.pda.messages.show', ['message' => Message::with('sender')->findOrFail($this->recordId)]);
    }
}