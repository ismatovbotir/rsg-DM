@section('title', $title)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('menu') }}'">
    <x-pda.page :title="$title" :back="route('menu')" :notice="$notice" :failure="$failure">
        <ul class="list">
            @forelse ($docs as $doc)
                <li wire:key="doc-{{ $doc->id }}">
                    <a class="row" href="{{ route('docs.show', $doc->id) }}">
                        <span class="label">
                            {{ $doc->number }}
                            <span class="sub">{{ $doc->created_at->format('d.m.Y H:i') }} · {{ $doc->lines_count }} ta tovar</span>
                        </span>
                        <span class="badge {{ $doc->status }}">{{ $doc->status_label }}</span>
                        @if (! $doc->isDraft() && $doc->syncs())
                            <span class="badge {{ $doc->onec_status }}">{{ $doc->sync_label }}</span>
                        @endif
                        @if ($doc->print_label)<span class="badge {{ $doc->print_state }}">{{ $doc->print_count ? ($doc->printed_at ? 'Chop etilgan' : 'Chop etilmagan') : 'Yorliqsiz' }}</span>@endif
                        <span class="chev">›</span>
                    </a>
                </li>
            @empty
                <li class="placeholder">Hujjatlar yo‘q.</li>
            @endforelse
        </ul>

        <x-slot:footer>
            <div class="footer">
                <a class="btn" href="{{ route('docs.create', $type) }}">+ Yangi hujjat</a>
            </div>
        </x-slot:footer>
    </x-pda.page>
</div>