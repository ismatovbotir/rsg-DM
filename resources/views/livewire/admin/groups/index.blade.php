@section('title', 'Tovar guruhlari')

<div>
    @include('partials.lw-flash')

    <div class="bar">
        <input wire:model.live.debounce.300ms="q" placeholder="{{ __('Guruh nomi') }}" size="32">
        <span class="grow"></span>
        <span class="sub">{{ __('Guruhlar Adines dan keladi. Bu yerda narx tekshirgich ranglar sxemasi tanlanadi.') }}</span>
    </div>

    <table>
        <thead><tr><th>{{ __('Guruh') }}</th><th class="num">{{ __('Tovarlar') }}</th><th>{{ __('Ranglar sxemasi') }}</th><th>{{ __('Holat') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $group)
                @php $own = \App\Support\CheckerDesign::PALETTES[$group->color_scheme] ?? null; $eff = $group->effectiveColorScheme(); @endphp
                <tr wire:key="group-{{ $group->id }}">
                    <td><a class="lnk" href="{{ route('admin.groups.edit', $group->id) }}">{{ $group->path }}</a></td>
                    <td class="num">{{ $group->items_count }}</td>
                    <td>
                        @if ($eff)
                            @php $c = \App\Support\CheckerDesign::palette($eff); @endphp
                            <span class="inline-flex items-center gap-1.5"><i class="inline-block h-3 w-6 rounded-sm border border-line" style="background:linear-gradient(90deg,{{ $c['bg'] }} 55%,{{ $c['price_color'] }} 55%)"></i>{{ \App\Support\CheckerDesign::PALETTES[$eff][0] }}@unless ($own) <span class="sub">{{ __('(yuqori guruhdan)') }}</span>@endunless</span>
                        @else
                            <span class="sub">{{ __('Dizayn bo‘yicha (standart)') }}</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $group->is_active ? 'completed' : 'error' }}">{{ $group->is_active ? 'Faol' : 'Nofaol' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('Guruhlar yo‘q.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $rows])
</div>
