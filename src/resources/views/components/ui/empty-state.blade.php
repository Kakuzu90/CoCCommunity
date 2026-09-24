@props(['title', 'body' => null])
<div {{ $attributes->class('ui-empty') }}>
    <div class="ui-empty-art" aria-hidden="true">{{ $illustration ?? '' }}@unless(isset($illustration))<x-ui.icon name="layers" size="24" />
@endunless
</div>
    <h3>{{ $title }}</h3>@if($body)<p class="ui-help">{{ $body }}</p>
@endif

    {{ $slot }}
    @isset($action)<div>{{ $action }}</div>
@endisset

</div>
