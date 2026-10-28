<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\AdminPage;
use App\Models\User;

class Index extends AdminPage
{
    public function render()
    {
        return $this->page('livewire.admin.users.index', [
            'users' => User::orderByDesc('is_active')->orderBy('name')->get(),
            'menuCount' => count(User::manageableMenu()),
        ]);
    }
}
