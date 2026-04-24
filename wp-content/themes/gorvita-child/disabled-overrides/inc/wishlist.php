<?php
/**
 * Lightweight wishlist — stores product IDs in user meta (logged in) or
 * localStorage fallback (guests). No plugin dependency.
 *
 * Frontend: a heart button injected onto PLP cards and single product pages.
 * On click: toggles wishlist via admin-ajax for logged-in users, or via JS
 * for guests.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

const GORVITA_WISHLIST_META = '_gorvita_wishlist';

function gorvita_get_wishlist($user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) return [];
    $list = get_user_meta($user_id, GORVITA_WISHLIST_META, true);
    return is_array($list) ? array_map('intval', $list) : [];
}

function gorvita_is_in_wishlist($product_id, $user_id = null) {
    return in_array((int) $product_id, gorvita_get_wishlist($user_id), true);
}

function gorvita_toggle_wishlist($product_id, $user_id = null) {
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) return false;
    $list = gorvita_get_wishlist($user_id);
    $product_id = (int) $product_id;
    if (in_array($product_id, $list, true)) {
        $list = array_values(array_diff($list, [$product_id]));
    } else {
        $list[] = $product_id;
    }
    update_user_meta($user_id, GORVITA_WISHLIST_META, $list);
    return in_array($product_id, $list, true);
}

/**
 * AJAX endpoint.
 */
function gorvita_wishlist_ajax() {
    check_ajax_referer('gorvita_wishlist', 'nonce');
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    if (!$product_id || !get_post($product_id)) {
        wp_send_json_error(['message' => 'Invalid product'], 400);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Login required', 'redirect' => wp_login_url()], 401);
    }
    $in_list = gorvita_toggle_wishlist($product_id);
    wp_send_json_success([
        'in_list' => $in_list,
        'count' => count(gorvita_get_wishlist()),
    ]);
}
add_action('wp_ajax_gorvita_wishlist_toggle', 'gorvita_wishlist_ajax');
add_action('wp_ajax_nopriv_gorvita_wishlist_toggle', 'gorvita_wishlist_ajax');

/**
 * Render wishlist heart button.
 */
function gorvita_render_wishlist_button($product_id = null) {
    if (!$product_id) {
        global $product;
        $product_id = $product ? $product->get_id() : 0;
    }
    if (!$product_id) return;

    $in_list = is_user_logged_in() && gorvita_is_in_wishlist($product_id);
    $label = $in_list
        ? __('Usuń z ulubionych', 'gorvita-child')
        : __('Dodaj do ulubionych', 'gorvita-child');
    ?>
    <button type="button"
            class="gorvita-wishlist-btn<?php echo $in_list ? ' is-active' : ''; ?>"
            data-product-id="<?php echo esc_attr($product_id); ?>"
            aria-label="<?php echo esc_attr($label); ?>"
            aria-pressed="<?php echo $in_list ? 'true' : 'false'; ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 21s-7.5-4.6-10-9.5C.5 7 3 3 7 3c2 0 3.5 1 5 3 1.5-2 3-3 5-3 4 0 6.5 4 5 8.5-2.5 4.9-10 9.5-10 9.5z"
                  stroke="currentColor" stroke-width="1.75" stroke-linejoin="round" fill="none"/>
        </svg>
    </button>
    <?php
}

/**
 * Inject heart on product loop (top-right of card).
 */
function gorvita_inject_wishlist_on_loop() {
    gorvita_render_wishlist_button();
}
add_action('woocommerce_before_shop_loop_item_title', 'gorvita_inject_wishlist_on_loop', 5);

/**
 * Inject heart next to "Add to Cart" on single product.
 */
function gorvita_inject_wishlist_on_single() {
    gorvita_render_wishlist_button();
}
add_action('woocommerce_after_add_to_cart_button', 'gorvita_inject_wishlist_on_single');

/**
 * Enqueue wishlist JS.
 */
function gorvita_wishlist_scripts() {
    wp_enqueue_script(
        'gorvita-wishlist',
        get_stylesheet_directory_uri() . '/assets/js/wishlist.js',
        [],
        GORVITA_CHILD_VERSION,
        true
    );
    wp_localize_script('gorvita-wishlist', 'gorvitaWishlist', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gorvita_wishlist'),
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl' => wp_login_url(),
    ]);
}
add_action('wp_enqueue_scripts', 'gorvita_wishlist_scripts', 30);

/**
 * Shortcode [gorvita_wishlist] — lists user's wishlist.
 */
function gorvita_wishlist_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . sprintf(
            esc_html__('Zaloguj się, aby zobaczyć swoje ulubione produkty. %s', 'gorvita-child'),
            '<a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Zaloguj się', 'gorvita-child') . '</a>'
        ) . '</p>';
    }

    $ids = gorvita_get_wishlist();
    if (!$ids) {
        return '<p>' . esc_html__('Twoja lista ulubionych jest pusta.', 'gorvita-child') . '</p>';
    }

    ob_start();
    echo '<div class="woocommerce"><ul class="products columns-3">';
    foreach ($ids as $id) {
        $post_object = get_post($id);
        if (!$post_object) continue;
        setup_postdata($GLOBALS['post'] = $post_object);
        wc_get_template_part('content', 'product');
    }
    echo '</ul></div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('gorvita_wishlist', 'gorvita_wishlist_shortcode');
