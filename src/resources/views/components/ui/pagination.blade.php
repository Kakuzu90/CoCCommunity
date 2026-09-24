@props(['page' => 1, 'pages' => 1, 'label' => 'Pagination'])
<nav {{ $attributes->class('ui-pagination') }} aria-label="{{ $label }}">
    @for($number = 1; $number <= $pages; $number++)
        @if($number === (int) $page)<span aria-current="page" aria-label="Page {{ $number }}">{{ $number }}</span>
        
@else
<a href="{{ request()->fullUrlWithQuery(['page' => $number]) }}" aria-label="Page {{ $number }}">{{ $number }}</a>
@endif

    
@endfor

</nav>
