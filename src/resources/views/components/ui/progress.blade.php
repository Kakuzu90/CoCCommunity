@props(['id', 'label', 'value' => null, 'max' => 100, 'variant' => 'bar'])
@php($percent = $value === null ? null : max(0, min(100, (float) $value / max(1, (float) $max) * 100)))
<div {{ $attributes->class('ui-progress') }}>
    <label for="{{ $id }}" class="ui-label">{{ $label }}@if($percent !== null) · {{ round($percent) }}%
@endif
</label>
    @if($variant === 'ring')
        <svg class="ui-progress-ring" viewBox="0 0 80 80" aria-hidden="true">
<circle cx="40" cy="40" r="32" />
<circle cx="40" cy="40" r="32" pathLength="100" stroke-dasharray="{{ $percent ?? 25 }} 100" />
</svg>
    
@endif

    <progress id="{{ $id }}" max="100" @if($percent !== null) value="{{ $percent }}" 
@endif
 @class(['sr-only' => $variant === 'ring'])>{{ $percent }}%</progress>
</div>
