# UI primitives

See `/dev/components` locally for the living gallery. The route returns 404 in production,
including when routes were cached in another environment. No demo form persists data.

Layouts include `@vite(['resources/css/app.css', 'resources/js/app.js'])`, `@livewireStyles`,
`<x-ui.icons />` once, and `@livewireScriptConfig`. Livewire's bundled Alpine is the only Alpine
instance. The display font preload is shown in the gallery head. Fonts are installed through
Fontsource, served by Vite from this application, and use their bundled OFL licenses.

## Common conventions

Pass standard HTML, `wire:*`, and Alpine attributes through the attribute bag. On Blade
components use `::aria-expanded="open"` for Alpine bindings (a single colon means PHP).
Slots accept trusted Blade markup; render user strings with `{{ }}`. Never put user-provided
HTML into slots using unescaped output. Component identifiers must be unique in the document.
Colors come from semantic tokens in `resources/css/tokens.css`; arbitrary template colors and
Tailwind's raw color palettes are checked in the architecture suite.

## Controls

- `button`: `variant` primary/secondary/ghost/danger/success; `size` sm/md/lg;
  `block`, `icon-only`, `disabled`, `loading`. Icon-only requires `aria-label`.
  Default `type="button"`; explicitly request `type="submit"` for forms. Loading disables
  activation and keeps the label's width. Coarse pointers enlarge small targets to 44px.
- `input`, `textarea`, `select`: required `id` and `label`; optional `hint`, `error`.
  Standard `disabled`, `readonly`, `required`, `name`, `value` and Livewire attributes pass through.
  Errors and hints merge with a caller's `aria-describedby`. Callers focus the first invalid
  field after their server-side validation fails.
- `input` has `prefix`/`suffix`; `textarea` has `counter`/`maxlength` and `value`.
- `select` takes associative `options` and a selected `value`. `searchable` adds an Alpine-powered,
  labelled option filter while keeping native select keyboard behavior. Readonly is not a native
  select state: render a read-only value when editing is disallowed.
- `checkbox`, `radio`, `toggle`: `id`, `label`, native input attributes. Checkbox also accepts
  `indeterminate`. Group related options with a fieldset and legend. Toggle is a native checkbox
  with switch semantics.

## Surfaces and identity

- `card`: flat/raised/interactive/feature `variant`, optional `selected`, title/footer slots.
  Interactive cards get a hover treatment; put a real link or button inside for activation.
- `pill`: `tone` neutral/primary/accent/info/success/warning/danger; optional controlled `selected`
  renders a button. Category/TH/status labels use these tones; game-specific tier mapping is a
  later signature component. `removable` emits `pill-removed` and hides the pill.
- `badge`: verified/featured/moderator/admin/rarity `variant`; visible default or custom slot label.
- `avatar`: required `name`; optional HTTP(S) or root-relative `src`; `size` 24/32/48/64/96/128;
  `verified`, `loading`. Errors fall back to initials; declared dimensions prevent layout shift.
- `icon`: original outline sprite, `name` check/close/plus/chevron/arrow/info/star/shield/layers/search/
  spinner/heart; sizes 16/20/24. Icons are decorative: provide text or aria-label on the control.

## Interaction and feedback

- `modal`: unique `name`, `title`, default/actions slots; `sheet` defaults true on mobile.
  Open with `$dispatch('ui-modal', { name: 'your-name' })`. Inside, call `close()`.
  Native dialog makes the background inert; Tab wraps, Escape closes, and focus returns to the
  opener. Place `autofocus` on the intended initial control. Background scrolling is locked.
- `toast`: info/success/danger/reward `tone`, `title`, `dismissible`; live status semantics.
  Reward styling is only for real achievements in product flows, not routine confirmations.
  Toasts do not time out automatically, so users have time to read them.
- `alert`: info/warning/danger/maintenance `tone`, `title`, `dismissible`; danger uses alert semantics.
- `skeleton`: text-line/card/avatar/stat/media `variant`, accessible `label`.
- `empty-state`: `title`, `body`, illustration/action slots. Default art is original geometry.
- `tooltip`: `text`, top/bottom/left/right `position`; slot is the trigger label, not another control.
  Shows on focus/hover, closes on Escape, and clamps to the viewport.
- `dropdown`: `label`, `menu-item` children. Arrow keys, Home/End, Escape, Tab, and outside click
  are supported; disabled items are skipped.
- `tabs`: unique `id`, associative `tabs` mapping slot names to labels; underline/pill `variant`.
  Supply matching named slots. Arrow keys/Home/End move focus and activate the corresponding panel.
- `pagination`: `page`, `pages`, optional accessible `label`; preserves query parameters.
- `load-more`: controlled `loading`; attach the caller's click action. `automatic` also emits
  `load-more` on intersection, retaining the manual button. The caller guards in-flight requests.
- `progress`: `id`, `label`, nullable `value`, `max`, bar/ring `variant`; null is indeterminate.

Game assets, signature cards, domain-specific tier/category maps, and application layouts are
separate roadmap tasks. These primitives add no game artwork or business actions.

## Verification

`composer ci` includes rendering, escaping, production gating, and token checks. After the
frontend build, `npm run test:browser` runs Chromium interaction tests and axe WCAG 2.1 A/AA
checks at desktop and 360px, including reduced motion. On macOS it uses installed Chrome;
on Linux run `npx playwright install --with-deps chromium`. Set `UI_BASE_URL` when the app is
not on port 8080. The CI browser job starts an isolated Laravel server automatically.
