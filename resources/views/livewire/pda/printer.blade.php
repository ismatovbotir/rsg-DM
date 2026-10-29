@section('title', 'Printer')

<div class="contents" x-data="{
        found: [], note: '',
        async findBt() {
            this.note = ''; this.found = [];
            try {
                if (window.AndroidPrinter && typeof window.AndroidPrinter.pairedDevices === 'function') {
                    this.found = JSON.parse(window.AndroidPrinter.pairedDevices());
                    if (!this.found.length) this.note = 'Juftlangan printer yo‘q. Android «Bluetooth» sozlamalarida printerni juftlang.';
                } else if (navigator.bluetooth) {
                    const d = await navigator.bluetooth.requestDevice({ acceptAllDevices: true, optionalServices: window.BT_SERVICES });
                    this.found = [{ name: d.name || 'Printer', address: d.id }];
                } else {
                    this.note = 'Bu brauzer Bluetooth ro‘yxatini bermaydi (iOS Safari, HTTP sahifa yoki eski Android). Printer nomini qo‘lda kiriting va RawBT ilovasidan foydalaning.';
                }
            } catch (e) { this.note = e && e.name === 'NotFoundError' ? 'Printer tanlanmadi.' : ('Bluetooth xatosi: ' + (e.message || e)); }
        },
        pick(d) { $wire.set('bt_name', d.name); $wire.set('bt_address', d.address || ''); this.found = []; }
    }" @keydown.window.escape="window.location = '{{ route('menu') }}'">
    <x-pda.page title="Printer" :back="route('menu')" :notice="$notice" :failure="$failure">
        <div class="pad">
            <p class="sub m-0">Qurilma: <strong>{{ $device?->name ?: 'nomsiz' }}</strong></p>
            <p class="m-0 mt-1">Hozirgi printer: <strong>{{ $current?->name ?? 'tanlanmagan' }}</strong>
                @if ($current)<span class="badge">{{ $current->isBluetooth() ? 'Bluetooth' : 'LAN' }}</span>@endif
            </p>
            @if ($current)<p class="sub m-0">{{ $current->isBluetooth() ? ($current->bt_name ?: 'Bluetooth') : $current->address }}</p>@endif
            <button type="button" class="btn mt-2 w-full" wire:click="testPrint" wire:loading.attr="disabled" @disabled(! $current)>Sinov chop etish</button>
        </div>

        <form class="pad" wire:submit="saveOwn">
            <h3 class="mt-0">Mening printerim</h3>
            @foreach ($errors->all() as $error)<div class="flash error !mx-0">{{ $error }}</div>@endforeach

            <div class="ptabs !static">
                <button type="button" class="{{ $kind === 'lan' ? 'on' : '' }}" wire:click="$set('kind', 'lan')">LAN</button>
                <button type="button" class="{{ $kind === 'bluetooth' ? 'on' : '' }}" wire:click="$set('kind', 'bluetooth')">Bluetooth</button>
            </div>

            @if ($kind === 'lan')
                <label class="lbl">IP manzil
                    <input class="field" wire:model="host" inputmode="decimal" placeholder="192.168.1.50" autocomplete="off" @kb>
                </label>
                <label class="lbl">Port
                    <input class="field" wire:model="port" inputmode="numeric" autocomplete="off" @kb>
                </label>
                <p class="sub">Chop etishni server qiladi (ZPL, port 9100). Printer va server bir tarmoqda bo‘lishi kerak.</p>
            @else
                <button type="button" class="btn gray w-full" @click="findBt()">Juftlangan printerlarni topish</button>
                <p class="sub" x-show="note" x-text="note"></p>
                <ul class="list" x-show="found.length" x-cloak>
                    <template x-for="d in found" :key="d.address">
                        <li><button type="button" class="row" @click="pick(d)"><span class="label"><span x-text="d.name"></span><span class="sub" x-text="d.address"></span></span></button></li>
                    </template>
                </ul>
                <label class="lbl">Printer nomi
                    <input class="field" wire:model="bt_name" placeholder="masalan, ZQ320-1234" autocomplete="off" @kb>
                </label>
                @if ($bt_address !== '')<p class="sub">Manzil: {{ $bt_address }}</p>@endif
                <p class="sub">Chop etishni shu telefon / PDA qiladi. Printer avval Android «Bluetooth» sozlamalarida juftlangan bo‘lishi kerak.</p>
            @endif

            <button class="btn green mt-2 w-full">Saqlash</button>
        </form>

        @if ($printers->isNotEmpty())
            <h3 class="px-3 pb-1 pt-3 text-sm font-semibold text-ink-muted">Yoki umumiy printerlardan tanlang</h3>
            <ul class="list">
                @foreach ($printers as $p)
                    <li wire:key="p-{{ $p->id }}">
                        <button type="button" class="row" wire:click="choose({{ $p->id }})">
                            <span class="label">{{ $p->name }}<span class="sub">{{ $p->isBluetooth() ? 'Bluetooth · '.($p->bt_name ?: 'telefon orqali') : 'LAN · '.$p->address }}</span></span>
                            @if ($device?->printer_id === $p->id)<span class="badge completed">Tanlangan</span>@endif
                        </button>
                    </li>
                @endforeach
                @if ($device?->printer_id && $device->printer_id !== $own?->id)
                    <li><button type="button" class="row" wire:click="choose(null)"><span class="label">Tanlovni olib tashlash</span></button></li>
                @endif
            </ul>
        @endif

        <div class="pad">
            <h3 class="mt-0">Bluetooth haqida</h3>
            <p class="sub m-0">Veb-sahifa telefonning juftlangan qurilmalar ro‘yxatini o‘qiy olmaydi. Ro‘yxat faqat Android ilovasi (wrapper) orqali yoki Chrome Bluetooth oynasi (HTTPS, BLE printer) orqali chiqadi. Aks holda nomini kiriting: chop etish <strong>RawBT</strong> ilovasi orqali bo‘ladi (u printerni o‘zi tanlaydi). iPhone / iPad Safari Bluetooth chop etishni qo‘llamaydi.</p>
        </div>
    </x-pda.page>
</div>
