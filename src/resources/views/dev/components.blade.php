<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Component library · Clash Commons</title>
    <link rel="preload" href="{{ Vite::asset('node_modules/@fontsource/lilita-one/files/lilita-one-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<a class="ui-skip" href="#content">Skip to content</a>
<x-ui.icons />
<div class="gallery">
    <header class="gallery-header">
<span class="gallery-brand">Clash<span class="text-primary"> Commons</span>
</span>
<x-ui.pill tone="primary">Design system · v0.1</x-ui.pill>
</header>
    <main id="content">
        <div class="gallery-hero">
<p class="gallery-eyebrow">Built for the community</p>
<h1>A common language.<br>Room to make it yours.</h1>
<p class="gallery-description">Bold where we celebrate. Clear where we work. The building blocks of Clash Commons, from the first tap to the next milestone.</p>
</div>
        <nav class="gallery-nav" aria-label="Component sections">@foreach(['foundations' => 'Foundations', 'actions' => 'Actions', 'forms' => 'Forms', 'identity' => 'Identity', 'surfaces' => 'Surfaces', 'feedback' => 'Feedback', 'navigation' => 'Navigation'] as $id => $text)<a href="#{{ $id }}">{{ $text }}</a>

@endforeach
</nav>
        <section id="foundations" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">01</span>
<h2>Foundations</h2>
</div>
            <x-ui.card>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">@foreach(['brand-primary' => 'Gold / action', 'accent' => 'Purple / accent', 'state-success' => 'Green / success', 'state-danger' => 'Red / danger', 'state-info' => 'Blue / info', 'bg-surface-raised' => 'Navy / surface'] as $token => $label)<div>
<div class="gallery-swatch" style="background: var(--{{ $token }})">
</div>
<p class="ui-label">{{ $label }}</p>
</div>

@endforeach
</div>
</x-ui.card>
            <div class="gallery-grid mt-6">
<x-ui.card>
<p class="gallery-eyebrow mb-3">Lilita One · display</p>
<h3 class="font-heading text-3xl font-normal">Your next great idea.</h3>
<p class="gallery-caption mt-3">Original identity. A little weight. Plenty of character.</p>
</x-ui.card>
<x-ui.card>
<p class="gallery-eyebrow mb-3">Inter · body / JetBrains Mono · tags</p>
<p>Clear information, without getting in the way.</p>
<code class="text-secondary mt-3 block">#P0Y8Q2 · 24,680</code>
</x-ui.card>
</div>
            <x-ui.card class="mt-6">
<p class="gallery-eyebrow mb-3">Iconography · original 24px outline set, no game marks</p>
<div class="grid grid-cols-4 sm:grid-cols-8 gap-4">@foreach(['home', 'search', 'layers', 'shield', 'user', 'bell', 'menu', 'heart', 'star', 'plus', 'check', 'close', 'chevron', 'arrow', 'info', 'spinner'] as $name)<div class="gallery-swatch" style="display:grid;place-items:center">
<x-ui.icon :name="$name" size="24" />
</div>
@endforeach
</div>
</x-ui.card>
        </section>
        <section id="actions" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">02</span>
<h2>Actions with a little weight</h2>
</div>
            <x-ui.card>
<div class="gallery-stack">
<div class="gallery-row">@foreach(['primary' => 'Publish a base', 'secondary' => 'Save draft', 'ghost' => 'Not now', 'danger' => 'Delete', 'success' => 'Confirm'] as $variant => $text)<x-ui.button :variant="$variant">{{ $text }}</x-ui.button>

@endforeach
</div>
<div class="gallery-row">
<x-ui.button size="sm">Small</x-ui.button>
<x-ui.button>Medium</x-ui.button>
<x-ui.button size="lg">Large <x-ui.icon name="arrow" />
</x-ui.button>
<x-ui.button :icon-only="true" aria-label="Add item">
<x-ui.icon name="plus" />
</x-ui.button>
<x-ui.button disabled>Unavailable</x-ui.button>
<x-ui.button :loading="true">Saving changes</x-ui.button>
</div>
<x-ui.button :block="true" variant="secondary">Full-width action</x-ui.button>
<p class="gallery-caption">Tab for focus. Press for depth. Loading keeps the original button width.</p>
</div>
</x-ui.card>
        </section>
        <section id="forms" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">03</span>
<h2>Clear inputs. Useful feedback.</h2>
</div>
            <div class="gallery-grid">
                <x-ui.card>
<div class="gallery-stack">
<x-ui.input id="display-name" label="Display name" placeholder="What should we call you?" hint="A name the community will recognize." />
<x-ui.input id="player-tag" label="Player tag" prefix="#" value="P0Y8Q2" />
<x-ui.input id="website" label="Website" suffix="↗" placeholder="https://example.com" />
<x-ui.input id="invalid-tag" label="Invalid player tag" value="ABC" error="Use your in-game tag, such as #P0Y8Q2." />
<x-ui.input id="readonly-field" label="Read-only value" value="Clash Commons" readonly />
<x-ui.input id="disabled-field" label="Disabled value" value="Not available yet" disabled />
</div>
</x-ui.card>
                <x-ui.card>
