@section('title', 'Xabarlar')

<div>
    <div class="bar">
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.messages.create') }}">{{ __('+ Yangi xabar') }}</a>
    </div>

    <table>
        <thead><tr><th>{{ __('Sarlavha') }}</th><th>{{ __('Muhimlik') }}</th><th class="num">{{ __('Qabul qiluvchilar') }}</th><th class="num">{{ __('O‘qilgan') }}</th><th>{{ __('Yuborilgan') }}</th></tr></thead>
        <tbody>
            @forelse ($messages as $message)
                <tr wire:key="msg-{{ $message->id }}">
                    <td><a class="text-brand underline" href="{{ route('admin.messages.show', $message->id) }}">{{ $message->title }}</a></td>
                    <td>@if ($message->priority === 'urgent')<span class="badge error">{{ __('Shoshilinch') }}</span>@else {{ __('Oddiy') }} @endif</td>
                    <td class="num">{{ $message->recipients_count }}</td>
                    <td class="num">{{ $message->read_count }}</td>
                    <td>{{ $message->created_at->format('d.m.Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('Xabarlar yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $messages])
</div>