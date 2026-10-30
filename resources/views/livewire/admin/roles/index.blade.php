@section('title', 'Rollar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span class="hint">{{ __('Rol — foydalanuvchi nimalarni ochishi mumkinligi to‘plami (boshqaruv paneli bo‘limlari va PDA menyusi). Foydalanuvchiga rolni foydalanuvchi sahifasida tanlaysiz.') }}</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.roles.create') }}">{{ __('+ Yangi rol') }}</a>
    </div>

    <table>
        <thead><tr><th>{{ __('Nomi') }}</th><th>{{ __('Huquqlar') }}</th><th class="num">{{ __('Foydalanuvchilar') }}</th><th></th></tr></thead>
        <tbody>
            @foreach ($roles as $role)
                <tr wire:key="role-{{ $role->id }}">
                    <td><a class="lnk" href="{{ route('admin.roles.edit', $role->id) }}">{{ $role->name }}</a></td>
                    <td>
                        @if ($role->is_admin)<span class="badge completed">{{ __('Barcha huquqlar') }}</span>
                        @else
                            @php $keys = $role->permissions ?? []; $admin = count(array_filter($keys, fn ($k) => str_starts_with($k, 'admin.'))); $pdaCount = count(array_filter($keys, fn ($k) => str_starts_with($k, 'pda.'))); @endphp
                            <span class="hint">{{ __('Boshqaruv paneli:') }} {{ $admin }} {{ __('· PDA menyu:') }} {{ $pdaCount }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $role->users_count }}</td>
                    <td class="text-right">
                        @unless ($role->is_admin)
                            <button class="btn sm red" wire:click="delete({{ $role->id }})" wire:confirm="{{ __('Rol o‘chirilsinmi?') }}">{{ __('O‘chirish') }}</button>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
