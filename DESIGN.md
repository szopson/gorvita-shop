# Design

Captured from the live "Premium CSS v6.2" currently in WP Customizer → Additional CSS on staging (`gorvita.srv1594477.hstgr.cloud`). This file is the *source of truth* for visual decisions, *not* the older `--gorvita-*` tokens in `wp-content/themes/gorvita-child/style.css`.

**v6.2 deltas vs v6.1** (added in soul-pass-2, post-impeccable critique):

- Vstrip background moved from muddy `--gorvita-ink` `#3D3D3D` → `var(--gor-text)` `#1A1A18` near-black tinted-warm. Resolves the "third neutral" anti-pattern.
- New tokens: `--gor-sage` `#6B8E5F`, `--gor-sage-on-dark` `#8DC87A` (formerly hardcoded), `--gor-rose-faint`/`--gor-rose` for warm chips, `--gor-focus-dark` for sage-tinted focus rings on dark backgrounds, `--gor-font-mono` for `JetBrains Mono`.
- Add-to-cart hierarchy: loop `add_to_cart_button` becomes outlined by default (transparent + 1.5px green-dark border), fills green on hover. Three-tier loudness: hero CTA > single-product CTA > loop CTA.
- Add-to-cart success: `.added_to_cart` renders as a filled green pill with `✓` prefix instead of underlined text link.
- Universal disabled state (`opacity: 0.4`, `cursor: not-allowed`) and brand-tinted loading pulse replacing Woo's GIF spinner.
- Quantity input pilled to match button shape.
- Wishlist/compare/quick-view quietened (cream-tinted, fills green-light on hover) so they stop competing with primary CTAs.
- Woo notifications (`.woocommerce-message`, `.woocommerce-info`, `.woocommerce-error`) restyled into branded banners.
- Dark-section ghost button: any `wp-block-button` inside `.gorvita-spring` auto-flips to white-on-transparent.
- Editorial utilities ready for Gutenberg: `.gorvita-pull` (pulled italic quote), `.gorvita-dropcap` (magazine first-letter), `.gorvita-divider` (centered hairline), `.gorvita-link-view-all` (text + animated arrow), `.gorvita-mono` (JetBrains data label), `.gorvita-chip--limited` (warm rose chip).
- `::selection` brand-tinted, `scroll-behavior: smooth`, `prefers-reduced-motion` respected globally — closes DESIGN.md open-questions #3.

## Theme

Light, **cream-on-mountain-green**. Editorial-natural register: enough negative space and serif italic accents to feel like a small-batch herbal producer, not a clinical pharmacy. Backgrounds are warm `#F9F9F9` (not pure white); deep forest green is reserved for primary actions and dark reverse-out sections. There is no dark-mode variant; the daily B2C visitor is browsing on a phone, indoors, in normal household light.

## Color

**Strategy: Restrained.** Tinted neutrals carry 90%+ of the surface, deep forest green is the single committed accent, and a desaturated sage (`#8DC87A`) appears only inside dark sections as a quiet emphasis color.

### Tokens (verbatim from v6.2)

| Token | Value | Use |
|---|---|---|
| `--gor-green` | `#2D5016` | Primary action, brand. Buttons, focus, active states. |
| `--gor-green-dark` | `#1E3A0F` | Button hover, deeper accent, loop-CTA outline border. |
| `--gor-green-deeper` | `#17310a` | Deepest gradient stop on button hover. |
| `--gor-green-light` | `#EEF4E8` | Outlined-button hover background. Quiet "selected" state. Wishlist hover. |
| `--gor-cream` | `#F9F9F9` | Page background. Tinted neutral, not pure white. |
| `--gor-cream-card` | `#F9F9F9` | Product / category card background. |
| `--gor-cream-light` | `#FBF8F2` | Subtle warm secondary surface. |
| `--gor-text` | `#1A1A18` | Headings, primary copy. Tinted near-black. **Also: vstrip background (v6.2).** |
| `--gor-text-body` | `#3A3A38` | Body copy. |
| `--gor-text-muted` | `#6A6A66` | Secondary labels, footer links, sidebar widget titles, mono data labels. |
| `--gor-text-faint` | `#9A9A96` | Placeholder text, very low-emphasis meta, divider labels. |
| `--gor-border` | `rgba(45,80,22,0.08)` | Default border. Tinted toward brand green. |
| `--gor-border-hover` | `rgba(45,80,22,0.16)` | Border on hover / focus-within. |
| `--gor-focus` | `rgba(107,142,80,0.20)` | Focus-ring color on light backgrounds (sage at 20% alpha). |
| `--gor-sage` *(v6.2)* | `#6B8E5F` | Base sage. Tokenized from prior hardcoded uses. |
| `--gor-sage-on-dark` *(v6.2)* | `#8DC87A` | Italic emphasis + link color inside dark sections. Loop-CTA loading pulse. |
| `--gor-rose` *(v6.2)* | `#C97B63` | Warm accent (limited-stock chip text). |
| `--gor-rose-faint` *(v6.2)* | `rgba(201,123,99,0.10)` | Warm chip background. Use sparingly — once or twice per page. |
| `--gor-focus-dark` *(v6.2)* | `rgba(141,200,122,0.30)` | Focus ring on dark backgrounds (`.gorvita-spring`, vstrip, dark gspb rows). |
| `--gor-font-mono` *(v6.2)* | `'JetBrains Mono', monospace` | Numeric / data label face — ingredient ratios, dosage, geo coords. |

