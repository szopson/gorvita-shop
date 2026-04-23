# Blocksy Setup — Disable Template Overrides

## Status
✅ **DONE** — Branch `feat/blocksy-clean-render` pushed, **PR #9 open**

3 template override files moved to `disabled-overrides/` (git history preserved):
- `front-page.php` — 566-line custom homepage
- `footer.php` — custom footer  
- `woocommerce/content-product.php` — product card override

**⚠️ Important:** CSS is still active (`style.css`, `assets/css/`)! Our design system variables (--gorvita-green, etc.) are still loaded. CSS does NOT block Blocksy block rendering — it just styles elements.

**All 9 `inc/` modules untouched:**
- b2b.php, mobile-ux.php, search.php, wishlist.php, quick-views.php, translations.php, woocommerce.php, mega-menu.php, performance.php

## Goal
Move child theme template override files to `disabled-overrides/` so WordPress falls through to Blocksy's own templates. This lets us evaluate and configure Blocksy layout from admin panel BEFORE adding only necessary CSS/code customizations.

## Implementation Checklist

### ✅ Krok 1: Git history-safe move — DONE
```bash
✅ git mv front-page.php disabled-overrides/
✅ git mv footer.php disabled-overrides/
✅ git mv woocommerce disabled-overrides/
✅ Branch: feat/blocksy-clean-render
✅ PR #9 open on GitHub
```

### 👉 Krok 2: Merge PR i deploy na staging — NEXT
- [ ] Go to GitHub PR #9
- [ ] Review changes (git history preserved, 3 files moved)
- [ ] **Merge PR** → GitHub Actions auto-deploys to staging
- [ ] Wait 30-60s for rsync to /opt/gorvita-shop/

### 👉 Krok 3: Verify staging render — NEXT
Open: https://gorvita.srv1594477.hstgr.cloud
- [ ] Homepage renders (Blocksy template, NOT our front-page.php)
- [ ] /sklep/ (shop archive) renders
- [ ] Footer renders (Blocksy footer, NOT our footer.php)
- [ ] Product cards show (Blocksy product card, NOT our content-product.php)
- [ ] No 404 errors
- [ ] Console errors? (F12 → Console, check for PHP warnings)
- [ ] Design looks reasonable (our CSS variables still applied)

### 👉 Krok 4: Blocksy configuration (w adminie) — NEXT
Admin > Appearance > Customize
- [ ] Wybrać Blocksy template (np. "Storefront" albo "Clean Shop") jeśli dostępne
- [ ] Ustawić kolory z design system:
  - Primary: #2D5016 (deep forest green)
  - Accent: #6B8E5F (sage)
  - Background: #F5F3F0 (cream)
- [ ] Konfigurować nagłówek, menu, stopkę
- [ ] Dodać hero section, gridy produktów
- [ ] Ustawić strony: archive, single product

### 👉 Krok 5: CSS/JS tweaks — AFTER BLOCKSY CONFIGURED
- [ ] Zapisz listę zmian które są potrzebne (padding, spacing, custom styles)
- [ ] Te zmiany → `assets/css/overrides.css` (NIE do functions.php)
- [ ] Commit, push → auto-deploy

### 👉 Krok 6: Test pełny flow — FINAL
- [ ] B2C: dodaj do koszyka, przejdź do checkout
- [ ] B2B: zaloguj się jako b2b_customer, sprawdź cenę
- [ ] Mobile: responsywność OK na iPhone/Android
- [ ] PageSpeed: LCP < 2.5s

## Why This Approach
1. **Najpierw admin** — pozwól Pawłowi konfigurować wygląd bez dewelopera
2. **Potem kod** — dodaj CSS/hooki tylko jeśli Blocksy nie starcza
3. **Czystszy kod** — gorvita-child pozostaje lekki
4. **Łatwiej utrzymać** — mniej miejsc gdzie mogą być konflikty

## Branch Status
```
main                      — stary stan (template overrides aktywne)
  └─ feat/blocksy-clean-render (PR #9)
     └─ [READY TO MERGE] 3 templates disabled, inc/ + CSS untouched
```

## Files Status After Move
```
wp-content/themes/gorvita-child/
├── functions.php           ✅ ACTIVE (hooki, setup)
├── style.css               ✅ ACTIVE (custom CSS vars, design system)
├── inc/                    ✅ ACTIVE (wszystkie 9 moduły)
├── assets/                 ✅ ACTIVE (CSS, JS, images)
└── disabled-overrides/     ❌ IGNORED BY WORDPRESS
    ├── front-page.php      (566 lines)
    ├── footer.php
    └── woocommerce/
```

## Rollback Plan
Jeśli coś pójdzie źle po merge PR #9:

**Option 1: Nie merguj PR #9**
- main pozostaje niezmieniony
- Blocksy templates nadal są wyłączone

**Option 2: Revert merge (jeśli już zmergowałeś)**
```bash
git revert -m 1 2e1ec35  # commit hash z merge PR #9
git push origin main
```

**Option 3: Przenieś pliki z powrotem (git mv preserve history)**
```bash
cd wp-content/themes/gorvita-child
git mv disabled-overrides/front-page.php .
git mv disabled-overrides/footer.php .
git mv disabled-overrides/woocommerce .
git commit -m "refactor: re-enable template overrides"
git push origin main
```

## Next: Custom CSS/Components
Po Blocksy setup, jeśli potrzebujesz custom sekcji:
- Dodaj do `assets/css/overrides.css` (PREFERRED)
- Lub użyj Blocksy Content Blocks w adminie
- Lub dodaj custom shortcode w `inc/quick-views.php`
- ❌ Nie wracaj do template overrides (front-page.php, footer.php, woocommerce/)
