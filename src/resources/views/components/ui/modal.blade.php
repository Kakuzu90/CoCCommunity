@props(['name', 'title', 'sheet' => true])
<dialog {{ $attributes->class('ui-modal') }} x-data="uiModal" data-name="{{ $name }}" @ui-modal.window="if ($event.detail.name === $el.dataset.name) show()" @keydown.tab="trap($event)" @cancel.prevent="close()" @click="if ($event.target === $el) backdrop($event)" @if($sheet) data-sheet 
@endif
 aria-labelledby="{{ $name }}-title">
    <div class="ui-modal-header">
<h2 id="{{ $name }}-title">{{ $title }}</h2>
<x-ui.button variant="ghost" :icon-only="true" aria-label="Close dialog" @click="close()">
<x-ui.icon name="close" />
</x-ui.button>
</div>
    {{ $slot }}
    @isset($actions)<div class="ui-modal-actions">{{ $actions }}</div>
@endisset

</dialog>