<div class="gallery-stack">
<x-ui.textarea id="bio" label="About you" value="Always refining the next layout." :counter="true" :maxlength="120" hint="Keep it short and make it yours." />
<x-ui.select id="category" label="Category" :options="['war' => 'War', 'farming' => 'Farming', 'trophy' => 'Trophy']" value="war" />
<x-ui.select id="search-category" label="Searchable category" :searchable="true" :options="['war' => 'War', 'farming' => 'Farming', 'trophy' => 'Trophy']" />
<x-ui.select id="disabled-select" label="Disabled select" :options="['war' => 'War']" disabled />
<x-ui.select id="invalid-select" label="Category with error" :options="['war' => 'War']" error="Choose a category." />
<x-ui.textarea id="invalid-bio" label="Note with error" value="Too much detail." error="Please shorten this note." />
<x-ui.textarea id="disabled-bio" label="Disabled note" disabled />
<x-ui.textarea id="readonly-bio" label="Read-only note" value="Saved for later." readonly />
</div>
</x-ui.card>
                <x-ui.card>
<div class="gallery-stack">
<fieldset>
<legend class="ui-label mb-2">Preferences</legend>
<div class="grid">
<x-ui.checkbox id="check-one" label="Email me about replies" checked />
<x-ui.checkbox id="check-two" label="Select some items" :indeterminate="true" />
<x-ui.checkbox id="check-three" label="Coming later" disabled />
<x-ui.toggle id="toggle-one" label="Show public profile" checked />
<x-ui.toggle id="toggle-two" label="Disabled toggle" disabled />
</div>
</fieldset>
<fieldset>
<legend class="ui-label">Default visibility</legend>
<div class="grid">
<x-ui.radio id="radio-public" label="Public" name="visibility" value="public" checked />
<x-ui.radio id="radio-unlisted" label="Unlisted" name="visibility" value="unlisted" />
<x-ui.radio id="radio-private" label="Private (disabled)" name="visibility" disabled />
</div>
</fieldset>
</div>
</x-ui.card>
            </div>
        </section>
        <section id="identity" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">04</span>
<h2>Small details, clear meaning</h2>
</div>
            <div class="gallery-grid">
<x-ui.card>
<div class="gallery-stack">
<div class="gallery-row">@foreach(['verified','featured','moderator','admin','rarity'] as $badge)<x-ui.badge :variant="$badge" />

@endforeach
</div>
<div class="gallery-row">
<x-ui.pill>Neutral</x-ui.pill>
<x-ui.pill tone="accent">War</x-ui.pill>
<x-ui.pill tone="primary">TH 17</x-ui.pill>
<x-ui.pill tone="success">Ready</x-ui.pill>
<x-ui.pill tone="danger">Failed</x-ui.pill>
<x-ui.pill :selected="true">Selected</x-ui.pill>
<x-ui.pill :selected="false">Unselected</x-ui.pill>
<x-ui.pill :removable="true" label="Farming">Farming</x-ui.pill>
</div>
<p class="gallery-caption">Labels carry the meaning. Color supports it.</p>
</div>
</x-ui.card>
<x-ui.card>
<div class="gallery-row">@foreach([24,32,48,64,96,128] as $size)<x-ui.avatar name="Alex River" :size="$size" :verified="$size === 64" />

@endforeach
<x-ui.avatar name="Alex River image" src="/images/avatar-demo.svg" /><x-ui.avatar name="Loading avatar" :loading="true" />
<x-ui.avatar name="Image fallback" src="/missing-gallery-avatar.png" />
</div>
<p class="gallery-caption mt-4">Six sizes, verified rings, and initials when an image is unavailable.</p>
</x-ui.card>
</div>
        </section>
        <section id="surfaces" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">05</span>
<h2>A place for every moment</h2>
</div>
            <div class="gallery-grid">@foreach(['flat','raised','interactive','feature'] as $variant)<x-ui.card :variant="$variant">
<x-slot:title>{{ ucfirst($variant) }} card</x-slot:title>
<p class="ui-help">A quiet surface with room for what matters.</p>@if($variant === 'interactive')<x-slot:footer>
<x-ui.button variant="ghost">Explore card <x-ui.icon name="arrow" />
</x-ui.button>
</x-slot:footer>
@endif
</x-ui.card>

@endforeach
<x-ui.card :selected="true">
<x-slot:title>Selected card</x-slot:title>
<x-ui.pill tone="primary">Selected</x-ui.pill>
</x-ui.card>
<x-ui.card>
<x-slot:title>Modal & bottom sheet</x-slot:title>
<p class="ui-help mb-4">One focused decision. A bottom sheet on smaller screens.</p>
<x-ui.button id="open-dialog" x-data @click="$dispatch('ui-modal', { name: 'gallery-dialog' })">Open dialog</x-ui.button>
</x-ui.card>
</div>
            <x-ui.modal name="gallery-dialog" title="Make it your own">
