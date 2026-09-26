{{--
    <x-player.unit> — one progression tile: unmodified game art with the level in a corner badge,
    the way the in-game profile shows it. A maxed unit gets the gold badge with the flame ring.
    Pass :opens="modal-name" to make the tile a button that opens that dialog (hero equipment).
--}}
@props(['unit', 'size' => 56, 'opens' => null])
@php
    $unlocked = $unit['unlocked'] ?? true;
    $state = ! $unlocked ? 'Not unlocked' : ('Level '.$unit['level'].($unit['maxed'] ? ', maxed' : ''));
    $tag = $opens ? 'button' : 'span';
@endphp
<{{ $tag }}
    {{ $attributes->class(['unit-tile', 'unit-tile--maxed' => $unit['maxed'], 'unit-tile--locked' => ! $unlocked, 'unit-tile--action' => $opens]) }}
    title="{{ $unit['name'] }}"
    @if($opens) type="button" aria-haspopup="dialog" x-on:click="$dispatch('ui-modal', { name: @js($opens) })" @endif
>
    {{-- The hidden text names the unit and its state; the art would only repeat the name. --}}
    <span class="unit-tile__art" aria-hidden="true">
        <x-game.asset type="unit" :value="$unit['slug']" :name="$unit['name']" :size="$size" />
        @if($unlocked)
            <span class="unit-tile__level" aria-hidden="true">
                @if($unit['maxed'])
                    <span class="unit-tile__fire-ring" aria-hidden="true">
                        <canvas class="unit-tile__fire" x-data="unitFire" width="48" height="48"></canvas>
                    </span>
                @endif
                <span class="unit-tile__number">{{ $unit['level'] }}</span>
            </span>
        @endif
    </span>
    <span class="unit-tile__sr">{{ $unit['name'] }}, {{ $state }}@if($opens), show equipment @endif</span>
</{{ $tag }}>
