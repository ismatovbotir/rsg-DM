@section('title', 'Yangi xabar')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="send">
        <label>Sarlavha <input wire:model="title" required></label>
        <label>Matn <textarea wire:model="body" rows="5" required></textarea></label>
        <label>Muhimlik
            <select wire:model="priority">
                <option value="normal">Oddiy</option>
                <option value="urgent">Shoshilinch (bosh sahifada ko‘rsatiladi)</option>
            </select>
        </label>

        <fieldset>
            <legend>Kimga</legend>
            <label class="chk"><input type="radio" wire:model.live="audience" value="all"> Barcha faol operatorlarga</label>
            <label class="chk"><input type="radio" wire:model.live="audience" value="selected"> Tanlanganlarga</label>
            @if ($audience === 'selected')
                @foreach ($operators as $user)
                    <label class="chk ml-6" wire:key="op-{{ $user->id }}"><input type="checkbox" wire:model="users" value="{{ $user->id }}"> {{ $user->name }}</label>
                @endforeach
            @endif
        </fieldset>

        <div class="actions">
            <button class="btn">Yuborish</button>
            <a class="btn gray" href="{{ route('admin.messages.index') }}">Bekor qilish</a>
        </div>
    </form>
</div>