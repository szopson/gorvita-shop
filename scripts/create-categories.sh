#!/bin/bash
# Create 8 top-level WooCommerce product categories per information-architecture.md.
# Idempotent — safe to re-run.
#
# Run inside wordpress container:
#   docker compose exec -T wordpress bash /var/scripts/create-categories.sh
set -euo pipefail

WP="wp --allow-root --path=/var/www/html"

echo "=== Tworzenie kategorii WooCommerce ==="

# [name]|[slug]|[emoji/icon marker in description]|[description]
CATS=(
    "Stawy i mięśnie|stawy-miesnie|🦴|Produkty wspierające stawy, ścięgna i regenerację mięśni — maści żywokostowe, kasztanowe, kolagen, Artrevit, ArtroŻel."
    "Skóra i ciało|skora-cialo|🌿|Codzienna pielęgnacja skóry — balsamy, żele, maści naturalne. Aloe Vera, Propolis, Arnika, naturalne kosmetyki ziołowe."
    "Odporność|odpornosc|🛡️|Wzmocnienie układu odpornościowego — Acerola, Rokitnik, Propolis, witamina C, naturalne suplementy."
    "Wątroba i trawienie|watroba-trawienie|🍃|Ochrona wątroby i wsparcie trawienia — Ostropest, Babka Płesznik, Hepasal, CARBOsal, Kudzu, Spirulina."
    "Krążenie|krazenie|❤️|Wsparcie układu krążenia i żył — Kasztanowiec, Chrom, Magnez, Cynk, żele z rutyną."
    "Energia i stres|energia-stres|💼|Redukcja stresu, zwiększenie energii i witalności — Energia, Geriafix, Gotu Kola, Kudzu."
    "Nos, gardło, jama ustna|nos-gardlo-jama-ustna|🌬️|Produkty na problemy górnych dróg oddechowych i jamy ustnej — Pneumovit, Aurix, Aphtihelp."
    "CBD / Konopie|cbd-konopie|🌱|Oleje CBD, maści konopne, kapsułki — 5% i 10%. Naturalne wsparcie przy bólu, stresie, problemach skórnych."
)

# Track created/skipped
CREATED=0
SKIPPED=0

for line in "${CATS[@]}"; do
    IFS='|' read -r NAME SLUG ICON DESC <<< "$line"

    # Check if already exists (by slug)
    EXISTING=$($WP term get product_cat "$SLUG" --by=slug --field=term_id 2>/dev/null || echo "")

    if [ -n "$EXISTING" ]; then
        echo "  ∙ ${ICON} ${NAME} (slug: $SLUG) — już istnieje [ID $EXISTING]"
        SKIPPED=$((SKIPPED+1))
        continue
    fi

    NEW_ID=$($WP term create product_cat "$NAME" --slug="$SLUG" --description="$DESC" --porcelain 2>&1 || echo "")

    if [[ "$NEW_ID" =~ ^[0-9]+$ ]]; then
        # Save the icon as term meta so child theme can render it
        $WP term meta update "$NEW_ID" gorvita_icon "$ICON" --allow-root >/dev/null 2>&1 || true
        echo "  ✓ ${ICON} ${NAME} (slug: $SLUG) [ID $NEW_ID]"
        CREATED=$((CREATED+1))
    else
        echo "  ✗ ${NAME} — nie utworzono: $NEW_ID"
    fi
done

# Display order — set `menu_order` in the term meta via a more manual approach
echo ""
echo "Ustawianie kolejności wyświetlania..."
ORDER=1
for line in "${CATS[@]}"; do
    IFS='|' read -r NAME SLUG _ _ <<< "$line"
    TID=$($WP term get product_cat "$SLUG" --by=slug --field=term_id 2>/dev/null || echo "")
    if [ -n "$TID" ]; then
        $WP term meta update "$TID" order "$ORDER" --allow-root >/dev/null 2>&1 || true
        ORDER=$((ORDER+1))
    fi
done

# Remove the "Uncategorized" default category items would land in
UNCAT_ID=$($WP term get product_cat "uncategorized" --by=slug --field=term_id 2>/dev/null || echo "")

echo ""
echo "=== DONE ==="
echo "Utworzonych: ${CREATED}"
echo "Istniejących pominietych: ${SKIPPED}"
echo ""
echo "Sprawdź: $WP wc product_cat list --user=1"
