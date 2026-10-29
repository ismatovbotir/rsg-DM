<?php

namespace App\Livewire\Admin\Checkers;

use App\Livewire\AdminPage;
use App\Models\PriceChecker;

class Index extends AdminPage
{
    public function regenerate(int $id): void
    {
        PriceChecker::findOrFail($id)->regenerateToken();
        $this->ok('Yangi token yaratildi. Eski QR kod endi ishlamaydi.');
    }

    public function delete(int $id): void
    {
        PriceChecker::findOrFail($id)->delete();
        $this->ok('Narx tekshirgich o‘chirildi.');
    }

    public function render()
    {
        return $this->page('livewire.admin.checkers.index', ['checkers' => PriceChecker::with('priceType', 'theme')->orderBy('name')->get()]);
    }
}
