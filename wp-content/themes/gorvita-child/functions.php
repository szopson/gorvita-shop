<?php
/**
 * Gorvita Child Theme — main functions file.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

define('GORVITA_CHILD_VERSION', '1.2.0');
define('GORVITA_CHILD_DIR', get_stylesheet_directory());
define('GORVITA_CHILD_URI', get_stylesheet_directory_uri());

/**
 * Enqueue parent + child styles and Google Fonts.
 * Replaces Lato with Inter + adds JetBrains Mono for eyebrow/stat labels.
 */
function gorvita_enqueue_styles() {
    wp_enqueue_style(
        'gorvita-fonts',
        'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'blocksy-parent',
        get_template_directory_uri() . '/style.css',
        ['gorvita-fonts'],
        wp_get_theme(get_template())->get('Version')
    );

    wp_enqueue_style(
        'gorvita-child',
        get_stylesheet_uri(),
        ['blocksy-parent'],
        GORVITA_CHILD_VERSION
    );
}
add_action('wp_enqueue_scripts', 'gorvita_enqueue_styles', 20);

/**
 * Preconnect Google Fonts.
 */
function gorvita_resource_hints($hints, $relation_type) {
    if ('preconnect' === $relation_type) {
        $hints[] = ['href' => 'https://fonts.googleapis.com', 'crossorigin' => ''];
        $hints[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => ''];
    }
    return $hints;
}
add_filter('wp_resource_hints', 'gorvita_resource_hints', 10, 2);

/**
 * Preload the hero background image on the front page (LCP optimization).
 */
add_action('wp_head', function () {
    if (!is_front_page()) return;
    $hero_url = GORVITA_CHILD_URI . '/assets/images/gorce.webp';
    echo '<link rel="preload" as="image" href="' . esc_url($hero_url) . '" type="image/webp" fetchpriority="high">' . "\n";
}, 2);

/**
 * WooCommerce support declaration.
 */
function gorvita_theme_setup() {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('custom-logo', [
        'width' => 355,
        'height' => 167,
        'flex-width' => true,
        'flex-height' => true,
    ]);
    load_child_theme_textdomain('gorvita-child', GORVITA_CHILD_DIR . '/languages');
}
add_action('after_setup_theme', 'gorvita_theme_setup');

/**
 * Auto-install the Gorvita logo on theme activation.
 */
function gorvita_install_logo() {
    if (get_theme_mod('custom_logo')) return;
    $logo_path = GORVITA_CHILD_DIR . '/assets/images/logo.png';
    if (!file_exists($logo_path)) return;

    $upload_dir = wp_upload_dir();
    $filename = 'gorvita-logo.png';
    $dest = trailingslashit($upload_dir['path']) . $filename;
    if (!file_exists($dest)) {
        copy($logo_path, $dest);
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $filetype = wp_check_filetype($filename);
    $attachment_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'],
        'post_title' => 'Gorvita Logo',
        'post_content' => '',
        'post_status' => 'inherit',
    ], $dest);
    if (is_wp_error($attachment_id)) return;
    $metadata = wp_generate_attachment_metadata($attachment_id, $dest);
    wp_update_attachment_metadata($attachment_id, $metadata);
    set_theme_mod('custom_logo', $attachment_id);
}
add_action('after_switch_theme', 'gorvita_install_logo');

/**
 * Include modules.
 */
require_once GORVITA_CHILD_DIR . '/inc/b2b.php';
require_once GORVITA_CHILD_DIR . '/inc/woocommerce.php';
require_once GORVITA_CHILD_DIR . '/inc/performance.php';
require_once GORVITA_CHILD_DIR . '/inc/mobile-ux.php';
require_once GORVITA_CHILD_DIR . '/inc/wishlist.php';
require_once GORVITA_CHILD_DIR . '/inc/quick-views.php';
require_once GORVITA_CHILD_DIR . '/inc/translations.php';
require_once GORVITA_CHILD_DIR . '/inc/search.php';
require_once GORVITA_CHILD_DIR . '/inc/mega-menu.php';

/**
 * Remove WP version from head (security hygiene).
 */
remove_action('wp_head', 'wp_generator');

/**
 * Disable XML-RPC (rarely needed, commonly abused).
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Admin bar tweak.
 */
add_action('admin_head', function () {
    echo '<style>#wpadminbar{background:#1A1A1A !important}</style>';
});

/**
 * Expose a per-product "gorvita_shade" meta field for product card backgrounds.
 * Shows up as a simple text input under the Product Data → General tab.
 */
add_action('woocommerce_product_options_general_product_data', function () {
    woocommerce_wp_text_input([
        'id' => '_gorvita_shade',
        'label' => __('Kolor karty (hex)', 'gorvita-child'),
        'placeholder' => '#8db87a',
        'desc_tip' => true,
        'description' => __('Hex kolor (np. #8db87a) używany do radialnego gradientu tła karty produktu na stronie głównej. Pusty = domyślny sage.', 'gorvita-child'),
    ]);
    woocommerce_wp_select([
        'id' => '_gorvita_badge',
        'label' => __('Badge na karcie', 'gorvita-child'),
        'options' => [
            ''     => '— brak —',
            'best' => 'Bestseller',
            'new'  => 'Nowość',
            'sale' => 'Promocja',
            'cbd'  => 'CBD Gold',
        ],
    ]);
});
add_action('woocommerce_process_product_meta', function ($post_id) {
    $shade = isset($_POST['_gorvita_shade']) ? sanitize_hex_color(wp_unslash($_POST['_gorvita_shade'])) : '';
    update_post_meta($post_id, '_gorvita_shade', $shade ?: '');
    $badge = isset($_POST['_gorvita_badge']) ? sanitize_text_field(wp_unslash($_POST['_gorvita_badge'])) : '';
    update_post_meta($post_id, '_gorvita_badge', in_array($badge, ['best','new','sale','cbd'], true) ? $badge : '');
});
