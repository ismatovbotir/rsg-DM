@section('title', 'Narx tekshirgich dizaynlari')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span class="hint">Dizaynni narx tekshirgich sozlamalarida tanlaysiz. Asosiy dizayn ulanish (QR) ekrani va dizayni tanlanmagan terminallar uchun.</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.themes.create') }}">+ Yangi dizayn</a>
    </div>

    <table>
        <thead><tr><th>Nomi</th><th>Ekran</th><th class="num">Narx tekshirgichlar</th><th>Asosiy</th><th></th></tr></thead>
        <tbody>
            @forelse ($themes as $theme)
                @php $s = $theme->screen; @endphp
                <tr wire:key="theme-{{ $theme->id }}">
                    <td><a class="lnk" href="{{ route('admin.themes.edit', $theme->id) }}">{{ $theme->name }}</a></td>
                    <td><span class="inline-block h-4 w-4 rounded align-middle" style="background:{{ $s['bg'] }}"></span> <span class="inline-block h-4 w-4 rounded align-middle" style="background:{{ $s['price_color'] }}"></span> <span class="hint">{{ $s['rotation'] }}°, {{ \App\Support\CheckerDesign::LAYOUTS[$s['layout']] }}</span></td>
                    <td class="num">{{ $theme->checkers_count }}</td>
                    <td>
                        @if ($theme->is_default)<span class="badge completed">Asosiy</span>
                        @else<button class="btn sm gray" wire:click="makeDefault({{ $theme->id }})">Asosiy qilish</button>@endif
                    </td>
                    <td class="text-right"><button class="btn sm red" wire:click="delete({{ $theme->id }})" wire:confirm="Dizayn o‘chirilsinmi?">O‘chirish</button></td>
                </tr>
            @empty
                <tr><td colspan="5">Hali dizayn yo‘q.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
