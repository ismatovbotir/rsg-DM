@section('title', $recordId ? 'Foydalanuvchini tahrirlash' : 'Yangi foydalanuvchi')

<div x-data="{ tab: location.hash.slice(1) || 'main' }" x-init="$watch('tab', v => history.replaceState(null, '', '#' + v))">
    @include('partials.lw-flash')

    <div class="card">
        <div class="tabs">
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">{{ __('Asosiy') }}</button>
            <button type="button" :class="{ on: tab === 'shops' }" @click="tab = 'shops'">{{ __('Do‘konlar (') }}{{ count($warehouse_ids) + ($warehouse_id !== '' && ! in_array($warehouse_id, $warehouse_ids) ? 1 : 0) }})</button>
            <button type="button" :class="{ on: tab === 'pricetypes' }" @click="tab = 'pricetypes'">{{ __('Narx turlari (') }}{{ count($price_type_ids) }})</button>
            <button type="button" :class="{ on: tab === 'access' }" @click="tab = 'access'">{{ __('Ruxsatlar') }}</button>
            @if ($recordId)
                <button type="button" :class="{ on: tab === 'badge' }" @click="tab = 'badge'">{{ __('PIN va badge') }}</button>
            @endif
        </div>

        <form class="tabpanel on" x-show="tab === 'main'" wire:submit="save">
            <label>{{ __('Ism') }} <input wire:model="name" required></label>
            <label>{{ __('Login (email)') }} <input wire:model="email" required autocomplete="off"></label>
            <label>{{ __('Rol') }}
                <select wire:model.live="role_id" required>
                    @foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}@if ($r->is_admin) ({{ __('barcha huquqlar') }})@endif</option>@endforeach
                </select>
                <span class="hint">{{ __('Rol nimalarni ochishi mumkinligini belgilaydi. Rollar:') }} <a class="lnk" href="{{ route('admin.roles.index') }}">{{ __('Tizim → Rollar') }}</a>.</span>
            </label>
            <label>{{ __('Do‘kon (ombor)') }}
                <select wire:model.live="warehouse_id">
                    <option value="">{{ __('— biriktirilmagan (hammasi) —') }}</option>
                    @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                </select>
                <span class="hint">{{ __('Biriktirilsa: hujjatlarda chiqim ombori shu do‘kon bo‘ladi, tovar qoldig‘i va narxlari shu do‘kon bo‘yicha ko‘rsatiladi. Ko‘chirishda qabul qiluvchi omborni ro‘yxatdan tanlash mumkin.') }}</span>
            </label>
            <label>{{ __('Til') }}
                <select wire:model="locale">
                    @foreach (config('app.locales') as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                </select>
                @error('locale')<span class="err">{{ $message }}</span>@enderror
            </label>
            <label>{{ __('Parol') }} @if ($recordId)<small>{{ __('(o‘zgartirmaslik uchun bo‘sh qoldiring)') }}</small>@endif
                <input type="password" wire:model="password" autocomplete="new-password" @required(! $recordId)>
            </label>

            <div class="full">
                <label class="chk"><input type="checkbox" wire:model="is_active"> {{ __('Faol (kira oladi)') }}</label>
                <label class="chk"><input type="checkbox" wire:model="pda_user"> {{ __('PDA foydalanuvchisi (kirgandan keyin doim PDA ko‘rinishi ochiladi)') }}</label>
            </div>

            @unless ($recordId)<p class="hint">{{ __('Saqlaganingizda 4 xonali PIN va shaxsiy badge QR kodi avtomatik yaratiladi.') }}</p>@endunless
            <div class="actions">
                <button class="btn">{{ __('Saqlash') }}</button>
                <a class="btn gray" href="{{ route('admin.users.index') }}">{{ __('Bekor qilish') }}</a>
            </div>
        </form>

        {{-- the three tabs below are saved with the "Saqlash" button of the first tab --}}
        <div class="tabpanel on" x-show="tab === 'shops'" x-cloak>
            <p class="full hint">{{ __('Foydalanuvchi qaysi do‘kon / omborlarning qoldig‘ini ko‘radi va hujjatda chiqim ombori sifatida tanlay oladi. Hech narsa belgilanmasa va asosiy do‘kon bo‘lmasa, hammasi ochiq. Asosiy do‘kon («Asosiy» tabdagi «Do‘kon») doim ruxsat etilgan. Ko‘chirishda qabul qiluvchi ombor hammasidan tanlanadi.') }}</p>
            <div class="full grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-x-6">
                @foreach ($warehouses as $w)
                    <label class="chk"><input type="checkbox" wire:model.live="warehouse_ids" value="{{ $w->id }}" @checked($warehouse_id === (string) $w->id) @disabled($warehouse_id === (string) $w->id)> {{ $w->name }}@if ($warehouse_id === (string) $w->id) <span class="badge">{{ __('asosiy') }}</span>@endif</label>
                @endforeach
            </div>
            <div class="actions"><button class="btn" wire:click="save" type="button">{{ __('Saqlash') }}</button></div>
        </div>

        <div class="tabpanel on" x-show="tab === 'pricetypes'" x-cloak>
            <p class="full hint">{{ __('Foydalanuvchi ko‘radigan va hujjatlarda tanlay oladigan narx turlari. Hech narsa belgilanmasa va do‘konning narx turi bo‘lmasa, hammasi ochiq. Asosiy do‘konning narx turi doim ruxsat etilgan.') }}</p>
            <div class="full grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-x-6">
                @foreach ($priceTypes as $pt)
                    <label class="chk"><input type="checkbox" wire:model.live="price_type_ids" value="{{ $pt->id }}"> {{ $pt->name }}</label>
                @endforeach
            </div>
            <div class="actions"><button class="btn" wire:click="save" type="button">{{ __('Saqlash') }}</button></div>
        </div>

        <div class="tabpanel on" x-show="tab === 'access'" x-cloak>
            @php
                $toggle = "\$el.closest('details').querySelectorAll('input[data-item]').forEach(i => { if (i.checked !== \$el.checked) { i.checked = \$el.checked; i.dispatchEvent(new Event('change', { bubbles: true })); } })";
                $granted = $chosenRole?->permissions ?? [];
            @endphp
            <div class="full">
                <p class="hint">{{ __('Rol:') }} <strong>{{ $chosenRole?->name ?? '—' }}</strong>@if ($chosenRole) · <a class="lnk" href="{{ route('admin.roles.edit', $chosenRole->id) }}">{{ __('rolni tahrirlash') }}</a>@endif{{ __('. Bu yerda rol bergan ruxsatlardan faqat shu foydalanuvchi uchun ayrimlarini o‘chirish mumkin; rolda yo‘q ruxsatni berib bo‘lmaydi. Rolni almashtirsangiz, hammasi yana yoqiladi. Saqlash uchun «Asosiy» tabdagi «Saqlash» tugmasini bosing.') }}</p>
                @if ($chosenRole?->is_admin)
                    <p><span class="badge completed">{{ __('Barcha huquqlar') }}</span> {{ __('Administrator roli o‘zgartirilmaydi.') }}</p>
                @elseif ($chosenRole)
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($adminGroups as $group)
                            @php $items = collect($group['items'])->filter(fn ($i) => in_array($i['key'], $granted, true)); @endphp
                            @continue($items->isEmpty())
                            <details class="panel !mb-0" open x-data>
                                <summary class="flex cursor-pointer items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" class="!m-0 !w-auto" title="{{ __('Hammasini belgilash') }}" @click.stop @change="{{ $toggle }}" @checked($items->every(fn ($i) => in_array($i['key'], $perms, true)))>
                                    {{ $group['title'] }}
                                </summary>
                                <div class="mt-2">
                                    @foreach ($items as $item)
                                        <label class="chk"><input type="checkbox" data-item wire:model="perms" value="{{ $item['key'] }}"> {{ $item['title'] }}</label>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach

                        @php $pdaGranted = collect($pdaItems)->filter(fn ($t, $k) => in_array($k, $granted, true)); @endphp
                        @if ($pdaGranted->isNotEmpty())
                            <details class="panel !mb-0" open x-data>
                                <summary class="flex cursor-pointer items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" class="!m-0 !w-auto" title="{{ __('Hammasini belgilash') }}" @click.stop @change="{{ $toggle }}" @checked($pdaGranted->keys()->every(fn ($k) => in_array($k, $perms, true)))>
                                    {{ __('PDA menyusi') }}
                                </summary>
                                <div class="mt-2">
                                    @foreach ($pdaGranted as $key => $title)
                                        <label class="chk"><input type="checkbox" data-item wire:model="perms" value="{{ $key }}"> {{ $title }}</label>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                    @if (empty($granted))<p class="sub">{{ __('Bu rolga hech qanday ruxsat berilmagan.') }}</p>@endif
                @endif
                <div class="actions"><button class="btn" wire:click="save" type="button">{{ __('Saqlash') }}</button></div>
            </div>
        </div>

        @if ($recordId && $user)
            <div class="tabpanel on" x-show="tab === 'badge'" x-cloak>
                <div class="full flex flex-wrap items-start gap-8">
                    <div class="rounded border border-line bg-white p-3">{!! $qr !!}</div>
                    <div class="min-w-[280px] flex-1">
                        <h3 class="mt-0">{{ __('Badge QR kodi') }}</h3>
                        <p>{{ __('PDA kirish ekranida badge QR kodini skanerlang — foydalanuvchi PIN siz kiradi. Badge ni chop eting va xodimga bering.') }}</p>
                        <div class="actions">
                            <a class="btn" href="{{ route('admin.badges.print', ['ids' => [$user->id]]) }}" target="_blank">{{ __('Badge chop etish') }}</a>
                            <button type="button" class="btn gray" wire:click="regenerateBadge" wire:confirm="{{ __('Badge yangilansa, eski chop etilgan badge ishlamay qoladi. Davom etilsinmi?') }}">{{ __('Badge ni yangilash') }}</button>
                        </div>
                        <p class="hint">{{ __('Badge ichidagi havola:') }} <code class="break-all">{{ $user->badge_url }}</code></p>

                        <h3>{{ __('PIN kod (4 xonali)') }}</h3>
                        @if ($shownPin)
                            <div class="flash">{{ __('Yangi PIN:') }} <b class="text-xl tracking-[.3em]">{{ $shownPin }}</b> {{ __('— faqat hozir ko‘rsatiladi, foydalanuvchiga yetkazing. Keyin uni ko‘rib bo‘lmaydi (faqat yangilash mumkin).') }}</div>
                        @endif
                        <p>@if ($user->pin)<span class="badge completed">{{ __('PIN o‘rnatilgan') }}</span>@else<span class="badge error">{{ __('PIN yo‘q') }}</span>@endif
                            @if ($user->isPinLocked())<span class="badge error">{{ __('Bloklangan:') }} {{ $user->pin_locked_until->format('H:i') }} {{ __('gacha') }}</span>@endif</p>
                        <div class="actions">
                            <button type="button" class="btn gray" wire:click="generatePin" wire:confirm="{{ __('Yangi tasodifiy PIN yaratilsinmi? Eski PIN ishlamay qoladi.') }}">{{ __('Yangi PIN yaratish') }}</button>
                        </div>
                        <form wire:submit="setPin" class="mt-3 flex flex-wrap items-end gap-2">
                            <label class="m-0">{{ __('O‘zim PIN belgilayman') }}
                                <input wire:model="pinInput" inputmode="numeric" maxlength="4" size="6" placeholder="0000" autocomplete="off">
                            </label>
                            <button class="btn gray">{{ __('PIN o‘rnatish') }}</button>
                        </form>
                        @error('pinInput')<div class="flash error mt-2">{{ $message }}</div>@enderror
                        <p class="hint">{{ __('PIN faqat PDA ga kirish uchun. Ketma-ket 5 ta noto‘g‘ri urinishdan keyin') }} {{ \App\Models\User::PIN_LOCK_MINUTES }} {{ __('daqiqaga bloklanadi (yangi PIN yaratsangiz blok olinadi). PIN va badge hamma foydalanuvchilar (administratorlar ham) uchun ishlaydi.') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
