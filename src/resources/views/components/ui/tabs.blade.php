@props(['id', 'tabs', 'variant' => 'underline', 'label' => 'Sections'])
<div {{ $attributes }} data-variant="{{ $variant }}" x-data="uiTabs">
    <div role="tablist" aria-label="{{ $label }}" class="ui-tablist" @keydown="navigate($event)">
    @foreach($tabs as $key => $text)
        <button type="button" role="tab" id="{{ $id }}-tab-{{ $key }}" aria-controls="{{ $id }}-panel-{{ $key }}" class="ui-tab" :aria-selected="(active === {{ $loop->index }}).toString()" :tabindex="active === {{ $loop->index }} ? 0 : -1" @click="active = {{ $loop->index }}">{{ $text }}</button>
    

@endforeach

    </div>
    @foreach($tabs as $key => $text)<section role="tabpanel" id="{{ $id }}-panel-{{ $key }}" aria-labelledby="{{ $id }}-tab-{{ $key }}" tabindex="0" class="ui-panel" x-show="active === {{ $loop->index }}" @if(!$loop->first) x-cloak 
@endif
>{{ $$key ?? '' }}</section>

@endforeach

</div>
