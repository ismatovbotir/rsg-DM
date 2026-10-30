@section('title', 'Yangi vazifa')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>{{ __('Vazifa nomi') }} <input wire:model="title" required></label>
        <label>{{ __('Izoh') }} <textarea wire:model="description" rows="3"></textarea></label>
        <label>{{ __('Ijrochi') }}
            <select wire:model="assignee_id" required>
                <option value="">{{ __('— tanlang —') }}</option>
                @foreach ($operators as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
            </select>
        </label>
        <label>{{ __('Operatsiya') }} <small>{{ __('(PDA da vazifadan shu hujjatni boshlash mumkin)') }}</small>
            <select wire:model="doc_type">
                <option value="">{{ __('— hujjatsiz vazifa —') }}</option>
                @foreach ($docTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
        </label>
        <label>{{ __('Muhimlik') }}
            <select wire:model="priority">
                <option value="normal">{{ __('Oddiy') }}</option>
                <option value="high">{{ __('Shoshilinch') }}</option>
                <option value="low">{{ __('Past') }}</option>
            </select>
        </label>
        <label>{{ __('Muddat') }} <input type="datetime-local" wire:model="due_at"></label>

        <div class="actions">
            <button class="btn">{{ __('Tayinlash') }}</button>
            <a class="btn gray" href="{{ route('admin.tasks.index') }}">{{ __('Bekor qilish') }}</a>
        </div>
    </form>
</div>