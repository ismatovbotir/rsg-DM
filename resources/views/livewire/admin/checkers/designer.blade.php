@php
    $fonts = \App\Support\CheckerDesign::FONTS;
    $w = $portrait ? 315 : 560;
    $h = $portrait ? 560 : 315;
@endphp
<div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,560px)]">
    <div>
        @if ($notice)<div class="flash">{{ $notice }}</div>@endif

        <details class="panel" open>
            <summary class="cursor-pointer text-sm font-semibold">Joylashuv va burilish</summary>
            <div class="mt-3 grid gap-x-4 sm:grid-cols-2">
                <label>Joylashuv
                    <select wire:model.live="design.layout">
                        @foreach (\App\Support\CheckerDesign::LAYOUTS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label>Ekran burilishi (terminal o‘rnatilgan holat)
                    <select wire:model.live="design.rotation">@foreach ([0, 90, 180, 270] as $r)<option value="{{ $r }}">{{ $r }}°</option>@endforeach</select>
                </label>
                <label>Tekislash
                    <select wire:model.live="design.align">
                        @foreach (\App\Support\CheckerDesign::ALIGNS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label>Shrift
                    <select wire:model.live="design.font">@foreach ($fonts as $key => $f)<option value="{{ $key }}">{{ $f[0] }}</option>@endforeach</select>
                </label>
                <label>Tovar nomi o‘lchami: {{ $safe['name_scale'] }}%
                    <input type="range" min="50" max="200" step="5" wire:model.live.debounce.100ms="design.name_scale">
                </label>
                <label>Narx o‘lchami: {{ $safe['price_scale'] }}%
                    <input type="range" min="50" max="200" step="5" wire:model.live.debounce.100ms="design.price_scale">
                </label>
            </div>
        </details>

        <details class="panel" open>
            <summary class="cursor-pointer text-sm font-semibold">Ranglar</summary>
            <div class="mt-3 grid grid-cols-2 gap-x-4 sm:grid-cols-3">
                @foreach (['bg' => 'Fon', 'text' => 'Matn', 'name_color' => 'Tovar nomi', 'price_color' => 'Narx', 'error_color' => 'Xato xabari', 'bar_bg' => 'Yuqori/pastki panel', 'bar_text' => 'Panel matni'] as $key => $label)
                    <label>{{ $label }} <input type="color" class="h-10 cursor-pointer p-1" wire:model.live="design.{{ $key }}"></label>
                @endforeach
            </div>
        </details>

        <details class="panel" open>
            <summary class="cursor-pointer text-sm font-semibold">Matnlar</summary>
            <div class="mt-3 grid gap-x-4 sm:grid-cols-2">
                <label>Kutish matni <input wire:model.live.debounce.250ms="design.hint_text" maxlength="120"></label>
                <label>«Topilmadi» matni <input wire:model.live.debounce.250ms="design.not_found_text" maxlength="120"></label>
                <label>Valyuta <input wire:model.live.debounce.250ms="design.currency_text" maxlength="20"></label>
                <label>Terminal ko‘rsatish vaqti, soniya <input type="number" min="3" max="60" wire:model.live.debounce.250ms="design.timeout"></label>
                <label>Yuqori panel matni <input wire:model.live.debounce.250ms="design.header_text" maxlength="80" placeholder="{{ $checker->name }}"></label>
                <label>Pastki panel matni <input wire:model.live.debounce.250ms="design.footer_text" maxlength="80" placeholder="{{ config('menu.app_name') }}"></label>
            </div>
            <div class="mt-1 flex flex-wrap gap-x-6">
                <label class="chk"><input type="checkbox" wire:model.live="design.show_header"> Yuqori panel</label>
                <label class="chk"><input type="checkbox" wire:model.live="design.show_footer"> Pastki panel</label>
                <label class="chk"><input type="checkbox" wire:model.live="design.show_unit"> Birlikni ko‘rsatish</label>
                <label class="chk"><input type="checkbox" wire:model.live="design.show_code"> Tovar kodini ko‘rsatish</label>
            </div>
        </details>

        <div class="actions">
            <button type="button" class="btn" wire:click="save">Saqlash va terminalga yuborish</button>
            <button type="button" class="btn gray" wire:click="resetDefaults" wire:confirm="Standart sozlamalar tiklansinmi?">Standartga qaytarish</button>
            @if ($others->isNotEmpty())
                <select class="max-w-[240px]" wire:change="copyFrom($event.target.value)">
                    <option value="">Boshqasidan nusxa olish…</option>
                    @foreach ($others as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                </select>
            @endif
        </div>
    </div>

    <div class="sticky top-0">
        <div class="panel">
            <h3>Ko‘rinishi (hozir)</h3>
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <div class="actions">
                    @foreach (['idle' => 'Kutish', 'result' => 'Natija', 'error' => 'Topilmadi'] as $key => $label)
                        <button type="button" class="btn {{ $state === $key ? '' : 'gray' }}" wire:click="$set('state', '{{ $key }}')">{{ $label }}</button>
                    @endforeach
                </div>
                <label class="chk m-0"><input type="checkbox" wire:model.live="portrait"> Vertikal ekran</label>
            </div>
            <div class="mx-auto overflow-hidden rounded-md shadow-lg" style="width:{{ $w }}px;max-width:100%">
                @include('pricechecker._stage', ['d' => $safe, 'title' => $checker->name, 'state' => $state, 'item' => $item, 'w' => $w.'px', 'h' => $h.'px', 'u' => (min($w, $h) / 100).'px', 'id' => 'pcs-preview'])
            </div>
            @if ($items->isNotEmpty())
                <label class="mt-3">Namuna tovar
                    <select wire:model.live="sample">
                        @foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            <p class="hint">Burilish: ko‘rinish terminal ekranining haqiqiy holatini ko‘rsatadi (masalan 90° — ekran yonboshiga o‘rnatilgan).</p>
        </div>
    </div>
</div>
