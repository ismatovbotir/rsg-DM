@section('title', $title)

<div>
    @include('partials.lw-flash')

    <div class="bar">
        @if ($searchPlaceholder)
            <input wire:model.live.debounce.300ms="q" placeholder="{{ $searchPlaceholder }}" size="32">
        @endif
        <span class="grow"></span>
        <span class="sub">Ma’lumotlar Adines dan keladi.</span>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)<th @class(['num' => ($column[2] ?? null) === 'num'])>{{ $column[0] }}</th>@endforeach
                @if ($hasStatus)<th>Holat</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr wire:key="row-{{ $row->id }}">
                    @foreach ($columns as $column)
                        <td @class(['num' => ($column[2] ?? null) === 'num'])>@if ($loop->first)<a class="lnk" href="{{ route($routes['edit'], $row->id) }}">@endif{{ is_callable($column[1]) ? $column[1]($row) : $row->{$column[1]} }}@if ($loop->first)</a>@endif</td>
                    @endforeach
                    @if ($hasStatus)<td><span class="badge {{ $row->is_active ? 'completed' : 'error' }}">{{ $row->is_active ? 'Faol' : 'Nofaol' }}</span></td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + ($hasStatus ? 1 : 0) }}">{{ $routes['empty'] }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $rows])
</div>