<p class="ui-help mb-4">A focused space for the next step. Escape closes this dialog and returns focus to its trigger.</p>
<x-ui.input id="dialog-name" label="Collection name" autofocus placeholder="Weekend ideas" />
<x-slot:actions>
<x-ui.button @click="close()">Done</x-ui.button>
<x-ui.button variant="secondary" @click="close()">Cancel</x-ui.button>
</x-slot:actions>
</x-ui.modal>
        </section>
        <section id="feedback" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">06</span>
<h2>Every state belongs</h2>
</div>
            <div class="gallery-grid">
<div class="gallery-stack">@foreach(['info' => 'Your draft is saved here.', 'success' => 'Changes saved successfully.', 'danger' => 'That did not work. Please try again.', 'reward' => 'Your first milestone. Nicely done.'] as $tone => $text)<x-ui.toast :tone="$tone" :title="ucfirst($tone)">{{ $text }}</x-ui.toast>

@endforeach
</div>
<div class="gallery-stack">@foreach(['info' => 'You can update these details later.', 'warning' => 'Check your details before continuing.', 'danger' => 'We could not save your changes.', 'maintenance' => 'Game data is temporarily unavailable.'] as $tone => $text)<x-ui.alert :tone="$tone" :dismissible="true">{{ $text }}</x-ui.alert>

@endforeach
</div>
<x-ui.card>
<x-ui.empty-state title="A fresh start" body="Your saved ideas will feel right at home here.">
<x-slot:action>
<x-ui.button variant="secondary">Find inspiration <x-ui.icon name="arrow" />
</x-ui.button>
</x-slot:action>
</x-ui.empty-state>
</x-ui.card>
</div>
            <div class="gallery-grid mt-6">
<x-ui.card>
<div class="gallery-stack">
<h3>Loading with intention</h3>
<div class="gallery-row">
<x-ui.skeleton variant="avatar" />
<x-ui.skeleton variant="stat" />
</div>
<x-ui.skeleton />
<x-ui.skeleton variant="card" />
<x-ui.skeleton variant="media" />
<p class="gallery-caption">Motion becomes a static tint when reduced motion is enabled.</p>
</div>
</x-ui.card>
<x-ui.card>
<div class="gallery-stack">
<h3>Progress, at a glance</h3>
<x-ui.progress id="upload-progress" label="Uploading screenshots" :value="64" />
<x-ui.progress id="indeterminate-progress" label="Preparing your upload" />
<x-ui.progress id="ring-progress" label="Complete" :value="75" variant="ring" />
</div>
</x-ui.card>
</div>
        </section>
        <section id="navigation" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">07</span>
<h2>Find your way</h2>
</div>
            <div class="gallery-grid">
<x-ui.card>
<div class="gallery-stack">@foreach(['underline','pill'] as $variant)<x-ui.tabs :id="'tabs-'.$variant" :variant="$variant" :tabs="['overview' => 'Overview', 'details' => 'Details', 'history' => 'History']">
<x-slot:overview>Everything you need at a glance.</x-slot:overview>
<x-slot:details>The details that make the difference.</x-slot:details>
<x-slot:history>A little context goes a long way.</x-slot:history>
</x-ui.tabs>

@endforeach
</div>
</x-ui.card>
<x-ui.card>
<div class="gallery-stack">
<div class="gallery-row">
<x-ui.dropdown label="More options">
<x-ui.menu-item>Save to collection</x-ui.menu-item>
<x-ui.menu-item>Copy link</x-ui.menu-item>
<x-ui.menu-item disabled>Coming later</x-ui.menu-item>
</x-ui.dropdown>@foreach(['top','bottom','left','right'] as $position)<x-ui.tooltip :position="$position" :text="'Helpful context, '.$position">{{ ucfirst($position) }} hint</x-ui.tooltip>

@endforeach
</div>
<x-ui.pagination :page="2" :pages="4" />
<div class="gallery-row">
<x-ui.load-more />
<x-ui.load-more :loading="true" />
<div x-data="{ reached: false }" @load-more="reached = true"><x-ui.load-more :automatic="true" @click="reached = true">Load more automatically</x-ui.load-more><p class="ui-help" x-show="reached">Sentinel reached; the button remains available.</p></div>
</div>
</div>
</x-ui.card>
</div>
        </section>
    </main>
    <footer class="gallery-footer">
<p>Clash Commons · Component library · Local development</p>
<p class="mt-2">This material is unofficial and is not endorsed by Supercell. For more information see <a href="https://supercell.com/en/fan-content-policy/" rel="noopener noreferrer">Supercell's Fan Content Policy</a>.</p>
</footer>
</div>
@livewireScriptConfig
</body>
</html>
