@section('title', $group->name)

<form wire:submit="save">
    @include('partials.lw-flash')

    <div class="bar">
        <a class="btn gray" href="{{ route('admin.groups.index') }}">{{ __('← Guruhlar') }}</a>
        <strong>{{ $group->path }}</strong>
        <span class="sub">{{ $group->items_count }} {{ __('ta tovar') }}</span>
        <span class="grow"></span>
        <button class="btn">{{ __('Saqlash') }}</button>
    </div>

    <div class="panel">
        <h3 class="mt-0">{{ __('Narx tekshirgich ranglar sxemasi') }}</h3>
        <p class="hint">{{ __('Standart: narx tekshirgich dizaynidagi ranglar. Boshqa sxema tanlansa, shu guruh tovarlari narx tekshirgich natija ekranida o‘sha ranglarda chiqadi.') }}@if ($inherited) {{ __('Hozircha yuqori guruhdan:') }} <strong>{{ \App\Support\CheckerDesign::PALETTES[$inherited][0] }}</strong>.@endif {{ __('Kichik guruhlar o‘z sxemasi bo‘lmasa yuqori guruhnikini oladi.') }}</p>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-5">
            <button type="button" wire:click="choose('')"
                    class="flex min-h-[64px] flex-col items-center justify-center rounded border px-2 text-center text-sm {{ $color_scheme === '' ? 'border-brand ring-2 ring-brand' : 'border-line' }}">
                <strong>{{ __('Dizayn bo‘yicha') }}</strong><span class="sub">{{ __('standart') }}</span>
            </button>
            @foreach (\App\Support\CheckerDesign::PALETTES as $key => $row)
                @php $c = \App\Support\CheckerDesign::palette($key); @endphp
                <button type="button" wire:click="choose('{{ $key }}')" title="{{ $row[0] }}"
                        class="flex flex-col overflow-hidden rounded border text-left {{ $color_scheme === $key ? 'border-brand ring-2 ring-brand' : 'border-line' }}">
                    <span class="flex items-center gap-1.5 px-2 py-1.5" style="background:{{ $c['bg'] }}">
                        <b class="text-sm" style="color:{{ $c['price_color'] }}">4 000</b>
                        <span class="text-[10px]" style="color:{{ $c['name_color'] }}">{{ __('Non') }}</span>
                        <i class="ml-auto h-2 w-3 rounded-sm" style="background:{{ $c['badge_bg'] }}"></i>
                    </span>
                    <span class="px-2 py-0.5 text-[11px]" style="background:{{ $c['bar_bg'] }};color:{{ $c['bar_text'] }}">{{ $row[0] }}</span>
                </button>
            @endforeach
        </div>
        @error('color_scheme')<div class="flash error">{{ $message }}</div>@enderror
    </div>
</form>
