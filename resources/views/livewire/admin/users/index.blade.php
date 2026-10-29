@section('title', 'Foydalanuvchilar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.users.create') }}">+ Yangi foydalanuvchi</a>
    </div>

    <table>
        <thead><tr><th>Ism</th><th>Login (email)</th><th>Rol</th><th>Do‘kon</th><th>Holat</th><th>Oxirgi kirish</th></tr></thead>
        <tbody>
            @foreach ($users as $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td><a class="lnk" href="{{ route('admin.users.edit', $user) }}">{{ $user->name }}</a></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role_label }}</td>
                    <td>{{ $user->warehouse?->name }}</td>
                    <td><span class="badge {{ $user->is_active ? 'completed' : 'error' }}">{{ $user->is_active ? 'Faol' : 'O‘chirilgan' }}</span></td>
                    <td>{{ $user->last_login_at?->format('d.m.Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
