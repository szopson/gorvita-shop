#!/bin/bash
# Create landing pages and quick-view pages via WP-CLI.
# Idempotent — checks existing by slug and updates instead of creating duplicates.
#
# Run inside wordpress container:
#   docker compose exec -T wordpress bash /var/scripts/create-pages.sh
set -euo pipefail

WP="wp --allow-root --path=/var/www/html"

create_or_update_page() {
    local slug="$1" title="$2" content_file="$3"

    local existing
    existing=$($WP post list --post_type=page --name="$slug" --format=ids 2>/dev/null || echo "")
    existing=$(echo "$existing" | tr -d '\r')

    if [ -n "$existing" ]; then
        $WP post update "$existing" \
            --post_title="$title" \
            --post_name="$slug" \
            --post_status=publish \
            --post_content="$(cat "$content_file")" >/dev/null
        echo "  ∙ [ID $existing] $title (updated)"
    else
        local new_id
        new_id=$($WP post create \
            --post_type=page \
            --post_status=publish \
            --post_title="$title" \
            --post_name="$slug" \
            --post_content="$(cat "$content_file")" \
            --porcelain 2>/dev/null)
        echo "  ✓ [ID $new_id] $title (created)"
    fi
}

# Generate page content files in /tmp
TMP=$(mktemp -d)

# ----------------------------- NOWOŚCI -------------------------------------
cat > "$TMP/nowosci.html" <<'EOF'
<!-- wp:paragraph -->
<p class="has-large-font-size">Najnowsze produkty dodane do oferty Gorvita — suplementy, maści, żele i kosmetyki naturalne na bazie wody z Rabki.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[gorvita_new_products limit="24" columns="4" days="90"]
<!-- /wp:shortcode -->
EOF

# ----------------------------- BESTSELLERY ---------------------------------
cat > "$TMP/bestsellery.html" <<'EOF'
<!-- wp:paragraph -->
<p class="has-large-font-size">Najczęściej kupowane produkty Gorvita — wybrane przez naszych klientów.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[gorvita_bestsellers limit="24" columns="4"]
<!-- /wp:shortcode -->
EOF

# ----------------------------- PROMOCJE ------------------------------------
cat > "$TMP/promocje.html" <<'EOF'
<!-- wp:paragraph -->
<p class="has-large-font-size">Produkty w promocji. Ceny obniżone na wybrany asortyment — sprawdź co aktualnie jest w ofercie specjalnej.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[gorvita_sale_products limit="24" columns="4"]
<!-- /wp:shortcode -->
EOF

# ----------------------------- POLECANE ------------------------------------
cat > "$TMP/polecane.html" <<'EOF'
<!-- wp:paragraph -->
<p class="has-large-font-size">Produkty polecane przez zespół Gorvita — starannie wyselekcjonowane propozycje z różnych kategorii.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[gorvita_featured_products limit="24" columns="4"]
<!-- /wp:shortcode -->
EOF

# ----------------------------- CBD LANDING ---------------------------------
cat > "$TMP/cbd.html" <<'EOF'
<!-- wp:html -->
<section class="gorvita-landing-hero">
    <h1>CBD i konopie — naturalne wsparcie</h1>
    <p class="lead">Oleje CBD 5% i 10%, maści konopne, kapsułki — wyprodukowane w Polsce z wykorzystaniem tradycyjnych receptur ziołowych Gorvita.</p>
</section>
<!-- /wp:html -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="has-text-align-center">Czym jest CBD?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Kannabidiol (CBD) to jeden z ponad 100 naturalnych związków znalezionych w konopiach. W przeciwieństwie do THC nie wykazuje działania psychoaktywnego. Od lat badany pod kątem wsparcia w zmaganiach ze stresem, bólem, problemami skórnymi i bezsennością.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<section class="gorvita-landing-section">
  <h2>Nasze produkty CBD</h2>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[products category="cbd-konopie" limit="12" columns="4" orderby="title" order="ASC"]
<!-- /wp:shortcode -->

<!-- wp:html -->
<section class="gorvita-landing-section">
  <h2>Jak wybrać stężenie?</h2>
  <div class="gorvita-benefits">
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">🌱</div>
      <h3>5% CBD</h3>
      <p>Dla początkujących i na codzienne wsparcie — łagodne dawkowanie, dobre do regularnego stosowania.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">🌿</div>
      <h3>10% CBD</h3>
      <p>Większa moc — dla osób szukających silniejszego efektu lub przy przewlekłych dolegliwościach.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">🫙</div>
      <h3>Maści i balsamy</h3>
      <p>Aplikacja miejscowa — na obolałe stawy, mięśnie, problematyczną skórę. Synergiczne działanie z ziołami.</p>
    </div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="gorvita-cta-block">
  <h2>Masz pytania?</h2>
  <p>Nasz zespół chętnie pomoże wybrać odpowiedni produkt pod Twoje potrzeby.</p>
  <a href="/kontakt/" class="button">Skontaktuj się z nami</a>
