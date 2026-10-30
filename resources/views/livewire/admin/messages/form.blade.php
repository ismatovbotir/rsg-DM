@section('title', 'Yangi xabar')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="send">
        <label>{{ __('Sarlavha') }} <input wire:model="title" required></label>
        <label>{{ __('Matn') }} <textarea wire:model="body" rows="5" required></textarea></label>
        <label>{{ __('Muhimlik') }}
            <select wire:model="priority">
                <option value="normal">{{ __('Oddiy') }}</option>
                <option value="urgent">{{ __('Shoshilinch (bosh sahifada ko‘rsatiladi)') }}</option>
            </select>
        </label>

        <fieldset>
            <legend>{{ __('Kimga') }}</legend>
            <label class="chk"><input type="radio" wire:model.live="audience" value="all"> {{ __('Barcha faol operatorlarga') }}</label>
            <label class="chk"><input type="radio" wire:model.live="audience" value="selected"> {{ __('Tanlanganlarga') }}</label>
            @if ($audience === 'selected')
                @foreach ($operators as $user)
                    <label class="chk ml-6" wire:key="op-{{ $user->id }}"><input type="checkbox" wire:model="users" value="{{ $user->id }}"> {{ $user->name }}</label>
                @endforeach
            @endif
        </fieldset>

        <div class="actions">
            <button class="btn">{{ __('Yuborish') }}</button>
            <a class="btn gray" href="{{ route('admin.messages.index') }}">{{ __('Bekor qilish') }}</a>
        </div>
    </form>
</div>