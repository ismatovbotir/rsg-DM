@section('title', $theme ? 'Dizayn: '.$theme->name : 'Yangi narx tekshirgich dizayni')

@php
    $fonts = \App\Support\CheckerDesign::FONTS;
    $w = $portrait ? 400 : 640;
    $h = $portrait ? 640 : 400;
    $bool = fn ($key, $label) => '<label class="chk"><input type="checkbox" wire:model.live="design.'.$key.'"> '.e($label).'</label>';
@endphp
<div>
    @include('partials.lw-flash')

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,660px)]">
        <div>
            <div class="panel">
                <div class="grid gap-x-4 sm:grid-cols-[1fr_auto] sm:items-end">
                    <label>Dizayn nomi <input wire:model="name" maxlength="100" placeholder="Masalan: Standart 10 dyuym"></label>
                    <div class="actions mb-2.5">
                        <button type="button" class="btn" wire:click="save">Saqlash</button>
                        <a class="btn gray" href="{{ route('admin.themes.index') }}">Ro‘yxat</a>
                    </div>
                </div>
                @error('name')<div class="flash error">{{ $message }}</div>@enderror
            </div>

            <details class="panel" open>
                <summary class="cursor-pointer text-sm font-semibold">Rasmlar</summary>
                <div class="mt-3 grid gap-x-4 sm:grid-cols-2">
                    <div>
                        <label>Orqa fon rasmi (JPG/PNG/WEBP, 4 MB gacha) <input type="file" accept="image/*" wire:model="bgUpload"></label>
                        @if ($safe['bg_image'])
                            <img class="mb-2 h-16 rounded border border-line" src="{{ \App\Support\CheckerDesign::imageUrl($safe['bg_image']) }}" alt="">
                            <button type="button" class="btn sm gray" wire:click="removeImage('bg_image')">Olib tashlash</button>
                        @endif
                        <label class="mt-2">Qoraytirish: {{ $safe['bg_dim'] }}% <input type="range" min="0" max="90" step="5" wire:model.live.debounce.100ms="design.bg_dim"></label>
                    </div>
                    <div>
                        <label>Logotip (shaffof PNG yaxshi) <input type="file" accept="image/*" wire:model="logoUpload"></label>
                        @if ($safe['logo'])
                            <img class="mb-2 h-12 rounded border border-line bg-slate-700 p-1" src="{{ \App\Support\CheckerDesign::imageUrl($safe['logo']) }}" alt="">
                            <button type="button" class="btn sm gray" wire:click="removeImage('logo')">Olib tashlash</button>
                        @endif
                        <label class="mt-2">Logotip o‘lchami: {{ $safe['logo_scale'] }}% <input type="range" min="50" max="250" step="10" wire:model.live.debounce.100ms="design.logo_scale"></label>
                    </div>
                </div>
                <div class="mt-3 border-t border-line pt-3">
                    <label>Kutish ekrani bannerlari (bir nechta rasm tanlash mumkin, ko‘pi bilan 8 ta)
                        <input type="file" accept="image/*" multiple wire:model="bannerUploads">
                    </label>
                    @if (! empty($safe['banners']))
                        <div class="mb-2 flex flex-wrap gap-2">
                            @foreach ($safe['banners'] as $i => $b)
                                <div class="relative" wire:key="banner-{{ $i }}-{{ $b }}">
                                    <img class="h-16 rounded border border-line" src="{{ \App\Support\CheckerDesign::imageUrl($b) }}" alt="">
                                    <button type="button" class="btn sm red absolute right-0.5 top-0.5" wire:click="removeBanner({{ $i }})" title="Olib tashlash">×</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label>Bannerlar almashish vaqti, soniya <input type="number" min="2" max="60" wire:model.live.debounce.250ms="design.banner_interval"></label>
                    <p class="hint">Bannerlar kutish ekranida to‘liq ekran slayd-shou bo‘lib aylanadi; kutish matni pastda ustiga chiqadi. Tavsiya: 1280x720 yoki 1280x800, JPG.</p>
                </div>
                <div wire:loading wire:target="bgUpload,logoUpload,bannerUploads" class="hint">Rasm yuklanmoqda…</div>
                @error('f')<div class="flash error">{{ $message }}</div>@enderror
                <p class="hint">Tovar rasmi tovar kartochkasidan olinadi. Matn rasm ustida o‘qilishi qiyin bo‘lsa, «Qoraytirish» yoki «Ma’lumot kartasi» shaffofligini oshiring.</p>
            </details>

            <details class="panel" open>
                <summary class="cursor-pointer text-sm font-semibold">Joylashuv va burilish</summary>
                <div class="mt-3 grid gap-x-4 sm:grid-cols-2">
                    <label>Matn joylashuvi
                        <select wire:model.live="design.layout">@foreach (\App\Support\CheckerDesign::LAYOUTS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                    </label>
                    <label>Ekran burilishi (terminal o‘rnatilgan holat)
                        <select wire:model.live="design.rotation">@foreach ([0, 90, 180, 270] as $r)<option value="{{ $r }}">{{ $r }}°</option>@endforeach</select>
                    </label>
                    <label>Tekislash
                        <select wire:model.live="design.align">@foreach (\App\Support\CheckerDesign::ALIGNS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                    </label>
                    <label>Tovar rasmi
                        <select wire:model.live="design.image_pos">@foreach (\App\Support\CheckerDesign::IMAGE_POS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                    </label>
                    <label>Shrift
                        <select wire:model.live="design.font">@foreach ($fonts as $key => $f)<option value="{{ $key }}">{{ $f[0] }}</option>@endforeach</select>
                    </label>
                    <label>Kutish ekranida skaner strelkasi
                        <select wire:model.live="design.scan_arrow">@foreach (\App\Support\CheckerDesign::ARROWS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                    </label>
                    <label>Tovar nomi o‘lchami: {{ $safe['name_scale'] }}% <input type="range" min="50" max="200" step="5" wire:model.live.debounce.100ms="design.name_scale"></label>
                    <label>Narx o‘lchami: {{ $safe['price_scale'] }}% <input type="range" min="50" max="200" step="5" wire:model.live.debounce.100ms="design.price_scale"></label>
                    <label>Ma’lumot kartasi shaffofligi: {{ $safe['card_opacity'] }}% <input type="range" min="0" max="100" step="5" wire:model.live.debounce.100ms="design.card_opacity"></label>
                    <label>Natija ko‘rsatish vaqti, soniya <input type="number" min="3" max="60" wire:model.live.debounce.250ms="design.timeout"></label>
                </div>
            </details>

            <details class="panel" open>
                <summary class="cursor-pointer text-sm font-semibold">Ranglar</summary>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach (\App\Support\CheckerDesign::PALETTES as $key => $row)
                        @php $c = \App\Support\CheckerDesign::palette($key); $on = collect($c)->every(fn ($v, $k) => ($safe[$k] ?? null) === $v); @endphp
                        <button type="button" wire:click="applyPalette('{{ $key }}')" title="{{ $row[0] }}"
                                class="flex flex-col overflow-hidden rounded border text-left {{ $on ? 'border-brand ring-2 ring-brand' : 'border-line' }}">
                            <span class="flex items-center gap-1.5 px-2 py-1.5" style="background:{{ $c['bg'] }}">
                                <b class="text-sm" style="color:{{ $c['price_color'] }}">4 000</b>
                                <span class="text-[10px]" style="color:{{ $c['name_color'] }}">Non</span>
                                <i class="ml-auto h-2 w-3 rounded-sm" style="background:{{ $c['badge_bg'] }}"></i>
                            </span>
                            <span class="px-2 py-0.5 text-[11px]" style="background:{{ $c['bar_bg'] }};color:{{ $c['bar_text'] }}">{{ $row[0] }}</span>
                        </button>
                    @endforeach
                </div>
                <p class="hint">Tayyor ranglar to‘plamidan birini tanlang. Tovar guruhi uchun boshqa to‘plam tanlansa («Tovar guruhlari» bo‘limi), shu guruh tovarlari natija ekranida o‘sha ranglarda chiqadi. Uzoqdan o‘qish uchun kontrasti yuqori to‘plamlarni tanlang.</p>
            </details>

            <details class="panel" open>
                <summary class="cursor-pointer text-sm font-semibold">Natija ekranidagi bloklar</summary>
                <div class="mt-2 grid gap-x-6 sm:grid-cols-2">
                    {!! $bool('show_image', 'Tovar rasmi') !!}
                    {!! $bool('show_unit', 'Birlik (so‘m / dona, kg)') !!}
                    {!! $bool('show_stock', 'Qoldiq holati (Mavjud / Oz qoldi / Tugagan)') !!}
                    {!! $bool('show_code', 'Tovar kodi') !!}
                    {!! $bool('show_article', 'Artikul') !!}
                    {!! $bool('show_barcode', 'Shtrix-kod') !!}
                    {!! $bool('show_group', 'Guruh') !!}
                </div>
                <div class="mt-3 grid gap-x-4 sm:grid-cols-2">
                    <label>Eski narx (aksiya): narx turi
                        <select wire:model.live="design.old_price_type_id">
                            <option value="">— ko‘rsatilmasin —</option>
                            @foreach ($priceTypes as $pt)<option value="{{ $pt->id }}">{{ $pt->name }}</option>@endforeach
                        </select>
                    </label>
                    <label>Aksiya belgisi matni <input wire:model.live.debounce.250ms="design.promo_text" maxlength="30"></label>
                    <label>Ikkinchi narx (masalan karta bilan): narx turi
                        <select wire:model.live="design.second_price_type_id">
                            <option value="">— ko‘rsatilmasin —</option>
                            @foreach ($priceTypes as $pt)<option value="{{ $pt->id }}">{{ $pt->name }}</option>@endforeach
                        </select>
                    </label>
                    <label>Ikkinchi narx yorlig‘i <input wire:model.live.debounce.250ms="design.second_price_label" maxlength="40"></label>
                    <label>«Oz qoldi» chegarasi (shu miqdorgacha) <input type="number" min="1" wire:model.live.debounce.250ms="design.stock_low"></label>
                </div>
                <p class="hint">Eski narx faqat tanlangan narx turidagi narx joriy narxdan yuqori bo‘lsa ko‘rinadi (aksiya). Qoldiq aniq miqdorda emas, faqat holat sifatida ko‘rsatiladi.</p>
            </details>

            <details class="panel" open>
                <summary class="cursor-pointer text-sm font-semibold">Panellar va matnlar</summary>
                <div class="mt-2 flex flex-wrap gap-x-6">
                    {!! $bool('show_header', 'Yuqori panel') !!}
                    {!! $bool('show_footer', 'Pastki panel') !!}
                    {!! $bool('show_logo', 'Logotip') !!}
                    {!! $bool('show_clock', 'Soat') !!}
                </div>
                <div class="mt-2 grid gap-x-4 sm:grid-cols-2">
                    <label>Yuqori panel matni <input wire:model.live.debounce.250ms="design.header_text" maxlength="80" placeholder="Narx tekshirgich nomi"></label>
                    <label>Pastki panel matni <input wire:model.live.debounce.250ms="design.footer_text" maxlength="80" placeholder="{{ config('menu.app_name') }}"></label>
                    <label>Kutish matni <input wire:model.live.debounce.250ms="design.hint_text" maxlength="120"></label>
                    <label>Valyuta <input wire:model.live.debounce.250ms="design.currency_text" maxlength="20"></label>
                    <label>«Topilmadi» matni <input wire:model.live.debounce.250ms="design.not_found_text" maxlength="120"></label>
                    <label>«Topilmadi» izohi <input wire:model.live.debounce.250ms="design.not_found_hint" maxlength="120"></label>
                    <label>Ulanish ekrani sarlavhasi <input wire:model.live.debounce.250ms="design.auth_title" maxlength="80"></label>
                    <label>Ulanish ekrani matni <input wire:model.live.debounce.250ms="design.auth_text" maxlength="200"></label>
                </div>
            </details>

            <div class="actions">
                <button type="button" class="btn" wire:click="save">Saqlash</button>
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
                <h3>Ko‘rinishi (hozir) · 10 dyuym, 16:10</h3>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <div class="actions">
                        @foreach (['auth' => 'Avtorizatsiya', 'idle' => 'Kutish', 'result' => 'Tovar sahifasi', 'error' => 'Topilmadi'] as $key => $label)
                            <button type="button" class="btn sm {{ $state === $key ? '' : 'gray' }}" wire:click="$set('state', '{{ $key }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                    <label class="chk m-0"><input type="checkbox" wire:model.live="portrait"> Vertikal ekran</label>
                </div>
                <div class="mx-auto overflow-hidden rounded shadow-lg" style="width:{{ $w }}px;max-width:100%">
                    @include('pricechecker._stage', ['d' => $safe, 'title' => 'Narx tekshirgich', 'state' => $state, 'item' => $item, 'preview' => true, 'w' => $w.'px', 'h' => $h.'px', 'u' => (min($w, $h) / 100).'px', 'id' => 'pcs-preview'])
                </div>
                @if ($items->isNotEmpty())
                    <label class="mt-3">Namuna tovar
                        <select wire:model.live="sample">@foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select>
                    </label>
                @endif
                <p class="hint">Burilish ekranning haqiqiy holatini ko‘rsatadi (90° — yonboshiga o‘rnatilgan). Rasm, aksiya, ikkinchi narx va qoldiq bloklari namuna qiymatlari bilan ko‘rsatiladi.</p>
            </div>
        </div>
    </div>
</div>
