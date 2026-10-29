@section('title', $title)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('menu') }}'">
    <x-pda.page :title="$title" :back="route('menu')" :notice="$notice" :failure="$failure">
        @if ($check)
            {{-- price check start: scope + one tap; the shop and its price list come from the user --}}
            <form class="pad" wire:submit="startCheck">
                <h2 class="m-0 mb-2 text-lg">Yangi tekshiruv</h2>
                @foreach ($errors->all() as $error)<div class="flash error !mx-0">{{ $error }}</div>@endforeach

                @if ($warehouses->isNotEmpty())
                    <label class="lbl">Do‘kon (ombor)
                        <select class="field" wire:model="warehouse_id"><option value="">— tanlang —</option>@foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
                    </label>
                @elseif ($shop)
                    <p class="sub m-0 mb-2">Do‘kon: <strong>{{ $shop->name }}</strong>@if ($shop->priceType ?? null) · Narx turi: <strong>{{ $shop->priceType->name }}</strong>@endif</p>
                @endif
                @if ($priceTypes->isNotEmpty())
                    <label class="lbl">Narx turi
                        <select class="field" wire:model="price_type_id"><option value="">— tanlang —</option>@foreach ($priceTypes as $pt)<option value="{{ $pt->id }}">{{ $pt->name }}</option>@endforeach</select>
                    </label>
                @endif

                <div class="lbl">Nima tekshiramiz?</div>
                <label class="row !min-h-[48px]"><input type="radio" wire:model.live="scope" value="all"> Butun do‘kon</label>
                <label class="row !min-h-[48px]"><input type="radio" wire:model.live="scope" value="group"> Guruh</label>
                @if ($scope === 'group')
                    <select class="field" wire:model="group_id"><option value="">— guruhni tanlang —</option>@foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->path }}</option>@endforeach</select>
                @endif
                <label class="row !min-h-[48px]"><input type="radio" wire:model.live="scope" value="free"> Erkin skanerlash</label>

                <button class="btn mt-3 w-full">Boshlash</button>
            </form>
            @if ($docs->isNotEmpty())<h3 class="px-3 pb-1 pt-3 text-sm font-semibold text-ink-muted">Tekshiruvlar</h3>@endif
        @endif

        <ul class="list">
            @forelse ($docs as $doc)
                <li wire:key="doc-{{ $doc->id }}">
                    <a class="row" href="{{ route('docs.show', $doc->id) }}">
                        <span class="label">
                            {{ $doc->number }}
                            <span class="sub">{{ $doc->created_at->format('d.m.Y H:i') }} · {{ $doc->lines_count }} ta tovar @if ($check && $doc->hasCheckList() && isset($progress[$doc->id]) && $progress[$doc->id][1] > 0) · {{ $progress[$doc->id][0] }} / {{ $progress[$doc->id][1] }}@endif@if ($check && $doc->scope === 'group' && $doc->group) · {{ $doc->group->name }}@endif</span>
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
            @unless ($check)
                <div class="footer">
                    <a class="btn" href="{{ route('docs.create', $type) }}">+ Yangi hujjat</a>
                </div>
            @endunless
        </x-slot:footer>
    </x-pda.page>
</div>