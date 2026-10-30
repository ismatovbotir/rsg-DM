@section('title', $doc->number)

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <a class="btn gray" href="{{ route('admin.documents.index') }}">{{ __('← Ro‘yxat') }}</a>
        <span class="badge {{ $doc->status }}">{{ $doc->status_label }}</span>
        @if (! $doc->isDraft() && $doc->syncs())<span class="badge {{ $doc->onec_status }}">{{ $doc->sync_label }}</span>@endif
        @if ($doc->print_label)<span class="badge {{ $doc->print_state }}">{{ $doc->print_label }}</span>@endif
        <span class="grow"></span>
        @if ($doc->isPriceCheck() && ! $doc->isDraft())
            <button class="btn" wire:click="print" wire:loading.attr="disabled" @disabled($doc->print_count === 0)>{{ $doc->printed_at ? 'Qayta chop etish' : 'Chop etish' }}</button>
        @endif
        @if (! $doc->isDraft() && $doc->syncs() && $doc->onec_status !== 'synced')
            <button class="btn" wire:click="sync" wire:loading.attr="disabled">{{ __('Adines ga yuborish') }}</button>
        @endif
    </div>

    <div class="card !block mb-4">
        <strong>{{ $doc->type_label }}</strong> · {{ $doc->user?->name }}
        {{ __('· yaratilgan') }} {{ $doc->created_at->format('d.m.Y H:i') }}
        @if ($doc->completed_at) {{ __('· yakunlangan') }} {{ $doc->completed_at->format('d.m.Y H:i') }}@endif
        @if ($doc->printed_at) {{ __('· chop etilgan') }} {{ $doc->printed_at->format('d.m.Y H:i') }}@endif
        @if ($doc->synced_at) {{ __('· Adines ga yuborilgan') }} {{ $doc->synced_at->format('d.m.Y H:i') }}@endif
        <table class="mt-3">
            @foreach ($doc->headerDisplay() as $name => $value)
                <tr>
                    <th class="w-56">{{ $doc->headerFields()[$name]['label'] }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </div>
    @if ($doc->onec_error)<div class="flash error">{{ $doc->onec_error }}</div>@endif

    <table>
        <thead>
            <tr>
                <th>{{ __('Kod') }}</th><th>{{ __('Artikul') }}</th><th>{{ __('Shtrix-kod') }}</th><th>{{ __('Nomi') }}</th><th>{{ __('Birlik') }}</th>
                @if ($doc->isPriceCheck())<th class="num">{{ __('Tizim narxi') }}</th><th class="num">{{ __('Javondagi narx') }}</th><th>{{ __('Natija') }}</th>
                @else<th class="num">{{ __('Miqdor') }}</th><th class="num">{{ __('Narx') }}</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($doc->lines as $line)
                <tr wire:key="line-{{ $line->id }}">
                    <td>{{ $line->item->code }}</td>
                    <td>{{ $line->item->article }}</td>
                    <td>{{ $line->item->barcode }}</td>
                    <td>{{ $line->item->name }}</td>
                    <td>{{ $line->item->unit }}</td>
                    @if ($doc->isPriceCheck())
                        <td class="num">{{ number_format($line->price, 2, '.', ' ') }}</td>
                        <td class="num">{{ $line->shelf_price !== null ? number_format($line->shelf_price, 2, '.', ' ') : '' }}</td>
                        <td><span class="badge {{ $line->check_action === 'print' ? 'error' : 'completed' }}">{{ $line->check_action === 'print' ? 'Yorliq chop etilsin' : 'Narx to‘g‘ri' }}</span></td>
                    @else
                        <td class="num">{{ $doc->canEditQty() ? $line->qty + 0 : '' }}</td>
                        <td class="num">{{ $doc->canEditPrice() ? number_format($line->price, 2, '.', ' ') : '' }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9">{{ __('Qatorlar yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>