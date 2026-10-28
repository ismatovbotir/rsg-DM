{{-- Header inputs for a document type, bound to the Livewire $header array. Params: $fields, $warehouses, $priceTypes, $cpResults. --}}
@foreach ($fields as $name => $f)
    @if ($f['type'] === 'counterparty')
        <div class="lbl" wire:key="f-{{ $name }}">
            {{ $f['label'] }}@if ($f['required'])<span class="req"> *</span>@endif
            <div class="scanrow mt-1">
                <input class="field" wire:model.live.debounce.250ms="cp_q" placeholder="Nomi yoki STIR" autocomplete="off" @kb>
                <button type="button" class="btn" wire:click="toggleCpList">Tanlash</button>
            </div>
            @if (filled($header[$name] ?? null) && ($picked = \App\Models\Counterparty::find($header[$name])) && $picked->stir)<span class="sub">STIR {{ $picked->stir }}</span>@endif
            @if ($cp_list)
                <ul class="list max-h-60 overflow-y-auto">
                    @forelse ($cpResults as $r)
                        <li wire:key="cp-{{ $r->id }}">
                            <button type="button" class="row" wire:click="pickCounterparty({{ $r->id }})">
                                <span class="label">{{ $r->name }}<span class="sub">{{ $r->stir ? 'STIR '.$r->stir : '' }}</span></span>
                            </button>
                        </li>
                    @empty
                        <li class="placeholder">Topilmadi.</li>
                    @endforelse
                </ul>
            @endif
        </div>
    @elseif ($f['type'] === 'warehouse')
        <label class="lbl" wire:key="f-{{ $name }}">
            {{ $f['label'] }}@if ($f['required'])<span class="req"> *</span>@endif
            <select class="field" wire:model="header.{{ $name }}">
                <option value="">— tanlang —</option>
                @foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </label>
    @elseif ($f['type'] === 'pricetype')
        <label class="lbl" wire:key="f-{{ $name }}">
            {{ $f['label'] }}@if ($f['required'])<span class="req"> *</span>@endif
            <select class="field" wire:model="header.{{ $name }}">
                <option value="">— tanlang —</option>
                @foreach ($priceTypes as $pt)<option value="{{ $pt->id }}">{{ $pt->name }}</option>@endforeach
            </select>
        </label>
    @else
        <label class="lbl" wire:key="f-{{ $name }}">
            {{ $f['label'] }}@if ($f['required'])<span class="req"> *</span>@endif
            <input class="field" type="{{ $f['type'] }}" wire:model="header.{{ $name }}" autocomplete="off" @if ($f['type'] === 'text') @kb @endif>
        </label>
    @endif
@endforeach