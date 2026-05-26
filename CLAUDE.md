# CLAUDE.md — Gorvita Shop

## Projekt
Sklep WooCommerce dla PPUH Gorvita Sp. z o.o. — naturalne suplementy i kosmetyki ziołowe na bazie wody z Rabki. Migracja z offon.pl. Model sprzedaży: B2C + B2B (z ukrytymi cenami dla gości na produktach B2B).

## Infrastruktura
- GitHub: `github.com/szopson/gorvita-shop` (private, main)
- VPS: `76.13.156.173` (srv1594477, Ubuntu 24.04)
- Working dir na VPS: `/opt/gorvita-shop`
- Working dir lokalnie (dev): `/root/gorvita-shop`
- Domena staging: `gorvita.srv1594477.hstgr.cloud`
- Domena produkcja: `sklep.gorvita.pl` (planowana)

## Stack
- Docker + Traefik v3.0 (SSL Let's Encrypt)
- WordPress (PHP 8.2 Apache) + WooCommerce
- MariaDB 11 + Redis 7
- WP-CLI + Composer preinstalowane
- Motyw: Blocksy + child `gorvita-child`

## Komendy — lokalnie
```bash
# Edytuj pliki
vim wp-content/themes/gorvita-child/style.css

# Push → auto-deploy na staging
git add -A && git commit -m "feat: ..." && git push origin main
```

## Komendy — na VPS (SSH)
```bash
cd /opt/gorvita-shop

# Start/stop stack
docker compose up -d
docker compose down
docker compose logs -f traefik

# WP-CLI (w kontenerze)
docker compose exec wordpress wp plugin list --allow-root
docker compose exec wordpress wp wc product list --allow-root --user=1
docker compose exec wordpress wp cache flush --allow-root

# DB dump
docker compose exec mariadb mysqldump -uroot -p$MYSQL_ROOT_PASSWORD gorvita > backup.sql

# Refresh plików theme/plugins z repo
cd /opt/gorvita-shop && git pull origin main
docker compose exec wordpress wp cache flush --allow-root
```

## Deployment
- Push do `main` → GitHub Actions `deploy-staging.yml` → rsync do VPS `/opt/gorvita-shop/` → `docker compose exec wordpress wp cache flush`
- Merge do `production` → `deploy-production.yml` (gdy będzie produkcja gotowa)

## Credentials (NIE w repo!)
- `.env` na VPS w `/opt/gorvita-shop/.env`
- `.env.example` w repo jako template
- Licencje premium: `B2BKING_LICENSE`, `RANKMATH_LICENSE` w `.env`
- API: PayU (`WOO_PAYU_POS_ID`, `WOO_PAYU_MD5`), InPost (`WOO_INPOST_API_KEY`)

## Design System
- Plik: `docs/design-system.md`
- Kolor primary: `#2D5016` (deep forest green)
- Accent: `#6B8E5F` (sage), `#8B7355` (earth brown)
- Neutral: `#FFFFFF`, `#F5F3F0` (cream), `#1A1A1A` (ink)
- Typografia: Inter (body, UI) + Fraunces (headings) — Google Fonts
- Vibe: premium natural wellness — Apteka Meduz × Weleda × Lush

## Styl kodu
- PHP: WordPress Coding Standards, wszystkie fn z prefiksem `gorvita_`
- CSS: custom properties w `:root`, BEM-like naming `.gorvita-{component}__{element}--{modifier}`
- Komentarze: po angielsku (internal), UI strings po polsku
- Commits: Conventional Commits (`feat:`, `fix:`, `chore:`, `refactor:`, `docs:`)

## Zasady pracy

- NIE odpalaj parallel ani autonomicznych agentów
- Dozwolony 1 read-only search agent TYLKO gdy alternatywą jest wczytanie wielu plików do głównego kontekstu
- NIE eksploruj codebase jeśli nie masz konkretnego zadania
- Przed każdą zmianą podaj dokładny plik i linię
- Używaj WP-CLI do operacji na bazie danych
- Jedna zmiana = jeden krok, czekaj na potwierdzenie

## Struktura projektu
- Strona o-marce: page ID 119, blok wp:html
- Theme: /opt/gorvita-shop/wp-content/themes/gorvita-child/ (lokalnie: /root/gorvita-shop/wp-content/themes/gorvita-child/)
- Feature bar: [tu dopiszemy po znalezieniu]
- Newsletter bar: [tu dopiszemy po znalezieniu]

## B2B
- Plan: B2BKing ($179 Startup) — do zakupu przed launchem
- Grupy: hurtownie farmaceutyczne, apteki, dystrybutorzy, sklepy zielarskie
- Minimum zamówienia: 250 zł netto
- WP Desk Pole NIP (~€40/rok) — walidacja NIP + GUS autofill, wymagane do KSeF
- Szczegóły: `.claude/tasks/b2b.md`

## Linki
- Obecny sklep klienta: `sklep.gorvita.com.pl`
- Strona korporacyjna: `gorvita.pl` (design reference)

## Stan projektu (2026-05-06)
### ✅ Zrobione
- VPS + Docker + Traefik SSL — działa
- Design system (style.css + functions.php)
- Produkty zaimportowane (108 szt.)
- Zdjęcia załadowane do WP Media
- SMTP: FluentSMTP + Resend (test OK, From: contact@nexoperandi.cloud)
- Blocksy cleanup zmergowany; CSS source-of-truth → `docs/customizer-additional-css-v6.3.css` (paste do WP Customizer → Additional CSS)
- **B2BKing Pro v5.5.40** zainstalowany i skonfigurowany:
  - PL labels formularza rejestracji (gettext filter dla `blocksy-companion`)
  - Polonizacja menu konta (Dashboard → Kokpit, My Account → Moje konto, Wishlist → Lista życzeń, Edit Profile → Edytuj profil, Log Out → Wyloguj się)
  - Polish NIP mod-11 validation (`gorvita_validate_polish_nip`, fields 1759 NIP + 1072 VAT)
  - Dynamic toggle pól rejestracji per rola (`assets/js/b2b-registration-toggle.js`) — ukrywa pola B2B-only gdy rola ≠ 1062
- **Wishlist (Blocksy Pro)**:
  - Slug `woo-wish-list` → `lista-zyczen` (filter `blocksy:pro:woocommerce-extra:wish-list:slug`)
  - Wszystkie warianty URL → 301 do canonical `/moje-konto/lista-zyczen/`
  - Hotfix DB: `woocommerce_myaccount_page_id` 10 → 9 (page 10 nie istniała, generowała relatywne URL-e wishlist linkujące do bieżącej strony)
- **Hotfix DB: `woocommerce_cart_page_id` 980 → 7** (page 980 nie istniała, `wc_get_cart_url()` spadało do `home_url('/')`, więc mini-cart „Zobacz koszyk" prowadził na stronę główną zamiast `/koszyk/`)
- **Checkout/Cart split (v6.5)** — page 7 (Koszyk) zostaje jako `wp:woocommerce/cart` block; page 8 (Zamówienie) wrócił do `[woocommerce_checkout]` classic shortcode (Przelewy24/PayU compatibility w bloku jeszcze nie ready)
- **Customizer CSS v6.5** (post 260, źródło `docs/customizer-additional-css-v6.3.css`):
  - przywrócone classic-checkout rules (`.woocommerce-checkout {input[…],label,form.checkout,h3,#customer_details,#order_review}`) — bezpieczne, bo page 8 jest klasyczna ekskluzywnie (`body.woocommerce-checkout` nie współwystępuje z `.wc-block-checkout` markup)
  - sidebar card chrome zawężony tylko do zewnętrznego `.wc-block-cart__sidebar` (cart page); wewnętrzne `.wc-block-components-totals-wrapper` rows mają stripped chrome
  - dla cart-block: InPost icon inline-flex w `__label-group`, block-input `padding-top: 26px` + label `line-height: 1.1`
  - **Backupy block-content stron 7+8** w `.claude/backups/page-{7,8}-koszyk/zamowienie-2026-05-06.html` (rollback po reimporcie starter site / gdyby ktoś niechcący nadpisał DB content)

### 🔧 Do zrobienia — priorytety
1. **[POST-DEPLOY]** Wkleić aktualną zawartość `docs/customizer-additional-css-v6.3.css` do WP Admin → Wygląd → Dostosuj → Dodatkowy CSS — bez tego CSS fixy z Customizer nie zadziałają.

2. **[CLEANUP]** Stopka strony głównej: leftover demo-link „Wishlist" → `https://startersites.io/blocksy/furniture/...` z importu starter site. Do wyczyszczenia w Footer Builder / widget.

3. **[PO BLOCKSY]** PayU — konfiguracja (czeka credentials od Pawła)

4. **[PRZED LAUNCHEM]** InPost — plugin aktywny, brak API key

5. **[PRZED LAUNCHEM]** WP Desk Pole NIP — do zakupu (autofill GUS, wymagane do KSeF)

6. **[PRZED LAUNCHEM]** SMTP From → sklep@gorvita.pl + weryfikacja w Resend

7. **[PRZED LAUNCHEM]** GA4 + GTM + Facebook Pixel

8. **[LAUNCH]** DNS: sklep.gorvita.pl → 76.13.156.173

### 📋 Szczegółowe taski
- `.claude/tasks/blocksy-cleanup.md` — PRIORITY: move templates do disabled-overrides/
- `.claude/tasks/smtp.md` — SMTP status i fix
- `.claude/tasks/payments.md` — PayU, Przelewy24, InPost, FedEx
- `.claude/tasks/b2b.md` — B2BKing, grupy, rabaty
- `.claude/tasks/launch-checklist.md` — pełna checklista przed launchem

## Kontekst dla AI
- `.claude/theme-index.md` — mapa wszystkich plików i funkcji gorvita-child
- `.claude/decisions.md` — dlaczego wybraliśmy dane rozwiązania (nie sugeruj alternatyw)
- `.claude/project-map.md` — struktura katalogów na VPS i w repo
- `.claude/wpcli-cheatsheet.md` — gotowe komendy WP-CLI
