@props(['name', 'src' => null, 'size' => 48, 'verified' => false, 'loading' => false])
@php
    if (! in_array((int) $size, [24, 32, 48, 64, 96, 128], true)) throw new InvalidArgumentException('Unknown avatar size.');
    if ($src && ! preg_match('~\A(?:https?://|/(?!/))~i', $src)) throw new InvalidArgumentException('Avatar URL must be HTTP(S) or root-relative.');
    $initials = collect(preg_split('/\s+/u', trim($name)) ?: [])->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
@endphp
<span {{ $attributes->class('ui-avatar') }} data-size="{{ $size }}" @if($verified) data-verified 
@endif
 role="img" aria-label="{{ $name }}{{ $verified ? ', verified' : '' }}" @if($loading) aria-busy="true" 
@endif
 x-data="{ failed: false }">
    <span aria-hidden="true">{{ mb_strtoupper($initials) }}</span>
    @if($src && ! $loading)<img src="{{ $src }}" alt="" width="{{ $size }}" height="{{ $size }}" loading="lazy" x-show="!failed" x-on:error="failed = true">
@endif

    @if($loading)<span class="ui-skeleton absolute inset-0" aria-hidden="true">
</span>
@endif

</span>