### Bridge tokens (do not remove)

The v6.1 CSS keeps two legacy aliases at the bottom of `:root`:

```css
--gorvita-green: #2D5016;
--gorvita-green-dark: #1F3A0F;
```

These exist so older child-theme classes referencing `--gorvita-*` keep rendering. **When extending, prefer the `--gor-*` namespace.** Do not add new `--gorvita-*` tokens.

### Use rules

- Pure black (`#000`) and pure white (`#fff`) are banned. The closest legitimate values are `--gor-text` (`#1A1A18`) and `--gor-cream` (`#F9F9F9`). The header background is currently `#ffffff` — a known exception kept because Blocksy's header structure resists tinting; revisit during the next polish pass.
- Headings (`h1–h4`) always render in `--gor-text`, never on a colored background unless inside a `.gorvita-spring` dark section.
- Buttons get the **green linear-gradient** (`linear-gradient(175deg, var(--gor-green) 0%, var(--gor-green-dark) 100%)`) — single source of "primary action" energy. Outline variant uses transparent background + 1.5px `--gor-border-hover`.

## Typography

Three-family editorial stack, all loaded from Google Fonts in one request:

```
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Inter:wght@400;500&family=JetBrains+Mono:wght@400&display=swap');
```

| Family | Role | Weights loaded |
|---|---|---|
| **Cormorant Garamond** | Editorial display: hero italic accents, brand-story pulled phrases, magazine-style emphasis. Used sparingly for *soul*. | 300, 400, 500, 300i, 400i |
| **Inter** | UI + body: navigation, product names, paragraph copy, prices, buttons. | 400, 500 |
| **JetBrains Mono** | Tabular / data: ingredient ratios, dosage tables, technical specs. | 400 |

### Weight & letterspacing rules

- `h1` → weight **300**, letter-spacing **-0.015em** (very light, generous tracking-tight).
- `h2`, `h3`, `h4` → weight **400**, same letter-spacing.
- Body copy → Inter 400, line-height ~1.5–1.65 (1.75 in long product descriptions, capped at 72ch).
- Uppercase labels (sidebar widget titles, sort dropdowns, ticker, tab triggers): font-size 11–13px, letter-spacing **0.06–0.10em**, weight 500–600.
- Buttons: Inter 500, font-size 14px, letter-spacing 0.04em — *not* uppercase.

### Hierarchy contrast

The scale relies on **weight + letter-spacing contrast** rather than huge size jumps. Hero is sized via `clamp()` (legacy `--fs-hero: clamp(2.5rem, 5vw, 4rem)` from `style.css` still applies). Avoid flat scales — there must be ≥1.25× ratio between heading levels.

## Spacing

8-point grid (legacy `--space-*` from `style.css` still used by older `gorvita-*` components):

```
8 / 16 / 24 / 32 / 64 / 96px
```

Section vertical padding ranges 48–96px desktop, 32–64px mobile.

## Radii

| Token | Value | Use |
|---|---|---|
| `--gor-r-sm` | `6px` | Inputs, sort dropdowns, small chips. |
| `--gor-r-md` | `12px` | Sidebar mini product cards, tab nav corners, table cells. |
| `--gor-r-lg` | `18px` | Product cards, category cards. |
| `--gor-r-xl` | `24px` | B2B hero block, large editorial cards. |
| `--gor-r-pill` | `999px` | Buttons (default), search box, sale badges, all primary CTAs. |

### Use rules

