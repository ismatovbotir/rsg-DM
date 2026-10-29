@section('title', $recordId ? 'Foydalanuvchini tahrirlash' : 'Yangi foydalanuvchi')

<div x-data="{ tab: location.hash.slice(1) || 'main' }" x-init="$watch('tab', v => history.replaceState(null, '', '#' + v))">
    @include('partials.lw-flash')

    <div class="card">
        <div class="tabs">
            <button type="button" :class="{ on: tab === 'main' }" @click="tab = 'main'">Asosiy</button>
            <button type="button" :class="{ on: tab === 'shops' }" @click="tab = 'shops'">Do‘konlar ({{ count($warehouse_ids) + ($warehouse_id !== '' && ! in_array($warehouse_id, $warehouse_ids) ? 1 : 0) }})</button>
            <button type="button" :class="{ on: tab === 'pricetypes' }" @click="tab = 'pricetypes'">Narx turlari ({{ count($price_type_ids) }})</button>
            <button type="button" :class="{ on: tab === 'access' }" @click="tab = 'access'">Ruxsatlar</button>
            @if ($recordId)
                <button type="button" :class="{ on: tab === 'badge' }" @click="tab = 'badge'">PIN va badge</button>
            @endif
        </div>

        <form class="tabpanel on" x-show="tab === 'main'" wire:submit="save">
            <label>Ism <input wire:model="name" required></label>
            <label>Login (email) <input wire:model="email" required autocomplete="off"></label>
            <label>Rol
                <select wire:model.live="role_id" required>
                    @foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}{{ $r->is_admin ? ' (barcha huquqlar)' : '' }}</option>@endforeach
                </select>
                <span class="hint">Rol nimalarni ochishi mumkinligini belgilaydi. Rollar: <a class="lnk" href="{{ route('admin.roles.index') }}">Tizim → Rollar</a>.</span>
            </label>
            <label>Do‘kon (ombor)
                <select wire:model.live="warehouse_id">
                    <option value="">— biriktirilmagan (hammasi) —</option>
                    @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                </select>
                <span class="hint">Biriktirilsa: hujjatlarda chiqim ombori shu do‘kon bo‘ladi, tovar qoldig‘i va narxlari shu do‘kon bo‘yicha ko‘rsatiladi. Ko‘chirishda qabul qiluvchi omborni ro‘yxatdan tanlash mumkin.</span>
            </label>
            <label>Parol @if ($recordId)<small>(o‘zgartirmaslik uchun bo‘sh qoldiring)</small>@endif
                <input type="password" wire:model="password" autocomplete="new-password" @required(! $recordId)>
            </label>

            <div class="full">
                <label class="chk"><input type="checkbox" wire:model="is_active"> Faol (kira oladi)</label>
                <label class="chk"><input type="checkbox" wire:model="pda_user"> PDA foydalanuvchisi (kirgandan keyin doim PDA ko‘rinishi ochiladi)</label>
            </div>

            @unless ($recordId)<p class="hint">Saqlaganingizda 4 xonali PIN va shaxsiy badge QR kodi avtomatik yaratiladi.</p>@endunless
            <div class="actions">
                <button class="btn">Saqlash</button>
                <a class="btn gray" href="{{ route('admin.users.index') }}">Bekor qilish</a>
            </div>
        </form>

        {{-- the three tabs below are saved with the "Saqlash" button of the first tab --}}
        <div class="tabpanel on" x-show="tab === 'shops'" x-cloak>
            <p class="full hint">Foydalanuvchi qaysi do‘kon / omborlarning qoldig‘ini ko‘radi va hujjatda chiqim ombori sifatida tanlay oladi. Hech narsa belgilanmasa va asosiy do‘kon bo‘lmasa, hammasi ochiq. Asosiy do‘kon («Asosiy» tabdagi «Do‘kon») doim ruxsat etilgan. Ko‘chirishda qabul qiluvchi ombor hammasidan tanlanadi.</p>
            <div class="full grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-x-6">
                @foreach ($warehouses as $w)
                    <label class="chk"><input type="checkbox" wire:model.live="warehouse_ids" value="{{ $w->id }}" @checked($warehouse_id === (string) $w->id) @disabled($warehouse_id === (string) $w->id)> {{ $w->name }}@if ($warehouse_id === (string) $w->id) <span class="badge">asosiy</span>@endif</label>
                @endforeach
            </div>
            <div class="actions"><button class="btn" wire:click="save" type="button">Saqlash</button></div>
        </div>

        <div class="tabpanel on" x-show="tab === 'pricetypes'" x-cloak>
            <p class="full hint">Foydalanuvchi ko‘radigan va hujjatlarda tanlay oladigan narx turlari. Hech narsa belgilanmasa va do‘konning narx turi bo‘lmasa, hammasi ochiq. Asosiy do‘konning narx turi doim ruxsat etilgan.</p>
            <div class="full grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-x-6">
                @foreach ($priceTypes as $pt)
                    <label class="chk"><input type="checkbox" wire:model.live="price_type_ids" value="{{ $pt->id }}"> {{ $pt->name }}</label>
                @endforeach
            </div>
            <div class="actions"><button class="btn" wire:click="save" type="button">Saqlash</button></div>
        </div>

        <div class="tabpanel on" x-show="tab === 'access'" x-cloak>
            @php
                $toggle = "\$el.closest('details').querySelectorAll('input[data-item]').forEach(i => { if (i.checked !== \$el.checked) { i.checked = \$el.checked; i.dispatchEvent(new Event('change', { bubbles: true })); } })";
                $granted = $chosenRole?->permissions ?? [];
            @endphp
            <div class="full">
                <p class="hint">Rol: <strong>{{ $chosenRole?->name ?? '—' }}</strong>@if ($chosenRole) · <a class="lnk" href="{{ route('admin.roles.edit', $chosenRole->id) }}">rolni tahrirlash</a>@endif.
                    Bu yerda rol bergan ruxsatlardan faqat shu foydalanuvchi uchun ayrimlarini o‘chirish mumkin; rolda yo‘q ruxsatni berib bo‘lmaydi. Rolni almashtirsangiz, hammasi yana yoqiladi. Saqlash uchun «Asosiy» tabdagi «Saqlash» tugmasini bosing.</p>
                @if ($chosenRole?->is_admin)
                    <p><span class="badge completed">Barcha huquqlar</span> Administrator roli o‘zgartirilmaydi.</p>
                @elseif ($chosenRole)
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($adminGroups as $group)
                            @php $items = collect($group['items'])->filter(fn ($i) => in_array($i['key'], $granted, true)); @endphp
                            @continue($items->isEmpty())
                            <details class="panel !mb-0" open x-data>
                                <summary class="flex cursor-pointer items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" class="!m-0 !w-auto" title="Hammasini belgilash" @click.stop @change="{{ $toggle }}" @checked($items->every(fn ($i) => in_array($i['key'], $perms, true)))>
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
                                    <input type="checkbox" class="!m-0 !w-auto" title="Hammasini belgilash" @click.stop @change="{{ $toggle }}" @checked($pdaGranted->keys()->every(fn ($k) => in_array($k, $perms, true)))>
                                    PDA menyusi
                                </summary>
                                <div class="mt-2">
                                    @foreach ($pdaGranted as $key => $title)
                                        <label class="chk"><input type="checkbox" data-item wire:model="perms" value="{{ $key }}"> {{ $title }}</label>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                    @if (empty($granted))<p class="sub">Bu rolga hech qanday ruxsat berilmagan.</p>@endif
                @endif
                <div class="actions"><button class="btn" wire:click="save" type="button">Saqlash</button></div>
            </div>
        </div>

        @if ($recordId && $user)
            <div class="tabpanel on" x-show="tab === 'badge'" x-cloak>
                <div class="full flex flex-wrap items-start gap-8">
                    <div class="rounded border border-line bg-white p-3">{!! $qr !!}</div>
                    <div class="min-w-[280px] flex-1">
                        <h3 class="mt-0">Badge QR kodi</h3>
                        <p>PDA kirish ekranida badge QR kodini skanerlang — foydalanuvchi PIN siz kiradi. Badge ni chop eting va xodimga bering.</p>
                        <div class="actions">
                            <a class="btn" href="{{ route('admin.badges.print', ['ids' => [$user->id]]) }}" target="_blank">Badge chop etish</a>
                            <button type="button" class="btn gray" wire:click="regenerateBadge" wire:confirm="Badge yangilansa, eski chop etilgan badge ishlamay qoladi. Davom etilsinmi?">Badge ni yangilash</button>
                        </div>
                        <p class="hint">Badge ichidagi havola: <code class="break-all">{{ $user->badge_url }}</code></p>

                        <h3>PIN kod (4 xonali)</h3>
                        @if ($shownPin)
                            <div class="flash">Yangi PIN: <b class="text-xl tracking-[.3em]">{{ $shownPin }}</b> — faqat hozir ko‘rsatiladi, foydalanuvchiga yetkazing. Keyin uni ko‘rib bo‘lmaydi (faqat yangilash mumkin).</div>
                        @endif
                        <p>@if ($user->pin)<span class="badge completed">PIN o‘rnatilgan</span>@else<span class="badge error">PIN yo‘q</span>@endif
                            @if ($user->isPinLocked())<span class="badge error">Bloklangan: {{ $user->pin_locked_until->format('H:i') }} gacha</span>@endif</p>
                        <div class="actions">
                            <button type="button" class="btn gray" wire:click="generatePin" wire:confirm="Yangi tasodifiy PIN yaratilsinmi? Eski PIN ishlamay qoladi.">Yangi PIN yaratish</button>
                        </div>
                        <form wire:submit="setPin" class="mt-3 flex flex-wrap items-end gap-2">
                            <label class="m-0">O‘zim PIN belgilayman
                                <input wire:model="pinInput" inputmode="numeric" maxlength="4" size="6" placeholder="0000" autocomplete="off">
                            </label>
                            <button class="btn gray">PIN o‘rnatish</button>
                        </form>
                        @error('pinInput')<div class="flash error mt-2">{{ $message }}</div>@enderror
                        <p class="hint">PIN faqat PDA ga kirish uchun. Ketma-ket 5 ta noto‘g‘ri urinishdan keyin {{ \App\Models\User::PIN_LOCK_MINUTES }} daqiqaga bloklanadi (yangi PIN yaratsangiz blok olinadi). PIN va badge hamma foydalanuvchilar (administratorlar ham) uchun ishlaydi.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
