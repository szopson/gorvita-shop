#!/usr/bin/env bash
#
# v6.29 — poprawki treści od Pawła (screenshoty).
#
# UWAGA: ta treść żyje w BAZIE DANYCH (bloki Greenshift / wp:html), nie w plikach repo.
# Deploy (rsync) synchronizuje tylko pliki, więc git push NIE przeniesie tych zmian.
# Skrypt aplikuje je bezpośrednio na DB działającego stacku w /opt/gorvita-shop (WP-CLI).
#
# Zakres:
#   - page 119 (/o-marce/)            — 8 zamian
#   - post 991 (Home) + 424 (podstrony, ct_content_block "Above Footer Section") — paski
#
# Hero background na stronie głównej to OSOBNA zmiana w pliku motywu
# (wp-content/themes/gorvita-child/functions.php, shortcode gorvita_hero_shortcode) — idzie przez git.
#
# Backup przed zmianą: .claude/backups/v6.29-content-fixes-2026-05-26/page-{119,991,424}.html
# Skrypt jest re-runnable: po zastosowaniu stare stringi po prostu nie matchują (0 replacements).
#
set -euo pipefail
cd /opt/gorvita-shop

# search-replace na wp_posts.post_content (obejmuje też rewizje stron — nieszkodliwe)
sr() {
  docker compose exec -T wordpress wp search-replace "$1" "$2" wp_posts \
    --include-columns=post_content --precise --report-changed-only --allow-root
}

echo "== page 119 (/o-marce/) =="
sr 'z ponad 35-letnią tradycją' 'z ponad 30-letnią tradycją'
sr '<div class="stat-number">35+</div>' '<div class="stat-number">30+</div>'
sr 'w Szczawie — w dolinie Kamienicy Gorczańskiej, na granicy' 'w Szczawie, na granicy'
sr 'z tego, co dają nam Gorce i Rabka' 'z tego, co daje natura'
sr 'właściwości naszej wody mineralnej.' 'właściwości naszej wody leczniczej.'
sr '<div class="cert-name">GMP · ISO 22716:2007</div>' '<div class="cert-name">GMP</div>'
sr '<div class="cert-name">ISO 9001:2015</div>' '<div class="cert-name">ISO</div>'
sr 'ISO 9001 · HACCP — certyfikowana jakość' 'ISO · HACCP — certyfikowana jakość'

echo "== paski: post 991 (Home) + 424 (podstrony) =="
sr 'Zamówienia powyżej 149 zł dostarczamy bezpłatnie przez InPost lub DHL.' 'Zamówienia powyżej 150 zł dostarczamy bezpłatnie przez FedEx, InPost lub Poczta Polska.'
sr 'Napisz lub zadzwoń — odpowiemy szybko i po ludzku.' 'Napisz lub zadzwoń — Szybko odpowiemy.'
sr 'Subskrybuj i zgarnij nawet 10% RABATU!' 'Subskrybuj i zgarnij 5% RABATU!'
sr 'Zgarnij nawet  10% rabatu na pierwsze zakupy' 'Zgarnij 5% rabatu na pierwsze zakupy'

echo "== flush cache =="
docker compose exec -T wordpress wp cache flush --allow-root
docker compose exec -T wordpress wp eval 'function_exists("rocket_clean_domain") && rocket_clean_domain(); function_exists("rocket_clean_minify") && rocket_clean_minify();' --allow-root || true

echo "DONE"
