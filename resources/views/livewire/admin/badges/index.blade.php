@section('title', 'Badge chop etish')

<div>
    @include('partials.lw-flash')

    {{-- a plain GET form: the sheet opens in a new tab ready to print --}}
    <form method="GET" action="{{ route('admin.badges.print') }}" target="_blank" x-data="{ all: false }">
        <div class="bar">
            <label class="chk m-0"><input type="checkbox" x-model="all" @change="$el.closest('form').querySelectorAll('input[name=&quot;ids[]&quot;]').forEach(i => i.checked = all)"> {{ __('Hammasini belgilash') }}</label>
            <span class="grow"></span>
            <button class="btn">{{ __('Tanlanganlarni chop etish') }}</button>
        </div>

        <table>
            <thead><tr><th></th><th>{{ __('Ism') }}</th><th>{{ __('Login') }}</th><th>{{ __('Rol') }}</th><th>{{ __('PIN') }}</th></tr></thead>
            <tbody>
                @foreach ($users as $user)
                    <tr wire:key="b-{{ $user->id }}">
                        <td><input type="checkbox" name="ids[]" value="{{ $user->id }}"></td>
                        <td><a class="lnk" href="{{ route('admin.users.edit', $user) }}#badge">{{ $user->name }}</a></td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role_label }}</td>
                        <td>@if ($user->pin)<span class="badge completed">{{ __('Bor') }}</span>@else<span class="badge error">{{ __('Yo‘q') }}</span>@endif</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="hint">{{ __('Badge da ism, rol va shaxsiy QR kod bor (PIN yozilmaydi). Belgilamasangiz hamma faol foydalanuvchilar chop etiladi. QR kod yangilangan bo‘lsa eski badge ishlamaydi.') }}</p>
    </form>
</div>
