@section('title', 'Yangi vazifa')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>Vazifa nomi <input wire:model="title" required></label>
        <label>Izoh <textarea wire:model="description" rows="3"></textarea></label>
        <label>Ijrochi
            <select wire:model="assignee_id" required>
                <option value="">— tanlang —</option>
                @foreach ($operators as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
            </select>
        </label>
        <label>Operatsiya <small>(PDA da vazifadan shu hujjatni boshlash mumkin)</small>
            <select wire:model="doc_type">
                <option value="">— hujjatsiz vazifa —</option>
                @foreach ($docTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
        </label>
        <label>Muhimlik
            <select wire:model="priority">
                <option value="normal">Oddiy</option>
                <option value="high">Shoshilinch</option>
                <option value="low">Past</option>
            </select>
        </label>
        <label>Muddat <input type="datetime-local" wire:model="due_at"></label>

        <div class="actions">
            <button class="btn">Tayinlash</button>
            <a class="btn gray" href="{{ route('admin.tasks.index') }}">Bekor qilish</a>
        </div>
    </form>
</div>