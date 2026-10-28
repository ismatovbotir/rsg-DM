<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\AdminPage;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

/** Mobile (PDA) users and administrators. Users are deactivated, never deleted: documents reference them. */
class Form extends AdminPage
{
    #[Locked]
    public ?int $recordId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'user';

    public string $password = '';

    public bool $is_active = true;

    /** @var list<string> menu keys the operator may open */
    public array $menu = [];

    public function mount(?int $record = null): void
    {
        $user = $record ? User::findOrFail($record) : null;
        $this->recordId = $user?->id;

        if ($user) {
            $this->fill(['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'is_active' => $user->is_active]);
        }

        $this->menu = $user?->menu_access ?? array_keys(User::manageableMenu());
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'max:255', Rule::unique('users', 'email')->ignore($this->recordId)],
            'role' => 'required|in:user,admin',
            'password' => [$this->recordId ? 'nullable' : 'required', 'string', 'min:6', 'max:100'], // blank = keep the current password
            'is_active' => 'boolean',
            'menu' => 'array',
        ], [], ['name' => 'Ism', 'email' => 'Login', 'password' => 'Parol']);

        // An admin cannot lock themselves out.
        if ($this->recordId === auth()->id()) {
            $data['role'] = 'admin';
            $data['is_active'] = true;
        }

        $data['menu_access'] = $this->menuAccess($data['role'], $data['menu']);
        unset($data['menu']);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $this->recordId ? User::findOrFail($this->recordId)->update($data) : User::create($data);

        session()->flash('status', $this->recordId ? 'Foydalanuvchi saqlandi.' : 'Foydalanuvchi qo‘shildi.');

        return $this->redirect(route('admin.users.index'));
    }

    /**
     * Menu keys the user may open. null = no restriction: admins, or every item ticked
     * (so menu items added later are available automatically).
     */
    private function menuAccess(string $role, array $ticked): ?array
    {
        if ($role === 'admin') {
            return null;
        }

        $all = array_keys(User::manageableMenu());
        $allowed = array_values(array_intersect($all, $ticked));

        return count($allowed) === count($all) ? null : $allowed;
    }

    public function render()
    {
        return $this->page('livewire.admin.users.form', ['menuItems' => User::manageableMenu()]);
    }
}
