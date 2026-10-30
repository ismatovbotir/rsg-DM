@section('title', $recordId ? 'Printerni tahrirlash' : 'Yangi printer')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>{{ __('Nomi') }} <input wire:model="name" required></label>
        <label>{{ __('Ulanish turi') }}
            <select wire:model.live="kind">@foreach (\App\Models\Printer::CONNECTIONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        </label>
        @if ($kind === 'lan')
            <label>{{ __('IP manzil yoki tarmoq nomi') }} <input wire:model="host" placeholder="192.168.1.50" required></label>
            <label>{{ __('Port') }} <input type="number" min="1" max="65535" wire:model="port" required></label>
        @endif
        <label>{{ __('Yorliq kengligi (mm)') }} <input type="number" min="20" max="200" wire:model="label_width_mm" required></label>
        <label>{{ __('Yorliq balandligi (mm)') }} <input type="number" min="15" max="300" wire:model="label_height_mm" required></label>
        <div class="full">
            <label class="chk"><input type="checkbox" wire:model="is_price_tag"> {{ __('Narx yorliqlari shu printerda chop etiladi (faqat bittasi)') }}</label>
            <label class="chk"><input type="checkbox" wire:model="is_active"> {{ __('Faol') }}</label>
        </div>
        <p><small>@if ($kind === 'lan'){{ __('Printer ZPL tilini qo‘llab-quvvatlashi va tarmoqda 9100 (raw) portida ochiq bo‘lishi kerak. Chop etishni server qiladi.') }}@else {{ __('Bluetooth printer telefon / PDA ga ulanadi (RawBT ilovasi orqali); chop etishni qurilmaning o‘zi qiladi, shuning uchun bu yerda manzil kerak emas. Printer ZPL tilini bilishi kerak. PDA da «Printer» menyusidan tanlanadi.') }}@endif</small></p>

        <div class="actions">
            <button class="btn">{{ __('Saqlash') }}</button>
            <a class="btn gray" href="{{ route('admin.printers.index') }}">{{ __('Bekor qilish') }}</a>
        </div>
    </form>
</div>