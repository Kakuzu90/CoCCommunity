@php
    // Original placeholder art (ours) used only to demonstrate the resolver's clan-badge
    // pass-through <img> branch in this gallery — not a Supercell asset.
    $sampleBadge = 'data:image/svg+xml;utf8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 72 72"><rect width="72" height="72" rx="14" fill="#2A3150"/><path d="M36 12l18 6v14c0 12-8 20-18 26-10-6-18-14-18-26V18z" fill="#F5B800"/></svg>');
@endphp
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
        <nav class="gallery-nav" aria-label="Component sections">@foreach(['foundations' => 'Foundations', 'actions' => 'Actions', 'forms' => 'Forms', 'identity' => 'Identity', 'surfaces' => 'Surfaces', 'feedback' => 'Feedback', 'navigation' => 'Navigation', 'game-assets' => 'Game assets', 'auth' => 'Auth', 'admin' => 'Admin'] as $id => $text)<a href="#{{ $id }}">{{ $text }}</a>

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
<div class="grid grid-cols-4 sm:grid-cols-8 gap-4">@foreach(['home', 'search', 'layers', 'shield', 'user', 'bell', 'menu', 'heart', 'star', 'plus', 'check', 'close', 'chevron', 'arrow', 'info', 'spinner', 'dashboard', 'flag', 'scale', 'document', 'image', 'store', 'list'] as $name)<div class="gallery-swatch" style="display:grid;place-items:center">
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
<x-ui.card>
<p class="ui-help">Avatar editor (specs/07). The file input presigns an upload, sends the bytes straight to storage and attaches the processed image — the app never proxies the file.</p>
<div class="avatar-editor">
<x-ui.avatar name="Alex River" size="96" />
<div class="avatar-editor-controls">
<span class="ui-button" data-variant="secondary" data-size="sm">Upload new</span>
<span class="ui-button" data-variant="ghost" data-size="sm">Remove</span>
<p class="ui-help">JPG, PNG or WebP, up to 2&nbsp;MB. Square works best.</p>
</div>
</div>
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
        <section id="game-assets" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">08</span>
<h2>Game assets, unmodified and labelled</h2>
</div>
<x-ui.card>
<p class="ui-help">Every Clash of Clans asset is referenced through <code>&lt;x-game.asset&gt;</code> / <code>GameAssetResolver</code> — never a hardcoded path (specs/18 §2.3). Each carries an accessible name and, when the asset is unknown or the category kill switch is off, falls back to our own original placeholder. The curated pack itself lands in Phase 2, so the catalogue examples below render as placeholders today.</p>
<div class="gallery-row" style="align-items: flex-end; gap: var(--space-4)">
<figure><x-game.asset type="townhall" :value="15" :size="72" /><figcaption class="ui-label">Town Hall 15</figcaption></figure>
<figure><x-game.asset type="unit" value="barbarian-king" name="Barbarian King" :size="72" /><figcaption class="ui-label">Barbarian King</figcaption></figure>
<figure><x-game.asset type="league" :value="29000022" name="Legend League" :size="72" /><figcaption class="ui-label">Legend League</figcaption></figure>
<figure><x-game.asset type="clan" :value="$sampleBadge" name="Sample Clan" :size="72" /><figcaption class="ui-label">Clan badge (pass-through)</figcaption></figure>
</div>
<div class="gallery-row" style="align-items: flex-end; gap: var(--space-3); margin-top: var(--space-4)">@foreach([32, 48, 64, 96] as $s)<x-game.asset type="townhall" :value="14" :size="$s" />
@endforeach
</div>
</x-ui.card>
        </section>
        <section id="auth" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">09</span>
