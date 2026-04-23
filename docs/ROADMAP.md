# Gorvita Shop — Roadmap & Status

> Projekt: WooCommerce dla PPUH Gorvita Sp. z o.o.
> Staging: https://gorvita.srv1594477.hstgr.cloud
> Produkcja: https://sklep.gorvita.pl (planowana)

---

## Etap 1 — Motyw, UX, Infrastruktura ✅ UKOŃCZONY

*Zakres: Kompletna warstwa frontendowa, design system, mobile UX, wdrożenie automatyczne.*

### 1.1 Design System
- [x] CSS variables (kolory, typografia, spacing, cienie, border-radius) w `style.css :root`
- [x] Kolory brand: forest green `#2D5016`, sage `#6B8E5F`, cream `#F5F3F0`, ink `#1A1A1A`
- [x] Typografia: Inter (body/UI) + Fraunces (nagłówki) via Google Fonts
- [x] Motyw: Blocksy parent + `gorvita-child` (child theme)

### 1.2 Strona główna
- [x] **Hero** — pełnoekranowy, zdjęcie Gorców, parallax JS, tekst + dwa CTA
- [x] **Value Strip** — 4 trust badges (Dostawa 24h, Naturalne składniki, Tradycja od 1989, 30 dni na zwrot)
- [x] **Featured Categories** — CSS grid 4 kolumny, zdjęcia kategorii
- [x] **Bestsellers** — WooCommerce shortcode, własny grid
- [x] **Stream Section** — Ken Burns CSS animation, rzeka górska
- [x] **Brand Story** — flex layout, foto Gorców + dark column z historią firmy
- [x] **Newsletter** — sekcja zapisu do newslettera (formularz statyczny)
- [x] **Footer** — kompletny custom footer: logo, kolumny nawigacji, social, kontakt

### 1.3 Karty produktów (PLP)
- [x] Custom WooCommerce template `content-product.php`
- [x] Thumbnail z aspect-ratio 4:3, LQIP fade-in
- [x] Tytuł 2-liniowy clamp, równa wysokość kart
- [x] Cena zawsze na dole (flex column + margin-top: auto)
- [x] Przycisk "Do koszyka" zawsze widoczny (override Blocksy hover-only)
- [x] Usunięte overlay Blocksy (`.ct-woo-card-extra`, czarna belka)
- [x] Badge sprzedaży

### 1.4 Mobile UX
- [x] Sticky bottom nav (Home / Sklep / CBD / Koszyk / Konto)
- [x] Sticky "Dodaj do koszyka" bar na karcie produktu
- [x] Checkout progress indicator (4 kroki)
- [x] Cart table reflow na mobile (card layout per row)
- [x] Product tabs accordion na mobile
- [x] Hamburger drawer (Blocksy) — cream background, zielone akcenty, 44px touch targets
- [x] Breakpoint hero/CBD przeniesiony z 860px → 768px (ochrona layoutu laptop)
- [x] CBD callout — zdjęcie nad tekstem na mobile, brak pustej przestrzeni (aspect-ratio fix)

### 1.5 Komponenty UI
- [x] **Mega menu** — overlay dla "Sklep" w nav (8 kategorii + featured products)
- [x] **Search overlay** — live search (AJAX, debounce 300ms, Ctrl+K)
- [x] **Wishlist** — serce na kartach produktów (user meta + localStorage dla gości)
- [x] **Quick views** — shortcody `[gorvita_new_products]`, `[gorvita_bestsellers]` itd.

### 1.6 WooCommerce
- [x] Tekst "Do koszyka" (override polskiego tłumaczenia)
- [x] Wyłączony "Coming Soon" mode
- [x] Redirect `/shop/` → właściwa strona sklepu
- [x] B2B: custom role `b2b_customer`, ukryte ceny dla gości na produktach B2B
- [x] Tłumaczenia UI (checkout, koszyk, konto, pola formularza)

### 1.7 Performance
- [x] `filemtime()` versioning na CSS/JS — automatyczny cache-bust po każdym deployu
- [x] Defer non-critical scripts (WooCommerce add-to-cart)
- [x] Lazy loading obrazów (WP core default) + `fetchpriority="high"` na LCP
- [x] Preconnect Google Fonts
- [x] Preload hero image (`.webp`)
- [x] Wyłączone emoji scripts, wp-embed
- [x] Wyłączony heartbeat na froncie
- [x] Limit 5 rewizji postów
- [x] REST API zablokowane dla gości (poza WC/JWT/oEmbed)
- [x] Redis Object Cache (plugin gotowy, drop-in aktywny)

