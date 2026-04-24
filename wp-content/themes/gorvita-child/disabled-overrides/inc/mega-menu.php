<?php
/**
 * Mega menu panel for the shop navigation item.
 *
 * Architecture:
 * - Panel HTML is pre-rendered in wp_footer (not inline in nav markup)
 *   so we never touch Blocksy's Walker_Nav_Menu.
 * - JS finds the desktop "Sklep" nav link and positions the panel below it.
 * - On mobile (≤ 860 px) the panel is never shown — Blocksy's accordion works.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

/* ---------------------------------------------------------------
   Category config — adjust slugs if needed after import
--------------------------------------------------------------- */

function gorvita_mega_categories(): array {
    return [
        ['slug' => 'stawy-miesnie',       'label' => 'Stawy i mięśnie',       'icon' => '💪'],
        ['slug' => 'skora-cialo',         'label' => 'Skóra i ciało',          'icon' => '🌿'],
        ['slug' => 'odpornosc',           'label' => 'Odporność',              'icon' => '🛡'],
        ['slug' => 'watroba-trawienie',   'label' => 'Wątroba i trawienie',    'icon' => '🌱'],
        ['slug' => 'krazenie',            'label' => 'Krążenie',               'icon' => '❤️'],
        ['slug' => 'energia-stres',       'label' => 'Energia i stres',        'icon' => '⚡'],
        ['slug' => 'nos-gardlo-jama-ustna','label' => 'Nos, gardło, jama ustna','icon' => '🌬'],
        ['slug' => 'cbd-konopie',         'label' => 'CBD / Konopie',          'icon' => '🌾'],
    ];
}

/* ---------------------------------------------------------------
   Featured products for the right column
--------------------------------------------------------------- */

function gorvita_mega_featured_products(): array {
    if (!function_exists('wc_get_product')) return [];

    $query = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'orderby'        => 'meta_value',
        'meta_key'       => 'total_sales',
        'order'          => 'DESC',
        'meta_query'     => [['key' => '_stock_status', 'value' => 'instock']],
    ]);

    $products = [];
    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if (!$product) continue;

        $image_id  = $product->get_image_id();
        $image_url = $image_id
            ? wp_get_attachment_image_url($image_id, [80, 80])
            : wc_placeholder_img_src([80, 80]);

        $badge = get_post_meta($post->ID, '_gorvita_badge', true);
        if (!$badge && $product->is_on_sale()) $badge = 'sale';

        $products[] = [
            'name'  => $product->get_name(),
            'url'   => get_permalink($post->ID),
            'price' => wp_strip_all_tags($product->get_price_html()),
            'image' => esc_url($image_url ?: ''),
            'badge' => $badge,
        ];
    }
    wp_reset_postdata();
    return $products;
}

/* ---------------------------------------------------------------
   Render panel HTML in footer
--------------------------------------------------------------- */

add_action('wp_footer', function () {
    $categories = gorvita_mega_categories();
    $products   = gorvita_mega_featured_products();
    $shop_url   = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : '/sklep/';

    $badge_map = [
        'sale' => ['Promocja',  'sale'],
        'best' => ['Bestseller','best'],
        'new'  => ['Nowość',    'new'],
        'cbd'  => ['CBD Gold',  'cbd'],
    ];
    ?>
    <div class="gorvita-mega" id="gorvitaMega" hidden aria-hidden="true">
        <div class="gorvita-mega__inner">

            <!-- Left: Categories -->
            <nav class="gorvita-mega__cats" aria-label="<?php esc_attr_e('Kategorie produktów', 'gorvita-child'); ?>">
                <p class="gorvita-mega__section-label"><?php esc_html_e('Kategorie', 'gorvita-child'); ?></p>
                <ul class="gorvita-mega__cat-list">
                    <?php foreach ($categories as $cat):
                        $term_link = get_term_link($cat['slug'], 'product_cat');
                        $cat_url   = is_wp_error($term_link) ? ($shop_url . 'kategoria/' . $cat['slug'] . '/') : $term_link;
                    ?>
                    <li>
                        <a class="gorvita-mega__cat-link"
                           href="<?php echo esc_url($cat_url); ?>">
                            <span class="gorvita-mega__cat-icon" aria-hidden="true"><?php echo $cat['icon']; ?></span>
                            <?php echo esc_html($cat['label']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a class="gorvita-mega__cat-all" href="<?php echo esc_url($shop_url); ?>">
                    <?php esc_html_e('Wszystkie produkty', 'gorvita-child'); ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </nav>

            <!-- Right: Featured products -->
            <?php if ($products): ?>
            <div class="gorvita-mega__featured">
                <p class="gorvita-mega__section-label"><?php esc_html_e('Najchętniej wybierane', 'gorvita-child'); ?></p>
                <div class="gorvita-mega__products">
                    <?php foreach ($products as $p):
                        [$badge_label, $badge_mod] = $badge_map[$p['badge']] ?? ['', ''];
                    ?>
                    <a class="gorvita-mega__product" href="<?php echo esc_url($p['url']); ?>">
                        <img class="gorvita-mega__product-img"
                             src="<?php echo esc_url($p['image']); ?>"
                             alt="<?php echo esc_attr($p['name']); ?>"
                             width="72" height="72" loading="lazy">
                        <div class="gorvita-mega__product-meta">
                            <p class="gorvita-mega__product-name"><?php echo esc_html($p['name']); ?></p>
                            <div class="gorvita-mega__product-bottom">
                                <span class="gorvita-mega__product-price"><?php echo esc_html($p['price']); ?></span>
                                <?php if ($badge_label): ?>
                                <span class="gorvita-mega__product-badge gorvita-mega__product-badge--<?php echo esc_attr($badge_mod); ?>">
                                    <?php echo esc_html($badge_label); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php
}, 25);

/* ---------------------------------------------------------------
   Enqueue assets (all pages — needed for header)
--------------------------------------------------------------- */

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'gorvita-mega-menu',
        GORVITA_CHILD_URI . '/assets/css/mega-menu.css',
        ['gorvita-child'],
        GORVITA_CHILD_VERSION
    );
    wp_enqueue_script(
        'gorvita-mega-menu',
        GORVITA_CHILD_URI . '/assets/js/mega-menu.js',
        [],
        GORVITA_CHILD_VERSION,
        true
    );
}, 25);
