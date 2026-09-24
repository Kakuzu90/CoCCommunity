@props(['text', 'position' => 'top'])
<span class="ui-tooltip-wrap" x-data="uiTooltip" x-id="['tooltip']" @mouseenter="visible = true" @mouseleave="visible = false" @focusin="visible = true" @focusout="visible = false" @keydown.escape.stop.prevent="visible = false">
    <button type="button" {{ $attributes->class('ui-button') }} data-variant="ghost" :aria-describedby="$id('tooltip')">{{ $slot }}</button>
    <span x-ref="tip" role="tooltip" :id="$id('tooltip')" class="ui-tooltip" data-position="{{ $position }}" x-show="visible" x-cloak>{{ $text }}</span>
</span>
