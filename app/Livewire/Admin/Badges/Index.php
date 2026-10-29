<?php

namespace App\Livewire\Admin\Badges;

use App\Livewire\AdminPage;
use App\Models\User;

/** Pick users and print their badges (the page is a plain GET form to the printable sheet). */
class Index extends AdminPage
{
    public function render()
    {
        return $this->page('livewire.admin.badges.index', ['users' => User::with('roleModel')->where('is_active', true)->orderBy('name')->get()]);
    }
}
