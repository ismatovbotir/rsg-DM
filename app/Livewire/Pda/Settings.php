<?php

namespace App\Livewire\Pda;

use App\Livewire\PdaPage;
use App\Models\Device;

/** PDA "Sozlamalar": shows this device's profile; the keyboard override itself lives in the browser (localStorage). */
class Settings extends PdaPage
{
    public function render()
    {
        return $this->page('livewire.pda.settings', ['device' => Device::current(), 'modes' => Device::MODES]);
    }
}