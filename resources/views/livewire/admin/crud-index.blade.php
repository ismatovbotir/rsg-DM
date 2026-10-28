@section('title', $title)

<div>
    @include('partials.lw-flash')

    <div class="bar">
        @if ($searchPlaceholder)
            <input wire:model.live.debounce.300ms="q" placeholder="{{ $searchPlaceholder }}" size="32">
        @endif
        <span class="grow"></span>
        <a class="btn" href="{{ route($routes['create']) }}">{{ $routes['newLabel'] }}</a>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)<th @class(['num' => ($column[2] ?? null) === 'num'])>{{ $column[0] }}</th>@endforeach
                @if ($hasStatus)<th>Holat</th>@endif
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr wire:key="row-{{ $row->id }}">
                    @foreach ($columns as $column)
                        <td @class(['num' => ($column[2] ?? null) === 'num'])>{{ is_callable($column[1]) ? $column[1]($row) : $row->{$column[1]} }}</td>
                    @endforeach
                    @if ($hasStatus)<td><span class="badge {{ $row->is_active ? 'completed' : 'error' }}">{{ $row->is_active ? 'Faol' : 'Nofaol' }}</span></td>@endif
                    <td class="text-right"><a class="btn gray" href="{{ route($routes['edit'], $row->id) }}">Tahrirlash</a></td>
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + 1 + ($hasStatus ? 1 : 0) }}">{{ $routes['empty'] }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('partials.lw-pager', ['paginator' => $rows])
</div>
