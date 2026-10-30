@section('title', $roleId ? 'Rolni tahrirlash' : 'Yangi rol')

@php
    // one tick-all box per group: ticks / unticks every item of the group and tells Livewire
    $toggle = "\$el.closest('details').querySelectorAll('input[data-item]').forEach(i => { if (i.checked !== \$el.checked) { i.checked = \$el.checked; i.dispatchEvent(new Event('change', { bubbles: true })); } })";
@endphp
<div>
    @include('partials.lw-flash')

    <form class="card" wire:submit="save">
        <label>{{ __('Rol nomi') }} <input wire:model="name" required maxlength="100" placeholder="{{ __('Masalan: Ombor mudiri') }}"></label>

        @if ($isAdminRole)
            <p class="full"><span class="badge completed">{{ __('Barcha huquqlar') }}</span> {{ __('Administrator roli hamma narsaga ruxsat beradi va o‘zgartirilmaydi.') }}</p>
        @else
            <div class="full">
                <p class="hint">{{ __('Bo‘lim yoki alohida punktni belgilang. Belgilanmagan bo‘limlar foydalanuvchiga ko‘rinmaydi va server tomonidan ham yopiladi.') }}</p>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($groups as $group)
                        <details class="panel !mb-0" open x-data>
                            <summary class="flex cursor-pointer items-center gap-2 text-sm font-semibold">
                                <input type="checkbox" class="!m-0 !w-auto" title="{{ __('Hammasini belgilash') }}" @click.stop @change="{{ $toggle }}"
                                       @checked(collect($group['items'])->every(fn ($i) => in_array($i['key'], $perms, true)))>
                                {{ $group['title'] }}
                                <span class="hint ml-auto font-normal">{{ __('Boshqaruv paneli') }}</span>
                            </summary>
                            <div class="mt-2">
                                @foreach ($group['items'] as $item)
                                    <label class="chk"><input type="checkbox" data-item wire:model="perms" value="{{ $item['key'] }}"> {{ $item['title'] }}</label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach

                    <details class="panel !mb-0" open x-data>
                        <summary class="flex cursor-pointer items-center gap-2 text-sm font-semibold">
                            <input type="checkbox" class="!m-0 !w-auto" title="{{ __('Hammasini belgilash') }}" @click.stop @change="{{ $toggle }}"
                                   @checked(collect($pda)->keys()->every(fn ($k) => in_array($k, $perms, true)))>
                            {{ __('PDA menyusi') }}
                            <span class="hint ml-auto font-normal">{{ __('Qo‘l terminali') }}</span>
                        </summary>
                        <div class="mt-2">
                            @foreach ($pda as $key => $title)
                                <label class="chk"><input type="checkbox" data-item wire:model="perms" value="{{ $key }}"> {{ $title }}</label>
                            @endforeach
                            <p class="hint">{{ __('“Vazifalarim” va xabarlar har doim mavjud.') }}</p>
                        </div>
                    </details>
                </div>
            </div>
        @endif

        <div class="actions">
            <button class="btn">{{ __('Saqlash') }}</button>
            <a class="btn gray" href="{{ route('admin.roles.index') }}">{{ __('Bekor qilish') }}</a>
        </div>
    </form>
</div>
