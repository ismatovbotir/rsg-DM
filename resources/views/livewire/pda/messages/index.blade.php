@section('title', 'Xabarlar')

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('menu') }}'" wire:poll.30s>
    <x-pda.page title="{{ __('Xabarlar') }}" :back="route('menu')" :notice="$notice" :failure="$failure">
        <ul class="list">
            @forelse ($inbox as $r)
                <li wire:key="msg-{{ $r->id }}">
                    <a class="row" href="{{ route('messages.show', $r->message_id) }}">
                        <span class="label" @style(['font-weight:700' => ! $r->read_at])>
                            {{ $r->message->title }}
                            <span class="sub">{{ $r->message->created_at->format('d.m.Y H:i') }}</span>
                        </span>
                        @if ($r->message->priority === 'urgent')<span class="badge error">{{ __('Shoshilinch') }}</span>@endif
                        @unless ($r->read_at)<span class="badge error">{{ __('Yangi') }}</span>@endunless
                        <span class="chev">›</span>
                    </a>
                </li>
            @empty
                <li class="placeholder">{{ __('Xabar yo‘q.') }}</li>
            @endforelse
        </ul>
    </x-pda.page>
</div>