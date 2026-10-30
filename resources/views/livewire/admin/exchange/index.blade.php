@section('title', 'Adines almashinuvi')

<div wire:poll.10s>
    <div class="bar">
        
            <select wire:model.live="entity">
                <option value="">{{ __('Barcha turlar') }}</option>
                @foreach ($entities as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
            </select>
            <label class="flex items-center gap-1.5"><input type="checkbox" wire:model.live="errors"> {{ __('Faqat xatolar') }}</label>
        
        <span class="grow"></span>
        <span class="sub">{{ __('POST /api/v1/sync/{tur}, /api/shop|price|priceData|stock — har so‘rovda ko‘pi bilan 500 qator') }}</span>
    </div>

    <table>
        <thead><tr><th>{{ __('Vaqt') }}</th><th>{{ __('Tur') }}</th><th class="num">{{ __('Qator') }}</th><th class="num">{{ __('Yangi') }}</th><th class="num">{{ __('Yangilangan') }}</th><th class="num">{{ __('Xato') }}</th><th>{{ __('HTTP') }}</th><th class="num">{{ __('ms') }}</th><th>{{ __('IP') }}</th></tr></thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                    <td>{{ $log->entity }}</td>
                    <td class="num">{{ $log->rows }}</td>
                    <td class="num">{{ $log->created }}</td>
                    <td class="num">{{ $log->updated }}</td>
                    <td class="num">{{ $log->errors_count }}</td>
                    <td><span class="badge {{ $log->http_status < 300 ? 'completed' : 'error' }}">{{ $log->http_status }}</span></td>
                    <td class="num">{{ $log->duration_ms }}</td>
                    <td>{{ $log->ip }}</td>
                </tr>
                @if ($log->errors_count)
                    <tr>
                        <td colspan="9">
                            <details>
                                <summary>{{ __('Xatolar (') }}{{ $log->errors_count }}@if ($log->errors_count > 50){{ __(', birinchi 50 tasi ko‘rsatilgan') }} @endif)</summary>
                                @foreach ($log->details ?? [] as $d)
                                    <div class="sub">@isset($d['index']) #{{ $d['index'] }}: @endisset{{ $d['message'] }}</div>
                                @endforeach
                            </details>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="9">{{ __('Adines hali ma’lumot yubormagan.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.lw-pager', ['paginator' => $logs])
</div>
