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

## Backup
- **Migawki całej VM u Hostingera** — automatyczne, przechowywane **poza serwerem**, ~4 kopie, czas przywracania ok. **54 min**. To jedyna kopia, która przeżyje utratę VPS-a. Niewidoczne z wnętrza maszyny (poziom hipernadzorcy) — sprawdzać w panelu Hostingera, nie przez `cron`/`systemctl` na VM.
  - **Zastrzeżenie 1:** przywracanie obejmuje **CAŁĄ maszynę** — produkcję i staging naraz. Nie da się przywrócić jednego środowiska.
  - **Zastrzeżenie 2:** nie da się odzyskać samej bazy ani pojedynczego pliku — tylko cały obraz.
  - **Zastrzeżenie 3:** najnowsza migawka bywa o kilkanaście godzin starsza niż ostatnia zmiana.
- **Do cofania pojedynczej operacji migawki się NIE nadają.** Przed każdą operacją na danych robić własny zrzut (`wp db export`) bezpośrednio przed nią — tak jak w całym audycie cen z 2026-07-22. Migawka to zabezpieczenie przed utratą maszyny, zrzut to zabezpieczenie przed własnym błędem.
- **Zrzut przed deployem** — `deploy-production.yml` robi bramkowany `mariadb-dump` do `backups/pre-deploy-*.sql.gz` i przerywa wdrożenie, gdy plik jest pusty. Wyzwalany **wdrożeniem, nie kalendarzem** — w tygodniu bez wdrożeń nie powstaje nic. Leży na tym samym dysku, więc nie zastępuje migawki.

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
- Hero (strona główna): shortcode `[gorvita-hero]` → `gorvita_hero_shortcode()` w functions.php (~l. 575); tło hero = hardcoded URL (l. 584), wizual w prawej kolumnie = `$gorce_url` assets/images/gorce2.webp (+ preload l. 22)
- Feature bar (benefity: płatności / darmowa dostawa / promocje / pomoc): DB — post 991 (Home, blok `wp:html`, 4× iconbox) ORAZ post 424 (`ct_content_block` "Shop Archive & Single - Above Footer Section", Greenshift, renderowany nad stopką na podstronach sklepu). Treść zduplikowana → zmieniać w obu (np. `wp search-replace … wp_posts --include-columns=post_content`)
- Newsletter bar: DB — post 991 (Greenshift `greenshift-blocks/text`: tekst występuje 2× — atrybut `textContent` w komentarzu bloku ORAZ widoczny `<div>`, muszą być identyczne, inaczej Gutenberg zgłasza błąd walidacji). Wariant na podstronach (callout "rabatu na pierwsze zakupy") w post 424

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
- Blocksy cleanup zmergowany; CSS source-of-truth → `docs/customizer-additional-css-v6.20.css` (mirror żywego post 260 / WP Customizer → Additional CSS)
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
- **Checkout/Cart — OBIE strony BLOKOWE (zweryfikowane na produkcji 2026-07-22):** page 7 (Koszyk) `wp:woocommerce/cart`, page 8 (Zamówienie) **`wp:woocommerce/checkout`**. Wcześniejszy zapis o powrocie page 8 do `[woocommerce_checkout]` classic był NIEAKTUALNY.
  - **Ścieżka HTTP do checkoutu to Store API, nie POST formularza.** `curl` na `/zamowienie/` nie zwróci `woocommerce-process-checkout-nonce` — blok renderuje się po stronie JS. Kolejność: `GET /wp-json/wc/store/v1/cart` (nagłówek odpowiedzi `Nonce`) → `POST .../cart/select-shipping-rate` → `POST .../checkout` z nagłówkiem `Nonce:`.
  - **NIP jest polem WYMAGANYM** — B2BKing custom field 1759, klucz w Store API `b2bking/b2bking_custom_field_1759`, wymagany w `billing_address` ORAZ `shipping_address`. Bez niego checkout zwraca 400 `rest_invalid_param`. Walidacja mod-11 (`gorvita_validate_polish_nip`).
  - **W grupie B2B 1073 NIE MA kont testowych** — wszystkie 7 to realni kontrahenci (hojewa, jurkowskipl, karis.biuro, magiarelaksu, sklep@herbisan, zielarniaksiazeca, sprzedaz). Do testów zakładać konto tymczasowe (`b2bking_customergroup=1073` + `b2bking_b2buser=yes` + `b2bking_account_approved=yes`) i usuwać po teście — NIE podpinać zamówień testowych pod historię realnego klienta.
