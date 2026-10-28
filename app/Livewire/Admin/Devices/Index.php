<?php

namespace App\Livewire\Admin\Devices;

use App\Livewire\AdminPage;
use App\Models\Device;

/** PDAs register themselves on first visit; the admin names them, sets the keyboard mode and assigns a printer. */
class Index extends AdminPage
{
    public function delete(int $id): void
    {
        Device::findOrFail($id)->delete(); // the PDA simply registers again on its next visit
        $this->ok('Qurilma o‘chirildi.');
    }

    public function render()
    {
        // Unnamed (just registered) devices first, then the most recently seen.
        $devices = Device::with('lastUser', 'printer')
            ->orderByRaw('case when name is null then 0 else 1 end')
            ->orderByDesc('last_seen_at')
            ->get();

        return $this->page('livewire.admin.devices.index', ['devices' => $devices]);
    }
}