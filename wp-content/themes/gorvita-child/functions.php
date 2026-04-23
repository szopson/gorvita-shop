<?php
/**
 * Gorvita Child Theme — main functions file.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

define('GORVITA_CHILD_VERSION', '1.3.0');
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
        filemtime(get_stylesheet_directory() . '/style.css') ?: GORVITA_CHILD_VERSION
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
 * Uses attachment 249 (hero-slide-sklep) — the actual LCP element.
 * Preloads WebP if available (smaller), falls back to JPEG.
 */
add_action('wp_head', function () {
    if (!is_front_page()) return;
    $webp_url  = wp_get_attachment_image_url(249, 'full', false);
    // WordPress auto-generates .webp alongside uploads — try that first.
    $webp_path = get_attached_file(249);
    if ($webp_path) {
        $candidate = preg_replace('/\.(jpe?g|png)$/i', '.webp', $webp_path);
        if (file_exists($candidate)) {
            $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $webp_url);
        }
    }
    if (!$webp_url) return;
    $type = str_ends_with($webp_url, '.webp') ? 'image/webp' : 'image/jpeg';
    echo '<link rel="preload" as="image" href="' . esc_url($webp_url) . '" type="' . $type . '" fetchpriority="high">' . "\n";
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
 * Inline SVG icon helper — lucide-style, 1.5px stroke.
 * Defined globally so footer.php and other templates can use it.
 */
function gorvita_icon($name, $size = 20) {
    $s = sprintf('width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"', $size, $size);
    $paths = [
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'cart'        => '<path d="M3 4h3l2 12h11l2-8H7"/><circle cx="9" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
        'heart'       => '<path d="M12 20s-7-4.5-9-9a4.5 4.5 0 0 1 9-2.5 4.5 4.5 0 0 1 9 2.5c-2 4.5-9 9-9 9z"/>',
        'chevron'     => '<path d="m6 9 6 6 6-6"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left'  => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'check'       => '<path d="m5 12 5 5L20 7"/>',
        'truck'       => '<path d="M3 7h11v10H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/>',
        'leaf'        => '<path d="M20 4c0 8-6 14-14 14-1 0-2-.2-2-.2s0-8 6-13c3-2.5 7-2 10-.8z"/><path d="M4 18C8 14 12 10 20 4"/>',
        'shield'      => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/>',
        'return'      => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'droplet'     => '<path d="M12 3s7 7 7 12a7 7 0 0 1-14 0c0-5 7-12 7-12z"/>',
        'certificate' => '<circle cx="12" cy="10" r="5"/><path d="m9 14-2 7 5-3 5 3-2-7"/>',
        'mountain'    => '<path d="m2 20 6-10 4 6 3-4 7 8z"/>',
        'star'        => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6L12 17l-5.4 2.8 1-6L3.3 9.4l6-.9L12 3z"/></svg>',
    ];
    if ($name === 'star') {
        echo $paths[$name]; // phpcs:ignore
        return;
    }
    if (!isset($paths[$name])) return;
    echo '<svg xmlns="http://www.w3.org/2000/svg" '.$s.'>'.$paths[$name].'</svg>'; // phpcs:ignore
}

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
