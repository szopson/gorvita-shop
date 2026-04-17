#!/bin/bash
# Import products from data/products.json into WooCommerce via WP-CLI.
# Run INSIDE the wordpress container: docker compose exec wordpress bash /var/scripts/import-products.sh
set -euo pipefail

WP="wp --allow-root --path=/var/www/html"
JSON_FILE="/var/scripts/../data/products.json"
IMAGES_DIR="/var/scripts/../data/images"

if [ ! -f "$JSON_FILE" ]; then
    # Fallback — look for absolute path mounted into container
    for candidate in /var/www/html/wp-content/data/products.json /tmp/products.json; do
        if [ -f "$candidate" ]; then JSON_FILE="$candidate"; break; fi
    done
fi

if [ ! -f "$JSON_FILE" ]; then
    echo "✗ products.json not found. Expected at $JSON_FILE" >&2
    exit 1
fi

echo "→ Importing from $JSON_FILE"

# Ensure jq is available
if ! command -v jq >/dev/null 2>&1; then
    apt-get update -qq && apt-get install -y -qq jq
fi

# 1. Create categories
echo "→ Creating categories..."
CATS_FILE="$(dirname "$JSON_FILE")/categories.json"
if [ -f "$CATS_FILE" ]; then
    jq -c '.[]' "$CATS_FILE" | while read -r cat; do
        name=$(echo "$cat" | jq -r '.name')
        slug=$(echo "$cat" | jq -r '.slug')
        if ! $WP wc product_cat list --slug="$slug" --format=count --user=1 2>/dev/null | grep -q '^1$'; then
            $WP wc product_cat create --name="$name" --slug="$slug" --user=1 >/dev/null
            echo "  + $name"
        fi
    done
fi

# 2. Import products
COUNT=0
SKIPPED=0
TOTAL=$(jq 'length' "$JSON_FILE")
echo "→ Importing $TOTAL products..."

jq -c '.[]' "$JSON_FILE" | while read -r product; do
    sku=$(echo "$product" | jq -r '.sku')
    title=$(echo "$product" | jq -r '.title')
    slug=$(echo "$product" | jq -r '.slug')
    price=$(echo "$product" | jq -r '.price // empty')
    short=$(echo "$product" | jq -r '.short_description // ""')
    desc=$(echo "$product" | jq -r '.description // ""')

    # Check if exists
    existing_id=$($WP post list --post_type=product --meta_key=_sku --meta_value="$sku" --format=ids --user=1 2>/dev/null || echo "")
    if [ -n "$existing_id" ]; then
        echo "  ∙ exists: $title (ID $existing_id) — skip"
        SKIPPED=$((SKIPPED+1))
        continue
    fi

    # Create product
    product_id=$($WP wc product create \
        --name="$title" \
        --slug="$slug" \
        --type=simple \
        --sku="$sku" \
        --regular_price="${price:-0}" \
        --short_description="$short" \
        --description="$desc" \
        --status=publish \
        --manage_stock=false \
        --user=1 \
        --porcelain 2>/dev/null || echo "")

    if [ -z "$product_id" ]; then
        echo "  ✗ failed: $title"
        continue
    fi

    # Categories
    echo "$product" | jq -r '.categories[]' | while read -r cat_name; do
        cat_slug=$(echo "$cat_name" | iconv -f utf-8 -t ascii//TRANSLIT | tr '[:upper:]' '[:lower:]' | tr ' ' '-' | tr -cd 'a-z0-9-')
        cat_id=$($WP wc product_cat list --slug="$cat_slug" --format=ids --user=1 2>/dev/null | head -1)
        if [ -n "$cat_id" ]; then
            $WP wc product update "$product_id" --categories="[{\"id\":$cat_id}]" --user=1 >/dev/null 2>&1 || true
        fi
    done

    # Images
    images=$(echo "$product" | jq -r '.local_images[]?' 2>/dev/null || true)
    first_image=true
    for img_rel in $images; do
        img_path="/var/scripts/../$img_rel"
        if [ ! -f "$img_path" ]; then continue; fi
        attach_id=$($WP media import "$img_path" --post_id="$product_id" --title="$title" --user=1 --porcelain 2>/dev/null || echo "")
        if [ -n "$attach_id" ] && [ "$first_image" = "true" ]; then
            $WP post meta update "$product_id" _thumbnail_id "$attach_id" --user=1 >/dev/null
            first_image=false
        fi
    done

    COUNT=$((COUNT+1))
    echo "  ✓ [$COUNT] $title — $price PLN"
done

echo ""
echo "=== DONE ==="
echo "Imported: $COUNT / Skipped: $SKIPPED / Total: $TOTAL"
