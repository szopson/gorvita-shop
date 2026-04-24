<?php
/**
 * Live product search overlay.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

/* ---------------------------------------------------------------
   AJAX: product search
--------------------------------------------------------------- */

add_action('wp_ajax_gorvita_search',        'gorvita_search_ajax');
add_action('wp_ajax_nopriv_gorvita_search', 'gorvita_search_ajax');

function gorvita_search_ajax(): void {
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'] ?? '')), 'gorvita_search_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    $query   = sanitize_text_field(wp_unslash($_GET['q']       ?? ''));
    $context = sanitize_text_field(wp_unslash($_GET['context'] ?? ''));

    if (strlen($query) < 2) {
        wp_send_json_success(['products' => []]);
        return;
    }

    $results     = [];
    $context_ids = [];

    // Phase 1: contextual results (category match, no text filter)
    if ($context) {
        $ctx = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
            'meta_query'     => [['key' => '_stock_status', 'value' => 'instock']],
            'tax_query'      => [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $context,
            ]],
        ]);
        foreach ($ctx->posts as $post) {
            $p = wc_get_product($post->ID);
            if ($p) {
                $results[]     = gorvita_format_search_product($p);
                $context_ids[] = $post->ID;
            }
        }
        wp_reset_postdata();
    }

    // Phase 2: text search, excluding context results
    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $context_ids ? 3 : 6,
        's'              => $query,
        'meta_query'     => [['key' => '_stock_status', 'value' => 'instock']],
    ];
    if ($context_ids) {
        $args['post__not_in'] = $context_ids;
    }

    $loop = new WP_Query($args);
    while ($loop->have_posts()) {
        $loop->the_post();
        $p = wc_get_product(get_the_ID());
        if ($p) $results[] = gorvita_format_search_product($p);
    }
    wp_reset_postdata();

    wp_send_json_success(['products' => $results]);
}

function gorvita_format_search_product(WC_Product $product): array {
    $image_id  = $product->get_image_id();
    $image_url = $image_id
        ? wp_get_attachment_image_url($image_id, 'thumbnail')
        : wc_placeholder_img_src('thumbnail');

    $cats  = wp_get_post_terms($product->get_id(), 'product_cat', ['number' => 1, 'fields' => 'all']);
    $badge = get_post_meta($product->get_id(), '_gorvita_badge', true);
    if (!$badge && $product->is_on_sale()) $badge = 'sale';

    return [
        'name'     => $product->get_name(),
        'url'      => get_permalink($product->get_id()),
        'price'    => wp_strip_all_tags($product->get_price_html()),
        'image'    => esc_url($image_url ?: ''),
        'category' => ($cats && !is_wp_error($cats)) ? esc_html($cats[0]->name) : '',
        'badge'    => $badge,
    ];
}

/* ---------------------------------------------------------------
   Context detection: pass to JS so search can boost results
--------------------------------------------------------------- */

function gorvita_get_search_context(): string {
    if (is_product_category()) {
        $term = get_queried_object();
        return $term instanceof WP_Term ? $term->slug : '';
    }
    if (is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        $map  = ['cbd' => 'cbd', 'bestsellery' => 'bestsellery', 'promocje' => 'sale'];
        return $map[$slug] ?? '';
    }
    return '';
}

/* ---------------------------------------------------------------
   Enqueue search assets (all pages)
--------------------------------------------------------------- */

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'gorvita-search',
        GORVITA_CHILD_URI . '/assets/css/search.css',
        ['gorvita-child'],
        GORVITA_CHILD_VERSION
    );
    wp_enqueue_script(
        'gorvita-search',
        GORVITA_CHILD_URI . '/assets/js/search.js',
        [],
        GORVITA_CHILD_VERSION,
        true
    );
    wp_localize_script('gorvita-search', 'gorvitaSearchCfg', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gorvita_search_nonce'),
        'context' => gorvita_get_search_context(),
        'i18n'    => [
            'placeholder' => __('Szukaj produktów, składników…', 'gorvita-child'),
            'popular'     => __('Najczęściej szukane', 'gorvita-child'),
            'noResults'   => __('Brak wyników dla', 'gorvita-child'),
            'cancel'      => __('Anuluj', 'gorvita-child'),
            'viewAll'     => __('Zobacz wszystkie wyniki', 'gorvita-child'),
        ],
    ]);
}, 25);

/* ---------------------------------------------------------------
   Overlay HTML — rendered in footer
--------------------------------------------------------------- */

add_action('wp_footer', function () {
    $chips = [
        ['CBD',        '/sklep/?s=cbd'],
        ['Stawy',      '/sklep/?s=stawy'],
        ['Odporność',  '/sklep/?s=odpornosc'],
        ['Skóra',      '/sklep/?s=skora'],
        ['Energia',    '/sklep/?s=energia'],
        ['Wątroba',    '/sklep/?s=watroba'],
    ];
    ?>
    <div class="gorvita-search" id="gorvitaSearch" role="dialog" aria-modal="true"
         aria-label="<?php esc_attr_e('Wyszukaj produkty', 'gorvita-child'); ?>" hidden>
        <div class="gorvita-search__backdrop" id="gorvitaSearchBackdrop"></div>
        <div class="gorvita-search__panel">

            <!-- Search bar -->
            <div class="gorvita-search__bar">
                <svg class="gorvita-search__bar-icon" width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <line x1="16.5" y1="16.5" x2="22" y2="22"/>
                </svg>
                <input class="gorvita-search__input" id="gorvitaSearchInput"
                       type="search" autocomplete="off" spellcheck="false"
                       aria-label="<?php esc_attr_e('Szukaj', 'gorvita-child'); ?>">
                <button class="gorvita-search__clear" id="gorvitaSearchClear" hidden
                        aria-label="<?php esc_attr_e('Wyczyść', 'gorvita-child'); ?>">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
                <button class="gorvita-search__cancel" id="gorvitaSearchClose">
                    <?php esc_html_e('Anuluj', 'gorvita-child'); ?>
                </button>
            </div>

            <!-- Body: suggestions / results / empty -->
            <div class="gorvita-search__body">

                <div class="gorvita-search__suggestions" id="gorvitaSearchSuggestions">
                    <p class="gorvita-search__section-label">
                        <?php esc_html_e('Najczęściej szukane', 'gorvita-child'); ?>
                    </p>
                    <div class="gorvita-search__chips">
                        <?php foreach ($chips as [$label, $url]): ?>
                        <a href="<?php echo esc_url($url); ?>" class="gorvita-search__chip">
                            <?php echo esc_html($label); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="gorvita-search__results" id="gorvitaSearchResults" hidden></div>

                <p class="gorvita-search__no-results" id="gorvitaSearchEmpty" hidden>
                    <?php esc_html_e('Brak wyników', 'gorvita-child'); ?>
                </p>

                <div class="gorvita-search__loader" id="gorvitaSearchLoader" hidden>
                    <span class="gorvita-search__spinner"></span>
                </div>

            </div>
        </div>
    </div>
    <?php
}, 20);
