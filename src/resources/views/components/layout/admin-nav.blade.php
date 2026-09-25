<nav class="adm-nav" aria-label="Admin">
    @foreach(config('navigation.admin') as $item)
        @if($item['enabled'])
            <a href="{{ route($item['route']) }}"
               @if(request()->routeIs($item['active'])) aria-current="page" @endif>
                <x-ui.icon :name="$item['icon']" size="18" />{{ $item['label'] }}
            </a>
        @else
            <span aria-disabled="true">
                <x-ui.icon :name="$item['icon']" size="18" />{{ $item['label'] }}
                <span class="adm-nav-soon">Soon</span>
            </span>
        @endif
    @endforeach
</nav>
