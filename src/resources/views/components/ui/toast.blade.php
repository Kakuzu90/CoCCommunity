@props(['tone' => 'info', 'title' => null, 'dismissible' => true])
<div {{ $attributes->class('ui-toast') }} data-tone="{{ $tone }}" role="status" x-data="{ visible: true }" x-show="visible" x-transition.opacity>
    <x-ui.icon :name="$tone === 'reward' ? 'star' : ($tone === 'success' ? 'check' : 'info')" />
    <div class="ui-toast-content">@if($title)<p class="font-semibold">{{ $title }}</p>
@endif
<div>{{ $slot }}</div>
</div>
    @if($dismissible)<x-ui.button variant="ghost" :icon-only="true" aria-label="Dismiss notification" @click="visible = false">
<x-ui.icon name="close" size="16" />
</x-ui.button>
@endif

</div>
