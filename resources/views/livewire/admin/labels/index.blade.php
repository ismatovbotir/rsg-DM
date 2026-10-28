@section('title', 'Yorliq dizayneri')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <span>Narx yorliqlari dizaynlari. «Asosiy» dizayn navbatdagi narx yorliqlarini chop etishda ishlatiladi.</span>
        <span class="grow"></span>
        <a class="btn" href="{{ route('admin.labels.create') }}">+ Yangi dizayn</a>
    </div>

    <table>
        <thead><tr><th>Nomi</th><th>O‘lcham</th><th>Burilish</th><th class="num">Elementlar</th><th>Asosiy</th><th></th></tr></thead>
        <tbody>
            @forelse ($templates as $t)
                <tr wire:key="label-{{ $t->id }}">
                    <td>{{ $t->name }}</td>
                    <td>{{ $t->width_mm + 0 }} × {{ $t->height_mm + 0 }} mm · {{ $t->dpi }} dpi</td>
                    <td>{{ $t->rotation }}°</td>
                    <td class="num">{{ count($t->elements ?? []) }}</td>
                    <td>
                        @if ($t->is_default)<span class="badge completed">Asosiy</span>
                        @else<button class="btn gray" wire:click="makeDefault({{ $t->id }})">Asosiy qilish</button>@endif
                    </td>
                    <td class="text-right">
                        <a class="btn gray" href="{{ route('admin.labels.edit', $t->id) }}">Tahrirlash</a>
                        <button class="btn red" wire:click="delete({{ $t->id }})" wire:confirm="Dizayn o‘chirilsinmi?">O‘chirish</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Hali dizayn yo‘q. Dizayn bo‘lmasa oddiy yorliq chop etiladi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>