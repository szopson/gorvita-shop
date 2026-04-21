<?php
/**
 * WooCommerce customizations.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

/**
 * Disable WooCommerce "Coming Soon" mode (WooCommerce 8.2+).
 * Forces store to be publicly accessible regardless of WP Admin setting.
 */
add_filter('pre_option_woocommerce_coming_soon', function () {
    return 'no';
});
add_filter('pre_option_woocommerce_store_pages_only', function () {
    return 'no';
});

/**
 * Redirect /shop/ → actual WooCommerce shop page (handles English slug from Blocksy menu).
 */
add_action('template_redirect', function () {
    if (!is_404()) return;
    $uri = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($uri === '/shop' && function_exists('wc_get_page_permalink')) {
        wp_redirect(wc_get_page_permalink('shop'), 301);
        exit;
    }
});

/**
 * Change "Add to cart" button text.
 */
add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return __('Do koszyka', 'gorvita-child');
});

add_filter('woocommerce_product_add_to_cart_text', function ($text, $product) {
    if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) {
        return __('Do koszyka', 'gorvita-child');
    }
    return $text;
}, 10, 2);

/**
 * Change number of products per row on shop page.
 */
add_filter('loop_shop_columns', function () { return 3; });
add_filter('loop_shop_per_page', function () { return 12; });

/**
 * Breadcrumbs: localize separator/home, wrap in Blocksy container for consistent padding.
 */
add_filter('woocommerce_breadcrumb_defaults', function ($defaults) {
    $defaults['delimiter']   = ' <span aria-hidden="true">/</span> ';
    $defaults['home']        = __('Strona główna', 'gorvita-child');
    $defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb gorvita-breadcrumbs" aria-label="' . esc_attr__('Breadcrumbs', 'gorvita-child') . '"><div class="ct-container">';
    $defaults['wrap_after']  = '</div></nav>';
    return $defaults;
});

/**
 * Render breadcrumbs on shop archive + single product (Blocksy doesn't by default).
 */
add_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 15);

/**
 * Remove "Description" tab title duplicate on single product.
 */
add_filter('woocommerce_product_description_heading', '__return_empty_string');
add_filter('woocommerce_product_additional_information_heading', '__return_empty_string');

/**
 * Add VAT info in Polish cart/checkout.
 */
add_filter('woocommerce_countries_tax_or_vat', function () {
    return __('VAT', 'gorvita-child');
});

/**
 * Add stock status badge on product loop.
 */
add_action('woocommerce_before_shop_loop_item_title', function () {
    global $product;
    if (!$product) return;
    if ($product->is_in_stock()) {
        echo '<span class="gorvita-stock-badge gorvita-stock-badge--in" aria-hidden="true"></span>';
    }
}, 15);

/**
 * Add meta viewport (mobile-first).
 */
add_action('wp_head', function () {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
}, 1);

/**
 * Add to cart AJAX fragments: update mini-cart count.
 */
add_filter('woocommerce_add_to_cart_fragments', function ($fragments) {
    ob_start();
    ?>
    <span class="gorvita-cart-count" data-count="<?php echo esc_attr(WC()->cart->get_cart_contents_count()); ?>">
        <?php echo esc_html(WC()->cart->get_cart_contents_count()); ?>
    </span>
    <?php
    $fragments['.gorvita-cart-count'] = ob_get_clean();
    return $fragments;
});

/**
 * Disable WooCommerce default styles we don't want (we handle in child CSS).
 */
add_filter('woocommerce_enqueue_styles', function ($styles) {
    // Keep all for now — override via CSS specificity.
    return $styles;
});

/**
 * Checkout: reorder fields so Polish users get logical tab order.
 */
add_filter('woocommerce_default_address_fields', function ($fields) {
    if (isset($fields['postcode'])) {
        $fields['postcode']['priority'] = 65;
        $fields['postcode']['placeholder'] = '00-000';
    }
    if (isset($fields['city'])) $fields['city']['priority'] = 70;
    return $fields;
});

/**
 * Require phone in checkout billing (often needed for InPost/FedEx).
 */
add_filter('woocommerce_checkout_fields', function ($fields) {
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['required'] = true;
    }
    return $fields;
});

/**
 * Checkout: add NIP field for invoice.
 */
add_action('woocommerce_after_checkout_billing_form', function () {
    woocommerce_form_field('billing_nip', [
        'type' => 'text',
        'class' => ['form-row-wide'],
        'label' => __('NIP (opcjonalnie — do faktury)', 'gorvita-child'),
        'required' => false,
    ], WC()->checkout->get_value('billing_nip'));
});

add_action('woocommerce_checkout_update_order_meta', function ($order_id) {
    if (!empty($_POST['billing_nip'])) {
        update_post_meta($order_id, '_billing_nip', sanitize_text_field(wp_unslash($_POST['billing_nip'])));
    }
});

add_filter('woocommerce_admin_billing_fields', function ($fields) {
    $fields['nip'] = [
        'label' => 'NIP',
        'show' => true,
    ];
    return $fields;
});

/**
 * Order confirmation email: add NIP if present.
 */
add_action('woocommerce_email_after_order_table', function ($order, $sent_to_admin, $plain_text, $email) {
    $nip = $order->get_meta('_billing_nip');
    if ($nip) {
        echo $plain_text
            ? "\n" . sprintf(__('NIP: %s', 'gorvita-child'), $nip) . "\n"
            : '<p><strong>NIP:</strong> ' . esc_html($nip) . '</p>';
    }
}, 10, 4);
