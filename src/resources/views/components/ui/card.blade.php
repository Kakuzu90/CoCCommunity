@props(['variant' => 'flat', 'selected' => false])
<div {{ $attributes->class('ui-card') }} data-variant="{{ $variant }}" @if($selected) data-selected 
@endif
>
    @isset($title)<h3 class="ui-card-title">{{ $title }}</h3>
@endisset

    {{ $slot }}
    @isset($footer)<div class="ui-modal-actions">{{ $footer }}</div>
@endisset

</div>
