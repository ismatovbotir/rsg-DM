@section('title', 'Foydalanuvchilar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.users.create') }}">+ Yangi foydalanuvchi</a>
    </div>

    <table>
        <thead><tr><th>Ism</th><th>Login (email)</th><th>Rol</th><th>Bo‘limlar</th><th>Holat</th><th>Oxirgi kirish</th><th></th></tr></thead>
        <tbody>
            @foreach ($users as $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role_label }}</td>
                    <td>
                        @if ($user->isAdmin() || $user->menu_access === null)
                            Barchasi
                        @else
                            {{ count($user->menu_access) }} / {{ $menuCount }}
                        @endif
                    </td>
                    <td><span class="badge {{ $user->is_active ? 'completed' : 'error' }}">{{ $user->is_active ? 'Faol' : 'O‘chirilgan' }}</span></td>
                    <td>{{ $user->last_login_at?->format('d.m.Y H:i') }}</td>
                    <td class="text-right"><a class="btn gray" href="{{ route('admin.users.edit', $user) }}">Tahrirlash</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
