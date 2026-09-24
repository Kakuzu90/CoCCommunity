@props(['label'])
<div {{ $attributes->class('ui-dropdown') }} x-data="uiDropdown" x-id="['menu']" @keydown.escape.stop.prevent="hide(true)" @click.outside="hide(false)">
    <x-ui.button variant="secondary" x-ref="trigger" aria-haspopup="menu" ::aria-expanded="open.toString()" ::aria-controls="$id('menu')" @click="toggle()" @keydown.arrow-down.prevent="show()">{{ $label }}<x-ui.icon name="chevron" />
</x-ui.button>
    <div class="ui-menu" role="menu" :id="$id('menu')" x-ref="menu" x-show="open" x-cloak @keydown="navigate($event)" @click="if ($event.target.closest('[role=menuitem]')) hide(true)">{{ $slot }}</div>
</div>