### 1.8 Infrastruktura & Deploy
- [x] Docker stack: Traefik v3 + WordPress PHP 8.2 + MariaDB 11 + Redis 7 + auto-backup
- [x] SSL Let's Encrypt via Traefik (staging domain)
- [x] GitHub Actions deploy: `git push main` → rsync → `docker compose restart` → `wp cache flush`
- [x] PHP OPcache (revalidate_freq=2) + container restart w pipeline

---

## Etap 2 — Import produktów i treści ✅ UKOŃCZONY

*99 produktów zaimportowanych, 9 kategorii, 141 obrazków w media library, wszystkie strony CMS.*

### 2.1 Import produktów ✅
- [x] Scraper `scripts/scraper.py` → `data/products.json` (99 produktów)
- [x] Zdjęcia od klienta (WeTransfer) + `match-images.py` → `data/image-mapping.json`
- [x] Import WP-CLI `scripts/import-products.py` → 99 produktów na VPS (publish)
- [x] Weryfikacja: produkty widoczne w WP admin z cenami i zdjęciami

### 2.2 Kategorie i nawigacja ✅
- [x] 9 kategorii produktowych (Stawy, Skóra, Odporność, Wątroba, Krążenie, Energia, Nos/Gardło, CBD, + główna)
- [x] Menu nawigacji skonfigurowane

### 2.3 Strony CMS ✅
- [x] Regulamin
- [x] Polityka prywatności
- [x] Kontakt
- [x] O marce Gorvita
- [x] Współpraca B2B (`/b2b-rejestracja`)
- [x] Leksykon składników
- [x] Strony WooCommerce (Koszyk, Zamówienie, Moje konto)
- [ ] **Treść stron** — wypełnić rzeczywistą treścią od klienta (Regulamin, O marce, Kontakt — aktualnie placeholdery)

### 2.4 Media ✅
- [x] 141 obrazków produktowych w media library
- [ ] OG image (1200×630) dla social sharing
- [ ] Favicon (ICO + PNG 192/512)
- [ ] Zdjęcia dla kategorii (okładki w WP admin)

---

## Etap 3 — Integracje płatności, wysyłki i SEO 🔲 TODO

*Cel: Sklep akceptuje zamówienia i jest zoptymalizowany pod SEO.*

### 3.1 Płatności — PayU
- [ ] Odebrać od klienta: `WOO_PAYU_POS_ID` + `WOO_PAYU_MD5`
- [ ] Dodać do `.env` na VPS
- [ ] Zainstalować/skonfigurować plugin PayU dla WooCommerce
- [ ] Test transakcji (tryb sandbox → produkcyjny)

### 3.2 Dostawa — InPost
- [ ] Odebrać od klienta: `WOO_INPOST_API_KEY`
- [ ] Dodać do `.env` na VPS
- [ ] Zainstalować plugin InPost dla WooCommerce (Paczkomaty + kurier)
- [ ] Skonfigurować stawki wysyłki (strefy PL, EU)
- [ ] Widget mapy paczkomatów na checkout

### 3.3 SEO — Rank Math
- [ ] Aktywować `RANKMATH_LICENSE` z `.env`
- [ ] Skonfigurować: title templates, meta description defaults
- [ ] Schema: `Product` (cena, dostępność, recenzje), `Organization`, `BreadcrumbList`
- [ ] Sitemap XML → submit do Google Search Console
- [ ] Robots.txt (blokuj `/wp-admin/`, `/wp-json/` dla botów)

### 3.4 Newsletter
- [ ] Wybrać provider: Mailchimp / Klaviyo / Brevo (rekomendacja: Brevo — darmowy plan 300/dzień)
- [ ] Podpiąć formularz homepage pod API providera
- [ ] Double opt-in (RODO)
- [ ] Email powitalny (automatyzacja)