- Buttons are pills. There is no rectangular-button variant.
- Cards and editorial blocks use `lg` (18px) or `xl` (24px). Don't mix.
- Inputs and small UI use `sm` (6px) — keeping form controls grounded against the soft pills.

## Elevation

Five tiers, all using a tinted `rgba(10,18,8,…)` shadow color (greenish-charcoal, not neutral gray) so shadows feel atmospheric, not pasted-on:

| Token | Value | Use |
|---|---|---|
| `--gor-shadow-sm` | `0 2px 12px rgba(10,18,8,0.06)` | Default product card, sidebar items. |
| `--gor-shadow-md` | `0 8px 28px rgba(10,18,8,0.08)` | Category card hover. |
| `--gor-shadow-lg` | `0 20px 52px rgba(10,18,8,0.10)` | Product card hover, modal overlays. |
| `--gor-shadow-btn` | `0 8px 24px rgba(45,80,22,0.22)` | Primary button at rest. Tinted toward brand green. |
| `--gor-shadow-btn-hov` | `0 16px 40px rgba(45,80,22,0.28)` | Primary button hover. |

## Motion

- Default ease: `cubic-bezier(0.2, 0.9, 0.3, 1)` (sharp ease-out) on card hover.
- Default duration: **0.18s** for color/border transforms, **0.22–0.28s** for transform-based hover lifts.
- Card hover: `translateY(-4px to -5px)` + shadow upgrade (`sm → lg`) + border-color shift to `--gor-border-hover`.
- Product image hover: first image scales `1.04` and fades to `0`, the duplicated `.gorvita-hover-img` fades to `1` — gives a "second-angle" reveal.
- Button hover: `translateY(-2px)` + shadow upgrade.
- **Banned**: bounce, elastic, animating CSS layout properties (width/height/top/left/margin). Stick to `transform`, `opacity`, `box-shadow`, `border-color`.
- **Reduced motion**: not yet wired. Add `@media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }` in the next polish pass.

## Components (current state)

Captured for reference. Editing should preserve these patterns unless a `$impeccable shape` brief explicitly redesigns one.

### Header (`.site-header`, `.ct-header`)

- Pure white background (one of the two `#ffffff` exceptions noted above).
- Bottom border: `0.5px solid var(--gor-border)`.
- Subtle shadow: `0 1px 16px rgba(10,18,8,0.04)` — softer than card shadows, just enough to detach the header from page content on scroll.
- The Blocksy "secondary header row" is hidden (`display: none`) — Gorvita uses a single-row header by design.
- Menu links: Inter 14.5px, letter-spacing `0.01em`, color `--gor-text`, hover `--gor-green`.

### Search box (`.ct-search-box`)

- Pill-shaped (`--gor-r-pill`), white background, 1px border tinted with `--gor-border`.
- Focus-within: border deepens to `--gor-green` + 3px glow ring (`var(--gor-focus)`).
- Submit button is icon-only, transparent, color `--gor-text-muted` → `--gor-green` on hover. No filled-in submit pill.

### Buttons (primary)

- The selector list is *intentionally long* — every WooCommerce / Blocksy / Greenshift button class is normalized into the same pill. Do not "simplify" by removing selectors; that's how Blocksy bleeds back through.
- Linear gradient `175deg` (slightly off-vertical), green → green-dark.
- Padding 12px 22px, weight 500, font-size 14px, letter-spacing 0.04em.
- Hover: lift 2px + deeper gradient + bigger shadow.
- Focus-visible: 3px sage outline at 4px offset.
- Outlined variant: transparent + 1.5px `--gor-border-hover`, fills to `--gor-green-light` on hover (no transform — outlined buttons stay still).

### Add-to-cart hierarchy *(v6.2)*

Three tiers of loudness, designed so the page never has more than one filled-green pill at a time:

1. **Hero CTA** ("Odkryj produkty") and **single-product `.single_add_to_cart_button`** — filled green pill with gradient + shadow. Loud.
2. **Loop add-to-cart** (Bestsellers/Nowości grid `.button.add_to_cart_button`) — outlined green-dark pill, fills green-dark on hover. Quiet.
3. **Wishlist / Compare / Quick-view** — cream-tinted, fills `--gor-green-light` on hover. Quietest.

States:

