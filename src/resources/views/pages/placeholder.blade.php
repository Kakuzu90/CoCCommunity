{{-- Shell-wrapped placeholder so navigation targets resolve before their phase lands. --}}
<x-layouts.app :title="$heading">
    <x-ui.empty-state :title="$heading" :body="$body">
        <x-slot:action>
            <a href="{{ route('home') }}" class="ui-button" data-variant="secondary">Back to home</a>
        </x-slot:action>
    </x-ui.empty-state>
</x-layouts.app>
