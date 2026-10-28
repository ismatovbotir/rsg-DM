@section('title', $recordId ? 'Foydalanuvchini tahrirlash' : 'Yangi foydalanuvchi')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>Ism <input wire:model="name" required></label>
        <label>Login (email) <input wire:model="email" required autocomplete="off"></label>
        <label>Rol
            <select wire:model="role">
                <option value="user">Operator (PDA)</option>
                <option value="admin">Administrator</option>
            </select>
        </label>
        <label>Parol @if ($recordId)<small>(o‘zgartirmaslik uchun bo‘sh qoldiring)</small>@endif
            <input type="password" wire:model="password" autocomplete="new-password" @required(! $recordId)>
        </label>

        <label class="chk"><input type="checkbox" wire:model="is_active"> Faol (kira oladi)</label>

        {{-- Menu access: unchecked items are hidden on the PDA and refused by the server. Administrators always see everything. --}}
        <fieldset x-data x-show="$wire.role === 'user'">
            <legend>PDA bo‘limlariga ruxsat (operator uchun)</legend>
            @foreach ($menuItems as $key => $title)
                <label class="chk"><input type="checkbox" wire:model="menu" value="{{ $key }}"> {{ $title }}</label>
            @endforeach
            <small>“Vazifalarim” va xabarlar har doim mavjud.</small>
        </fieldset>

        <div class="actions">
            <button class="btn">Saqlash</button>
            <a class="btn gray" href="{{ route('admin.users.index') }}">Bekor qilish</a>
        </div>
    </form>
</div>