- **Success** (`.button.added`, `.added_to_cart`) — filled green pill with `✓ ` prefix on the success link. Reads as confirmation, not a second CTA.
- **Disabled** (`:disabled`, `[aria-disabled="true"]`, `.disabled`) — opacity 0.4, `cursor: not-allowed`, no transform / shadow.
- **Loading** (`.loading`) — replaces Woo's GIF spinner with an 8px sage-on-dark dot pulsing at 1.1s. No GIF, no spinner, just a quiet pulse.
- **Ghost on dark** (`.gorvita-spring .wp-block-button__link`, `.gorvita-btn--on-dark`) — transparent + 1.5px `rgba(255,255,255,0.35)`, fills white at 10% on hover. Use inside `.gorvita-spring` and other dark sections.

### Quantity input *(v6.2)*

- Pill-shaped (`--gor-r-pill`) to match button system.
- 1.5px `--gor-border`, white bg, 80px wide, centered text, weight 500.
- Focus: border deepens to `--gor-green` + 3px `--gor-focus` ring.

### Woo notifications *(v6.2)*

`.woocommerce-message`, `.woocommerce-info`, `.woocommerce-error` rendered as branded banners:

- Radius `--gor-r-md`, 3px left border, 14px 18px padding, `--gor-shadow-sm`.
- Default (success/info): `--gor-green-light` bg, `--gor-green-dark` text, green left border.
- Error: `rgba(220,38,38,0.06)` bg, dark red text, red left border.
- Embedded buttons (e.g. "View cart") render as outlined small pills, never another filled green.

### Product cards (`.woocommerce ul.products li.product`)

- Cream-card surface (`--gor-cream-card`), 1px tinted border, 18px radius, 18px padding, `--gor-shadow-sm`.
- Hover: lift 5px, shadow → `--gor-shadow-lg`, border → `--gor-border-hover`.
- Image container: aspect-ratio 1:1, 7% padding, **subtle vertical gradient** (`#ffffff → #f6f2ec`) — products feel like they're sitting on a soft cream pedestal, not on white.
- Image: `drop-shadow(0 6px 14px rgba(0,0,0,0.07))` so the bottle/box has its own ground shadow inside the card.
- Title: Inter 15px, weight 500, color `--gor-text`, centered, **`min-height: 3em`** so 1-line and 2-line titles align across the grid.
- Price: Inter 15px weight 600, color `--gor-green-dark`.
- Hover-image swap (`.gorvita-hover-img`) is wired — provide a second image per product to use it.

### Wishlist / Compare / Quick-view buttons

- Pill (`999px`), white background, 1.5px green border, green text, `--gor-shadow-sm` tinted.
- Hover: fill green, white text, scale `1.06`.
- The default Blocksy `::before` icon is hidden — Gorvita uses its own labels.

### Dark sections (`.gorvita-spring`, certain `gspb_row-id-*` overrides)

- Dark background (set per-section, typically deep green or near-black image overlay).
- All headings + body inverted to white / 88% white.
- *Italic emphasis* and links color: `#8DC87A` (the lighter sage, only legitimate in dark contexts).
- Newsletter input inside dark sections: white field, dark text — high contrast preserved.

### Product page tabs (`.gorvita-tabs-nav`, `.gorvita-tab-btn`)

- Glassmorphic shell: white at 70% opacity + `backdrop-filter: blur(12px)`.
- Tab triggers: weight 600, uppercase, letter-spacing 0.10em, font-size 11px (small caps energy).
- Active state: green color + bottom underline + 6% green-tinted background.
- Panel below: white at 50% opacity + 8px blur.
- **Note for impeccable critique**: glassmorphism is on the impeccable "rare and purposeful" watchlist. Current use is decorative; flag for review in a `$impeccable critique product-page` pass and either (a) commit harder to the editorial-glass concept across more surfaces or (b) drop the blur in favor of a flat cream-on-cream panel.

### Mobile accordion (product page, `<768px`)

- Replaces the desktop tabs with a stacked accordion.
- Trigger: 17px Inter weight 500, `+` indicator (rotates to `−` when open) in green.
- Border-bottom: 1px `--gor-border`. Hover/focus-visible deepens text to green + 2px sage outline.
- Content: 16px top padding, 8px bottom, same border-bottom.

### FAQ sub-accordion

- Tighter than the product-page accordion: 14px font, 14px vertical padding, smaller `+` indicator (18px).
- Used inside any tab/accordion panel that needs nested expand/collapse.

### Tables (inside tab panels)

- Full-width, 1px `--gor-border` cells, 10×12 padding.
- TH gets the cream surface (`--gor-cream`) + uppercase 11px + letter-spacing 0.08em.
- Body cells use 13.5px Inter — slightly tighter than running prose to keep tables compact.

### Sidebar mini-product list (`.ct-sidebar .wc-block-product`)

