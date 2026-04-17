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

## B2B
- Custom role `b2b_customer` (zamiast B2BKing na start)
- Guest → widzi produkty, ale cena ukryta → CTA "Zaloguj się aby zobaczyć cenę"
- Formularz rejestracji B2B: `/b2b-rejestracja` z polami NIP, REGON, adres, osoba kontaktowa
- Admin approval manual; po zatwierdzeniu role → `b2b_customer`
- Osobne grupy cenowe przez Woo Memberships lub meta `_b2b_price` (zobacz `inc/b2b.php`)

## Scraping źródła (offon.pl)
- `scripts/scraper.py` — Python BeautifulSoup, input: URL listy produktów, output: `data/products.json` + `data/images/`
- `scripts/import-products.sh` — WP-CLI: iteruje JSON, `wp wc product create`, ładuje obrazy

## Linki do sprawdzenia
- Obecny sklep klienta: `sklep.gorvita.com.pl`
- Strona korporacyjna: `gorvita.pl` (design reference)

## Do zrobienia — priorytety
1. [BLOCKER VPS] Traefik SSL — `systemctl restart docker && docker compose up -d`
2. Design system w child theme — ZROBIONE (style.css + functions.php)
3. Scraping 108 produktów z sklep.gorvita.com.pl
4. Import WP-CLI + zdjęcia (WeTransfer od klienta)
5. B2B rejestracja + hidden pricing
6. Rank Math SEO + schema
7. PayU + InPost (po otrzymaniu API keys)
8. DNS `sklep.gorvita.pl` → VPS + launch
