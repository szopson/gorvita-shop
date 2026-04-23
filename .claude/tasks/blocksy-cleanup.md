# Blocksy Setup — Disable Template Overrides

## Goal
Move child theme template override files to `disabled-overrides/` so WordPress falls through to Blocksy's own templates. This lets us evaluate and configure Blocksy layout from admin panel BEFORE adding only necessary CSS/code customizations.

## Architecture
**Pliki które przeniesiemy:**
- `front-page.php` → `disabled-overrides/front-page.php`
- `footer.php` → `disabled-overrides/footer.php`
- `woocommerce/` → `disabled-overrides/woocommerce/`

**Pliki które zostają aktywne:**
- `functions.php` (všechny hooki, setupy)
- `inc/` (wszystkie moduły: b2b, mobile-ux, search, wishlist, etc.)
- `assets/` (CSS, JS, images)
- `style.css` (custom CSS)

## Implementation Checklist

### Krok 1: Git history-safe move
```bash
cd /root/gorvita-shop/wp-content/themes/gorvita-child
mkdir -p disabled-overrides

git mv front-page.php disabled-overrides/
git mv footer.php disabled-overrides/
git mv woocommerce disabled-overrides/
```

### Krok 2: Verify
- [ ] WordPress teraz renderuje Blocksy templates zamiast naszych overrides
- [ ] Admin panel → Appearance → Customize: widać Blocksy ustawienia
- [ ] Staging: https://gorvita.srv1594477.hstgr.cloud — brak błędów, Blocksy layout działa
- [ ] Zdjęcia produktów pokazują się, strona główna renderuje

### Krok 3: Blocksy configuration (w adminie)
- [ ] Wybrać Blocksy template (np. "Storefront" albo "Clean Shop")
- [ ] Ustawić kolory z design system (#2D5016, #6B8E5F, #8B7355)
- [ ] Konfigurować nagłówek, menu, stopkę
- [ ] Dodać hero section, gridy produktów
- [ ] Ustawić strony: archive, single product

### Krok 4: Zbierz CSS/JS tweaks
- [ ] Zapisz list zmian które są potrzebne (padding, spacing, custom styles)
- [ ] Te zmiany → `assets/css/overrides.css`
- [ ] NIC nie idzie z powrotem do `functions.php` jeśli to tylko CSS

### Krok 5: Test pełny flow
- [ ] B2C: dodaj do koszyka, przejdź do checkout
- [ ] B2B: zaloguj się jako b2b_customer, sprawdź cenę
- [ ] Mobile: responsywność OK
- [ ] PageSpeed: LCP < 2.5s

### Krok 6: Commit i deploy
```bash
git add wp-content/themes/gorvita-child/
git commit -m "refactor: move template overrides to disabled-overrides/ for Blocksy-first approach"
git push origin main  # → auto-deploy na staging
```

## Why This Approach
1. **Najpierw admin** — pozwól Pawłowi konfigurować wygląd bez dewelopera
2. **Potem kod** — dodaj CSS/hooki tylko jeśli Blocksy nie starcza
3. **Czystszy kod** — gorvita-child pozostaje lekki
4. **Łatwiej utrzymać** — mniej miejsc gdzie mogą być konflikty

## Files Status After Move
```
wp-content/themes/gorvita-child/
├── functions.php           ✅ ACTIVE (hooki, setup)
├── style.css               ✅ ACTIVE (custom CSS)
├── inc/                    ✅ ACTIVE (wszystkie moduły)
├── assets/                 ✅ ACTIVE (CSS, JS, images)
├── disabled-overrides/     ❌ IGNORED BY WORDPRESS
│   ├── front-page.php
│   ├── footer.php
│   └── woocommerce/
```

## Next: Custom CSS/Components
Po Blocksy setup, jeśli potrzebujesz custom sekcji:
- Dodaj do `assets/css/overrides.css`
- Lub użyj Blocksy Content Blocks w adminie
- Lub dodaj custom shortcode w `inc/`
