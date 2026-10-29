@section('title', $type !== '' ? (\App\Models\Document::types()[$type] ?? 'Hujjatlar') : 'Hujjatlar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <select wire:model.live="type">
            <option value="">Barcha turlar</option>
            @foreach (collect(\App\Models\Document::types())->when(auth()->user()->adminDocumentTypes() !== null, fn ($c) => $c->only(auth()->user()->adminDocumentTypes())) as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <select wire:model.live="status">
            <option value="">Barcha holatlar</option>
            <option value="draft">Qoralama</option>
            <option value="completed">Yakunlangan</option>
        </select>
        <select wire:model.live="sync">
            <option value="">Adines: barchasi</option>
            <option value="pending">Kutilmoqda</option>
            <option value="synced">Yuborilgan</option>
            <option value="error">Xato</option>
        </select>
        <span class="grow"></span>
        <button class="btn" wire:click="syncAll" wire:loading.attr="disabled">Yakunlanganlarni Adines ga yuborish</button>
    </div>

    <table>
        <thead>
            <tr><th>Raqam</th><th>Turi</th><th>Operator</th><th class="num">Tovarlar</th><th>Holat</th><th>Adines / chop etish</th><th>Sana</th></tr>
        </thead>
        <tbody>
            @forelse ($docs as $doc)
                <tr wire:key="doc-{{ $doc->id }}">
                    <td><a href="{{ route('admin.documents.show', $doc->id) }}" class="text-brand underline">{{ $doc->number }}</a></td>
                    <td>{{ $doc->type_label }}</td>
                    <td>{{ $doc->user?->name }}</td>
                    <td class="num">{{ $doc->lines_count }}</td>
                    <td><span class="badge {{ $doc->status }}">{{ $doc->status_label }}</span></td>
                    <td>
                        @if (! $doc->isDraft() && $doc->syncs())<span class="badge {{ $doc->onec_status }}">{{ $doc->sync_label }}</span>@endif
                        @if ($doc->print_label)<span class="badge {{ $doc->print_state }}">{{ $doc->print_label }}</span>@endif
                    </td>
                    <td>{{ $doc->created_at->format('d.m.Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Hujjatlar yo‘q.</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $docs])
</div>