@section('title', $item['title'] ?? '')

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('menu') }}'">
    <x-pda.page :title="$item['title'] ?? ''" :back="route('menu')">
        <div class="placeholder">
            <div class="icon"><svg class="ico"><use href="#i-{{ $item['icon'] ?? 'directories' }}"/></svg></div>
            <h2>{{ $item['title'] ?? '' }}</h2>
            <p>Bu ekran hali tayyor emas.</p>
        </div>
    </x-pda.page>
</div>