<?php
/**
 * Performance optimizations.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

/**
 * Disable emoji support (saves HTTP requests).
 */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
add_filter('tiny_mce_plugins', function ($plugins) {
    return array_diff($plugins, ['wpemoji']);
});

/**
 * Remove WP embed script when not needed on frontend.
 */
add_action('wp_footer', function () {
    wp_dequeue_script('wp-embed');
});

/**
 * Defer non-critical scripts.
 */
add_filter('script_loader_tag', function ($tag, $handle) {
    $defer = ['wc-add-to-cart', 'jquery-blockui', 'wc-add-to-cart-variation'];
    if (in_array($handle, $defer, true)) {
        return str_replace(' src', ' defer src', $tag);
    }
    return $tag;
}, 10, 2);

/**
 * Image lazy loading is WP core default; add fetchpriority="high" to LCP image (first product/hero).
 */
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
    static $first = true;
    if ($first && (is_front_page() || is_shop() || is_singular('product'))) {
        $attr['fetchpriority'] = 'high';
        $attr['loading'] = 'eager';
        $first = false;
    }
    return $attr;
}, 10, 3);

/**
 * Limit post revisions to reduce DB bloat.
 */
add_filter('wp_revisions_to_keep', function ($num, $post) {
    return 5;
}, 10, 2);

/**
 * Disable heartbeat on frontend (reduces CPU).
 */
add_action('init', function () {
    if (!is_admin()) {
        wp_deregister_script('heartbeat');
    }
});

/**
 * Redis-friendly: use persistent object cache (assumes Redis Object Cache plugin is active).
 */
// (Plugin handles wp-config drop-in; no code here needed.)

/**
 * Disable REST API for non-authenticated users (security + perf).
 * Keep allowed for WooCommerce/WP core paths.
 */
add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) return $result;
    if (is_user_logged_in()) return $result;

    $route = $_SERVER['REQUEST_URI'] ?? '';
    $allowed = ['/wp-json/wc/', '/wp-json/oembed/', '/wp-json/jwt-auth/'];
    foreach ($allowed as $path) {
        if (str_contains($route, $path)) return $result;
    }

    // Block everything else for guests
    return new WP_Error(
        'rest_not_logged_in',
        __('REST API dostępne tylko dla zalogowanych.', 'gorvita-child'),
        ['status' => 401]
    );
});

/**
 * Homepage-specific CSS — enqueued only on front page.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_front_page()) {
        wp_enqueue_style(
            'gorvita-homepage',
            get_stylesheet_directory_uri() . '/assets/css/homepage.css',
            [],
            filemtime(get_stylesheet_directory() . '/assets/css/homepage.css') ?: '1.5'
        );
    }
}, 20);

/**
 * Homepage animations JS — parallax, Ken Burns, fade-in, header scroll.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_front_page()) {
        wp_enqueue_script(
            'gorvita-animations',
            get_stylesheet_directory_uri() . '/assets/js/animations.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/animations.js') ?: '1.2',
            true
        );
    }
}, 20);

/**
 * Footer accordion: keep sections open on desktop, closed on mobile.
 * Inline — tiny, no extra HTTP request.
 */
add_action('wp_footer', function () {
    ?>
    <script>
    (function(){
        function syncFooter(){
            var open = window.innerWidth > 768;
            document.querySelectorAll('.gorvita-footer__section').forEach(function(el){
                if(open) el.setAttribute('open','');
            });
        }
        document.addEventListener('DOMContentLoaded', syncFooter);
        window.addEventListener('resize', syncFooter);
    })();
    </script>
    <?php
}, 30);

/**
 * Mobile product tabs accordion — product pages only, loaded in footer.
 */
add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_script(
            'gorvita-mobile-tabs',
            get_stylesheet_directory_uri() . '/assets/js/mobile-tabs.js',
            [],
            filemtime(get_stylesheet_directory() . '/assets/js/mobile-tabs.js') ?: '1.0',
            true
        );
    }
}, 20);

/**
 * Product card CSS + LQIP JS — shop, category archives, and single product pages.
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product()) {
        return;
    }
    wp_enqueue_style(
        'gorvita-product-card',
        get_stylesheet_directory_uri() . '/assets/css/product-card.css',
        ['gorvita-child'],
        filemtime(get_stylesheet_directory() . '/assets/css/product-card.css') ?: '1.2'
    );
    wp_enqueue_script(
        'gorvita-product-card',
        get_stylesheet_directory_uri() . '/assets/js/product-card.js',
        [],
        filemtime(get_stylesheet_directory() . '/assets/js/product-card.js') ?: '1.2',
        true
    );
}, 20);
