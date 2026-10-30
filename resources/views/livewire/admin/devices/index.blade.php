@section('title', 'Qurilmalar (PDA)')

<div>
    @include('partials.lw-flash')

    <p>{{ __('Har bir PDA birinchi ochilganda shu yerda nomsiz qurilma sifatida paydo bo‘ladi. Unga nom bering, ekran klaviaturasi rejimini tanlang va printer biriktiring.') }}</p>

    <table>
        <thead><tr><th>{{ __('Nomi') }}</th><th>{{ __('Klaviatura') }}</th><th>{{ __('Printer') }}</th><th>{{ __('Oxirgi foydalanuvchi') }}</th><th>{{ __('Oxirgi faollik') }}</th><th>{{ __('Brauzer') }}</th><th></th></tr></thead>
        <tbody>
            @forelse ($devices as $device)
                <tr wire:key="device-{{ $device->id }}">
                    <td><a class="lnk" href="{{ route('admin.devices.edit', $device) }}">{{ $device->name ?: '— nomsiz —' }}</a> <small>{{ substr($device->uuid, 0, 8) }}</small></td>
                    <td>{{ \App\Models\Device::MODES[$device->keyboard_mode] ?? $device->keyboard_mode }}</td>
                    <td>{{ $device->printer?->name }}</td>
                    <td>{{ $device->lastUser?->name }}</td>
                    <td>{{ $device->last_seen_at?->format('d.m.Y H:i') }}</td>
                    <td><small>{{ \Illuminate\Support\Str::limit($device->user_agent, 50) }}</small></td>
                    <td class="text-right">
                        <button class="btn sm red" wire:click="delete({{ $device->id }})" wire:confirm="{{ __('Qurilma o‘chirilsinmi?') }}">{{ __('O‘chirish') }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('Hali qurilma yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>