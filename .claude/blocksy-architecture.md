# Blocksy Architecture — Trójwarstwowy model

## Ogólny model
```
┌──────────────────────────────────────────────────────────┐
│  WARSTWA 3: Admin Panel (content)                        │
│  - produkty, kategorie, menu                             │
│  - tekst, SEO, promocje                                  │
│  - ustawienia WooCommerce                                │
│  - edytowalne bannery                                    │
└──────────────────────────────────────────────────────────┘
              ↓
┌──────────────────────────────────────────────────────────┐
│  WARSTWA 2: Repo (gorvita-child)                         │
│  - custom CSS (style.css, assets/)                       │
│  - custom JS (assets/js/)                                │
│  - lekkie hooki PHP (functions.php, inc/)                │
│  - chatbot + n8n integracja                              │
│  - własne komponenty                                     │
└──────────────────────────────────────────────────────────┘
              ↓
┌──────────────────────────────────────────────────────────┐
│  WARSTWA 1: Blocksy (layout engine)                      │
│  - template wybrany z Blocksy                            │
│  - layout sekcji (hero, gridy, bannery)                  │
│  - wygląd globalny (kolory, spacing, font)               │
│  - archive/single product                                │
│  - nagłówek, menu, stopka                                │
└──────────────────────────────────────────────────────────┘
```

## Warstwa 1 — Blocksy (Layout Engine)

Co się tutaj dzieje:
- Blocksy to tema parent — dostarcza szablony, komponenty buildera
- Wybieramy template z Blocksy (np. "Storefront" albo "Clean Shop")
- Konfigurujesz w adminie: Appearance → Customize
- Blocksy buduje HTML outputu

Co konfigurować:
```
Admin > Appearance > Customize
├── Colors
│   ├── Primary: #2D5016
│   ├── Accent: #6B8E5F
│   └── Backgrounds: #F5F3F0, #FFFFFF
├── Typography
│   ├── Body: Inter
│   └── Headings: Fraunces
├── Layouts
│   ├── Homepage hero
│   ├── Product grid
│   ├── Archive pages
│   └── Single product
├── Header & Footer
│   ├── Logo placement
│   ├── Menu positioning
│   └── Footer widgets
└── Spacing & Borders
    ├── Global padding
    └── Global margins
```

## Warstwa 2 — gorvita-child (Overrides & Enhancements)

Co się tutaj dzieje:
- Child theme na repo
- Dodajemy CSS/JS tylko jeśli Blocksy nie starcza
- Hooki PHP dla logiki biznesowej (B2B, wishlist, search)
- NIC szablonów (front-page.php, footer.php) — jeśli Blocksy render OK

Struktura:
```
wp-content/themes/gorvita-child/
├── functions.php
│   ├── gorvita_enqueue_styles()
│   ├── gorvita_theme_setup()
│   └── gorvita_icon()
├── style.css               ← custom CSS vars, overrides
├── inc/
│   ├── b2b.php             ← hidden pricing, rola b2b_customer
│   ├── mobile-ux.php       ← mobile nav, sticky ATC
│   ├── wishlist.php        ← lista życzeń
│   ├── search.php          ← live AJAX search
│   ├── quick-views.php     ← shortcodes: bestsellers, new, sale
│   ├── translations.php    ← polskie stringi
│   └── woocommerce.php     ← WooCommerce hooks
├── assets/
│   ├── css/
│   │   └── overrides.css   ← CSS tweaks (padding, spacing)
│   ├── js/
│   │   └── wishlist.js     ← wishlist frontend
│   └── images/
│       └── icons/
└── disabled-overrides/
    ├── front-page.php      ← disabled (Blocksy handles)
    ├── footer.php          ← disabled (Blocksy handles)
    └── woocommerce/        ← disabled (Blocksy handles)
```

Co tam dodajemy:
- CSS tweaks: `assets/css/overrides.css`
- JS logika: `assets/js/`
- PHP hooki: `inc/` moduły
- Custom shortcodes: `inc/quick-views.php`

Co NIE tam dodajemy:
- Szablony (front-page.php, footer.php) — Blocksy ma lepsze
- Całe layouty — to powinno być w Blocksy
- Huge CSS rewrites — jeśli potrzebujesz, to znaczy źle skonfi­gurowałeś Blocksy

