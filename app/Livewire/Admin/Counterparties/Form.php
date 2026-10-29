<?php

namespace App\Livewire\Admin\Counterparties;

use App\Livewire\Admin\CrudForm;
use App\Models\Counterparty;

/** Counterparty card (view only, data comes from Adines). */
class Form extends CrudForm
{
    protected function model(): string
    {
        return Counterparty::class;
    }

    protected function listTitle(): string
    {
        return 'Kontragentlar';
    }

    protected function indexRoute(): string
    {
        return 'admin.counterparties.index';
    }

    protected function fields(): array
    {
        return [
            ['Nomi', 'name'],
            ['To‘liq nomi', 'full_name'],
            ['Turi', fn ($c) => $c->type === 'employee' ? 'Xodim' : 'Kontragent'],
            ['STIR (INN)', 'stir'],
            ['Telefon', 'phone'],
            ['Manzil', 'address'],
            ['QQS to‘lovchi', fn ($c) => $c->is_vat_payer ? 'Ha' : 'Yo‘q'],
            ['Adines kodi', 'id'],
        ];
    }

    protected function figures($record): array
    {
        return [['Adines kodi', $record->id], ['STIR', $record->stir ?: '—'], ['Hujjatlar', \App\Models\Document::where('counterparty_id', $record->id)->count()]];
    }
}
