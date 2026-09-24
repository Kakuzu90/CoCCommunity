@props(['loading' => false, 'automatic' => false])
<div @if($automatic) x-data="uiSentinel" 
@endif
>
    <x-ui.button variant="secondary" :loading="$loading" {{ $attributes }}>{{ $slot->isEmpty() ? 'Load more' : $slot }}</x-ui.button>
</div>
