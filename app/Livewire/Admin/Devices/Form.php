<?php

namespace App\Livewire\Admin\Devices;

use App\Livewire\AdminPage;
use App\Models\Device;
use App\Models\Printer;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class Form extends AdminPage
{
    #[Locked]
    public int $recordId;

    public string $name = '';

    public string $keyboard_mode = 'auto';

    public ?string $printer_id = null;

    public function mount(int $record): void
    {
        $device = Device::findOrFail($record);
        $this->recordId = $device->id;
        $this->name = (string) $device->name;
        $this->keyboard_mode = $device->keyboard_mode;
        $this->printer_id = $device->printer_id ? (string) $device->printer_id : null;
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'nullable|string|max:100',
            'keyboard_mode' => ['required', Rule::in(array_keys(Device::MODES))],
            'printer_id' => 'nullable|exists:printers,id',
        ]);

        Device::findOrFail($this->recordId)->update([
            'name' => $data['name'] ?: null, 'keyboard_mode' => $data['keyboard_mode'], 'printer_id' => $data['printer_id'] ?: null,
        ]);

        session()->flash('status', 'Qurilma saqlandi.');

        return $this->redirect(route('admin.devices.index'));
    }

    public function render()
    {
        return $this->page('livewire.admin.devices.form', ['printers' => Printer::active()->orderBy('name')->get(), 'modes' => Device::MODES]);
    }
}