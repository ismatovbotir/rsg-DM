@section('title', $message->title)

<div wire:poll.15s>
    <div class="bar">
        <a class="btn gray" href="{{ route('admin.messages.index') }}">← Ro‘yxat</a>
        @if ($message->priority === 'urgent')<span class="badge error">Shoshilinch</span>@endif
    </div>

    <div class="card !block mb-4">
        <p class="mt-0 whitespace-pre-wrap">{{ $message->body }}</p>
        <small>{{ $message->created_at->format('d.m.Y H:i') }}</small>
    </div>

    <table>
        <thead><tr><th>Qabul qiluvchi</th><th>O‘qilgan</th></tr></thead>
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