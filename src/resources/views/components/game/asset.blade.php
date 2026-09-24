{{--
    <x-game.asset> — the ONLY way a game asset reaches a template (specs/18 §2.3). It renders the
    unmodified artwork through GameAssetResolver, or our own original placeholder when the asset is
    unknown or the category is switched off. An accessible name is always present, so a game asset
    never carries meaning alone; callers still render the visible numeral/label beside it.

    Pass a resolved asset (:asset) or a type + value:
      <x-game.asset type="townhall" :value="15" />
      <x-game.asset type="unit" value="barbarian" name="Barbarian" />
      <x-game.asset type="league" :value="29000022" name="Legend League" />
      <x-game.asset type="clan" :value="$clan->badge_url" :name="$clan->name" />
--}}
@props(['asset' => null, 'type' => null, 'value' => null, 'name' => null, 'size' => 64])
@php
    if (! $asset instanceof \App\Domain\GameAssets\Data\GameAsset) {
        $resolver = app(\App\Domain\GameAssets\Contracts\GameAssetResolver::class);
        $asset = match ($type) {
            'unit' => $resolver->unit((string) $value, $name),
            'townhall' => $resolver->townHall((int) $value),
            'league' => $resolver->league((int) $value, $name),
            'clan' => $resolver->clanBadge($value === null ? null : (string) $value, (string) ($name ?? 'Clan')),
            default => throw new \InvalidArgumentException('<x-game.asset> needs a known type or an :asset.'),
        };
    }
    $px = (int) $size;
@endphp
@if ($asset->isPlaceholder())
    <span
        {{ $attributes->class('game-asset game-asset--placeholder')->merge(['style' => "--game-asset-size: {$px}px"]) }}
        role="img"
        aria-label="{{ $asset->name }}"
        data-category="{{ $asset->category }}"
    >
        <span class="game-asset__mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($asset->name, 0, 1)) }}</span>
    </span>
@else
    <img
        {{ $attributes->class('game-asset') }}
        src="{{ $asset->url }}"
        alt="{{ $asset->name }}"
        width="{{ $px }}"
        height="{{ $px }}"
        loading="lazy"
        decoding="async"
        data-category="{{ $asset->category }}"
    >
@endif
