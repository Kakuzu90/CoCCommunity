{{-- Gold check with a tooltip saying what verification means (specs/18 §4 VerifiedBadge). The trigger is a
     button so keyboard users can reach the explanation; its accessible name is the badge text itself. --}}
<x-ui.tooltip text="Owns at least one Clash of Clans account, proven with an in-game API token." position="bottom" class="verified-badge">
    <x-ui.icon name="check" size="16" />
    <span>Verified player</span>
</x-ui.tooltip>
