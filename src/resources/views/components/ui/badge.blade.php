@props(['variant' => 'verified'])
@php($styles = ['verified' => ['primary', 'check'], 'featured' => ['accent', 'star'], 'moderator' => ['info', 'shield'], 'admin' => ['warning', 'shield'], 'rarity' => ['accent', 'star']])
@php($style = $styles[$variant] ?? throw new InvalidArgumentException('Unknown badge variant.'))
<span {{ $attributes->class('ui-badge') }} data-tone="{{ $style[0] }}">
<x-ui.icon :name="$style[1]" size="16" />{{ $slot->isEmpty() ? ucfirst($variant) : $slot }}</span>
