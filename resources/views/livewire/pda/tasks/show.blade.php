@section('title', $task->title)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('tasks.index') }}'">
    <x-pda.page :title="$task->title" :back="route('tasks.index')" :notice="$notice" :failure="$failure">
        <div class="pad">
            <span class="badge">{{ $task->status_label }}</span>
            @if ($task->priority === 'high')<span class="badge error">Shoshilinch</span>@endif
            @if ($task->isOverdue())<span class="badge error">Muddati o‘tgan</span>@endif

            @if ($task->description)<p class="whitespace-pre-wrap">{{ $task->description }}</p>@endif

            <span class="sub">
                @if ($task->doc_type_label)Operatsiya: {{ $task->doc_type_label }}<br>@endif
                @if ($task->due_at)Muddat: {{ $task->due_at->format('d.m.Y H:i') }}<br>@endif
                @if ($task->cancel_reason)Sabab: {{ $task->cancel_reason }}@endif
            </span>
        </div>

        @if ($task->documents->isNotEmpty())
            <p class="pad sub">Hujjatlar</p>
            <ul class="list">
                @foreach ($task->documents as $doc)
                    <li wire:key="doc-{{ $doc->id }}">
                        <a class="row" href="{{ route('docs.show', $doc->id) }}">
                            <span class="label">{{ $doc->number }}</span>
                            <span class="badge {{ $doc->status }}">{{ $doc->status_label }}</span>
                            <span class="chev">›</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($task->isOpen())
            <x-slot:footer>
                <div class="footer grid gap-2">
                    @if ($task->status === 'new')
                        <button type="button" class="btn green" wire:click="accept">Qabul qilish</button>
                        <form class="scanrow" wire:submit="decline">
                            <input class="field" wire:model="reason" placeholder="Rad etish sababi" required @kb>
                            <button class="btn red">Rad etish</button>
                        </form>
                    @elseif ($task->doc_type)
                        <a class="btn" href="{{ route('docs.create', ['type' => $task->doc_type, 'task' => $task->id]) }}">
                            {{ $task->documents->isEmpty() ? 'Boshlash' : 'Yana hujjat' }}: {{ $task->doc_type_label }}
                        </a>
                    @else
                        <button type="button" class="btn green" wire:click="complete">Bajarildi</button>
                    @endif
                </div>
            </x-slot:footer>
        @endif
    </x-pda.page>
</div>