- **Customizer CSS** (post 260, mirror w repo `docs/customizer-additional-css-v6.20.css` — live wyprzedził, plik odświeżony 2026-06-15):
  - ⚠ classic-checkout rules (`.woocommerce-checkout {input[…],label,form.checkout,h3,#customer_details,#order_review}`) — uzasadnienie „page 8 jest klasyczna ekskluzywnie" jest **NIEAKTUALNE**: page 8 to blok `wp:woocommerce/checkout`, więc te selektory są martwe (`form.checkout`, `#customer_details`, `#order_review` nie istnieją w markupie blokowym). Nieszkodliwe, ale przy porządkach w CSS to kandydat do usunięcia — najpierw sprawdzić live post 260.
  - sidebar card chrome zawężony tylko do zewnętrznego `.wc-block-cart__sidebar` (cart page); wewnętrzne `.wc-block-components-totals-wrapper` rows mają stripped chrome
  - dla cart-block: InPost icon inline-flex w `__label-group`, block-input `padding-top: 26px` + label `line-height: 1.1`
  - **Backupy block-content stron 7+8** w `.claude/backups/page-{7,8}-koszyk/zamowienie-2026-05-06.html` (rollback po reimporcie starter site / gdyby ktoś niechcący nadpisał DB content)

- **Rola „Księgowość – zamówienia" (2026-07-16):** rola `ksiegowosc_zamowienia` (DB, `wp_user_roles`) — dokładnie 7 capów: `read`, `view_admin_dashboard` (WYMAGANY — `WC_Admin::prevent_admin_access()` wpuszcza do wp-admin tylko `edit_posts`/`manage_woocommerce`/`view_admin_dashboard`), `edit_shop_orders`, `edit_others_shop_orders`, `edit_private_shop_orders`, `edit_published_shop_orders`, `read_private_shop_orders`. User: `joanna.szewczyk` (joanna.szewczyk@lim-tax.pl, księgowa lim-tax). Zmiana capów: `remove_role` → `add_role` od nowa (`add_role` nie nadpisuje). PUŁAPKA HPOS: placeholder posty zamówień (`shop_order_placehold`) mają `capability_type: post`, więc meta-capy `edit_shop_order` mapują się na `edit_others_posts` — naprawia to `gorvita_map_hpos_order_caps()` w functions.php child theme (bez tego filtra rola widzi listę, ale otwarcie zamówienia = wp_die)
- **Wariant B: rabat B2B w cenach produktów (2026-07-16):** reguła 1760 przełączona na `b2bking_rule_discount_show_everywhere=1` (rabat -18% wliczony w ceny produktów dla B2B, BEZ linii rabatu w koszyku/FV), duplikat 2395 → draft; po każdej zmianie meta reguł: `wp eval 'B2bking_Admin::b2bking_calculate_rule_numbers_database();'` + cache flush. Kod: `gorvita_b2b_plain_price_html` + `gorvita_b2b_hide_sale_flash` w functions.php child (cena po rabacie jako zwykła cena — bez przekreślenia/badge; gate: everywhere-rules aktywne + user b2bking_b2buser=yes). Reguła 13811 „Zniżka -10% nowy produkt" = promo B2C na product 11586, nie ruszać. Rollback: `.claude/backups/b2bking-wariantB-prod-2026-07-16/`.
- **Faktura PDF netto/VAT/brutto (2026-07-16):** custom szablon WCPDF `theme/gorvita` w `wp-content/themes/gorvita-child/woocommerce/pdf/gorvita/` (aktywacja: `wpo_wcpdf_settings_general.template_path = theme/gorvita`). Pozycje: cena/wartość netto + stawka i kwota VAT + brutto; sumy: netto pozycji → rabat B2BKing jako fee NETTO (tylko stare zamówienia sprzed wariantu B) → razem netto → VAT per stawka → brutto. KONTEKST: rabaty dynamiczne B2BKing typu `cart_total` liczą % od NETTO (`WC()->cart->get_subtotal()`), a WooCommerce ujemnym fee proporcjonalnie zdejmuje VAT — brutto rabatu wygląda jak "% od brutto", ale to tylko prezentacja. Duplikat reguł 18%: 1760 (wszyscy B2B, etykieta "Rabat B2B 18%") i 2395 (grupa 2394) — B2BKing bierze większą, NIE sumuje. Reguła 2367 "Zwolnienie z podatku" (`showtax=yes`) tylko wyświetla ceny netto dla B2B, NIE zwalnia z VAT — odznaczenie showtax zwolniłoby wszystkich B2B z VAT, nie ruszać.
- **CENY W BAZIE TO NETTO (2026-07-22, staging + produkcja):** `woocommerce_prices_include_tax = **no**`. `_regular_price` i `_sale_price` przechowują **netto**; B2C dalej widzi brutto, bo `tax_display_shop` i `tax_display_cart` zostają `incl`. Powód: B2BKing liczy rabat % wprost od `$regular_price` (`class-b2bking-dynamic-rules.php:2960`) i **nie ma ustawienia podstawy** — przy cenach brutto rabat −18% zaokrąglał się na brutto i 29/122 pozycji różniło się o 1 gr od `netto_cennik × 0,82`. Po zmianie: 0 rozjazdów. Przy każdej pracy z cenami pamiętać: **wartość w `_regular_price` NIE jest tym, co widzi klient detaliczny.** Plan + wykonanie + weryfikacja: `_audyt/PLAN_netto_PROD.md`, backup `_audyt/backup_przed_netto_PROD_20260722_0829.sql`.
- **mu-plugins: produkcja bind-mountuje, STAGING NIE (2026-07-22).** Na produkcji `/opt/gorvita-shop/wp-content/mu-plugins/` jest podmontowany do kontenera — plik wrzucony tam jest żywy. **Na stagingu tego mountu nie ma**: katalog w kontenerze należy do wolumenu, a plik wrzucony do `/opt/gorvita-staging/wp-content/mu-plugins/` jest **martwy**. Na staging wgrywać przez `docker compose cp … wordpress:/var/www/html/wp-content/mu-plugins/`. Weryfikacja: `docker compose exec -T wordpress ls /var/www/html/wp-content/mu-plugins/` — nie `ls` na `/opt`.
  - ⚠ **`wp-content/mu-plugins/` NIE jest wykluczony z rsynca w `deploy-production.yml`** (`--exclude='wp-content/plugins/'` go nie łapie) — przeżywa wyłącznie dlatego, że repo i produkcja mają dziś identyczną zawartość. **Każdy mu-plugin dodany na produkcji, a nieobecny w repo, zniknie przy najbliższym deployu.** Nienaprawione, świadomie odnotowane 2026-07-22.
  - **Blokadę poczty weryfikować POMIAREM LOGU, nigdy wartością zwracaną przez `wp_mail()`.** `wp_mail()` zwraca `true` także przy udanej wysyłce, więc `true` nie dowodzi niczego. Filtr blokujący ma logować każdą zatrzymaną wiadomość; dowodem jest **niepusty log**. Ta pułapka realnie zadziałała 2026-07-22: mu-plugin blokujący leżał na stagingu w `/opt` i był nieaktywny, a `wp_mail()` grzecznie zwracał `true` — bez kontroli logu trzy powiadomienia o zamówieniach testowych poszłyby na `sklep@gorvita.pl`.
