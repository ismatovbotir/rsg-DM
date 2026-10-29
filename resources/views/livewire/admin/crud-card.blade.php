@section('title', $record->name)

<div>
    @include('partials.lw-flash')

    <div class="text-xs text-ink-muted"><a class="lnk" href="{{ $back }}">{{ $listTitle }}</a> / {{ $record->name }}</div>

    <div class="bar">
        <h1 class="m-0 text-base font-semibold">{{ $record->name }}</h1>
        @if ($hasStatus)<span class="badge {{ $record->is_active ? 'completed' : 'error' }}">{{ $record->is_active ? 'Faol' : 'Nofaol' }}</span>@endif
        <span class="badge">Adines · faqat ko‘rish</span>
        <span class="grow"></span>
        <a class="btn sm gray" href="{{ $back }}">Yopish</a>
    </div>

    @if ($figures)
        <div class="grid grid-cols-2 gap-px overflow-hidden rounded border border-line bg-line sm:grid-cols-4">
            @foreach ($figures as [$label, $value])
                <div class="bg-white px-3 py-2"><div class="text-[11px] uppercase text-ink-muted">{{ $label }}</div><div class="text-base font-semibold tabular-nums">{{ $value }}</div></div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="full">
            <table>
                <tbody>
                    @foreach ($rows as [$label, $value])
                        <tr><th class="w-56">{{ $label }}</th><td>{{ $value === null || $value === '' ? '—' : $value }}</td></tr>
                    @endforeach
                    <tr><th class="w-56">Yaratilgan</th><td>{{ $record->created_at?->format('d.m.Y H:i') ?? '—' }}</td></tr>
                    <tr><th>Yangilangan (Adines)</th><td>{{ $record->updated_at?->format('d.m.Y H:i') ?? '—' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
