{{-- Step 2 of 2: items. The burger menu holds the document header and save / cancel / complete. --}}
@section('title', $doc->number)

<div class="contents" x-data="{ drawer: false }"
     x-effect="document.body.classList.toggle('drawer-open', drawer)"
     @open-drawer.window="drawer = true" @close-drawer.window="drawer = false"
     @keydown.window.escape="drawer ? drawer = false : window.location = '{{ route('docs.index', $doc->type) }}'">
    <x-pda.page :notice="$notice" :failure="$failure">
        <x-slot:header>
            <button class="hbtn" type="button" aria-label="Hujjat sarlavhasi" @click="drawer = true">☰</button>
            <span class="title">{{ $doc->type_label }}<small class="block text-xs font-normal opacity-80">{{ $doc->number }} · 2/2 Tovarlar</small></span>
            <span class="badge {{ $doc->status }}">{{ $doc->status_label }}</span>
            <a class="hbtn" href="{{ route('docs.index', $doc->type) }}" aria-label="Yopish">✕</a>
        </x-slot:header>

        @if ($doc->isDraft())
            @include('livewire.pda._scanbar', ['submit' => 'scan', 'model' => 'code', 'browse' => route('docs.browse', $doc->id)])
        @else
            <div class="pad">
                @if ($doc->syncs())<span class="badge {{ $doc->onec_status }}">{{ $doc->sync_label }}</span>@endif
                @if ($doc->onec_error)<span class="sub">{{ $doc->onec_error }}</span>@endif
                @if ($doc->print_label)
                    <span class="badge {{ $doc->print_state }}">{{ $doc->print_label }}</span>
                    @if ($doc->print_count)
                        <div class="mt-2.5"><button type="button" class="btn green" wire:click="print" wire:loading.attr="disabled">{{ $doc->printed_at ? 'Qayta chop etish' : 'Chop etish' }} ({{ $doc->print_count }})</button></div>
                    @endif
                @endif
            </div>
        @endif

        <ul class="list">
            @forelse ($doc->lines as $line)
                <li class="line" wire:key="line-{{ $line->id }}">
                    <div>
                        {{ $line->item->name }}
                        <span class="sub">{{ $line->item->code }}@if ($line->item->article) · {{ $line->item->article }}@endif · {{ $line->item->unit }}</span>
                        @if ($line->production_date)<span class="sub">Ishlab chiqarilgan: {{ $line->production_date->format('d.m.Y') }}@if ($line->expiry_date) · Yaroqlilik: {{ $line->expiry_date->format('d.m.Y') }}@endif</span>@endif
                    </div>

                    @if ($doc->isPriceCheck())
                        <div class="my-1 text-3xl font-bold">{{ number_format($line->price, 0, '.', ' ') }} <small class="sub !inline">so'm / {{ $line->item->unit }}</small></div>
                        @if ($line->shelf_price !== null)<div class="sub">Javondagi narx: {{ number_format($line->shelf_price, 0, '.', ' ') }}</div>@endif
                        @if ($doc->isDraft())
                            <div class="lc">
                                <div class="flex flex-1 gap-2">
                                    <button type="button" class="btn {{ $line->check_action === 'print' ? 'gray' : 'green' }}" wire:click="setCheck({{ $line->id }}, 'confirmed')">✓ To‘g‘ri</button>
                                    <button type="button" class="btn {{ $line->check_action === 'print' ? '' : 'gray' }}" wire:click="setCheck({{ $line->id }}, 'print')">Yorliq</button>
                                </div>
                                <button type="button" class="x" aria-label="O'chirish" wire:click="removeLine({{ $line->id }})">✕</button>
                            </div>
                        @else
                            <span class="badge {{ $line->check_action === 'print' ? '' : 'completed' }}">{{ $line->check_action === 'print' ? 'Yorliq chop etishga' : 'Narx to‘g‘ri' }}</span>
                        @endif
                    @elseif ($doc->isDraft())
                        <div class="lc">
                            <div class="flex flex-1 gap-2">
                                @if ($doc->canEditQty())
                                    <label class="mini">Miqdor
                                        <input class="qty" type="number" step="any" min="0.001" value="{{ $line->qty + 0 }}" @kb wire:change="updateQty({{ $line->id }}, $event.target.value)">
                                    </label>
                                @endif
                                @if ($doc->canEditPrice())
                                    <label class="mini">Narx
                                        <input class="qty" type="number" step="any" min="0" value="{{ $line->price + 0 }}" @kb wire:change="updatePrice({{ $line->id }}, $event.target.value)">
                                    </label>
                                @endif
                            </div>
                            <button type="button" class="x" aria-label="O‘chirish" wire:click="removeLine({{ $line->id }})">✕</button>
                        </div>
                    @else
                        <div class="lc">
                            <strong>
                                @if ($doc->canEditQty()){{ $line->qty + 0 }} {{ $line->item->unit }}@endif
                                @if ($doc->canEditPrice()) × {{ number_format($line->price, 0, '.', ' ') }}@endif
                            </strong>
                        </div>
                    @endif
                </li>
            @empty
                <li class="placeholder">Tovar skanerlang yoki “Tanlash” tugmasini bosing.</li>
            @endforelse
        </ul>

        {{-- Burger menu: the document header, with save / cancel / complete at the bottom. --}}
        <div id="scrim" @click="drawer = false"></div>
        <nav id="drawer" class="wide">
            <div class="head">Hujjat sarlavhasi<small>{{ $doc->number }} · {{ $doc->type_label }}</small></div>

            <div class="dbody">
                @if ($doc->isDraft())
                    <form id="hdr" wire:submit="saveHeader('draft')">
                        @foreach (\App\Models\Document::LOCKED_AFTER_CREATE as $locked)
                            @if (isset($fields[$locked]))
                                <div class="kv"><span>{{ $fields[$locked]['label'] }}</span><span>{{ $doc->headerDisplay()[$locked] ?: '—' }}</span></div>
                            @endif
                        @endforeach
                        @include('livewire.pda.docs._fields', ['fields' => $editable])
                    </form>
                @else
                    @foreach ($doc->headerDisplay() as $name => $value)
                        <div class="kv"><span>{{ $fields[$name]['label'] }}</span><span>{{ $value }}</span></div>
                    @endforeach
                @endif
            </div>

            <div class="dactions">
                @if ($doc->isDraft())
                    <button type="button" class="btn" wire:click="saveHeader('draft')">Saqlash va chiqish</button>
                @endif
                {{-- Exit: back to the document list without touching anything; the draft and its lines stay saved. --}}
                <a class="btn gray" id="docExit" href="{{ route('docs.index', $doc->type) }}">Chiqish</a>
                @if ($doc->isDraft())
                    <button type="button" class="btn red" wire:click="cancelDocument" wire:confirm="Hujjat o‘chiriladi. Bekor qilinsinmi?">Bekor qilish</button>
                    <button type="button" class="btn green" wire:click="saveHeader('complete')">Yakunlash va saqlash</button>
                @endif
            </div>
        </nav>

        <x-slot:footer>
            <div class="footer totals">
                <span>Pozitsiya: <strong>{{ $doc->lines->count() }}</strong></span>
                @if ($doc->isPriceCheck())<span>Yorliq: <strong>{{ $doc->lines->where('check_action', 'print')->count() }}</strong></span>@endif
                @if ($doc->canEditQty())<span>Miqdor: <strong>{{ $doc->lines->sum('qty') + 0 }}</strong></span>@endif
                @if ($doc->canEditQty() && $doc->canEditPrice())
                    <span>Summa: <strong>{{ number_format($doc->lines->sum(fn ($l) => $l->qty * $l->price), 0, '.', ' ') }}</strong></span>
                @endif
            </div>
        </x-slot:footer>
    </x-pda.page>
</div>