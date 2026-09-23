# 12 — Design System

The visual source of truth for all UI. When building any screen, use these tokens — never hardcode hex values that duplicate a token. Referenced by the `livewire-ui` skill.

**Direction: Dark Elixir Royal.** Violet-led with gold as a reserved accent, on a deep violet-black ground in dark mode. Premium/esports feel while staying in the Clash world.

## Typefaces

| Role | Face | Fallback | Usage |
| --- | --- | --- | --- |
| Display | **Baloo 2** (600–800) | `system-ui, sans-serif` | Headings, titles, stat numbers, logo |
| Body / data | **Rubik** (400–700) | `system-ui, -apple-system, sans-serif` | Body text, labels, inputs; use `font-variant-numeric: tabular-nums` for aligned figures |

Load from Google Fonts with a real fallback stack. Keep to these two — do not introduce a third face.

## Colour tokens

Define as CSS custom properties on `:root` (light) and override under dark. **Primary = Royal violet** (main CTAs, brand). **Secondary = Gold** (reserved accent, highlights, "featured"). Semantic colours (verified/alert/warning) are separate from the accents.

### Light theme (`:root`)

| Token | Hex | Role |
| --- | --- | --- |
| `--primary` | `#6C3BF5` | Royal violet — primary actions, brand |
| `--primary-hi` | `#8A5CFF` | Gradient/hover top stop |
| `--on-primary` | `#FFFFFF` | Text/icon on primary |
| `--accent` | `#F0A81F` | Gold — secondary accent, featured, highlights |
| `--verified` | `#1FA463` | Semantic — verified ownership |
| `--alert` | `#E0483D` | Semantic — danger, reports, suspended |
| `--warning` | `#C77A12` | Semantic — caution, disputed |
| `--bg` | `#EEEFF8` | App ground |
| `--surface` | `#FFFFFF` | Cards, sheets |
| `--surface-2` | `#F5F5FC` | Insets, inputs |
| `--surface-3` | `#EAEAF5` | Deeper insets, chips |
| `--border` | `#DEDFEE` | Hairlines |
| `--border-strong` | `#C9CAE2` | Emphasised borders |
| `--text` | `#1A1830` | Primary text |
| `--muted` | `#5D5A78` | Secondary text |
| `--faint` | `#918DAE` | Tertiary/placeholder |
| `--primary-soft` | `#E7DEFC` | Primary badge/chip fill |
| `--accent-soft` | `#FBEBCC` | Accent badge fill |
| `--verified-soft` | `#D6F0E1` | Verified badge fill |
| `--alert-soft` | `#FBE0DE` | Alert badge fill |

### Dark theme (`@media (prefers-color-scheme: dark)` + `[data-theme="dark"]`)

| Token | Hex | Role |
| --- | --- | --- |
| `--primary` | `#8A5CFF` | Royal violet (lifted for dark ground) |
| `--primary-hi` | `#A480FF` | Gradient/hover top stop |
| `--on-primary` | `#FFFFFF` | Text on primary |
| `--accent` | `#F2AE2A` | Gold |
| `--verified` | `#35C67E` | Verified |
| `--alert` | `#F26056` | Alert |
| `--warning` | `#F2AE2A` | Warning |
| `--bg` | `#0C0A1A` | Void — app ground |
| `--surface` | `#171333` | Cards |
| `--surface-2` | `#201B44` | Insets, inputs |
| `--surface-3` | `#28224F` | Deeper insets, chips |
| `--border` | `#2C2656` | Hairlines |
| `--border-strong` | `#3E3670` | Emphasised borders |
| `--text` | `#ECEAF8` | Primary text |
| `--muted` | `#A29CC4` | Secondary text |
| `--faint` | `#726C96` | Tertiary/placeholder |
| `--primary-soft` | `rgba(138,92,255,.18)` | Primary badge fill |
| `--accent-soft` | `rgba(242,174,42,.16)` | Accent badge fill |
| `--verified-soft` | `rgba(53,198,126,.16)` | Verified badge fill |
| `--alert-soft` | `rgba(226,72,61,.16)` | Alert badge fill |

Set `color-scheme: dark` wherever the dark palette applies so native controls and scrollbars follow. Guard the OS-dark block as `:root:not([data-theme="light"])` and add a `:root[data-theme="dark"]` block so a manual toggle wins in both directions.

## Tailwind mapping

Expose the tokens in `tailwind.config.js` so utilities read them:

```js
// theme.extend.colors
primary:  'rgb(var(--primary) / <alpha-value>)',   // or hex via CSS vars
accent:   'var(--accent)',
verified: 'var(--verified)',
alert:    'var(--alert)',
warning:  'var(--warning)',
surface:  { DEFAULT: 'var(--surface)', 2: 'var(--surface-2)', 3: 'var(--surface-3)' },
// bg / text / muted / border likewise
fontFamily: {
  display: ['"Baloo 2"', 'system-ui', 'sans-serif'],
  sans:    ['Rubik', 'system-ui', '-apple-system', 'sans-serif'],
},
```

Prefer semantic utility names (`bg-primary`, `text-muted`, `border-border`) over raw colours in markup.

## Usage rules

- **Primary CTA = Royal violet** (`--primary`), one per view where possible. Gold (`--accent`) is a highlight, not a second CTA colour.
- **Semantic colour is not decoration.** `--verified` only for verified ownership; `--alert` only for danger/report/suspended; `--warning` for disputed/caution. Don't use them as accents.
- Radii: cards `~14px`, controls `~11px`, pills `999px`. Shadows soft and sparing — spend elevation by role, not on every block.
- Always design both themes; give `body` an explicit `--bg` background. See the `livewire-ui` skill for layout, states, and accessibility.

Reference mock (visual, not code to copy): the "Dark Elixir Royal" column of the palette comparison.
