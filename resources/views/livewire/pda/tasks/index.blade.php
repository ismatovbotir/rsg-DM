@section('title', 'Vazifalarim')

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('menu') }}'" wire:poll.30s>
    <x-pda.page title="{{ __('Vazifalarim') }}" :back="route('menu')" :notice="$notice" :failure="$failure">
        <ul class="list">
            @forelse ($open as $task)
                <li wire:key="open-{{ $task->id }}">
                    <a class="row" href="{{ route('tasks.show', $task->id) }}">
                        <span class="label">
                            {{ $task->title }}
                            <span class="sub">
                                {{ $task->doc_type_label ?: 'Vazifa' }}
                                @if ($task->due_at) {{ __('· muddat') }} {{ $task->due_at->format('d.m H:i') }}@endif
                            </span>
                        </span>
                        @if ($task->priority === 'high')<span class="badge error">{{ __('Shoshilinch') }}</span>@endif
                        @if ($task->isOverdue())<span class="badge error">{{ __('Muddati o‘tgan') }}</span>@endif
                        <span class="badge">{{ $task->status_label }}</span>
                        <span class="chev">›</span>
                    </a>
                </li>
            @empty
                <li class="placeholder">{{ __('Ochiq vazifa yo‘q.') }}</li>
            @endforelse
        </ul>

        @if ($recent->isNotEmpty())
            <p class="pad sub">{{ __('Oxirgilari') }}</p>
            <ul class="list">
                @foreach ($recent as $task)
                    <li wire:key="recent-{{ $task->id }}">
                        <a class="row" href="{{ route('tasks.show', $task->id) }}">
                            <span class="label">{{ $task->title }}</span>
                            <span class="badge {{ $task->status === 'done' ? 'completed' : 'error' }}">{{ $task->status_label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-pda.page>
</div>