<h2>Sign-in, built from the kit</h2>
</div>
<x-ui.card>
<p class="ui-help">The auth pages are compositions of the primitives above on a centred single-column layout (specs/04). Rate limiting, Turnstile, the honeypot and generic enumeration-safe messages live server-side.</p>
<div class="auth-card-wrap" style="margin-inline:auto">
<x-ui.card class="auth-card">
<h3 class="auth-title">Welcome back</h3>
<div class="auth-form">
<x-ui.input id="demo-email" label="Email" type="email" autocomplete="email" />
<x-ui.input id="demo-password" label="Password" type="password" autocomplete="current-password" />
<div class="auth-row">
<x-ui.checkbox id="demo-remember" label="Remember me" />
<span class="auth-link">Forgot password?</span>
</div>
<x-ui.button :block="true">Sign in</x-ui.button>
</div>
</x-ui.card>
</div>
</x-ui.card>
<x-ui.card>
<p class="ui-help">Roles are hierarchical and account status is orthogonal to role (specs/04 §1). Writes pass three gates in order — verified email, active status, verified in-game account — before any policy runs.</p>
<div class="gallery-stack">
<div>
<span class="ui-label">Roles</span>
<div class="gallery-row">@foreach(['user' => 'neutral', 'moderator' => 'info', 'admin' => 'warning', 'super_admin' => 'primary'] as $role => $tone)<x-ui.pill :tone="$tone">{{ \App\Domain\Auth\Enums\UserRole::from($role)->label() }}</x-ui.pill>
@endforeach</div>
</div>
<div>
<span class="ui-label">Account status</span>
<div class="gallery-row">@foreach(['active' => 'success', 'restricted' => 'warning', 'suspended' => 'warning', 'banned' => 'danger', 'pending_deletion' => 'neutral'] as $status => $tone)<x-ui.pill :tone="$tone">{{ \App\Domain\Auth\Enums\UserStatus::from($status)->label() }}</x-ui.pill>
@endforeach</div>
</div>
</div>
</x-ui.card>
        </section>
        <section id="admin" class="gallery-section">
            <div class="gallery-section-head">
<span class="gallery-number">10</span>
<h2>Back-office, intentionally plain</h2>
</div>
<x-ui.card>
<p class="ui-help">Admin surfaces (specs/18 §4) drop the game styling: body font, small radius, denser spacing, no lift or glow. These are where staff work, so they stay clean and boring. The classes below (<code>.adm-*</code>) are the same ones the <code>/admin</code> area renders.</p>

<span class="ui-label">Account status</span>
<div class="gallery-row" style="margin-bottom: var(--space-4)">@foreach(['active','restricted','suspended','banned','pending_deletion'] as $status)<span class="adm-status" data-status="{{ $status }}">{{ \App\Domain\Auth\Enums\UserStatus::from($status)->label() }}</span>
@endforeach</div>

<span class="ui-label">Stat cards</span>
<div class="adm-cards" style="margin-bottom: var(--space-4)">
<div class="adm-card"><div class="adm-card-label">Total users</div><div class="adm-card-value">1,248</div></div>
<div class="adm-card"><div class="adm-card-label">Staff accounts</div><div class="adm-card-value">7</div></div>
<div class="adm-card"><div class="adm-card-label">Under sanction</div><div class="adm-card-value">12</div></div>
</div>

<span class="ui-label">DataTable</span>
<div class="adm-panel" style="margin-bottom: var(--space-4)">
<div class="adm-table-wrap"><table class="adm-table">
<thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
<tbody>
<tr><td><a href="#admin">sparrowhawk</a></td><td><span class="adm-role">User</span></td><td><span class="adm-status" data-status="active">Active</span></td><td>2024-11-02</td></tr>
<tr><td><a href="#admin">gollum</a></td><td><span class="adm-role">User</span></td><td><span class="adm-status" data-status="suspended">Suspended</span></td><td>2025-01-18</td></tr>
<tr><td><a href="#admin">gandalf</a></td><td><span class="adm-role">Admin</span></td><td><span class="adm-status" data-status="active">Active</span></td><td>2024-08-10</td></tr>
</tbody>
</table></div>
</div>

<span class="ui-label">Audit trail with before/after diff</span>
<div class="adm-panel">
<div class="adm-table-wrap"><table class="adm-table">
<thead><tr><th>When</th><th>Action</th><th>Actor</th><th>Target</th><th>Change</th></tr></thead>
<tbody>
<tr><td>2 hours ago</td><td>User suspended</td><td>gandalf</td><td><a href="#admin">gollum</a></td><td><pre class="adm-diff">- status: active
+ status: suspended</pre></td></tr>
</tbody>
</table></div>
</div>
</x-ui.card>
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
