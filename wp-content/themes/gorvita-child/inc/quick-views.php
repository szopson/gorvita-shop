<?php
/**
 * Shortcode helpers for curated product listings (Nowości, Bestsellery,
 * Promocje, Polecane). Used on dedicated landing pages.
 *
 * Renders using WooCommerce's native `[products]` shortcode under the hood
 * so product card markup stays consistent everywhere.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

/**
 * [gorvita_new_products limit="12" days="30"] — products published in the
 * last N days.
 */
function gorvita_new_products_shortcode($atts) {
    $a = shortcode_atts([
        'limit' => 12,
        'columns' => 4,
        'days' => 30,
    ], $atts);

    $since = gmdate('Y-m-d 00:00:00', strtotime("-{$a['days']} days"));
    $sc = sprintf(
        '[products limit="%d" columns="%d" orderby="date" order="DESC" visibility="visible" date_query=\'{"after":"%s"}\']',
        (int) $a['limit'], (int) $a['columns'], esc_attr($since)
    );

    // WC shortcode doesn't support date_query directly; use a filter instead
    add_filter('woocommerce_shortcode_products_query', 'gorvita_filter_new_only_query', 10, 2);
    $GLOBALS['gorvita_new_products_since'] = $since;
    $out = do_shortcode(sprintf(
        '[products limit="%d" columns="%d" orderby="date" order="DESC" visibility="visible"]',
        (int) $a['limit'], (int) $a['columns']
    ));
    remove_filter('woocommerce_shortcode_products_query', 'gorvita_filter_new_only_query', 10);
    return $out;
}
add_shortcode('gorvita_new_products', 'gorvita_new_products_shortcode');

function gorvita_filter_new_only_query($args, $atts) {
    if (!empty($GLOBALS['gorvita_new_products_since'])) {
        $args['date_query'] = [['after' => $GLOBALS['gorvita_new_products_since']]];
    }
    return $args;
}

/**
 * [gorvita_bestsellers limit="12"] — products ordered by total_sales.
 */
function gorvita_bestsellers_shortcode($atts) {
    $a = shortcode_atts([
        'limit' => 12,
        'columns' => 4,
    ], $atts);

    return do_shortcode(sprintf(
        '[products limit="%d" columns="%d" orderby="popularity" order="DESC" visibility="visible"]',
        (int) $a['limit'], (int) $a['columns']
    ));
}
add_shortcode('gorvita_bestsellers', 'gorvita_bestsellers_shortcode');

/**
 * [gorvita_sale_products limit="12"] — products with a sale price.
 */
function gorvita_sale_products_shortcode($atts) {
    $a = shortcode_atts([
        'limit' => 12,
        'columns' => 4,
    ], $atts);

    return do_shortcode(sprintf(
        '[products limit="%d" columns="%d" on_sale="true" orderby="popularity" order="DESC" visibility="visible"]',
        (int) $a['limit'], (int) $a['columns']
    ));
}
add_shortcode('gorvita_sale_products', 'gorvita_sale_products_shortcode');

/**
 * [gorvita_featured_products limit="12"] — products marked featured.
 */
function gorvita_featured_products_shortcode($atts) {
    $a = shortcode_atts([
        'limit' => 12,
        'columns' => 4,
    ], $atts);

    return do_shortcode(sprintf(
        '[products limit="%d" columns="%d" visibility="featured" orderby="popularity" order="DESC"]',
        (int) $a['limit'], (int) $a['columns']
    ));
}
add_shortcode('gorvita_featured_products', 'gorvita_featured_products_shortcode');

/**
 * [gorvita_category_tiles] — homepage block: 8 Potrzeby categories with
 * icon + name + product count.
 */
function gorvita_category_tiles_shortcode($atts) {
    $cats = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'exclude' => [get_option('default_product_cat')],
        'orderby' => 'meta_value_num',
        'meta_key' => 'order',
        'order' => 'ASC',
    ]);
    if (is_wp_error($cats) || !$cats) return '';

    $html = '<div class="gorvita-category-tiles">';
    foreach ($cats as $cat) {
        $icon = get_term_meta($cat->term_id, 'gorvita_icon', true);
        $url = esc_url(get_term_link($cat));
        $count = (int) $cat->count;
        $html .= sprintf(
            '<a class="gorvita-category-tile" href="%s">
                <span class="gorvita-category-tile__icon" aria-hidden="true">%s</span>
                <span class="gorvita-category-tile__name">%s</span>
                <span class="gorvita-category-tile__count">%d %s</span>
            </a>',
            $url,
            esc_html($icon ?: '•'),
            esc_html($cat->name),
            $count,
            esc_html(_n('produkt', 'produktów', $count, 'gorvita-child'))
        );
    }
    $html .= '</div>';
    return $html;
}
add_shortcode('gorvita_category_tiles', 'gorvita_category_tiles_shortcode');
