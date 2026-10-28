@section('title', 'Qurilmani tahrirlash')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>Nomi (masalan: Ombor-1, Kassa PDA) <input wire:model="name"></label>
        <label>Ekran klaviaturasi
            <select wire:model="keyboard_mode">
                @foreach ($modes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
        </label>
        <label>Biriktirilgan printer
            <select wire:model="printer_id">
                <option value="">— yo‘q (asosiy yorliq printeri) —</option>
                @foreach ($printers as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->address }})</option>@endforeach
            </select>
        </label>
        <p><small>
            Klaviatura o‘chiq bo‘lsa, kiritish maydonlarida ekran klaviaturasi o‘z-o‘zidan chiqmaydi (skaner va tugmalar ishlaydi),
            maydon yonidagi ⌨ tugmasi bilan chaqiriladi. Operator o‘z qurilmasida Sozlamalar orqali buni almashtirishi mumkin.
        </small></p>

        <div class="actions">
            <button class="btn">Saqlash</button>
            <a class="btn gray" href="{{ route('admin.devices.index') }}">Bekor qilish</a>
        </div>
    </form>
</div>