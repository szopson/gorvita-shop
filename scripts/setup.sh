#!/bin/bash
set -e

echo "=== GORVITA SETUP ==="

WP_PATH="/var/www/html"

# Wait for DB
until wp db check --path="$WP_PATH" --allow-root 2>/dev/null; do
    echo "Waiting for database..."
    sleep 2
done

# Install WordPress if needed
if ! wp core is-installed --path="$WP_PATH" --allow-root 2>/dev/null; then
    echo "Installing WordPress..."
    wp core install \
        --path="$WP_PATH" \
        --url="${WORDPRESS_HOME}" \
        --title="Gorvita Sklep" \
        --admin_user="${WP_ADMIN_USER:-admin}" \
        --admin_password="${WP_ADMIN_PASSWORD:-admin123}" \
        --admin_email="${WP_ADMIN_EMAIL:-admin@gorvita.pl}" \
        --skip-email \
        --allow-root
fi

# Configure
wp option update timezone_string "Europe/Warsaw" --allow-root
wp option update date_format "j F Y" --allow-root
wp option update time_format "H:i" --allow-root
wp rewrite structure '/%postname%/' --allow-root

# Language
wp language core install pl_PL --allow-root || true
wp site switch-language pl_PL --allow-root || true

# Install WooCommerce
wp plugin install woocommerce --activate --allow-root || true

# Configure WooCommerce
wp option update woocommerce_currency "PLN" --allow-root
wp option update woocommerce_default_country "PL" --allow-root
wp option update woocommerce_currency_pos "right_space" --allow-root
wp option update woocommerce_price_thousand_sep " " --allow-root
wp option update woocommerce_price_decimal_sep "," --allow-root

# Install theme
wp theme install blocksy --activate --allow-root || true

# Activate child theme if exists
if wp theme is-installed gorvita-child --allow-root 2>/dev/null; then
    wp theme activate gorvita-child --allow-root
fi

# Flush
wp cache flush --allow-root || true
wp rewrite flush --allow-root || true

echo ""
echo "=== SETUP COMPLETE ==="
echo "URL: ${WORDPRESS_HOME}"
echo "Admin: ${WORDPRESS_HOME}/wp-admin/"
echo ""