- **DŁUG: `_sale_price` NIE idzie za ceną regularną (2026-07-22).** Ceny promocyjne są kwotami zamrożonymi w bazie — import cennika zmienia tylko `_regular_price`. **Przy każdym przyszłym imporcie cennika trzeba ręcznie przeliczyć wszystkie aktywne promocje**, inaczej rabat procentowy po cichu się rozjedzie (a w skrajnym przypadku `sale >= regular`). Na produkcji są obecnie **4**: `#95` AloeVera Żel, `#179` Mosqitos Zestaw, `#183` Olejek Pichtowy, `#11586` Maść z gojnikiem. Kontrola przed każdym importem: `SELECT post_id FROM wp_postmeta WHERE meta_key='_sale_price' AND meta_value<>''` — lista może się zmienić. Świadomy wybór: alternatywą była reguła B2BKing z procentem, ale ta **nie zapisuje `_price` ani nie odświeża lookup** (produkt figuruje w filtrach po cenie regularnej i ma `onsale=0`) — zmierzone na stagingu, patrz `_audyt/ETAP5_b2bking_porzadki.md`.
- **Zdjęcia produktów — ostrość siatek (2026-05-27):** `woocommerce_thumbnail_image_width` 300→500 + regeneracja 243 miniatur produktowych (Imagify lossless+backup, źródła 800px); `single_image_width` zostaje 800. Przyczyna była upscale miniatury 300px na siatkach (nie over-kompresja). Pełny reference + pułapki: `.claude/product-images.md`

### 🔧 Do zrobienia — priorytety
1. ~~**[POST-DEPLOY]** Wkleić Additional CSS~~ ✅ ZROBIONE — live Customizer (post 260) = **v6.20** z URL-ami `sklep.gorvita.pl`, aktywny. Repo `docs/customizer-additional-css-v6.20.css` to mirror (odświeżony 2026-06-15). Re-paste do Customizera potrzebny TYLKO gdy edytujesz plik by wepchnąć NOWE zmiany CSS. NIE wklejać starych wersji — cofnęłoby produkcję.

2. ~~**[CLEANUP]** Stopka demo-link „Wishlist" → startersites.io~~ ✅ ZROBIONE (2026-06-15) — usunięty orphan `wp_navigation` post 1041 (5 demo-linków mebli); zero żywych referencji `startersites.io` (poza nieszkodliwym komentarzem CSS w post 260). Backup: `.claude/backups/prod-nav-1041-categories-menu-2026-06-15.html`.

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
- `.claude/product-images.md` — rozmiary/jakość zdjęć produktowych, regeneracja miniatur, pułapki (cache flush PRZED regenerate)
