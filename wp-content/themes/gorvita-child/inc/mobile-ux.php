<?php
/**
 * Mobile UX enhancements — addresses findings from sylveco.pl UX audit.
 *
 * 1. Sticky mobile "Add to Cart" bar on PDP (Minor #4 from audit)
 * 2. Mobile breadcrumbs on PLP (Medium #4)
 * 3. Hide bottom navigation during checkout/cart flow (Critical #3)
 * 4. Touch targets ≥44x44px everywhere (Medium #3)
 * 5. Checkout progress bar (Critical #2)
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

/**
 * Render sticky bottom mobile navigation on all frontend pages EXCEPT checkout/cart.
 * Avoids the audit issue: "sticky bottom nav zasłania content w checkout".
 */
function gorvita_render_mobile_bottom_nav() {
    // Skip on admin, AJAX, login, checkout, cart
    if (is_admin() || wp_doing_ajax()) return;
    if (function_exists('is_checkout') && (is_checkout() || is_cart())) return;

    $cart_count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/sklep/');
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/koszyk/');
    $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/moje-konto/');
    ?>
    <nav class="gorvita-bottom-nav" aria-label="<?php esc_attr_e('Nawigacja mobilna', 'gorvita-child'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="gorvita-bottom-nav__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5Z"/></svg>
            <span><?php esc_html_e('Start', 'gorvita-child'); ?></span>
        </a>
        <a href="<?php echo esc_url($shop_url); ?>" class="gorvita-bottom-nav__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            <span><?php esc_html_e('Sklep', 'gorvita-child'); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/cbd/')); ?>" class="gorvita-bottom-nav__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2c2 4 2 8 0 12-2-4-2-8 0-12Z"/><path d="M8 10c-3 1-5 4-5 8 4 0 7-2 8-5"/><path d="M16 10c3 1 5 4 5 8-4 0-7-2-8-5"/></svg>
            <span>CBD</span>
        </a>
        <a href="<?php echo esc_url($cart_url); ?>" class="gorvita-bottom-nav__item gorvita-bottom-nav__item--cart" data-cart-link>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3h2l2.4 11.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L22 6H6"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
            <span><?php esc_html_e('Koszyk', 'gorvita-child'); ?></span>
            <span class="gorvita-bottom-nav__badge gorvita-cart-count" data-count="<?php echo esc_attr($cart_count); ?>" <?php if ($cart_count === 0) echo 'hidden'; ?>>
                <?php echo esc_html($cart_count); ?>
            </span>
        </a>
        <a href="<?php echo esc_url($account_url); ?>" class="gorvita-bottom-nav__item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            <span><?php esc_html_e('Konto', 'gorvita-child'); ?></span>
        </a>
    </nav>
    <?php
}
add_action('wp_footer', 'gorvita_render_mobile_bottom_nav', 5);

/**
 * Sticky mobile "Add to Cart" bar on single product pages.
 */
function gorvita_render_mobile_sticky_atc() {
    if (!function_exists('is_product') || !is_product()) return;
    global $product;
    if (!$product || !$product->is_purchasable()) return;
    ?>
    <div class="gorvita-sticky-atc" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
        <div class="gorvita-sticky-atc__info">
            <span class="gorvita-sticky-atc__title"><?php echo esc_html(wp_trim_words($product->get_name(), 6)); ?></span>
            <span class="gorvita-sticky-atc__price"><?php echo $product->get_price_html(); ?></span>
        </div>
        <form class="cart gorvita-sticky-atc__form" method="post" enctype="multipart/form-data">
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="button gorvita-sticky-atc__btn">
                <?php esc_html_e('Do koszyka', 'gorvita-child'); ?>
            </button>
        </form>
    </div>
    <?php
}
add_action('wp_footer', 'gorvita_render_mobile_sticky_atc');

/**
 * Checkout progress indicator — shows steps: Cart → Details → Shipping → Payment.
 */
