@props(['tone' => 'info', 'title' => null, 'dismissible' => false])
<div {{ $attributes->class('ui-alert') }} data-tone="{{ $tone === 'maintenance' ? 'warning' : $tone }}" role="{{ $tone === 'danger' ? 'alert' : 'status' }}" x-data="{ visible: true }" x-show="visible">
    <x-ui.icon name="info" />
<div class="ui-toast-content">@if($title)<p class="font-semibold">{{ $title }}</p>
@endif
{{ $slot }}</div>
    @if($dismissible)<x-ui.button variant="ghost" :icon-only="true" aria-label="Dismiss alert" @click="visible = false">
<x-ui.icon name="close" />
</x-ui.button>
@endif

</div>
