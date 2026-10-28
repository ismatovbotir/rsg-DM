@section('title', $recordId ? 'Printerni tahrirlash' : 'Yangi printer')

<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>Nomi <input wire:model="name" required></label>
        <label>IP manzil yoki tarmoq nomi <input wire:model="host" placeholder="192.168.1.50" required></label>
        <label>Port <input type="number" min="1" max="65535" wire:model="port" required></label>
        <label>Yorliq kengligi (mm) <input type="number" min="20" max="200" wire:model="label_width_mm" required></label>
        <label>Yorliq balandligi (mm) <input type="number" min="15" max="300" wire:model="label_height_mm" required></label>
        <div class="full">
            <label class="chk"><input type="checkbox" wire:model="is_price_tag"> Narx yorliqlari shu printerda chop etiladi (faqat bittasi)</label>
            <label class="chk"><input type="checkbox" wire:model="is_active"> Faol</label>
        </div>
        <p><small>Printer ZPL tilini qo‘llab-quvvatlashi va tarmoqda 9100 (raw) portida ochiq bo‘lishi kerak.</small></p>

        <div class="actions">
            <button class="btn">Saqlash</button>
            <a class="btn gray" href="{{ route('admin.printers.index') }}">Bekor qilish</a>
        </div>
    </form>
</div>