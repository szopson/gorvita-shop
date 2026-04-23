# UI Audit — Homepage & Product Card Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply the 9-item UI/UX audit to fix typography, spacing, chip consistency, newsletter radius, footer social icons, and section heading rhythm.

**Architecture:** All changes are CSS-only or minimal PHP/HTML additions. The homepage uses `.gorvita-homepage-root` scope (tokens: `--g-*`, classes: `.gorvita-*`) in `homepage.css`. The WooCommerce product loop uses `product-card.css` + `content-product.php`. The footer lives in `footer.php` + the `GORVITA FOOTER` section at the bottom of `style.css`.

**Tech Stack:** Vanilla CSS custom properties, PHP (WordPress child theme), no build step — edit files directly.

---

## Branch

`fix/ui-audit-homepage` — already checked out.

---

## File Map

| File | Responsibility | Tasks |
|------|---------------|-------|
| `assets/css/homepage.css` | Homepage-scoped tokens & components | 1, 3, 4, 6 |
| `assets/css/product-card.css` | WooCommerce loop card | 2 |
| `footer.php` | Footer HTML (brand col) | 5 |
| `style.css` (GORVITA FOOTER section ~L1565) | Footer social icon CSS | 5 |

---

## Task 1 — Product title typography (homepage `.gorvita-prod__title`)

**Problem:** `.gorvita-prod__title` uses `var(--f-display)` (Fraunces). Long Polish product names (3–4 words, 25+ chars) in a serif italic font cause scanning fatigue on the 4-column grid.

**Fix:** Switch to `var(--f-ui)` (Inter) with `font-weight: 600` and add a matching `product-brand` micro-label for the decorative serif accent.

**Files:**
- Modify: `wp-content/themes/gorvita-child/assets/css/homepage.css` — around line 567 (`.gorvita-prod__title` block)

- [ ] **Step 1: Locate the existing rule**

  Open `homepage.css`. Find the block starting at `.gorvita-prod__title {` (currently around line 567). It reads:
  ```css
  .gorvita-prod__title {
    font-family: var(--f-display);
    font-size: 18px;
    font-weight: 400;
    line-height: 1.2;
    color: var(--g-ink);
    letter-spacing: -0.01em;
    margin: 0;
  }
  .gorvita-prod__title a { color: inherit; }
  ```

- [ ] **Step 2: Replace with Inter-based rule**

  Replace the block with:
  ```css
  .gorvita-prod__title {
    font-family: var(--f-ui);
    font-size: 17px;
    font-weight: 600;
    line-height: 1.25;
    color: var(--g-ink);
    letter-spacing: -0.01em;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: calc(1.25em * 2);
  }
  .gorvita-prod__title a { color: inherit; text-decoration: none; }
  .gorvita-prod__title a:hover { color: var(--g-green); }
  ```

  Rationale: 2-line clamp + `min-height` keeps card heights equal across the grid.

- [ ] **Step 3: Commit**

  ```bash
  git add wp-content/themes/gorvita-child/assets/css/homepage.css
  git commit -m "fix: product title — Inter 600, 2-line clamp for homepage grid cards"
  ```

---

## Task 2 — WooCommerce loop card body padding

**Problem:** `.gv-card-body` uses `padding: var(--space-4)` (1 rem). Description text and price sit very close to the card edge. The card also lacks a consistent `min-height` guard on the title area.

**Files:**
- Modify: `wp-content/themes/gorvita-child/assets/css/product-card.css` — `.gv-card-body` block (~line 162)

- [ ] **Step 1: Locate `.gv-card-body`**

  In `product-card.css`, find:
  ```css
  .gv-card-body {
      padding: var(--space-4);
      display: flex;
      flex-direction: column;
      gap: var(--space-2);
      flex: 1 1 auto;
  }
  ```

- [ ] **Step 2: Increase padding and refine gap**

  Replace with:
  ```css
  .gv-card-body {
      padding: var(--space-5, 1.25rem) var(--space-5, 1.25rem) var(--space-4);
      display: flex;
      flex-direction: column;
      gap: var(--space-2);
      flex: 1 1 auto;
  }
  ```

  Note: `--space-5` isn't defined in the token set — use the fallback `1.25rem` as shown. This is intentional: avoids adding a new token just for this nudge.

- [ ] **Step 3: Also verify mobile override still works**

  In the same file, the `@media (max-width: 480px)` block already has `padding: var(--space-3)` — that's fine, no change needed there.

- [ ] **Step 4: Commit**

  ```bash
  git add wp-content/themes/gorvita-child/assets/css/product-card.css
  git commit -m "fix: WC loop card body padding — 1rem → 1.25rem for breathing room"
  ```

---

## Task 3 — Lexicon ingredient property chips

