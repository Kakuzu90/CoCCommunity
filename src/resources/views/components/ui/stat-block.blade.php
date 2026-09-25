@props(['value', 'label', 'icon' => null, 'delta' => null, 'countUp' => false])
{{-- One figure in a <dl>: the wrapper is a <div> group so it must sit inside a <dl> (specs/18 §4 StatBlock). --}}
@php($formatted = number_format((int) $value))
<div {{ $attributes->class('ui-stat') }}>
    <dt class="ui-stat-label">{{ $label }}</dt>
    <dd class="ui-stat-value">
        @if($icon)<x-ui.icon :name="$icon" size="20" class="ui-stat-icon" />@endif
        @if($countUp)
            <span aria-hidden="true" x-data="uiCountUp({{ (int) $value }})">{{ $formatted }}</span><span class="sr-only">{{ $formatted }}</span>
        @else
            <span>{{ $formatted }}</span>
        @endif
    </dd>
    @if($delta !== null && (int) $delta !== 0)
        <dd class="ui-stat-delta" data-direction="{{ $delta > 0 ? 'up' : 'down' }}">
            <span aria-hidden="true">{{ $delta > 0 ? '+' : '−' }}{{ number_format(abs((int) $delta)) }}</span>
            <span class="sr-only">{{ $delta > 0 ? 'Up' : 'Down' }} {{ number_format(abs((int) $delta)) }} since the last update</span>
        </dd>
    @endif
</div>
