<?php
/**
 * Gorvita child — WooCommerce product loop card override.
 * Adds LQIP lazy-load, sale-% badge, volume pill, wishlist heart.
 *
 * @package GorvitaChild
 * @version 1.0
 */

defined('ABSPATH') || exit;

global $product;
if (empty($product) || !$product->is_visible()) {
    return;
}

$product_id = $product->get_id();
$image_id   = $product->get_image_id();
$title      = get_the_title($product_id);
$permalink  = get_permalink($product_id);
$price_html = $product->get_price_html();

// Volume pill — attribute pa_pojemnosc
$volume = '';
$terms  = wc_get_product_terms($product_id, 'pa_pojemnosc', ['fields' => 'names']);
if (!empty($terms)) {
    $volume = $terms[0];
}

// LQIP placeholder — 1×1 transparent SVG
$lqip    = 'data:image/svg+xml,%3Csvg xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22 viewBox%3D%220 0 1 1%22%2F%3E';
$img_url = $image_id
    ? esc_url(wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail'))
    : esc_url(wc_placeholder_img_src('woocommerce_thumbnail'));

// Sale badge percentage
$sale_pct = 0;
if ($product->is_on_sale()) {
    $regular = (float) $product->get_regular_price();
    $sale    = (float) $product->get_sale_price();
    if ($regular > 0 && $sale >= 0) {
        $sale_pct = (int) round(($regular - $sale) / $regular * 100);
    }
}

// Wishlist state — integrates with inc/wishlist.php
$in_wishlist = function_exists('gorvita_is_in_wishlist') && gorvita_is_in_wishlist($product_id);
?>
<article id="product-<?php echo esc_attr($product_id); ?>" <?php wc_product_class('gv-product-card', $product); ?>>

    <div class="gv-thumb-wrap">

        <?php if ($sale_pct > 0) : ?>
            <div class="gv-sale-badge" aria-hidden="true">-<?php echo esc_html($sale_pct); ?>%</div>
        <?php endif; ?>

        <a class="gv-thumb-link" href="<?php echo esc_url($permalink); ?>" tabindex="-1" aria-hidden="true">
            <img
                class="gv-product-thumb lazy"
                src="<?php echo esc_attr($lqip); ?>"
                data-src="<?php echo esc_attr($img_url); ?>"
                alt="<?php echo esc_attr($title); ?>"
                width="600"
                height="600"
            >
        </a>

        <div class="gv-card-actions">
            <button
                class="gv-icon-btn gorvita-wishlist-btn<?php echo $in_wishlist ? ' is-active' : ''; ?>"
                data-product-id="<?php echo esc_attr($product_id); ?>"
                aria-pressed="<?php echo $in_wishlist ? 'true' : 'false'; ?>"
                aria-label="<?php echo esc_attr(sprintf(__('Dodaj "%s" do ulubionych', 'gorvita-child'), $title)); ?>"
                type="button"
            >
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
            </button>
        </div>

    </div><!-- .gv-thumb-wrap -->

    <div class="gv-card-body">

        <?php if ($product->get_review_count() > 0) : ?>
            <div class="gv-rating" aria-label="<?php echo esc_attr(sprintf(__('Ocena: %s na 5', 'gorvita-child'), $product->get_average_rating())); ?>">
                <?php echo wc_get_rating_html($product->get_average_rating(), $product->get_review_count()); ?>
            </div>
        <?php endif; ?>

        <h3 class="gv-card-title">
            <a href="<?php echo esc_url($permalink); ?>"><?php echo wp_kses_post(wp_trim_words($title, 10, '…')); ?></a>
        </h3>

        <?php if ($volume) : ?>
            <div class="gv-pills">
                <span class="gv-pill"><?php echo esc_html($volume); ?></span>
            </div>
        <?php endif; ?>

        <div class="gv-price-row">
            <?php echo wp_kses_post($price_html); ?>
        </div>

        <div class="gv-card-cta">
            <?php woocommerce_template_loop_add_to_cart(); ?>
        </div>

    </div><!-- .gv-card-body -->

</article>
