@section('title', 'Qurilmani tahrirlash')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>{{ __('Nomi (masalan: Ombor-1, Kassa PDA)') }} <input wire:model="name"></label>
        <label>{{ __('Ekran klaviaturasi') }}
            <select wire:model="keyboard_mode">
                @foreach ($modes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
        </label>
        <label>{{ __('Biriktirilgan printer') }}
            <select wire:model="printer_id">
                <option value="">{{ __('— yo‘q (asosiy yorliq printeri) —') }}</option>
                @foreach ($printers as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->address }})</option>@endforeach
            </select>
        </label>
        <p><small>
            {{ __('Klaviatura o‘chiq bo‘lsa, kiritish maydonlarida ekran klaviaturasi o‘z-o‘zidan chiqmaydi (skaner va tugmalar ishlaydi), maydon yonidagi ⌨ tugmasi bilan chaqiriladi. Operator o‘z qurilmasida Sozlamalar orqali buni almashtirishi mumkin.') }}
        </small></p>

        <div class="actions">
            <button class="btn">{{ __('Saqlash') }}</button>
            <a class="btn gray" href="{{ route('admin.devices.index') }}">{{ __('Bekor qilish') }}</a>
        </div>
    </form>
</div>