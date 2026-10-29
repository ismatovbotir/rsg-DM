@section('title', 'Narx tekshirgichlar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span>Mijozlar uchun narx tekshirish terminallari: <code>{{ url('/pricechecker') }}</code>. Terminal token QR kodini skanerlab ulanadi.</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.checkers.create') }}">+ Yangi narx tekshirgich</a>
    </div>

    <table>
        <thead><tr><th>Nomi</th><th>Narx turi</th><th>Dizayn</th><th>Terminal</th><th>Oxirgi faollik</th><th>Holat</th><th></th></tr></thead>
        <tbody>
            @forelse ($checkers as $checker)
                <tr wire:key="checker-{{ $checker->id }}">
                    <td><a class="lnk" href="{{ route('admin.checkers.edit', $checker) }}">{{ $checker->name }}</a></td>
                    <td>{{ $checker->priceType?->name ?? 'Asosiy narx' }}</td>
                    <td>@php $th = $checker->theme ?? \App\Models\CheckerTheme::default(); @endphp @if ($th)<a class="lnk" href="{{ route('admin.themes.edit', $th->id) }}">{{ $th->name }}</a>@else<span class="hint">—</span>@endif</td>
                    <td>@if ($checker->is_connected)<span class="badge completed">Ulangan {{ $checker->activated_at?->format('d.m.Y') }}</span>@else<span class="badge">Ulanmagan</span>@endif</td>
                    <td>{{ $checker->last_seen_at?->format('d.m.Y H:i') }}</td>
                    <td><span class="badge {{ $checker->is_active ? 'completed' : 'error' }}">{{ $checker->is_active ? 'Faol' : 'Nofaol' }}</span></td>
                    <td class="text-right">
                        <button class="btn sm gray" wire:click="regenerate({{ $checker->id }})" wire:confirm="Yangi token yaratilsa, hozirgi terminal chiqib ketadi. Davom etilsinmi?">Yangi token</button>
                        <button class="btn sm red" wire:click="delete({{ $checker->id }})" wire:confirm="O‘chirilsinmi?">O‘chirish</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Hali narx tekshirgich yo‘q.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
