{{-- Previous / next for a Livewire simplePaginate list (WithPagination) --}}
@if ($paginator->hasPages())
    <div class="pager">
        @if ($paginator->currentPage() > 1)<button type="button" class="btn gray" wire:click="previousPage">{{ __('← Oldingi') }}</button>@endif
        @if ($paginator->hasMorePages())<button type="button" class="btn gray" wire:click="nextPage">{{ __('Keyingi →') }}</button>@endif
    </div>
@endif
