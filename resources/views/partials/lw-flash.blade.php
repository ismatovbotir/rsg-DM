{{-- Messages inside a Livewire component (the layout's own flash is outside it and only shows after a redirect) --}}
@if (! empty($notice))<div class="flash">{{ $notice }}</div>@endif
@if (! empty($failure))<div class="flash error">{{ $failure }}</div>@endif
@foreach ($errors->all() as $error)<div class="flash error">{{ $error }}</div>@endforeach