**Problem:** `.gorvita-ingr-card__props span` renders as plain uppercase text with a `→ ` pseudo-content prefix. The audit notes visual inconsistency compared to the homepage product tag badges (`.gorvita-badge`). Fix: wrap each prop in a pill/chip badge that matches the rest of the design system.

**No HTML change needed** — only CSS change on `.gorvita-ingr-card__props span`.

**Files:**
- Modify: `wp-content/themes/gorvita-child/assets/css/homepage.css` — `.gorvita-ingr-card__props` block (~line 917)

- [ ] **Step 1: Locate the existing props block**

  Find in `homepage.css`:
  ```css
  .gorvita-ingr-card__props {
    display: flex;
    gap: 10px 18px;
    flex-wrap: wrap;
    font-family: var(--f-mono);
    font-size: 10.5px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--g-green);
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px solid var(--g-gray-200);
  }
  .gorvita-ingr-card__props span::before { content: '→ '; opacity: .5; }
  ```

- [ ] **Step 2: Replace with pill-badge style**

  Replace both rules with:
  ```css
  .gorvita-ingr-card__props {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px solid var(--g-gray-200);
  }
  .gorvita-ingr-card__props span {
    display: inline-block;
    padding: 3px 9px;
    border-radius: var(--r-sm);
    background: rgba(45, 80, 22, 0.07);
    color: var(--g-green);
    font-family: var(--f-mono);
    font-size: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    line-height: 1.6;
  }
  .gorvita-ingr-card__props span::before { content: none; }
  ```

  Rationale: `rgba(45,80,22,0.07)` = `var(--g-green)` at 7% opacity — matches the sage-mist palette without a new token. `var(--r-sm)` = 6px — consistent with `.gorvita-badge`.

- [ ] **Step 3: Commit**

  ```bash
  git add wp-content/themes/gorvita-child/assets/css/homepage.css
  git commit -m "fix: lexicon ingredient props — styled pill badges, consistent with design system"
  ```

---

## Task 4 — Newsletter input border-radius alignment

**Problem:** `.gorvita-newsletter__form` uses `border-radius: var(--r-full)` (pill shape) which clashes with the `var(--r-lg)` (14px) used elsewhere. Fix: align to `var(--r-lg)`.

**Files:**
- Modify: `wp-content/themes/gorvita-child/assets/css/homepage.css` — `.gorvita-newsletter__form` block (~line 1079)

- [ ] **Step 1: Locate the newsletter form block**

  Find:
  ```css
  .gorvita-newsletter__form {
  ```
  It contains a `border-radius` property. Also find the submit button CTA inside it.

- [ ] **Step 2: Read the exact current values**

  Run:
  ```bash
  grep -n "newsletter__form\|newsletter.*border\|newsletter.*radius" wp-content/themes/gorvita-child/assets/css/homepage.css
  ```

- [ ] **Step 3: Change border-radius on the form wrapper**

  In the `.gorvita-newsletter__form { ... }` block, change the `border-radius` value from `var(--r-full)` to `var(--r-lg)`.

  Also update the inner input and button if they have independent border-radius pill values:
  - Any `border-radius: var(--r-full)` inside `.gorvita-newsletter__form input` → change to `calc(var(--r-lg) - 4px)`
  - Any `border-radius: var(--r-full)` inside `.gorvita-newsletter__form .gorvita-btn` → change to `calc(var(--r-lg) - 4px)`

- [ ] **Step 4: Commit**

  ```bash
  git add wp-content/themes/gorvita-child/assets/css/homepage.css
  git commit -m "fix: newsletter form — align border-radius to design system r-lg (14px)"
  ```

---

## Task 5 — Footer social media icons

**Problem:** The footer brand column has no social media links. For a wellness brand, Instagram and Facebook are critical for brand trust and discoverability.

**Files:**
- Modify: `wp-content/themes/gorvita-child/footer.php` — brand column, after `.gorvita-footer__tagline`
- Modify: `wp-content/themes/gorvita-child/style.css` — GORVITA FOOTER section (~line 1628), add `.gorvita-footer__socials` rules

- [ ] **Step 1: Add social HTML to footer.php**

  In `footer.php`, find:
  ```php
  <p class="gorvita-footer__tagline">Naturalne preparaty ziołowe od 1989&nbsp;roku. Receptury dopracowane przez trzy pokolenia.</p>
  ```

  Add the socials block immediately after:
  ```php
  <div class="gorvita-footer__socials" aria-label="Social media">
      <a href="https://www.facebook.com/gorvita" class="gorvita-footer__social" target="_blank" rel="noopener noreferrer" aria-label="Facebook Gorvita">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.884v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
      </a>
      <a href="https://www.instagram.com/gorvita_pl" class="gorvita-footer__social" target="_blank" rel="noopener noreferrer" aria-label="Instagram Gorvita">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
      </a>
  </div>
  ```

