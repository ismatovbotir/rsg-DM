<?php

namespace App\Livewire\Admin\Warehouses;

use App\Livewire\Admin\CrudForm;
use App\Models\Warehouse;
use Illuminate\Validation\Rule;

class Form extends CrudForm
{
    protected function model(): string
    {
        return Warehouse::class;
    }

    protected function titles(): array
    {
        return ['new' => 'Yangi ombor', 'edit' => 'Omborni tahrirlash'];
    }

    protected function indexRoute(): string
    {
        return 'admin.warehouses.index';
    }

    protected function saved(): string
    {
        return 'Ombor saqlandi.';
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nomi', 'type' => 'text', 'rules' => 'required|string|max:255', 'attrs' => ['required' => 'required']],
            'code' => ['label' => 'Kod (Adines)', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($this->recordId)]],
            'is_active' => ['label' => 'Faol', 'type' => 'checkbox', 'rules' => 'boolean'],
        ];
    }
}