</section>
<!-- /wp:html -->
EOF

# ----------------------------- O MARCE -------------------------------------
cat > "$TMP/o-marce.html" <<'EOF'
<!-- wp:html -->
<section class="gorvita-landing-hero">
    <h1>O marce Gorvita</h1>
    <p class="lead">Polska marka naturalnych suplementów i kosmetyków ziołowych na bazie unikalnej wody termalnej z Rabki-Zdroju.</p>
</section>
<!-- /wp:html -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="has-text-align-center">Natura z tradycją</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Od ponad 25 lat tworzymy produkty oparte na składnikach pochodzenia naturalnego i wielopokoleniowej wiedzy ziołolecznictwa. Nasze maści, żele, balsamy i suplementy powstają w Rabce-Zdroju — uzdrowisku znanym z leczniczej wody mineralnej.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<section class="gorvita-landing-section">
  <h2>Co nas wyróżnia</h2>
  <div class="gorvita-benefits">
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">💧</div>
      <h3>Woda z Rabki-Zdroju</h3>
      <p>Nasze produkty powstają z wykorzystaniem leczniczej wody mineralnej z Rabki — bogatej w jod, brom i mikroelementy.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">🌿</div>
      <h3>Naturalne składniki</h3>
      <p>Żywokost, kasztanowiec, arnika, rumianek, rokitnik, kurkuma — zioła o udokumentowanym działaniu.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">🇵🇱</div>
      <h3>Polska produkcja</h3>
      <p>Produkujemy w Polsce, pod rygorystycznymi normami jakości. Wspieramy lokalnych dostawców i rolników.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">🧪</div>
      <h3>Badania i certyfikaty</h3>
      <p>Każda partia jest badana pod kątem jakości, bezpieczeństwa i zawartości aktywnych składników.</p>
    </div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="gorvita-landing-section">
  <h2>Nasza misja</h2>
</section>
<!-- /wp:html -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Wierzymy, że natura ma odpowiedzi na wiele codziennych dolegliwości. Naszą misją jest łączenie tradycyjnej wiedzy ziołoleczniczej z nowoczesnymi standardami produkcji, by dostarczać Polakom produkty, którym mogą zaufać.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<section class="gorvita-cta-block">
  <h2>Poznaj naszą ofertę</h2>
  <p>Ponad 90 produktów w 8 kategoriach — od suplementów i ziół mielonych po maści i żele regenerujące.</p>
  <a href="/shop/" class="button">Przeglądaj sklep</a>
</section>
<!-- /wp:html -->
EOF

# ----------------------------- B2B ------------------------------------------
cat > "$TMP/b2b.html" <<'EOF'
<!-- wp:html -->
<section class="gorvita-landing-hero">
    <h1>Współpraca B2B</h1>
    <p class="lead">Zostań partnerem Gorvita. Hurtowe ceny, dedykowany opiekun handlowy, elastyczne warunki współpracy dla aptek, sklepów zielarskich i gabinetów.</p>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="gorvita-landing-section">
  <h2>Co zyskujesz jako partner</h2>
  <div class="gorvita-benefits">
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">💰</div>
      <h3>Ceny hurtowe</h3>
      <p>Dedykowane cenniki dla zatwierdzonych klientów B2B. Rabaty rosną wraz z wolumenem zamówień.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">👤</div>
      <h3>Opiekun handlowy</h3>
      <p>Bezpośredni kontakt, szybka odpowiedź na zapytania, wsparcie przy doborze asortymentu.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">📦</div>
      <h3>Elastyczna wysyłka</h3>
      <p>Współpraca z InPost, FedEx, Pocztą Polską. Wysyłka na Twoje warunki — ekspres, paleta, cykliczne dostawy.</p>
    </div>
    <div class="gorvita-benefit">
      <div class="gorvita-benefit__icon">📄</div>
      <h3>Faktura VAT</h3>
      <p>Pełna dokumentacja, terminy płatności do uzgodnienia. Obsługujemy również KSeF.</p>
    </div>
  </div>
</section>
<!-- /wp:html -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="has-text-align-center">Zarejestruj konto B2B</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Wypełnij formularz poniżej. Weryfikacja zajmuje 1-2 dni robocze — po akceptacji otrzymasz dostęp do cen hurtowych.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[gorvita_b2b_registration]
<!-- /wp:shortcode -->