- [ ] **Step 2: Add social CSS to style.css**

  In `style.css`, find the GORVITA FOOTER section (around line 1625). After `.gorvita-footer__tagline { ... }`, add:
  ```css
  .gorvita-footer__socials {
      display: flex;
      gap: 10px;
      margin-top: 4px;
  }
  .gorvita-footer__social {
      width: 36px;
      height: 36px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      background: rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.55);
      transition: background .2s, color .2s;
      text-decoration: none;
  }
  .gorvita-footer__social:hover {
      background: rgba(255,255,255,0.15);
      color: #fff;
  }
  .gorvita-footer__social svg {
      width: 16px;
      height: 16px;
  }
  ```

- [ ] **Step 3: Commit**

  ```bash
  git add wp-content/themes/gorvita-child/footer.php wp-content/themes/gorvita-child/style.css
  git commit -m "feat: footer social icons — Instagram + Facebook in brand column"
  ```

---

## Task 6 — Section heading margin reduction

**Problem:** `.gorvita-section__head` uses `margin-bottom: 56px`. Combined with very large Fraunces headings, this creates very long scroll distances between sections.

**Fix:** Reduce to ~48px (≈ −15%).

**Files:**
- Modify: `wp-content/themes/gorvita-child/assets/css/homepage.css` — `.gorvita-section__head` block (~line 398)

- [ ] **Step 1: Locate the rule**

  Find:
  ```css
  .gorvita-section__head {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: end;
    gap: 40px;
    margin-bottom: 56px;
  }
  ```

- [ ] **Step 2: Reduce margin-bottom**

  Change `margin-bottom: 56px` to `margin-bottom: 48px`.

- [ ] **Step 3: Commit**

  ```bash
  git add wp-content/themes/gorvita-child/assets/css/homepage.css
  git commit -m "fix: section heading — reduce margin-bottom 56px → 48px for tighter scroll rhythm"
  ```

---

## Task 7 — Focus state polish (a11y)

**Problem:** `style.css` has a global `:focus-visible` rule, but it uses `rgba(107,142,95,0.20)` which can be too faint. WCAG 2.2 requires a 3:1 contrast ratio for focus indicators.

**Fix:** Increase the focus ring opacity and ensure it applies to all interactive elements.

**Files:**
- Modify: `wp-content/themes/gorvita-child/style.css` — `:focus-visible` rule (~line 110)

- [ ] **Step 1: Locate the existing rule**

  Find in `style.css`:
  ```css
  a:focus-visible {
      outline: 3px solid var(--gorvita-sage);
      outline-offset: 2px;
      border-radius: var(--radius-sm);
  }
  ```

- [ ] **Step 2: Extend to cover all interactive elements**

  Add after the `a:focus-visible` rule:
  ```css
  button:focus-visible,
  input:focus-visible,
  select:focus-visible,
  textarea:focus-visible,
  [tabindex]:focus-visible {
      outline: 3px solid var(--gorvita-sage);
      outline-offset: 2px;
      border-radius: var(--radius-sm);
  }
  ```

- [ ] **Step 3: Commit**

  ```bash
  git add wp-content/themes/gorvita-child/style.css
  git commit -m "fix: a11y — extend focus-visible ring to buttons, inputs, and tabbable elements"
  ```

---

## Task 8 — Final verification & push

- [ ] **Step 1: Check git log looks clean**

  ```bash
  git log --oneline -10
  ```

  Expected: 6–7 commits, all from this session.

- [ ] **Step 2: Push branch**

  ```bash
  git push -u origin fix/ui-audit-homepage
  ```

- [ ] **Step 3: Notify user**

  Report that the branch is pushed and the staging deploy should trigger automatically via GitHub Actions `deploy-staging.yml`.

---

## Self-Review Notes

| Audit item | Task | Status |
|---|---|---|
| Product card title typography (Fraunces → Inter) | 1 (homepage grid) + already correct on WC loop | ✓ covered |
| Product card CTA button visibility | Already implemented in previous sprint (`gv-card-cta`) | ✓ no change needed |
| Product card internal padding | 2 | ✓ covered |
| CBD button gold text contrast | Already `color: var(--g-ink)` on `.gorvita-btn--gold` line 141 | ✓ no change needed |
| Lexicon chip unification | 3 | ✓ covered |
| Category tile gradient overlay | Already has `::after` gradient at line 431 | ✓ no change needed |
| Category tile label positioning | All labels at `bottom: 16px; left: 16px` — already uniform | ✓ no change needed |
| Newsletter border-radius | 4 | ✓ covered |
| Footer social media icons | 5 | ✓ covered |
| Footer column layout | `grid-template-columns: 1.6fr 1fr 1fr 1.4fr` already in style.css | ✓ no change needed |
| Section heading spacing | 6 | ✓ covered |
| Focus states | 7 | ✓ covered |
