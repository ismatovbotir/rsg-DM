@section('title', $title)

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        @foreach ($fields as $key => $field)
            @if ($field['type'] === 'checkbox')
                <label class="chk"><input type="checkbox" wire:model="data.{{ $key }}"> {{ $field['label'] }}</label>
            @else
                <label>{{ $field['label'] }}
                    <input type="{{ $field['type'] }}" wire:model="data.{{ $key }}" @foreach ($field['attrs'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach>
                </label>
            @endif
        @endforeach

        <div class="actions">
            <button class="btn">Saqlash</button>
            <a class="btn gray" href="{{ $back }}">Bekor qilish</a>
        </div>
    </form>
</div>