### 3.5 Analityka
- [ ] Odebrać od klienta GTM ID (`GTM-XXXXXXX`)
- [ ] Dodać GTM snippet do `<head>` + `<body>` (`inc/` nowy plik lub functions.php)
- [ ] GA4 property setup + Enhanced Ecommerce (purchase, add_to_cart events)
- [ ] Google Search Console — weryfikacja domeny

### 3.6 RODO / Cookie Consent
- [ ] Zainstalować plugin cookie consent (rekomendacja: Cookiebot lub Complianz)
- [ ] Skonfigurować kategorie: niezbędne / analityka / marketing
- [ ] Polityka cookies powiązana ze stroną CMS

---

## Etap 4 — Testy i launch 🔲 TODO

*Cel: Sklep przechodzi pełny smoke test i jest gotowy na ruch produkcyjny.*

### 4.1 Testy funkcjonalne
- [ ] Pełny flow zakupu: produkt → koszyk → checkout → PayU → potwierdzenie
- [ ] B2B: rejestracja → pending → approval → widoczne ceny
- [ ] Wishlist: dodawanie/usuwanie (zalogowany + gość)
- [ ] Search overlay: wyniki, klawiatura, pusty stan
- [ ] Mega menu: hover desktop, tap mobile
- [ ] Mobile: sticky nav, sticky CTA, checkout progress indicator
- [ ] Formularze: kontakt, B2B rejestracja, newsletter

### 4.2 Testy cross-device
- [ ] Desktop Chrome/Firefox/Safari (1280px, 1440px)
- [ ] Tablet (768px–1024px)
- [ ] Mobile iPhone (390px), Android (360px)
- [ ] Dark mode (prefers-color-scheme — upewnij się że design trzyma)

### 4.3 Performance audit
- [ ] Google PageSpeed Insights: target ≥90 mobile, ≥95 desktop
- [ ] Core Web Vitals: LCP <2.5s, CLS <0.1, FID <100ms
- [ ] W3 Total Cache: włączyć minify CSS/JS (po weryfikacji że nie psuje)
- [ ] WebP dla wszystkich zdjęć produktowych (bulk convert lub plugin)

### 4.4 Bezpieczeństwo
- [ ] Zmienić domyślne prefixsy tabel DB (w `.env`)
- [ ] Upewnić się że `.env` NIE jest dostępny publicznie (Traefik reguła)
- [ ] Aktualizacja pluginów do najnowszych wersji przed launchem
- [ ] Backup test: sprawdzić czy `backup` service faktycznie zapisuje do `/backups/`

### 4.5 DNS i launch
- [ ] Potwierdzić od klienta datę switcha
- [ ] Przenieść DNS `sklep.gorvita.pl` → `76.13.156.173`
- [ ] Traefik: dodać `sklep.gorvita.pl` jako produkcyjną domenę w `docker-compose.yml`
- [ ] 301 redirects ze starego sklepu (stare URLe z `sklep.gorvita.com.pl`) — meta `_old_url` z importu
- [ ] Test SSL na domenie produkcyjnej
- [ ] Wyłączyć staging domain lub przekierować na produkcję

---

## Zależności zewnętrzne (czekamy na klienta)

| Co | Od kogo | Potrzebne do |
|----|---------|--------------|
| Zdjęcia produktów (WeTransfer) | Klient | Etap 2 — import |
| PayU POS ID + klucz MD5 | Klient | Etap 3 — płatności |
| InPost API key | Klient | Etap 3 — wysyłka |
| GTM ID | Klient | Etap 3 — analityka |
| Regulamin + Polityka prywatności | Klient / prawnik | Etap 2 — strony CMS |
| Treść "O nas" | Klient | Etap 2 — strony CMS |
| Data launchu | Klient | Etap 4 — DNS switch |

---

## Szybkie komendy

```bash
# Deploy (automatyczny po git push)
git add -A && git commit -m "feat: ..." && git push origin main

# WP-CLI na VPS
ssh deploy@76.13.156.173
cd /opt/gorvita-shop
docker compose exec wordpress wp cache flush --allow-root

# Import produktów (gdy gotowe zdjęcia)
docker compose exec wordpress bash /var/scripts/setup.sh

# Backup bazy ręczny
docker compose exec mariadb mysqldump -uroot -p$MYSQL_ROOT_PASSWORD gorvita > backup-$(date +%Y%m%d).sql
```
