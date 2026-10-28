<?php

namespace App\Livewire\Pda\Messages;

use App\Livewire\PdaPage;
use App\Models\MessageRecipient;

class Index extends PdaPage
{
    public function render()
    {
        return $this->page('livewire.pda.messages.index', [
            'inbox' => MessageRecipient::with('message.sender')->where('user_id', auth()->id())->latest()->limit(50)->get(),
        ]);
    }
}