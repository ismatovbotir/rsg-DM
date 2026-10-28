<?php

namespace App\Livewire\Admin\Counterparties;

use App\Livewire\Admin\CrudForm;
use App\Models\Counterparty;
use Illuminate\Validation\Rule;

class Form extends CrudForm
{
    protected function model(): string
    {
        return Counterparty::class;
    }

    protected function titles(): array
    {
        return ['new' => 'Yangi kontragent', 'edit' => 'Kontragentni tahrirlash'];
    }

    protected function indexRoute(): string
    {
        return 'admin.counterparties.index';
    }

    protected function saved(): string
    {
        return 'Kontragent saqlandi.';
    }

    protected function messages(): array
    {
        return ['data.stir.regex' => 'STIR 9-14 ta raqamdan iborat bo‘lishi kerak.'];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nomi', 'type' => 'text', 'rules' => 'required|string|max:255', 'attrs' => ['required' => 'required']],
            'stir' => ['label' => 'STIR', 'type' => 'text', 'rules' => ['nullable', 'regex:/^\\d{9,14}$/', Rule::unique('counterparties', 'stir')->ignore($this->recordId)], 'attrs' => ['inputmode' => 'numeric', 'maxlength' => 14]],
            'is_active' => ['label' => 'Faol', 'type' => 'checkbox', 'rules' => 'boolean'],
        ];
    }
}