function gorvita_render_checkout_progress() {
    if (!function_exists('is_checkout') || !is_checkout()) return;
    if (is_wc_endpoint_url('order-received')) return;
    ?>
    <ol class="gorvita-checkout-progress" aria-label="<?php esc_attr_e('Etap zamówienia', 'gorvita-child'); ?>">
        <li class="gorvita-checkout-progress__step gorvita-checkout-progress__step--done">
            <span class="gorvita-checkout-progress__num" aria-hidden="true">1</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Koszyk', 'gorvita-child'); ?></span>
        </li>
        <li class="gorvita-checkout-progress__step gorvita-checkout-progress__step--current">
            <span class="gorvita-checkout-progress__num" aria-hidden="true">2</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Dane i dostawa', 'gorvita-child'); ?></span>
        </li>
        <li class="gorvita-checkout-progress__step">
            <span class="gorvita-checkout-progress__num" aria-hidden="true">3</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Płatność', 'gorvita-child'); ?></span>
        </li>
        <li class="gorvita-checkout-progress__step">
            <span class="gorvita-checkout-progress__num" aria-hidden="true">4</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Gotowe', 'gorvita-child'); ?></span>
        </li>
    </ol>
    <?php
}
add_action('woocommerce_before_checkout_form', 'gorvita_render_checkout_progress', 5);

/**
 * Order received page: show progress as complete.
 */
function gorvita_render_order_received_progress() {
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) return;
    ?>
    <ol class="gorvita-checkout-progress gorvita-checkout-progress--complete">
        <li class="gorvita-checkout-progress__step gorvita-checkout-progress__step--done">
            <span class="gorvita-checkout-progress__num">1</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Koszyk', 'gorvita-child'); ?></span>
        </li>
        <li class="gorvita-checkout-progress__step gorvita-checkout-progress__step--done">
            <span class="gorvita-checkout-progress__num">2</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Dane', 'gorvita-child'); ?></span>
        </li>
        <li class="gorvita-checkout-progress__step gorvita-checkout-progress__step--done">
            <span class="gorvita-checkout-progress__num">3</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Płatność', 'gorvita-child'); ?></span>
        </li>
        <li class="gorvita-checkout-progress__step gorvita-checkout-progress__step--done">
            <span class="gorvita-checkout-progress__num">✓</span>
            <span class="gorvita-checkout-progress__label"><?php esc_html_e('Gotowe', 'gorvita-child'); ?></span>
        </li>
    </ol>
    <?php
}
add_action('woocommerce_before_thankyou', 'gorvita_render_order_received_progress');

/**
 * Shipping cost hint in cart — addresses Critical #1 from audit.
 */
function gorvita_render_shipping_hint() {
    if (!function_exists('is_cart') || !is_cart()) return;
    $free_shipping_threshold = 149;
    $current = WC()->cart->get_subtotal();
    $missing = $free_shipping_threshold - $current;
    ?>
    <div class="gorvita-shipping-hint">
        <?php if ($missing > 0): ?>
            <strong><?php esc_html_e('Dostawa:', 'gorvita-child'); ?></strong>
            <?php printf(
                esc_html__('kurier od 14,99 zł. Dodaj produkty za %s aby otrzymać darmową dostawę.', 'gorvita-child'),
                '<strong>' . wc_price($missing) . '</strong>'
            ); ?>
        <?php else: ?>
            <strong style="color:var(--gorvita-success)"><?php esc_html_e('✓ Darmowa dostawa!', 'gorvita-child'); ?></strong>
        <?php endif; ?>
    </div>
    <?php
}
add_action('woocommerce_before_cart_table', 'gorvita_render_shipping_hint', 5);

/**
 * Mobile breadcrumb "back to category" link on product pages.
 */
function gorvita_render_mobile_back_link() {
    if (!function_exists('is_product') || !is_product()) return;
    global $post;
    $cats = get_the_terms($post->ID, 'product_cat');
    if (!$cats || is_wp_error($cats)) return;
    $parent_cat = $cats[0];
    // Prefer top-level category
    while ($parent_cat->parent) {
        $parent = get_term($parent_cat->parent, 'product_cat');
        if (is_wp_error($parent) || !$parent) break;
        $parent_cat = $parent;
    }
    ?>
    <a href="<?php echo esc_url(get_term_link($parent_cat)); ?>" class="gorvita-mobile-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
        <?php printf(esc_html__('Wróć do: %s', 'gorvita-child'), esc_html($parent_cat->name)); ?>
    </a>
    <?php
}
add_action('woocommerce_before_single_product', 'gorvita_render_mobile_back_link', 5);

/**
 * Body classes for conditional styling (hide mobile nav on checkout etc).
 */
add_filter('body_class', function ($classes) {
    if (function_exists('is_checkout') && is_checkout()) $classes[] = 'gorvita-no-mobile-nav';
    if (function_exists('is_cart') && is_cart()) $classes[] = 'gorvita-no-mobile-nav';
    if (function_exists('is_product') && is_product()) $classes[] = 'gorvita-has-sticky-atc';
    return $classes;
});
