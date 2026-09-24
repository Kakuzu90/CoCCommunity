@props(['tone' => 'neutral', 'selected' => null, 'removable' => false, 'label' => null])
@if($removable)
<span class="ui-pill" data-tone="{{ $tone }}" x-data="{ visible: true }" x-show="visible">{{ $slot }}<button type="button" aria-label="Remove {{ $label ?: strip_tags($slot) }}" @click="visible = false; $dispatch('pill-removed')">
<x-ui.icon name="close" size="16" />
</button>
</span>

@elseif($selected !== null)
<button type="button" {{ $attributes->class('ui-pill') }} data-tone="{{ $tone }}" aria-pressed="{{ $selected ? 'true' : 'false' }}">{{ $slot }}</button>

@else

<span {{ $attributes->class('ui-pill') }} data-tone="{{ $tone }}">{{ $slot }}</span>

@endif

