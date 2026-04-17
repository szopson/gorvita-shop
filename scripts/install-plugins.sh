#!/bin/bash
# Install and activate free WordPress plugins via WP-CLI.
# Idempotent — skips already-installed plugins.
#
# Run inside wordpress container:
#   docker compose exec -T wordpress bash /var/scripts/install-plugins.sh
set -euo pipefail

WP="wp --allow-root --path=/var/www/html"

# Plugins to install (slug = wordpress.org plugin slug)
PLUGINS_ACTIVE=(
    "seo-by-rank-math"                         # SEO + Schema
    "redis-cache"                              # Object cache (Redis container)
    "fluent-smtp"                              # SMTP (Resend)
    "redirection"                              # 301 redirects
    "safe-svg"                                 # SVG upload safety
    "wp-crontrol"                              # Cron management
    "contact-form-7"                           # Contact forms
    "woocommerce-pdf-invoices-packing-slips"   # PDF invoices
    "wps-hide-login"                           # Hide /wp-login
)

# Optional — install but don't activate
PLUGINS_INACTIVE=(
    "wordfence"                                # Activate after initial setup
)

echo "=== Installing WP plugins ==="

install_plugin() {
    local slug="$1" activate="$2"

    # Check if already installed
    if $WP plugin is-installed "$slug" 2>/dev/null; then
        local status
        status=$($WP plugin status "$slug" 2>/dev/null | grep -oE 'Status: \w+' | awk '{print $2}')
        if [ "$activate" = "yes" ] && [ "$status" != "Active" ]; then
            $WP plugin activate "$slug" >/dev/null 2>&1
            echo "  ∙ $slug — already installed, activated now"
        else
            echo "  ∙ $slug — already installed (status: $status)"
        fi
        return
    fi

    # Install
    if [ "$activate" = "yes" ]; then
        if $WP plugin install "$slug" --activate 2>&1 | tail -5 | grep -qE "Success:|Plugin activated"; then
            echo "  ✓ $slug — installed & activated"
        else
            echo "  ✗ $slug — FAILED to install"
        fi
    else
        if $WP plugin install "$slug" 2>&1 | tail -5 | grep -q "Success:"; then
            echo "  ✓ $slug — installed (not activated)"
        else
            echo "  ✗ $slug — FAILED to install"
        fi
    fi
}

echo ""
echo "--- Active plugins ---"
for slug in "${PLUGINS_ACTIVE[@]}"; do
    install_plugin "$slug" "yes"
done

echo ""
echo "--- Inactive plugins (install only, manual activation later) ---"
for slug in "${PLUGINS_INACTIVE[@]}"; do
    install_plugin "$slug" "no"
done

# ======================================================================
# POST-INSTALL CONFIGURATION
# ======================================================================

echo ""
echo "=== Post-install configuration ==="

# Redis Object Cache — enable if plugin active
if $WP plugin is-active redis-cache 2>/dev/null; then
    # Redis service is named "redis" on the gorvita-internal network
    $WP config set WP_REDIS_HOST redis 2>/dev/null || true
    $WP config set WP_REDIS_PORT 6379 --raw 2>/dev/null || true
    $WP config set WP_REDIS_DATABASE 0 --raw 2>/dev/null || true
    $WP config set WP_CACHE true --raw 2>/dev/null || true
    # Enable object cache drop-in
    $WP redis enable 2>&1 | tail -3 || true
    echo "  ✓ Redis Object Cache configured (host=redis, port=6379)"
fi

# Rank Math — run basic setup
if $WP plugin is-active seo-by-rank-math 2>/dev/null; then
    $WP option update rank_math_is_configured 1 2>/dev/null || true
    # Basic settings
    $WP option update rank-math-options-general '{"content_ai_language":"Polish","content_ai_country":"PL","site_has_news":"off","local_business_type":"LocalBusiness"}' --format=json 2>/dev/null || true
    echo "  ✓ Rank Math basic config set"
fi

# Redirection — import from data/redirects.json via its CLI?
# Redirection plugin has WP-CLI support but JSON import needs groups set up.
# Skip for now — will do after launch.

# WPS Hide Login — set custom login path
if $WP plugin is-active wps-hide-login 2>/dev/null; then
    $WP option update whl_page panel-gorvita 2>/dev/null || true
    echo "  ✓ WPS Hide Login: /panel-gorvita/ (admin access)"
fi

# FluentSMTP — leave for manual (needs Resend API key from env)

# Flush caches
$WP cache flush 2>/dev/null || true
$WP rewrite flush 2>/dev/null || true

echo ""
echo "=== DONE ==="
echo ""
echo "Active plugins list:"
$WP plugin list --status=active --fields=name,version --allow-root 2>&1 | head -15
