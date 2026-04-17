#!/bin/bash
# Fix WordPress filesystem permissions.
# Ensures www-data (container user) owns everything it needs to write.
#
# Safe to run at any time. Run inside wordpress container:
#   docker compose exec -T wordpress bash /var/scripts/fix-permissions.sh
set -e

WP_ROOT="${WP_ROOT:-/var/www/html}"

# Ensure plugin dirs exist before chowning (prevents errors on first run)
mkdir -p "$WP_ROOT/wp-content/uploads"
mkdir -p "$WP_ROOT/wp-content/uploads/wc-logs"
mkdir -p "$WP_ROOT/wp-content/uploads/wpo_wcpdf/attachments"
mkdir -p "$WP_ROOT/wp-content/uploads/wpo_wcpdf/fonts"
mkdir -p "$WP_ROOT/wp-content/uploads/wpo_wcpdf/tmp"
mkdir -p "$WP_ROOT/wp-content/upgrade"
mkdir -p "$WP_ROOT/wp-content/cache"

# Owner: www-data (uid 33 in wordpress:apache image)
# Writable: uploads, upgrade, cache — plugins need these
chown -R www-data:www-data \
    "$WP_ROOT/wp-content/uploads" \
    "$WP_ROOT/wp-content/upgrade" \
    "$WP_ROOT/wp-content/cache" 2>/dev/null || true

# Standard perms: 755 dirs, 644 files (writable by owner, readable by group/others)
find "$WP_ROOT/wp-content/uploads" -type d -exec chmod 755 {} \; 2>/dev/null || true
find "$WP_ROOT/wp-content/uploads" -type f -exec chmod 644 {} \; 2>/dev/null || true

# wp-config.php must be readable by www-data but not world-readable (contains DB creds)
if [ -f "$WP_ROOT/wp-config.php" ]; then
    chown www-data:www-data "$WP_ROOT/wp-config.php" 2>/dev/null || true
    chmod 640 "$WP_ROOT/wp-config.php" 2>/dev/null || true
fi

echo "✓ Permissions fixed:"
echo "  - uploads/wc-logs: $(stat -c '%U:%G %a' "$WP_ROOT/wp-content/uploads/wc-logs")"
echo "  - uploads/wpo_wcpdf: $(stat -c '%U:%G %a' "$WP_ROOT/wp-content/uploads/wpo_wcpdf")"
echo "  - wp-config.php: $(stat -c '%U:%G %a' "$WP_ROOT/wp-config.php" 2>/dev/null || echo 'n/a')"
