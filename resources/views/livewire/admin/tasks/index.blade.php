@section('title', 'Vazifalar')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <select wire:model.live="status">
            <option value="">Barcha holatlar</option>
            @foreach (['new' => 'Yangi', 'accepted' => 'Qabul qilingan', 'in_progress' => 'Jarayonda', 'done' => 'Bajarilgan', 'declined' => 'Rad etilgan', 'cancelled' => 'Bekor qilingan'] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.tasks.create') }}">+ Yangi vazifa</a>
    </div>

    <table>
        <thead><tr><th>Vazifa</th><th>Ijrochi</th><th>Operatsiya</th><th>Muhimlik</th><th>Muddat</th><th>Holat</th><th class="num">Hujjat</th><th></th></tr></thead>
        <tbody>
            @forelse ($tasks as $task)
                <tr wire:key="task-{{ $task->id }}">
                    <td>{{ $task->title }}@if ($task->cancel_reason)<br><small>Sabab: {{ $task->cancel_reason }}</small>@endif</td>
                    <td>{{ $task->assignee?->name }}</td>
                    <td>{{ $task->doc_type_label }}</td>
                    <td>{{ $task->priority_label }}</td>
                    <td>
                        {{ $task->due_at?->format('d.m.Y H:i') }}
                        @if ($task->isOverdue())<span class="badge error">Muddati o‘tgan</span>@endif
                    </td>
                    <td><span class="badge {{ $task->status === 'done' ? 'completed' : ($task->status === 'declined' || $task->status === 'cancelled' ? 'error' : '') }}">{{ $task->status_label }}</span></td>
                    <td class="num">{{ $task->documents_count }}</td>
                    <td class="text-right">
                        @if ($task->isOpen())
                            <input wire:model="reasons.{{ $task->id }}" placeholder="Sabab" size="12">
                            <button class="btn red" wire:click="cancel({{ $task->id }})" wire:confirm="Vazifa bekor qilinsinmi?">Bekor qilish</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Vazifalar yo‘q.</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $tasks])
</div>