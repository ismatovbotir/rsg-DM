<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\AdminPage;
use App\Models\Role;

/** Roles: named sets of permissions. A role in use, or the administrator role, cannot be deleted. */
class Index extends AdminPage
{
    public function delete(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->is_admin) {
            $this->fail('Administrator rolini o‘chirib bo‘lmaydi.');

            return;
        }

        if ($role->users_count > 0) {
            $this->fail('Bu rol foydalanuvchilarga berilgan. Avval ularga boshqa rol tanlang.');

            return;
        }

        $role->delete();
        $this->ok('Rol o‘chirildi.');
    }

    public function render()
    {
        return $this->page('livewire.admin.roles.index', ['roles' => Role::withCount('users')->orderByDesc('is_admin')->orderBy('name')->get()]);
    }
}
