{{-- Step 1 of 2: document header. --}}
@section('title', $title)

<div class="contents" x-data @keydown.window.escape="window.location = '{{ route('docs.index', $type) }}'">
    <x-pda.page :notice="$notice" :failure="$failure">
        <x-slot:header>
            <a class="hbtn" href="{{ route('docs.index', $type) }}" aria-label="{{ __('Orqaga') }}">←</a>
            <span class="title">{{ $title }}<small class="block text-xs font-normal opacity-80">{{ __('1/2 — Hujjat sarlavhasi') }}</small></span>
        </x-slot:header>

        <form id="hdr" class="pad" wire:submit="save">
            <div class="kv"><span>{{ __('Sana') }}</span><span>{{ now()->format('d.m.Y') }}</span></div>
            @include('livewire.pda.docs._fields')
        </form>

        <x-slot:footer>
            <div class="footer"><button class="btn" form="hdr">{{ __('Keyingi: tovarlar →') }}</button></div>
        </x-slot:footer>
    </x-pda.page>
</div>