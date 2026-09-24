@props(['variant' => 'sidebar'])
{{-- One item list, three presentations (sidebar / top bar / bottom tabs) — specs/18 §5. --}}
<ul {{ $attributes->class('app-nav')->merge(['data-variant' => $variant]) }}>
    @foreach (config('navigation.primary') as $item)
        <li>
            <a href="{{ route($item['route']) }}" class="app-nav-link"
               @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                <x-ui.icon :name="$item['icon']" size="24" />
                <span class="app-nav-label">{{ $item['label'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
