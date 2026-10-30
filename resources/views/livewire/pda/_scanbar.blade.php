{{--
    Scanner input + "Tanlash" (browse the item list), bound to Livewire.
    Params: $submit (action name), $model (property), $browse (url), optional $placeholder.
    Barcode scanners type into the focused field and press Enter, which submits the form.
--}}
<form class="pad scanrow sticky" wire:submit="{{ $submit }}">
    <input class="field" wire:model="{{ $model }}" placeholder="{{ $placeholder ?? 'Shtrix-kod, kod yoki artikul' }}" autocomplete="off" @kb autofocus x-init="$el.focus()">
    <a class="btn browse" href="{{ $browse }}">{{ __('Tanlash') }}</a>
</form>