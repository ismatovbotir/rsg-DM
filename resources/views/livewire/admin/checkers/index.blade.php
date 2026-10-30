@section('title', 'Narx tekshirgichlar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span>{{ __('Mijozlar uchun narx tekshirish terminallari:') }} <code>{{ url('/pricechecker') }}</code>{{ __('. Terminal token QR kodini skanerlab ulanadi.') }}</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.checkers.create') }}">{{ __('+ Yangi narx tekshirgich') }}</a>
    </div>

    <table>
        <thead><tr><th>{{ __('Nomi') }}</th><th>{{ __('Narx turi') }}</th><th>{{ __('Dizayn') }}</th><th>{{ __('Terminal') }}</th><th>{{ __('Oxirgi faollik') }}</th><th>{{ __('Holat') }}</th><th></th></tr></thead>
        <tbody>
            @forelse ($checkers as $checker)
                <tr wire:key="checker-{{ $checker->id }}">
                    <td><a class="lnk" href="{{ route('admin.checkers.edit', $checker) }}">{{ $checker->name }}</a></td>
                    <td>{{ $checker->priceType?->name ?? 'Asosiy narx' }}</td>
                    <td>@php $th = $checker->theme ?? \App\Models\CheckerTheme::default(); @endphp @if ($th)<a class="lnk" href="{{ route('admin.themes.edit', $th->id) }}">{{ $th->name }}</a>@else<span class="hint">—</span>@endif</td>
                    <td>@if ($checker->is_connected)<span class="badge completed">{{ __('Ulangan') }} {{ $checker->activated_at?->format('d.m.Y') }}</span>@else<span class="badge">{{ __('Ulanmagan') }}</span>@endif</td>
                    <td>{{ $checker->last_seen_at?->format('d.m.Y H:i') }}</td>
                    <td><span class="badge {{ $checker->is_active ? 'completed' : 'error' }}">{{ $checker->is_active ? 'Faol' : 'Nofaol' }}</span></td>
                    <td class="text-right">
                        <button class="btn sm gray" wire:click="regenerate({{ $checker->id }})" wire:confirm="{{ __('Yangi token yaratilsa, hozirgi terminal chiqib ketadi. Davom etilsinmi?') }}">{{ __('Yangi token') }}</button>
                        <button class="btn sm red" wire:click="delete({{ $checker->id }})" wire:confirm="{{ __('O‘chirilsinmi?') }}">{{ __('O‘chirish') }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('Hali narx tekshirgich yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
