@props(['id', 'label', 'options' => [], 'value' => '', 'hint' => null, 'error' => null, 'searchable' => true])
@php($described = trim(($hint ? "$id-hint " : '').($error ? "$id-error " : '').$attributes->get('aria-describedby', '')))
<div x-data="uiSelect" class="ui-select" @click.outside="close(false)" @keydown="navigate($event)" @focusout="if (!$el.contains($event.relatedTarget)) close(false)">
    <x-ui.field :id="$id" :label="$label" :hint="$hint" :error="$error">
        <select id="{{ $id }}-native" x-ref="native" aria-label="{{ $label }}" :class="ready ? 'sr-only' : 'ui-control'" :tabindex="ready ? -1 : 0" :aria-hidden="ready" @change="sync()" @invalid.prevent="show()" {{ $attributes->except('aria-describedby') }} @if($error) aria-invalid="true" @endif @if($described) aria-describedby="{{ $described }}" @endif>
            <option value="">Choose an option</option>
            @foreach($options as $key => $text)
                <option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $text }}</option>
            @endforeach
        </select>
        <div class="ui-control-wrap" x-cloak x-show="ready">
            <button type="button" id="{{ $id }}" x-ref="trigger" class="ui-control ui-select-trigger" aria-haspopup="listbox" :aria-expanded="open" aria-controls="{{ $id }}-list" :disabled="disabled" @click="open ? close(true) : show()" @if($error) aria-invalid="true" @endif @if($described) aria-describedby="{{ $described }}" @endif>
                <span x-text="selectedLabel" :class="{ 'ui-help': !value }"></span><span aria-hidden="true">▾</span>
            </button>
            <button type="button" class="ui-select-clear" x-show="value && !disabled && !$refs.native.required" aria-label="Clear {{ $label }}" @click="choose('')">×</button>
        </div>
        <div class="ui-select-popup" x-cloak x-show="open">
            <input x-ref="search" class="ui-control ui-select-search" type="text" autocomplete="off" aria-label="Search {{ $label }} options" role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="{{ $id }}-list" :aria-activedescendant="activeId" x-model="query" @input="active = 0" @if(!$searchable) readonly @endif placeholder="{{ $searchable ? 'Search options…' : 'Choose an option' }}">
            <div id="{{ $id }}-list" x-ref="list" class="ui-select-options" role="listbox" aria-label="{{ $label }}">
                <template x-for="(option, index) in filtered" :key="option.value">
                    <div :id="$refs.list.id + '-' + index" role="option" :aria-selected="value === option.value" class="ui-select-option" :class="{ 'is-active': active === index }" @mouseenter="active = index" @mousedown.prevent @click="choose(option.value)">
                        <span x-text="option.label"></span><span aria-hidden="true" x-show="value === option.value">✓</span>
                    </div>
                </template>
            </div>
            <p class="ui-help ui-select-empty" role="status" x-show="!filtered.length">No matching options.</p>
        </div>
    </x-ui.field>
</div>
