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
- Plan: B2BKing ($179 Startup) — do zakupu przed launchem
- Grupy: hurtownie farmaceutyczne, apteki, dystrybutorzy, sklepy zielarskie
- Minimum zamówienia: 250 zł netto
- WP Desk Pole NIP (~€40/rok) — walidacja NIP + GUS autofill, wymagane do KSeF
- Szczegóły: `.claude/tasks/b2b.md`

## Linki
- Obecny sklep klienta: `sklep.gorvita.com.pl`
- Strona korporacyjna: `gorvita.pl` (design reference)

## Stan projektu (2026-04-23)
### ✅ Zrobione
- VPS + Docker + Traefik SSL — działa
- Design system (style.css + functions.php)
- Produkty zaimportowane (108 szt.)
- Zdjęcia załadowane do WP Media
- SMTP: FluentSMTP + Resend (test OK, From: contact@nexoperandi.cloud)
- **[NEW]** Blocksy cleanup: 3 template overrides (front-page.php, footer.php, woocommerce/) → disabled-overrides/
  - Branch: `feat/blocksy-clean-render`
  - PR #9 open, ready to merge
  - All inc/ modules + CSS untouched
  - Git history preserved

### 🔧 Do zrobienia — priorytety
1. **[TERAZ]** Blocksy cleanup:
   - ✅ Files moved, PR #9 open
   - 👉 **Merge PR #9** → GitHub Actions auto-deploys to staging
   - 👉 Verify on staging: https://gorvita.srv1594477.hstgr.cloud
   - 👉 Configure Blocksy in admin panel (Customizer + Content Blocks)
   - 👉 Zbierz CSS tweaks, commit to `assets/css/overrides.css`
   - See: `.claude/tasks/blocksy-cleanup.md`

2. **[PO BLOCKSY]** PayU — konfiguracja (czeka credentials od Pawła)

3. **[PRZED LAUNCHEM]** InPost — plugin aktywny, brak API key

4. **[PRZED LAUNCHEM]** B2BKing — do zakupu i konfiguracji

5. **[PRZED LAUNCHEM]** WP Desk Pole NIP — do zakupu

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
