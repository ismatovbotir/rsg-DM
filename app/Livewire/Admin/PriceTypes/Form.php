<?php

namespace App\Livewire\Admin\PriceTypes;

use App\Livewire\Admin\CrudForm;
use App\Models\PriceType;
use Illuminate\Validation\Rule;

class Form extends CrudForm
{
    protected function model(): string
    {
        return PriceType::class;
    }

    protected function titles(): array
    {
        return ['new' => 'Yangi narx turi', 'edit' => 'Narx turini tahrirlash'];
    }

    protected function indexRoute(): string
    {
        return 'admin.pricetypes.index';
    }

    protected function saved(): string
    {
        return 'Narx turi saqlandi.';
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nomi', 'type' => 'text', 'rules' => 'required|string|max:255', 'attrs' => ['required' => 'required']],
            'code' => ['label' => 'Kod (Adines)', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:50', Rule::unique('price_types', 'code')->ignore($this->recordId)]],
            'is_active' => ['label' => 'Faol', 'type' => 'checkbox', 'rules' => 'boolean'],
        ];
    }
}
