#!/bin/bash
# Create and populate primary navigation menu.
# Idempotent — finds existing menu by name or creates new one.
#
# Menu structure (per information-architecture.md):
#   Strona główna
#   Sklep ▾
#     └── 8 kategorii (Stawy, Skóra, Odporność, Wątroba, Krążenie, Stres, Nos/Gardło, CBD)
#   Nowości
#   Bestsellery
#   Promocje
#   CBD (landing)
#   O marce
#   B2B
#
# Run inside wordpress container:
#   docker compose exec -T wordpress bash /var/scripts/create-menu.sh
set -euo pipefail

WP="wp --allow-root --path=/var/www/html"

MENU_NAME="Główne menu"
MENU_SLUG="glowne-menu"
MENU_LOCATIONS=("menu_1" "menu_2" "menu_mobile")

# 1. Find or create menu
MENU_ID=$($WP menu list --fields=slug,term_id --format=csv 2>/dev/null | awk -F, -v s="$MENU_SLUG" '$1==s {print $2}')

if [ -n "$MENU_ID" ]; then
    echo "∙ Menu '$MENU_NAME' already exists [ID $MENU_ID] — clearing items..."
    # Remove all existing items so we rebuild cleanly
    ITEMS=$($WP menu item list "$MENU_SLUG" --fields=db_id --format=ids 2>/dev/null || echo "")
    for item in $ITEMS; do
        $WP menu item delete "$item" >/dev/null 2>&1 || true
    done
else
    MENU_ID=$($WP menu create "$MENU_NAME" --porcelain 2>/dev/null)
    echo "✓ Menu '$MENU_NAME' created [ID $MENU_ID]"
fi

# 2. Assign menu to Blocksy header + mobile locations
for loc in "${MENU_LOCATIONS[@]}"; do
    $WP menu location assign "$MENU_SLUG" "$loc" 2>/dev/null && echo "✓ Menu assigned to $loc" || echo "∙ Could not assign to $loc"
done

# 3. Helper: add menu item by post slug (page) — returns item ID
add_page_item() {
    local page_slug="$1" title="$2" parent_id="${3:-0}"
    local page_id
    page_id=$($WP post list --post_type=page --name="$page_slug" --format=ids 2>/dev/null | tr -d '\r')
    if [ -z "$page_id" ]; then
        echo "  ⚠ Page '$page_slug' not found — skipping"
        return
    fi
    local item_id
    local parent_arg=""
    [ "$parent_id" != "0" ] && parent_arg="--parent-id=$parent_id"
    item_id=$($WP menu item add-post "$MENU_SLUG" "$page_id" \
        --title="$title" \
        $parent_arg \
        --porcelain 2>/dev/null)
    echo "  ✓ [item $item_id] $title"
    echo "$item_id"
}

# Helper: add menu item for a product category
add_category_item() {
    local cat_slug="$1" parent_id="${2:-0}"
    local term_id
    term_id=$($WP term get product_cat "$cat_slug" --by=slug --field=term_id 2>/dev/null | tr -d '\r')
    if [ -z "$term_id" ]; then
        echo "  ⚠ Category '$cat_slug' not found — skipping"
        return
    fi
    local cat_name
    cat_name=$($WP term get product_cat "$term_id" --field=name 2>/dev/null)
    local parent_arg=""
    [ "$parent_id" != "0" ] && parent_arg="--parent-id=$parent_id"
    local item_id
    item_id=$($WP menu item add-term "$MENU_SLUG" product_cat "$term_id" \
        $parent_arg \
        --porcelain 2>/dev/null)
    echo "  ✓ [item $item_id] → $cat_name"
    echo "$item_id"
}

# Helper: add custom URL item (for Shop page)
add_custom_item() {
    local url="$1" title="$2" parent_id="${3:-0}"
    local parent_arg=""
    [ "$parent_id" != "0" ] && parent_arg="--parent-id=$parent_id"
    local item_id
    item_id=$($WP menu item add-custom "$MENU_SLUG" "$title" "$url" $parent_arg --porcelain 2>/dev/null)
    echo "  ✓ [item $item_id] $title"
    echo "$item_id"
}

echo ""
echo "=== Budowanie menu ==="

# Strona główna (custom URL)
add_custom_item "/" "Strona główna" >/dev/null

# Sklep ▾ (top-level custom URL to /shop/) with category children
SHOP_ITEM=$(add_custom_item "/shop/" "Sklep")
SHOP_ITEM=$(echo "$SHOP_ITEM" | grep -oE 'item [0-9]+' | head -1 | awk '{print $2}')
if [ -n "$SHOP_ITEM" ]; then
    add_category_item "stawy-miesnie"         "$SHOP_ITEM" >/dev/null
    add_category_item "skora-cialo"           "$SHOP_ITEM" >/dev/null
    add_category_item "odpornosc"             "$SHOP_ITEM" >/dev/null
    add_category_item "watroba-trawienie"     "$SHOP_ITEM" >/dev/null
    add_category_item "krazenie"              "$SHOP_ITEM" >/dev/null
    add_category_item "energia-stres"         "$SHOP_ITEM" >/dev/null
    add_category_item "nos-gardlo-jama-ustna" "$SHOP_ITEM" >/dev/null
    add_category_item "cbd-konopie"           "$SHOP_ITEM" >/dev/null
fi

# Quick views
add_page_item "nowosci"     "Nowości"     >/dev/null
add_page_item "bestsellery" "Bestsellery" >/dev/null
add_page_item "promocje"    "Promocje"    >/dev/null

# Landing pages
add_page_item "cbd"     "CBD"     >/dev/null
add_page_item "o-marce" "O marce" >/dev/null
add_page_item "b2b"     "B2B"     >/dev/null

echo ""
echo "=== DONE ==="
$WP menu item list "$MENU_SLUG" --fields=db_id,title,type,menu_item_parent 2>&1 | head -25
