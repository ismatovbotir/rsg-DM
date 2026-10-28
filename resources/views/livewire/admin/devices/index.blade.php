@section('title', 'Qurilmalar (PDA)')

<div>
    @include('partials.lw-flash')

    <p>Har bir PDA birinchi ochilganda shu yerda nomsiz qurilma sifatida paydo bo‘ladi. Unga nom bering, ekran klaviaturasi rejimini tanlang va printer biriktiring.</p>

    <table>
        <thead><tr><th>Nomi</th><th>Klaviatura</th><th>Printer</th><th>Oxirgi foydalanuvchi</th><th>Oxirgi faollik</th><th>Brauzer</th><th></th></tr></thead>
        <tbody>
            @forelse ($devices as $device)
                <tr wire:key="device-{{ $device->id }}">
                    <td>{{ $device->name ?: '— nomsiz —' }} <small>{{ substr($device->uuid, 0, 8) }}</small></td>
                    <td>{{ \App\Models\Device::MODES[$device->keyboard_mode] ?? $device->keyboard_mode }}</td>
                    <td>{{ $device->printer?->name }}</td>
                    <td>{{ $device->lastUser?->name }}</td>
                    <td>{{ $device->last_seen_at?->format('d.m.Y H:i') }}</td>
                    <td><small>{{ \Illuminate\Support\Str::limit($device->user_agent, 50) }}</small></td>
                    <td class="text-right">
                        <a class="btn gray" href="{{ route('admin.devices.edit', $device) }}">Tahrirlash</a>
                        <button class="btn red" wire:click="delete({{ $device->id }})" wire:confirm="Qurilma o‘chirilsinmi?">O‘chirish</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Hali qurilma yo‘q.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>