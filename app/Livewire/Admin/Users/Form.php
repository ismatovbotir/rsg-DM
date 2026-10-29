<?php

namespace App\Livewire\Admin\Users;

use App\Livewire\AdminPage;
use App\Models\PriceType;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Permissions;
use App\Support\Qr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

/**
 * Users (PDA operators and desk staff). Users are deactivated, never deleted: documents reference them.
 * What a user may open comes from the chosen role; sign-in is "pick yourself + 4-digit PIN" or the personal badge QR code.
 */
class Form extends AdminPage
{
    #[Locked]
    public ?int $recordId = null;

    public string $name = '';

    public string $email = '';

    public string $role_id = '';

    public string $warehouse_id = '';

    public string $password = '';

    public bool $is_active = true;

    public bool $pda_user = false;

    /** @var list<string> permission keys of the role that stay switched on for this user */
    public array $perms = [];

    /** @var list<string> warehouses the user may work with (besides the own shop) */
    public array $warehouse_ids = [];

    /** @var list<string> price lists the user may see */
    public array $price_type_ids = [];

    /** The PIN just issued, shown once (only its hash is stored). */
    public string $shownPin = '';

    public string $pinInput = '';

    public function mount(?int $record = null): void
    {
        $user = $record ? User::findOrFail($record) : null;
        $this->recordId = $user?->id;

        if ($user) {
            $this->fill(['name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active, 'pda_user' => $user->pda_user, 'role_id' => (string) $user->role_id, 'warehouse_id' => (string) $user->warehouse_id]);
            $this->warehouse_ids = $user->warehouseAccess()->pluck('warehouses.id')->map(fn ($id) => (string) $id)->all();
            $this->price_type_ids = $user->priceTypeAccess()->pluck('price_types.id')->map(fn ($id) => (string) $id)->all();
        } else {
            $this->role_id = (string) (Role::where('is_admin', false)->orderBy('id')->value('id') ?? '');
        }

        $role = Role::find($this->role_id);
        $this->perms = $role && ! $role->is_admin ? array_values(array_diff($role->permissions ?? [], $user?->denied_permissions ?? [])) : [];
        $this->shownPin = (string) session('new_pin', '');
    }

    /** A new role: all of its permissions are on again. */
    public function updatedRoleId(): void
    {
        $role = Role::find($this->role_id);
        $this->perms = $role && ! $role->is_admin ? array_values($role->permissions ?? []) : [];
    }

    public function save()
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'max:255', Rule::unique('users', 'email')->ignore($this->recordId)],
            'role_id' => 'required|exists:roles,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'password' => [$this->recordId ? 'nullable' : 'required', 'string', 'min:6', 'max:100'], // blank = keep the current password
            'is_active' => 'boolean',
            'pda_user' => 'boolean',
            'warehouse_ids' => 'array',
            'warehouse_ids.*' => 'exists:warehouses,id',
            'price_type_ids' => 'array',
            'price_type_ids.*' => 'exists:price_types,id',
        ], [], ['name' => 'Ism', 'email' => 'Login', 'password' => 'Parol', 'role_id' => 'Rol']);

        // An administrator cannot lock themselves out.
        if ($this->recordId === auth()->id()) {
            $data['role_id'] = auth()->user()->role_id ?: Role::where('is_admin', true)->value('id');
            $data['is_active'] = true;
        }

        $warehouseIds = array_map('intval', $data['warehouse_ids'] ?? []);
        $priceTypeIds = array_map('intval', $data['price_type_ids'] ?? []);
        unset($data['warehouse_ids'], $data['price_type_ids']);

        // switched-off permissions: what the role grants minus what stays ticked (nothing for a full administrator)
        $role = Role::findOrFail($data['role_id']);
        $data['denied_permissions'] = $role->is_admin ? null : (array_values(array_diff($role->permissions ?? [], $this->perms)) ?: null);

        $data['warehouse_id'] = $data['warehouse_id'] ?: null;
        $data['role'] = Role::findOrFail($data['role_id'])->is_admin ? 'admin' : 'user'; // old column kept in step

        if (blank($data['password'])) {
            unset($data['password']);
        }

        if ($this->recordId) {
            $user = tap(User::findOrFail($this->recordId))->update($data);
            $user->warehouseAccess()->sync($warehouseIds);
            $user->priceTypeAccess()->sync($priceTypeIds);
            session()->flash('status', 'Foydalanuvchi saqlandi.');

            return $this->redirect(route('admin.users.index'));
        }

        // a new user gets a PIN and a badge QR code at once; the PIN is shown once on the next page
        $user = User::create($data);
        $user->warehouseAccess()->sync($warehouseIds);
        $user->priceTypeAccess()->sync($priceTypeIds);
        $user->regenerateBadge();
        session()->flash('new_pin', $user->issuePin());
        session()->flash('status', 'Foydalanuvchi qo‘shildi. PIN va badge yaratildi — badge ni chop eting.');

        return $this->redirect(route('admin.users.edit', $user->id).'#badge');
    }

    /** New random PIN, shown once. */
    public function generatePin(): void
    {
        $this->shownPin = User::findOrFail($this->recordId)->issuePin();
        $this->pinInput = '';
        $this->ok('Yangi PIN yaratildi. U faqat hozir ko‘rsatiladi — foydalanuvchiga bering.');
    }

    /** A PIN chosen by the admin. */
    public function setPin(): void
    {
        $this->validate(
            ['pinInput' => ['required', 'regex:/^\d{4}$/']],
            ['pinInput.regex' => 'PIN aynan 4 ta raqamdan iborat bo‘lishi kerak.', 'pinInput.required' => 'PIN kiriting.'],
            ['pinInput' => 'PIN'],
        );
        $this->shownPin = User::findOrFail($this->recordId)->issuePin($this->pinInput);
        $this->pinInput = '';
        $this->ok('PIN o‘rnatildi.');
    }

    /** New badge QR code; the printed old badge stops working. */
    public function regenerateBadge(): void
    {
        User::findOrFail($this->recordId)->regenerateBadge();
        $this->ok('Badge yangilandi. Eski badge endi ishlamaydi — yangisini chop eting.');
    }

    public function render()
    {
        $user = $this->recordId ? User::find($this->recordId) : null;
        $user?->ensureBadge();

        return $this->page('livewire.admin.users.form', [
            'roles' => Role::orderByDesc('is_admin')->orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'priceTypes' => PriceType::active()->orderBy('name')->get(),
            'chosenRole' => Role::find($this->role_id),
            'adminGroups' => Permissions::adminGroups(),
            'pdaItems' => Permissions::pdaItems(),
            'user' => $user,
            'qr' => $user?->badge_url ? Qr::svg($user->badge_url) : null,
        ]);
    }
}
