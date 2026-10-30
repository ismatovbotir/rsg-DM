@section('title', 'Vazifalar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <select wire:model.live="status">
            <option value="">{{ __('Barcha holatlar') }}</option>
            @foreach (['new' => 'Yangi', 'accepted' => 'Qabul qilingan', 'in_progress' => 'Jarayonda', 'done' => 'Bajarilgan', 'declined' => 'Rad etilgan', 'cancelled' => 'Bekor qilingan'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.tasks.create') }}">{{ __('+ Yangi vazifa') }}</a>
    </div>

    <table>
        <thead><tr><th>{{ __('Vazifa') }}</th><th>{{ __('Ijrochi') }}</th><th>{{ __('Operatsiya') }}</th><th>{{ __('Muhimlik') }}</th><th>{{ __('Muddat') }}</th><th>{{ __('Holat') }}</th><th class="num">{{ __('Hujjat') }}</th><th></th></tr></thead>
        <tbody>
            @forelse ($tasks as $task)
                <tr wire:key="task-{{ $task->id }}">
                    <td>{{ $task->title }}@if ($task->cancel_reason)<br><small>{{ __('Sabab:') }} {{ $task->cancel_reason }}</small>@endif</td>
                    <td>{{ $task->assignee?->name }}</td>
                    <td>{{ $task->doc_type_label }}</td>
                    <td>{{ $task->priority_label }}</td>
                    <td>
                        {{ $task->due_at?->format('d.m.Y H:i') }}
                        @if ($task->isOverdue())<span class="badge error">{{ __('Muddati o‘tgan') }}</span>@endif
                    </td>
                    <td><span class="badge {{ $task->status === 'done' ? 'completed' : ($task->status === 'declined' || $task->status === 'cancelled' ? 'error' : '') }}">{{ $task->status_label }}</span></td>
                    <td class="num">{{ $task->documents_count }}</td>
                    <td class="text-right">
                        @if ($task->isOpen())
                            <input wire:model="reasons.{{ $task->id }}" placeholder="{{ __('Sabab') }}" size="12">
                            <button class="btn red" wire:click="cancel({{ $task->id }})" wire:confirm="{{ __('Vazifa bekor qilinsinmi?') }}">{{ __('Bekor qilish') }}</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('Vazifalar yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $tasks])
</div>