<?php

namespace App\Livewire\Admin\Roles;

use App\Livewire\AdminPage;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

/** One role: its name and the ticked sections / items (admin panel groups and the PDA menu). */
class Form extends AdminPage
{
    #[Locked]
    public ?int $roleId = null;

    public string $name = '';

    /** @var list<string> */
    public array $perms = [];

    public function mount(?int $record = null): void
    {
        $role = $record ? Role::findOrFail($record) : null;
        $this->roleId = $role?->id;
        $this->name = $role?->name ?? '';
        $this->perms = $role?->permissions ?? [];
    }

    public function save()
    {
        $this->validate(['name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->roleId)]], [], ['name' => 'Nomi']);
        $role = $this->roleId ? Role::findOrFail($this->roleId) : new Role();

        $role->name = $this->name;
        if (! $role->is_admin) {
            $role->permissions = array_values(array_intersect(Permissions::all(), $this->perms)); // only known keys
        }
        $role->save();

        session()->flash('status', 'Rol saqlandi.');

        return $this->redirect(route('admin.roles.index'));
    }

    public function render()
    {
        return $this->page('livewire.admin.roles.form', [
            'groups' => Permissions::adminGroups(),
            'pda' => Permissions::pdaItems(),
            'isAdminRole' => $this->roleId ? (bool) Role::whereKey($this->roleId)->value('is_admin') : false,
        ]);
    }
}
