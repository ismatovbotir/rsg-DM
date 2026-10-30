@section('title', $message->title)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('messages.index') }}'">
    <x-pda.page :title="$message->title" :back="route('messages.index')" :notice="$notice" :failure="$failure">
        <div class="pad">
            @if ($message->priority === 'urgent')<span class="badge error">{{ __('Shoshilinch') }}</span>@endif
            <p class="whitespace-pre-wrap text-[17px]">{{ $message->body }}</p>
            <span class="sub">{{ $message->sender?->name }} · {{ $message->created_at->format('d.m.Y H:i') }}</span>
        </div>
    </x-pda.page>
</div>