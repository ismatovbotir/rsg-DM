@section('title', $message->title)

<div wire:poll.15s>
    <div class="bar">
        <a class="btn gray" href="{{ route('admin.messages.index') }}">{{ __('← Ro‘yxat') }}</a>
        @if ($message->priority === 'urgent')<span class="badge error">{{ __('Shoshilinch') }}</span>@endif
    </div>

    <div class="card !block mb-4">
        <p class="mt-0 whitespace-pre-wrap">{{ $message->body }}</p>
        <small>{{ $message->created_at->format('d.m.Y H:i') }}</small>
    </div>

    <table>
        <thead><tr><th>{{ __('Qabul qiluvchi') }}</th><th>{{ __('O‘qilgan') }}</th></tr></thead>
        <tbody>
            @foreach ($message->recipients as $recipient)
                <tr wire:key="rcp-{{ $recipient->id }}">
                    <td>{{ $recipient->user?->name }}</td>
                    <td>{{ $recipient->read_at ? $recipient->read_at->format('d.m.Y H:i') : '— o‘qilmagan —' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>