## Warstwa 3 — Admin (Content)

Co się tutaj dzieje:
- Pawł edytuje zawartość bez dostępu do kodu
- Dashboard, produkty, ustawienia WooCommerce
- Edytowalne bannery (jeśli dodamy je jako CPT albo Blocksy blocks)
- SEO, promocje, kategorie

Co Pawł robi:
```
Admin Dashboard
├── Posts / Pages
│   ├── Dodaj strony
│   └── Edit content
├── Products
│   ├── Add/edit produkty
│   ├── Manage kategorii
│   ├── Ustawienia WooCommerce
│   └── Promocje
├── Appearance
│   ├── Customize (Blocksy)
│   ├── Menu
│   └── Widgets
├── Settings
│   ├── General
│   ├── Reading
│   └── Permalinks
└── SEO (Rank Math)
    ├── Optimize strony
    └── Manage sitemaps
```

Co się nie zmienia bez dewelopera:
- Layout (jest w Blocksy + repo CSS)
- Hooks/logika (jest w `inc/`)
- Chatbot (jest w n8n + custom widget)

## Reguły

### 1. Jedna warstwa = jedno źródło prawdy
```
❌ ŹLE:
- Layout jest w Blocksy
- Padding jest w CSS
- A margin jest w inc/hooks.php
→ chaos

✅ DOBRZE:
- Layout w Blocksy
- Wszystkie style w assets/css/overrides.css
- Hooki w inc/ tylko dla logiki biznesowej
```

### 2. CSS > PHP dla wizualnych zmian
```
❌ ŹLE:
add_filter('blocksy_output', function($html) {
  return str_replace('padding-left', 'padding-left: 20px', $html);
});

✅ DOBRZE:
.blocksy-container {
  padding-left: 20px;
}
```

### 3. Nie wracaj do szablonów
```
❌ ŹLE:
Dodaj front-page.php override bo „chcę inny hero"

✅ DOBRZE:
1. Blocksy Customizer: zmień hero section
2. Jeśli Blocksy nie ma opcji: dodaj CSS override
3. Jeśli CSS nie starcza: dodaj custom Blocksy Content Block
```

### 4. Trzymaj repo kontrolę
```
Pliki które versjonujesz:
✅ functions.php
✅ style.css
✅ inc/
✅ assets/css/js/

Pliki które NIE versjonujesz:
❌ Zmiany w adminie (appearance settings, produkty, treść)
❌ Zmiany w Blocksy Customizer
```

## Praktyczne przykłady

### Przykład 1: Zmiana koloru nagłówka
**Warstwa:** Blocksy
1. Admin > Appearance > Customize > Header
2. Zmień kolor na #2D5016
3. Save
✅ Done

### Przykład 2: Dodanie custom spacing do produktów
**Warstwa:** gorvita-child CSS
1. Edytuj `assets/css/overrides.css`:
```css
.blocksy-product-card {
  padding: 20px;
  margin-bottom: 24px;
}
```
2. Commit, push
3. Auto-deploy na staging
✅ Done

### Przykład 3: B2B hidden pricing
**Warstwa:** gorvita-child PHP
1. `inc/b2b.php` ma `gorvita_filter_price_html()`
2. Hook na `woocommerce_get_price_html`
3. Zwraca "Zaloguj się aby zobaczyć cenę" dla gości
✅ Done

### Przykład 4: Chatbot na stronie
**Warstwa:** gorvita-child JS + n8n webhook
1. Custom widget w `assets/js/chatbot.js`
2. Wysyła request do `https://n8n.example.com/webhook/gorvita-chat`
3. n8n zwraca odpowiedź, widget wyświetla
✅ Done

## Checklist — przejście na Blocksy-first

- [ ] Disable template overrides → `git mv front-page.php disabled-overrides/`
- [ ] Wybrać Blocksy template w adminie
- [ ] Skonfigurować Blocksy Customizer (kolory, typografia, layout)
- [ ] Przetestować strony bez gorvita-child overrides
- [ ] Zbierz listę CSS tweaksów
- [ ] Dodaj tweaki do `assets/css/overrides.css`
- [ ] Przetestuj full flow: B2C, B2B, checkout
- [ ] Commit, push, deploy