- Horizontal row layout: 48×48 image left + name + price stacked right.
- White background, 1px `--gor-border`, 12px radius, 8px padding.
- Hover: `--gor-shadow-sm` + border deepens. Add-to-cart is hidden in this density.

### Sale badges (`.onsale`)

- Pill shape, `--gor-green-dark` background, white text, 11px weight 500, 4×10 padding.
- No drop-shadow.

### Footer

- 0.5px top border (`--gor-border`).
- Heading weight 600.
- Links color `--gor-text-muted`, hover `--gor-green`.

### B2B hero (`.gorvita-b2b-hero`)

- 24px radius, full-width image background, soft white-to-transparent overlay 90deg.
- Mobile (≤768px): swaps to a vertical-format `wspolpraca_b2b_mobile.webp` background, increases overlay opacity to 55% for legibility, centers content.

## Editorial utilities *(v6.2)*

Class-based, opt-in. Use sparingly — the point is to have one or two soul-marks per surface, not to dress up every paragraph.

| Class | What it is | When to reach for it |
|---|---|---|
| `.gorvita-pull` | Cormorant italic pulled quote, 28ch max-width, 2px green left border, 24px left padding. | Inside long-form brand copy when one sentence should land harder than the rest. |
| `.gorvita-dropcap` | Cormorant first-letter, 4em, floated left, green-dark. | Brand-story or "O marce" page intro paragraph only. Never on short product copy. |
| `.gorvita-divider` | Centered hairline + middle text/glyph, 320px max-width. | Between major editorial sections that don't have their own background break. |
| `.gorvita-link-view-all` | Inter uppercase 13.5px, 1.5px underline, animated arrow gap on hover. | "Zobacz wszystkie" links at the foot of product collections / category strips. |
| `.gorvita-mono` / `.gorvita-data-label` | JetBrains Mono 12.5px, muted color. | Ingredient ratios, dosage, lot numbers, geo coordinates, certificate codes. |
| `.gorvita-chip--limited` | Rose-faint background + `--gor-rose` text, pill, 11px. | "Ostatnie sztuki" / "Limitowana edycja" badges. Use *once* per product card row max. |
| `.gorvita-btn--on-dark` | Ghost button: transparent + 1.5px white-35% border, fills white-10% on hover. | CTAs inside `.gorvita-spring` or other dark sections. |

## Anti-pattern checklist (run in `$impeccable critique`)

- [ ] No pure black or pure white tokens introduced (`#000`, `#fff`). Use `--gor-text` and `--gor-cream`.
- [ ] No side-stripe colored borders (left/right >1px) on cards or callouts. *(Exception: `.gorvita-pull` and `.woocommerce-message` use a 2–3px branded left border by design.)*
- [ ] No `background-clip: text` gradient text.
- [ ] No new glassmorphism beyond the existing product-page tabs (which is itself flagged for review).
- [ ] No "big-number small-label" hero-metric template imported from SaaS designs.
- [ ] No identical icon-tile-stack feature card grids.
- [ ] No em dashes in copy. Also no `--` substitution. Use commas, colons, semicolons, periods, parentheses.
- [x] All animations check `prefers-reduced-motion: reduce` (closed in v6.2 — global `@media (prefers-reduced-motion: reduce)` block in Customizer CSS zeroes animations + transitions, and explicitly stops the vstrip marquee, spring ripple, and hero Ken Burns image).
- [ ] Maximum one filled-green primary pill visible per viewport. If two are competing, downgrade the secondary to outlined or text-link.
- [ ] No more than one `.gorvita-chip--limited` chip in a single product-card row — the rose accent loses meaning if it repeats.

## Open design questions (to resolve in a future pass)

1. **Glassmorphism on product tabs**: keep, expand, or drop? Currently a one-off — feels editorial but isolated. Decide during `$impeccable critique product-page`.
2. **Header `#ffffff` background**: tint to `--gor-cream`, or keep pure white as a clean separator from the cream body? Current is pure white as a Blocksy-friction concession.
3. ~~**Reduced-motion fallback**~~ — *resolved in v6.2*. CSS-side covered globally; the JS animations in `assets/js/animations.js` (parallax, Ken Burns, scroll-reveal) still need a `matchMedia('(prefers-reduced-motion: reduce)')` guard for the JS-driven layer. Open as a separate small ticket.
4. **Token migration**: the live system is in WP Customizer "Additional CSS". Future PR should move v6.2 to a tracked `wp-content/themes/gorvita-child/assets/css/premium.css` so it's version-controlled and code-reviewable.