<!-- wp:html -->
<section class="gorvita-cta-block">
  <h2>Masz pytania?</h2>
  <p>Skontaktuj się z naszym działem handlowym — pomożemy przy wyborze asortymentu i negocjacji warunków.</p>
  <a href="mailto:handel@gorvita.pl" class="button">handel@gorvita.pl</a>
</section>
<!-- /wp:html -->
EOF

# ----------------------------- LEKSYKON ------------------------------------
cat > "$TMP/leksykon.html" <<'EOF'
<!-- wp:html -->
<section class="gorvita-landing-hero">
    <h1>Leksykon składników</h1>
    <p class="lead">Poznaj naturalne składniki używane w produktach Gorvita — ich działanie, zastosowanie i tradycje wykorzystania w ziołolecznictwie.</p>
</section>
<!-- /wp:html -->

<!-- wp:heading -->
<h2>🌱 CBD (kannabidiol)</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Niepsychoaktywny związek pozyskiwany z konopi siewnych. Wspiera organizm w walce ze stresem, bólem i problemami skórnymi. Występuje w naszych olejach 5% i 10%, maściach i kapsułkach.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🦴 Kolagen</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Główne białko tkanki łącznej — odpowiada za elastyczność skóry, mocne kości, stawy i ścięgna. Z wiekiem jego produkcja spada, dlatego suplementacja może wspierać regenerację. Znajdziesz go w Colafit, Colacal i Colahial.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🌿 Żywokost lekarski</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Tradycyjna roślina lecznicza używana od wieków w maściach na stłuczenia, złamania i bóle mięśniowe. Zawiera alantoinę wspierającą regenerację tkanek.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🌰 Kasztanowiec zwyczajny</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Ekstrakt z nasion kasztanowca zawiera escynę — związek wspierający układ żylny. Stosowany w żelach i maściach na problemy z krążeniem i pękającymi naczynkami.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🌼 Arnika górska</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Roślina o działaniu przeciwzapalnym i łagodzącym. Klasyk maści na stłuczenia, urazy i bóle mięśniowe po wysiłku fizycznym.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🍓 Acerola</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Owoc z Ameryki Południowej — najbogatsze naturalne źródło witaminy C. Wspiera odporność, zmniejsza uczucie zmęczenia, chroni komórki przed stresem oksydacyjnym.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🌾 Ostropest plamisty</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Roślina o udokumentowanym działaniu wspierającym wątrobę. Zawiera sylimarynę — kompleks flawonoidów chroniący komórki wątroby przed toksynami.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🧡 Rokitnik zwyczajny</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Krzew, którego owoce są bogate w witaminy (C, E, F), karotenoidy i omega. Wspiera odporność i regenerację skóry — stąd w naszej maści rokitnikowej.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>🐝 Propolis</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Naturalne antybiotykowe działanie, wspiera gojenie ran i walkę z infekcjami. Znajdziesz go w maści propolisowej z witaminami A, E, F.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>💛 Kurkuma</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Korzeń o silnych właściwościach przeciwzapalnych dzięki kurkuminie. W naszej ofercie — w maści z olejem CBD na problematyczną skórę.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<section class="gorvita-cta-block">
  <h2>Zobacz produkty z tymi składnikami</h2>
  <p>Każdy produkt w naszym sklepie ma szczegółowy opis składu i działania.</p>
  <a href="/shop/" class="button">Przeglądaj ofertę</a>
</section>
<!-- /wp:html -->
EOF

# ----------------------------- CREATE --------------------------------------
echo "=== Tworzenie stron ==="

create_or_update_page "nowosci"     "Nowości"     "$TMP/nowosci.html"
create_or_update_page "bestsellery" "Bestsellery" "$TMP/bestsellery.html"
create_or_update_page "promocje"    "Promocje"    "$TMP/promocje.html"
create_or_update_page "polecane"    "Polecane"    "$TMP/polecane.html"
create_or_update_page "cbd"         "CBD / Konopie" "$TMP/cbd.html"
create_or_update_page "o-marce"     "O marce Gorvita" "$TMP/o-marce.html"
create_or_update_page "b2b"         "Współpraca B2B" "$TMP/b2b.html"
create_or_update_page "leksykon-skladnikow" "Leksykon składników" "$TMP/leksykon.html"

rm -rf "$TMP"

echo ""
echo "=== DONE ==="
$WP post list --post_type=page --fields=ID,post_title,post_name,post_status 2>&1 | head -15
