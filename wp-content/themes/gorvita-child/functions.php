<?php
require_once get_stylesheet_directory() . '/inc/translations.php';
require_once get_stylesheet_directory() . '/inc/seo-schema.php';
require_once get_stylesheet_directory() . '/inc/cart-shipping-progress.php';
require_once get_stylesheet_directory() . '/inc/checkout-cta-mover.php';
require_once get_stylesheet_directory() . '/inc/product-description-cleanup.php';
require_once get_stylesheet_directory() . '/inc/b2b-announcement-bar.php';
require_once get_stylesheet_directory() . '/inc/free-shipping-equalize.php';

// Block staging from search-engine indexation. Belt-and-suspenders alongside
// the Traefik X-Robots-Tag header. Active for any host that is NOT the
// production sklep.gorvita.pl. Remove or invert this check at go-live.
function gorvita_staging_noindex() {
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( $_SERVER['HTTP_HOST'] ) : '';
    $is_production = ( $host === 'sklep.gorvita.pl' || $host === 'www.gorvita.pl' );
    if ( ! $is_production ) {
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">' . "\n";
    }
}
add_action( 'wp_head', 'gorvita_staging_noindex', 0 );

function gorvita_preload_hero() {
    echo '<link rel="preload" as="image" href="' . esc_url( get_stylesheet_directory_uri() . '/assets/images/gorce2.webp' ) . '" fetchpriority="high">' . "\n";
}
add_action( 'wp_head', 'gorvita_preload_hero', 1 );

/**
 * Google Tag Manager — container GTM-W9L2RVMZ.
 * GA4 measurement ID G-929B3GEFXW is configured inside the container.
 */
define( 'GORVITA_GTM_ID', 'GTM-W9L2RVMZ' );

function gorvita_gtm_head() {
    ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( GORVITA_GTM_ID ); ?>');</script>
<!-- End Google Tag Manager -->
    <?php
}
add_action( 'wp_head', 'gorvita_gtm_head', 2 );

function gorvita_gtm_body() {
    ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( GORVITA_GTM_ID ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <?php
}
add_action( 'wp_body_open', 'gorvita_gtm_body' );

function gorvita_dl_view_item() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }
    global $product;
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product ) {
        return;
    }
    $cats     = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
    $category = ( is_array( $cats ) && ! empty( $cats ) ) ? $cats[0] : '';
    ?>
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({ ecommerce: null });
window.dataLayer.push({
  event: 'view_item',
  ecommerce: {
    currency: '<?php echo esc_js( get_woocommerce_currency() ); ?>',
    value: <?php echo (float) $product->get_price(); ?>,
    items: [{
      item_id: '<?php echo esc_js( $product->get_sku() ?: $product->get_id() ); ?>',
      item_name: <?php echo wp_json_encode( $product->get_name() ); ?>,
      price: <?php echo (float) $product->get_price(); ?>,
      item_category: <?php echo wp_json_encode( $category ); ?>,
      quantity: 1
    }]
  }
});
</script>
    <?php
}
add_action( 'wp_footer', 'gorvita_dl_view_item' );

function gorvita_dl_add_to_cart_listener() {
    if ( ! function_exists( 'is_woocommerce' ) ) {
        return;
    }
    ?>
<script>
(function(){
  if (typeof jQuery === 'undefined') return;
  jQuery(document.body).on('added_to_cart', function(e, fragments, cart_hash, $button){
    if (!$button || !$button.length) return;
    var productId = $button.data('product_id');
    var qty = parseInt($button.data('quantity'), 10) || 1;
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({
      event: 'add_to_cart',
      ecommerce: {
        items: [{ item_id: String(productId), quantity: qty }]
      }
    });
  });
})();
</script>
    <?php
}
add_action( 'wp_footer', 'gorvita_dl_add_to_cart_listener' );

function gorvita_dl_purchase( $order_id ) {
    if ( ! $order_id ) {
        return;
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    $items = [];
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) {
            continue;
        }
        $items[] = [
            'item_id'   => $product->get_sku() ?: $product->get_id(),
            'item_name' => $item->get_name(),
            'price'     => (float) $order->get_item_total( $item, false, false ),
            'quantity'  => (int) $item->get_quantity(),
        ];
    }

    $payload = [
        'event'     => 'purchase',
        'ecommerce' => [
            'transaction_id' => (string) $order->get_id(),
            'value'          => (float) $order->get_total(),
            'tax'            => (float) $order->get_total_tax(),
            'shipping'       => (float) $order->get_shipping_total(),
            'currency'       => $order->get_currency(),
            'items'          => $items,
        ],
    ];
    ?>
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({ ecommerce: null });
window.dataLayer.push(<?php echo wp_json_encode( $payload ); ?>);
</script>
    <?php
}
add_action( 'woocommerce_thankyou', 'gorvita_dl_purchase', 10, 1 );

/**
 * Polish NIP validation for B2BKing registration.
 * The VAT field is a B2BKing custom field (post_type=b2bking_custom_field);
 * its ID lives in option `b2bking_vat_initial_field_id_setting` and B2BKing
 * reads it from $_POST as `field_<id>` (see plugin class-b2bking-public.php).
 * We only validate when a 10-digit value is entered, so non-PL VAT formats
 * (DE/IT/etc.) submitted into the same field still pass through B2BKing's
 * native VIES check.
 */
function gorvita_format_nip( $value ) {
    $digits = preg_replace( '/[^0-9]/', '', (string) $value );
    if ( strlen( $digits ) !== 10 ) {
        return (string) $value;
    }
    return substr( $digits, 0, 3 ) . '-' . substr( $digits, 3, 3 ) . '-' . substr( $digits, 6, 2 ) . '-' . substr( $digits, 8, 2 );
}

function gorvita_is_valid_polish_nip( $value ) {
    $nip = preg_replace( '/[^0-9]/', '', (string) $value );
    if ( strlen( $nip ) !== 10 ) {
        return false;
    }
    $weights = [ 6, 5, 7, 2, 3, 4, 5, 6, 7 ];
    $sum     = 0;
    for ( $i = 0; $i < 9; $i++ ) {
        $sum += (int) $nip[ $i ] * $weights[ $i ];
    }
    $check = $sum % 11;
    return ( $check !== 10 && $check === (int) $nip[9] );
}

function gorvita_get_nip_field_ids() {
    static $ids = null;
    if ( $ids !== null ) {
        return $ids;
    }
    $ids = [];
    // Primary: B2BKing's "VAT" field (settings-mapped) is most reliable when
    // a Polish NIP is entered into a single multi-country VAT field.
    $vat_id = (int) get_option( 'b2bking_vat_initial_field_id_setting' );
    if ( $vat_id ) {
        $ids[] = $vat_id;
    }
    // Also include any B2BKing custom fields explicitly named/slugged "nip"
    // or mapped to "NIP" (covers extra B2B-only registration fields).
    $extras = get_posts( [
        'post_type'      => 'b2bking_custom_field',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'fields'         => 'ids',
        'name'           => 'nip',
    ] );
    foreach ( $extras as $eid ) {
        if ( ! in_array( $eid, $ids, true ) ) {
            $ids[] = (int) $eid;
        }
    }
    return $ids;
}

function gorvita_validate_polish_nip( $errors, $username = '', $email = '' ) {
    $field_ids = gorvita_get_nip_field_ids();
    if ( empty( $field_ids ) ) {
        return $errors;
    }
    foreach ( $field_ids as $fid ) {
        // B2BKing reads custom fields from POST as `field_<id>` during registration
        // (see plugin class-b2bking-public.php:12193).
        $post_key = 'field_' . $fid;
        if ( empty( $_POST[ $post_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            continue;
        }
        $raw    = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $digits = preg_replace( '/[^0-9]/', '', $raw );

        // Only enforce mod-11 for exactly 10 digits (Polish NIP shape).
        // Other-country VAT numbers (different lengths) skip this check.
        if ( strlen( $digits ) !== 10 ) {
            continue;
        }
        if ( ! gorvita_is_valid_polish_nip( $digits ) && is_wp_error( $errors ) ) {
            $errors->add(
                'gorvita_invalid_nip',
                '<strong>Błąd:</strong> Podany numer NIP jest nieprawidłowy. Sprawdź czy wpisałeś poprawny 10-cyfrowy NIP.'
            );
            return $errors; // fail fast — one error per submission is enough
        }
    }
    return $errors;
}
add_filter( 'woocommerce_process_registration_errors', 'gorvita_validate_polish_nip', 20, 3 );
add_filter( 'woocommerce_registration_errors', 'gorvita_validate_polish_nip', 20, 3 );

/**
 * Show formatted NIP (XXX-XXX-XX-XX) on the admin user-edit screen.
 * Display only — the raw value in user_meta stays untouched.
 */
function gorvita_admin_show_formatted_nip( $user ) {
    if ( ! current_user_can( 'edit_users' ) ) {
        return;
    }
    $rows = [];
    foreach ( gorvita_get_nip_field_ids() as $fid ) {
        $meta_key = apply_filters( 'b2bking_custom_field_meta', 'b2bking_custom_field_' . $fid );
        $raw      = get_user_meta( $user->ID, $meta_key, true );
        if ( empty( $raw ) ) {
            continue;
        }
        $formatted = gorvita_format_nip( $raw );
        if ( $formatted === (string) $raw ) {
            continue;
        }
        $rows[] = [ 'fid' => $fid, 'raw' => $raw, 'fmt' => $formatted ];
    }
    if ( empty( $rows ) ) {
        return;
    }
    ?>
    <h3>NIP (sformatowany podgląd)</h3>
    <table class="form-table">
        <?php foreach ( $rows as $r ) : ?>
        <tr>
            <th><label>Sformatowany NIP (pole #<?php echo (int) $r['fid']; ?>)</label></th>
            <td>
                <code style="font-size:14px;"><?php echo esc_html( $r['fmt'] ); ?></code>
                <p class="description">Tylko podgląd. Wartość w bazie: <code><?php echo esc_html( $r['raw'] ); ?></code></p>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php
}
add_action( 'show_user_profile', 'gorvita_admin_show_formatted_nip' );
add_action( 'edit_user_profile', 'gorvita_admin_show_formatted_nip' );

function gorvita_theme_setup() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'gorvita_theme_setup' );

function gorvita_enqueue_styles() {
    $css_path = get_stylesheet_directory() . '/style.css';
    $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : null;
    wp_enqueue_style( 'gorvita-child-style', get_stylesheet_uri(), [], $css_ver );

    $b2b_path = get_stylesheet_directory() . '/css/b2b-banner.css';
    if ( file_exists( $b2b_path ) ) {
        wp_enqueue_style(
            'gorvita-b2b-banner',
            get_stylesheet_directory_uri() . '/css/b2b-banner.css',
            [ 'gorvita-child-style' ],
            filemtime( $b2b_path )
        );
    }

    $js_path = get_stylesheet_directory() . '/assets/js/animations.js';
    $js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : null;
    wp_enqueue_script( 'gorvita-animations', get_stylesheet_directory_uri() . '/assets/js/animations.js', [], $js_ver, true );
}
add_action( 'wp_enqueue_scripts', 'gorvita_enqueue_styles' );

function gorvita_enqueue_b2b_registration_toggle() {
    // The Blocksy account modal exposes the B2BKing register form from any
    // page header, so we cannot scope this to is_account_page() alone.
    // Skip only for logged-in users — they never see the register form.
    if ( is_user_logged_in() ) {
        return;
    }
    $rel  = '/assets/js/b2b-registration-toggle.js';
    $path = get_stylesheet_directory() . $rel;
    if ( ! file_exists( $path ) ) {
        return;
    }
    wp_enqueue_script(
        'gorvita-b2b-registration-toggle',
        get_stylesheet_directory_uri() . $rel,
        [],
        filemtime( $path ),
        true
    );
}
add_action( 'wp_enqueue_scripts', 'gorvita_enqueue_b2b_registration_toggle' );

function gorvita_enqueue_o_marce_assets() {
    if ( ! is_page( 119 ) && ! is_front_page() ) {
        return;
    }

    // Cormorant Garamond — shared by /o-marce/ (v6.26) and home (v6.29).
    wp_enqueue_style(
        'gorvita-cormorant',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&display=swap',
        [],
        null
    );

    if ( is_page( 119 ) ) {
        $path = get_stylesheet_directory() . '/css/o-marce.css';
        if ( file_exists( $path ) ) {
            wp_enqueue_style(
                'gorvita-omarce',
                get_stylesheet_directory_uri() . '/css/o-marce.css',
                [ 'gorvita-child-style' ],
                filemtime( $path )
            );
        }
    }

    if ( is_front_page() ) {
        $path = get_stylesheet_directory() . '/css/home-headings.css';
        if ( file_exists( $path ) ) {
            wp_enqueue_style(
                'gorvita-home-headings',
                get_stylesheet_directory_uri() . '/css/home-headings.css',
                [ 'gorvita-child-style' ],
                filemtime( $path )
            );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'gorvita_enqueue_o_marce_assets' );

/**
 * Premium product-card styling — shop, product archives (category/tag/attribute)
 * and single product (related products). Kept separate from the o-marce/front
 * enqueue above, which early-returns on every other page. Also loads Cormorant
 * Garamond here (it isn't enqueued globally) for the serif price.
 */
function gorvita_enqueue_product_cards_assets() {
    if ( ! function_exists( 'is_woocommerce' )
        || ! ( is_shop() || is_product_taxonomy() || is_product()
            || is_page( 114 ) || is_page( 116 ) || is_front_page() ) ) { // /nowosci/, /promocje/, home render product loops via shortcodes
        return;
    }
    wp_enqueue_style(
        'gorvita-cormorant',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&display=swap',
        [],
        null
    );
    $path = get_stylesheet_directory() . '/css/product-cards.css';
    if ( file_exists( $path ) ) {
        wp_enqueue_style(
            'gorvita-product-cards',
            get_stylesheet_directory_uri() . '/css/product-cards.css',
            [ 'gorvita-child-style' ],
            filemtime( $path )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'gorvita_enqueue_product_cards_assets', 20 );

function gorvita_o_marce_accordion_js() {
    if ( ! is_page( 119 ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var items = document.querySelectorAll('.gorvita-about .ga-faq__item');
        items.forEach(function(item) {
            var button = item.querySelector('.ga-faq__question');
            if ( ! button ) return;
            button.addEventListener('click', function () {
                var isOpen = item.classList.contains('is-open');
                items.forEach(function(other) {
                    other.classList.remove('is-open');
                    var otherBtn = other.querySelector('.ga-faq__question');
                    if ( otherBtn ) otherBtn.setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    item.classList.add('is-open');
                    button.setAttribute('aria-expanded', 'true');
                }
            });
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'gorvita_o_marce_accordion_js' );

function gorvita_icon( $name, $size = 20 ) {
    $s = sprintf( 'width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"', $size, $size );
    $paths = [
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'heart'       => '<path d="M12 20s-7-4.5-9-9a4.5 4.5 0 0 1 9-2.5 4.5 4.5 0 0 1 9 2.5c-2 4.5-9 9-9 9z"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left'  => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'leaf'        => '<path d="M20 4c0 8-6 14-14 14-1 0-2-.2-2-.2s0-8 6-13c3-2.5 7-2 10-.8z"/><path d="M4 18C8 14 12 10 20 4"/>',
        'shield'      => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/>',
        'droplet'     => '<path d="M12 3s7 7 7 12a7 7 0 0 1-14 0c0-5 7-12 7-12z"/>',
        'certificate' => '<circle cx="12" cy="10" r="5"/><path d="m9 14-2 7 5-3 5 3-2-7"/>',
        'truck'       => '<path d="M3 7h11v10H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/>',
        'return'      => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'chevron'     => '<path d="m6 9 6 6 6-6"/>',
    ];
    if ( ! isset( $paths[ $name ] ) ) {
        return;
    }
    echo '<svg xmlns="http://www.w3.org/2000/svg" ' . $s . '>' . $paths[ $name ] . '</svg>'; // phpcs:ignore
}

// [gorvita-usp] — 4 bloki wartości z tłem (attachment 279)
function gorvita_usp_shortcode() {
    $usps = [
        [ 'icon' => 'certificate', 'h' => 'Tradycja od 1989',   'p' => 'Trzy pokolenia ziołolecznictwa. Receptury sprawdzone przez tysiące polskich rodzin.' ],
        [ 'icon' => 'leaf',        'h' => 'Polskie zioła',       'p' => '100% naturalne ekstrakty z ziół zbieranych w Gorcach i certyfikowanych upraw ekologicznych.' ],
        [ 'icon' => 'droplet',     'h' => 'Woda uzdrowiskowa',   'p' => 'Naturalna woda mineralna bogata w minerały, wykorzystywana w maściach i żelach.' ],
        [ 'icon' => 'shield',      'h' => 'GMP + ISO 9001',      'p' => 'Laboratorium certyfikowane farmaceutycznie. Każda partia badana — bez kompromisów.' ],
    ];
    ob_start();
    ?>
    <div class="gorvita-usp-grid gorvita-usp-grid--photo">
        <?php echo wp_get_attachment_image( 279, 'full', false, [ 'class' => 'gorvita-usp-grid__bg', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ] ); // phpcs:ignore ?>
        <div class="gorvita-usp-grid__overlay" aria-hidden="true"></div>
        <?php foreach ( $usps as $u ) : ?>
            <div class="gorvita-usp">
                <div class="gorvita-usp__icon"><?php gorvita_icon( $u['icon'], 22 ); ?></div>
                <h4><?php echo esc_html( $u['h'] ); ?></h4>
                <p><?php echo esc_html( $u['p'] ); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'gorvita-usp', 'gorvita_usp_shortcode' );

// [gorvita-vstrip] — animowany pasek marquee z wartościami
function gorvita_vstrip_shortcode() {
    $items = [
        [ 'icon' => 'certificate', 'strong' => 'Rodzinna manufaktura',  'text' => '— Szczawa, od 1989' ],
        [ 'icon' => 'leaf',        'strong' => 'Polskie zioła',         'text' => '— z Gorców i upraw ekologicznych' ],
        [ 'icon' => 'shield',      'strong' => 'GMP + ISO 9001',        'text' => '— każda partia badana' ],
        [ 'icon' => 'truck',       'strong' => 'Dostawa 24H',           'text' => '— gratis od 250 zł' ],
        [ 'icon' => 'return',      'strong' => '14 dni na zwrot',       'text' => '— bez pytań' ],
    ];
    $all = array_merge( $items, $items ); // duplikat dla seamless loop
    ob_start();
    ?>
    <div class="gorvita-vstrip" aria-hidden="true">
        <div class="gorvita-vstrip__track">
            <?php foreach ( $all as $it ) : ?>
                <div class="gorvita-vstrip__item">
                    <?php gorvita_icon( $it['icon'], 18 ); ?>
                    <span><strong><?php echo esc_html( $it['strong'] ); ?></strong> <?php echo esc_html( $it['text'] ); ?></span>
                    <span class="dot"></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'gorvita-vstrip', 'gorvita_vstrip_shortcode' );

// [gorvita-spring] — sekcja "Woda, która leczy od wieków"
function gorvita_spring_shortcode() {
    $img_url = get_stylesheet_directory_uri() . '/assets/images/strumien.webp';
    ob_start();
    ?>
    <div class="gorvita-wrap gorvita-reveal">
        <div class="gorvita-spring">
            <div class="gorvita-spring__inner">
                <div class="gorvita-spring__visual">
                    <img class="gorvita-spring__visual-img" src="<?php echo esc_url( $img_url ); ?>" alt="Strumień w Gorcach" loading="lazy" decoding="async">
                    <div class="gorvita-spring__visual-grade"></div>
                    <div class="gorvita-spring__coord">
                        <span>49°34'N</span>
                        <span>SZCZAWA · RABKA-ZDRÓJ</span>
                        <span>20°16'E</span>
                    </div>
                    <div class="gorvita-spring__ripples" aria-hidden="true">
                        <div class="gorvita-spring__ripple"></div>
                        <div class="gorvita-spring__ripple"></div>
                        <div class="gorvita-spring__ripple"></div>
                    </div>
                </div>
                <div class="gorvita-spring__copy">
                    <div class="gorvita-eyebrow">RODZINNA MANUFAKTURA · OD 1989</div>
                    <h2>Trzy pokolenia, jedno źródło,<br><em>jedna receptura.</em></h2>
                    <p>Pierwszą partię maści zważono w Szczawie w 1989 roku. Tę samą recepturę warzymy dzisiaj, w tej samej manufakturze, na tej samej wodzie z Rabki-Zdrój. Ręcznie, partiami, bez kompromisów.</p>
                    <a class="gorvita-link-arrow" style="color:var(--gorvita-sage)" href="/o-marce/">
                        Poznaj rodzinę Gorvita <?php gorvita_icon( 'arrow-right', 16 ); ?>
                    </a>
                    <div class="gorvita-spring__stats gorvita-spring__stats--anchors">
                        <div>
                            <div class="gorvita-spring__stat-text">3 pokolenia</div>
                            <div class="gorvita-spring__stat-label">w jednej manufakturze</div>
                        </div>
                        <div>
                            <div class="gorvita-spring__stat-text">od 1989</div>
                            <div class="gorvita-spring__stat-label">nieprzerwanie ręcznie</div>
                        </div>
                        <div>
                            <div class="gorvita-spring__stat-text">GMP · ISO</div>
                            <div class="gorvita-spring__stat-label">każda partia badana</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'gorvita-spring', 'gorvita_spring_shortcode' );

// [gorvita-hero] — sekcja hero z tłem (attachment 249) i zdjęciem gór
function gorvita_hero_shortcode() {
    $gorce_url = get_stylesheet_directory_uri() . '/assets/images/gorce2.webp';
    $shop_url  = class_exists( 'WooCommerce' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : '/sklep/';
    ob_start();
    ?>
    <section class="gorvita-hero">
        <div class="gorvita-hero__bg">
            <img
                class="gorvita-hero__bg-img"
                src="https://sklep.gorvita.pl/wp-content/uploads/2026/05/gorvita_hero_4products_v2.webp"
                alt=""
                fetchpriority="high"
                loading="eager"
                decoding="async">
            <div class="gorvita-hero__bg-fade"></div>
        </div>
        <div class="gorvita-wrap gorvita-hero__grid">
            <div class="gorvita-hero__copy">
                <span class="gorvita-hero__eyebrow">EST. 1989 · SZCZAWA</span>
                <h1 class="gorvita-hero__title">
                    Z Gorców,<br>
                    <em>od rodziny</em><br>
                    Gorvita.
                </h1>
                <p class="gorvita-hero__sub">
                    Z Serca Gorców i Beskidu Wyspowego od rodziny Gorvita.
                </p>
                <p class="gorvita-hero__lead">
                    Kosmetyki, suplementy diety i wyroby medyczne<br>wytwarzane w Szczawie od 1989 roku.
                </p>
                <div class="gorvita-hero__cta">
                    <a class="gorvita-hero__btn gorvita-hero__btn--primary" href="<?php echo esc_url( $shop_url ); ?>">
                        Odkryj produkty <?php gorvita_icon( 'arrow-right', 16 ); ?>
                    </a>
                    <a class="gorvita-hero__btn gorvita-hero__btn--ghost" href="/o-marce/">Nasza historia</a>
                </div>
            </div>
            <div class="gorvita-hero__visual">
                <img class="gorvita-hero__visual-img" src="<?php echo esc_url( $gorce_url ); ?>" alt="Gorce — szczyty nad mgłą" loading="eager" fetchpriority="high" decoding="async">
                <span class="gorvita-hero__visual-label">SZCZAWA · 49°34'N 20°16'E</span>
                <div class="gorvita-hero__droplet" aria-hidden="true"></div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'gorvita-hero', 'gorvita_hero_shortcode' );

/**
 * [gorvita_sale_products] — list products currently on sale.
 * Wraps Woo's core sale-products query in the same product-card grid the homepage uses.
 * Also used by the /promocje/ page; tolerates Gutenberg curly-quote attributes.
 */
function gorvita_sale_products_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'limit'    => 24,
            'columns'  => 4,
            'orderby'  => 'date',
            'order'    => 'desc',
        ],
        is_array( $atts ) ? $atts : [],
        'gorvita_sale_products'
    );

    if ( ! function_exists( 'wc_get_product_ids_on_sale' ) ) {
        return '<p class="gorvita-empty">Brak produktów w promocji w tej chwili.</p>';
    }

    $sale_ids = wc_get_product_ids_on_sale();
    if ( empty( $sale_ids ) ) {
        return '<p class="gorvita-empty" style="text-align:center;color:var(--gor-text-muted,#6A6A66);padding:48px 0;">Aktualnie żaden produkt nie jest w promocji. Zajrzyj wkrótce — promocje zmieniają się regularnie.</p>';
    }

    $core = do_shortcode( sprintf(
        '[products limit="%d" columns="%d" orderby="%s" order="%s" on_sale="true" visibility="visible"]',
        intval( $atts['limit'] ),
        intval( $atts['columns'] ),
        esc_attr( $atts['orderby'] ),
        esc_attr( $atts['order'] )
    ) );

    return $core;
}
add_shortcode( 'gorvita_sale_products', 'gorvita_sale_products_shortcode' );

/**
 * Normalize Gutenberg curly quotes inside our custom shortcodes' attributes
 * so [gorvita_sale_products limit="24"] (smart-quoted) still parses.
 */
function gorvita_normalize_curly_quotes_in_shortcodes( $content ) {
    if ( strpos( $content, '[gorvita_sale_products' ) === false ) {
        return $content;
    }
    return preg_replace_callback(
        '/\[gorvita_sale_products([^\]]*)\]/u',
        function ( $m ) {
            $attrs = strtr( $m[1], [
                '“' => '"',
                '”' => '"',
                '„' => '"',
                '‘' => "'",
                '’' => "'",
            ] );
            return '[gorvita_sale_products' . $attrs . ']';
        },
        $content
    );
}
add_filter( 'the_content', 'gorvita_normalize_curly_quotes_in_shortcodes', 5 );

function gorvita_hover_image_css() {
    echo '<style>
    .gorvita-hover-img {
        position: absolute !important;
        opacity: 0;
        pointer-events: none;
    }
    .woocommerce ul.products li.product .ct-media-container {
        position: relative;
        overflow: hidden;
    }
    .woocommerce ul.products li.product .ct-media-container img {
        transition: opacity 0.35s ease;
    }
    .woocommerce ul.products li.product .gorvita-hover-img {
        position: absolute !important;
        inset: 0 !important;
        top: 0 !important; left: 0 !important;
        width: 100% !important; height: 100% !important;
        object-fit: contain !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        opacity: 0;
        transition: opacity 0.35s ease;
    }
    /* fade the PRIMARY image out on hover (Blocksy puts it in .ct-media-container, not .woocommerce-loop-product__link) */
    .woocommerce ul.products li.product:hover .ct-media-container img.wp-post-image:not(.gorvita-hover-img) {
        opacity: 0 !important;
    }
    .woocommerce ul.products li.product:hover .gorvita-hover-img {
        opacity: 1;
    }
    </style>';
}
add_action( 'wp_head', 'gorvita_hover_image_css' );

function gorvita_add_hover_image() {
    $product = wc_get_product( get_the_ID() );
    if ( ! $product ) return;
    $gallery = $product->get_gallery_image_ids();
    if ( ! empty( $gallery ) ) {
        echo wp_get_attachment_image( $gallery[0], 'woocommerce_thumbnail', false, [ 'class' => 'gorvita-hover-img' ] ); // phpcs:ignore
    }
}
add_action( 'woocommerce_before_shop_loop_item_title', 'gorvita_add_hover_image', 15 );

function gorvita_hover_image_js() {
    echo '<script>
    function gorvitaInitHover() {
        document.querySelectorAll(".woocommerce ul.products li.product").forEach(function(card) {
            var hoverImg = card.querySelector(".gorvita-hover-img");
            var container = card.querySelector(".ct-media-container");
            if (hoverImg && container && !container.dataset.hoverInit) {
                container.appendChild(hoverImg);
                container.setAttribute("style", "overflow:hidden!important;position:relative!important;");
                container.dataset.hoverInit = "1";
            }
        });
    }
    document.addEventListener("DOMContentLoaded", gorvitaInitHover);
    window.addEventListener("load", gorvitaInitHover);
    setTimeout(gorvitaInitHover, 500);
    setTimeout(gorvitaInitHover, 1500);
    setTimeout(gorvitaInitHover, 3000);

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            if (m.addedNodes.length) gorvitaInitHover();
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
    </script>';
}
add_action( 'wp_footer', 'gorvita_hover_image_js' );

/**
 * Hover image for WooCommerce Blocks Product Collection cards (block cart cross-sells,
 * shop block grids). The classic `woocommerce_before_shop_loop_item_title` hook above
 * does not fire for React-rendered .wc-block-product cards, so this fetches each
 * product's gallery via Store API and injects the second image as a hover overlay.
 */
function gorvita_block_product_hover_image_assets() {
    echo '<style>
    .wc-block-components-product-image { position: relative !important; overflow: hidden !important; }
    .wc-block-components-product-image img { transition: opacity 0.3s ease !important; }
    .gorvita-hover-img-block {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: contain !important;
        opacity: 0 !important;
        pointer-events: none !important;
        transition: opacity 0.3s ease !important;
    }
    .wc-block-product:hover .wc-block-components-product-image img:not(.gorvita-hover-img-block) { opacity: 0 !important; }
    .wc-block-product:hover .gorvita-hover-img-block { opacity: 1 !important; }
    </style>
    <script>
    (function() {
        var seen = new WeakSet();
        var cache = {};

        async function fetchSecondImage(id) {
            if (cache[id] !== undefined) return cache[id];
            try {
                var r = await fetch("/wp-json/wc/store/v1/products/" + id, { credentials: "same-origin" });
                if (!r.ok) { cache[id] = null; return null; }
                var p = await r.json();
                var imgs = p.images || [];
                cache[id] = imgs.length >= 2 ? imgs[1] : null;
                return cache[id];
            } catch (e) { cache[id] = null; return null; }
        }

        async function processCard(card) {
            if (seen.has(card)) return;
            seen.add(card);
            var m = card.className.match(/post-(\d+)/);
            if (!m) return;
            var imgWrap = card.querySelector(".wc-block-components-product-image");
            var primaryImg = imgWrap ? imgWrap.querySelector("img") : null;
            if (!imgWrap || !primaryImg) return;
            var second = await fetchSecondImage(m[1]);
            if (!second || !second.src) return;
            // Avoid duplicate inserts in case of race
            if (imgWrap.querySelector(".gorvita-hover-img-block")) return;
            var hover = document.createElement("img");
            hover.className = "gorvita-hover-img-block";
            hover.src = second.src;
            hover.alt = second.alt || "";
            hover.loading = "lazy";
            imgWrap.appendChild(hover);
        }

        function scan() {
            document.querySelectorAll(".wc-block-product").forEach(processCard);
        }

        document.addEventListener("DOMContentLoaded", scan);
        window.addEventListener("load", scan);
        setTimeout(scan, 800);
        setTimeout(scan, 2000);
        var mo = new MutationObserver(function() { scan(); });
        mo.observe(document.body, { childList: true, subtree: true });
    })();
    </script>';
}
add_action( 'wp_footer', 'gorvita_block_product_hover_image_assets' );

function gorvita_nowosc_badge_css() {
    echo '<style>
    .gorvita-badge-nowosc {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 10;
        background: #2D5016;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 3px;
        pointer-events: none;
    }
    .woocommerce ul.products li.product .ct-media-container {
        position: relative;
    }
    </style>';
}
add_action( 'wp_head', 'gorvita_nowosc_badge_css' );

function gorvita_nowosc_badge() {
    $product = wc_get_product( get_the_ID() );
    if ( ! $product ) return;
    $tags = wp_get_post_terms( get_the_ID(), 'product_tag', [ 'fields' => 'slugs' ] );
    if ( in_array( 'nowosc', $tags, true ) ) {
        echo '<span class="gorvita-badge-nowosc">Nowość</span>';
    }
}
add_action( 'woocommerce_before_shop_loop_item', 'gorvita_nowosc_badge', 5 );

function gorvita_product_accordion_js() {
    if ( ! is_product() ) return;
    echo '<script>
    (function(){
      document.addEventListener("DOMContentLoaded", function() {
        var descPanel = document.getElementById("tab-description");
        if (!descPanel || descPanel.dataset.gorvitaInit) return;
        descPanel.dataset.gorvitaInit = "1";

        var BREAKPOINT = 768;
        var headings = Array.prototype.slice.call(descPanel.querySelectorAll("h2"));
        if (!headings.length) return;

        // Cache sections ONCE at load — so when initTabs() detaches h2/content
        // from descPanel and a later resize swaps to initAccordion(), we still
        // have references and can re-attach them. Without this, h2.parentNode
        // is null and insertBefore() crashes.
        var SECTIONS = (function () {
          var secs = [];
          headings.forEach(function (h2) {
            var content = [];
            var next = h2.nextElementSibling;
            while (next && next.tagName !== "H2") {
              content.push(next);
              next = next.nextElementSibling;
            }
            secs.push({ title: (h2.textContent || "").trim(), header: h2, content: content });
          });
          return secs;
        })();

        function ensureAttached() {
          SECTIONS.forEach(function (section) {
            if (!section.header.parentNode) descPanel.appendChild(section.header);
            section.content.forEach(function (n) {
              if (!n.parentNode) descPanel.appendChild(n);
            });
          });
        }

        function collectSections() { return SECTIONS; }

        function isMobile() { return window.innerWidth < BREAKPOINT; }

        function clearGenerated() {
          var nav = descPanel.querySelector(".gorvita-tabs-nav");
          var panels = descPanel.querySelector(".gorvita-tabs-panels");
          if (nav) nav.remove();
          if (panels) panels.remove();
          descPanel.querySelectorAll(".gorvita-accordion-content").forEach(function(n){ n.remove(); });
          descPanel.querySelectorAll(".gorvita-accordion-trigger").forEach(function(h){
            h.classList.remove("gorvita-accordion-trigger","gorvita-accordion-open");
            h.removeAttribute("aria-expanded");
            h.removeAttribute("tabindex");
          });
        }

        function buildFaqAccordion(container) {
          if (container.querySelector(".gorvita-faq-item")) return;
          var ps = Array.prototype.slice.call(container.querySelectorAll("p"));
          ps.forEach(function(p){
            var strong = p.querySelector("strong");
            if (!strong) return;
            var br = p.querySelector("br");
            var answerHTML = "";
            if (br) {
              var node = br.nextSibling;
              while(node){
                answerHTML += (node.outerHTML || node.textContent);
                node = node.nextSibling;
              }
            } else {
              answerHTML = p.innerHTML.replace(/<\s*strong[^>]*>.*?<\s*\/\s*strong>/i,"").trim();
            }
            var q = document.createElement("div");
            var a = document.createElement("div");
            var item = document.createElement("div");
            q.className = "gorvita-faq-question";
            q.setAttribute("aria-expanded","false");
            q.setAttribute("role","button");
            q.tabIndex = 0;
            q.textContent = strong.textContent.trim();
            a.className = "gorvita-faq-answer";
            a.style.display = "none";
            a.innerHTML = answerHTML;
            item.className = "gorvita-faq-item";
            item.appendChild(q);
            item.appendChild(a);
            p.parentNode.insertBefore(item, p);
            p.remove();
            function toggleFaq() {
              var open = q.getAttribute("aria-expanded") === "true";
              q.setAttribute("aria-expanded", open ? "false" : "true");
              a.style.display = open ? "none" : "block";
              q.classList.toggle("gorvita-faq-open", !open);
            }
            q.addEventListener("click", toggleFaq);
            q.addEventListener("keydown", function(e){
              if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggleFaq(); }
            });
          });
        }

        function initAccordion() {
          clearGenerated();
          ensureAttached();
          var sections = collectSections();
          sections.forEach(function(section, index){
            var h2 = section.header;
            h2.classList.add("gorvita-accordion-trigger");
            h2.setAttribute("aria-expanded", index === 0 ? "true" : "false");
            h2.setAttribute("role","button");
            h2.tabIndex = 0;
            if (index === 0) h2.classList.add("gorvita-accordion-open");
            var wrapper = document.createElement("div");
            wrapper.className = "gorvita-accordion-content";
            wrapper.style.display = index === 0 ? "block" : "none";
            section.content.forEach(function(n){ wrapper.appendChild(n); });
            h2.parentNode.insertBefore(wrapper, h2.nextSibling);
            function toggleAccordion() {
              var exp = h2.getAttribute("aria-expanded") === "true";
              h2.setAttribute("aria-expanded", exp ? "false" : "true");
              wrapper.style.display = exp ? "none" : "block";
              h2.classList.toggle("gorvita-accordion-open", !exp);
            }
            h2.addEventListener("click", toggleAccordion);
            h2.addEventListener("keydown", function(e){
              if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggleAccordion(); }
            });
            if ((section.title||"").toLowerCase().indexOf("faq") !== -1) buildFaqAccordion(wrapper);
          });
        }

        function initTabs() {
          clearGenerated();
          ensureAttached();
          var sections = collectSections();
          if (!sections.length) return;
          var tabNav = document.createElement("div");
          tabNav.className = "gorvita-tabs-nav";
          tabNav.setAttribute("role","tablist");
          var tabPanels = document.createElement("div");
          tabPanels.className = "gorvita-tabs-panels";

          sections.forEach(function(section, i){
            var btn = document.createElement("button");
            btn.className = "gorvita-tab-btn";
            btn.textContent = section.title || ("Tab "+(i+1));
            btn.setAttribute("role","tab");
            btn.id = "gorvita-tab-"+i;
            btn.setAttribute("aria-selected", i === 0 ? "true" : "false");
            btn.setAttribute("aria-controls","gorvita-panel-"+i);
            btn.tabIndex = i === 0 ? 0 : -1;
            if (i === 0) btn.classList.add("active");

            var panel = document.createElement("div");
            panel.className = "gorvita-tab-panel";
            panel.id = "gorvita-panel-"+i;
            panel.setAttribute("role","tabpanel");
            panel.setAttribute("aria-labelledby","gorvita-tab-"+i);
            panel.setAttribute("aria-hidden", i === 0 ? "false" : "true");
            panel.style.display = i === 0 ? "block" : "none";
            section.content.forEach(function(n){ panel.appendChild(n); });

            if ((section.title||"").toLowerCase().indexOf("faq") !== -1) buildFaqAccordion(panel);

            function activateTab() {
              tabNav.querySelectorAll(".gorvita-tab-btn").forEach(function(b){
                b.classList.remove("active");
                b.setAttribute("aria-selected","false");
                b.tabIndex = -1;
              });
              tabPanels.querySelectorAll(".gorvita-tab-panel").forEach(function(p){
                p.style.display = "none";
                p.setAttribute("aria-hidden","true");
              });
              btn.classList.add("active");
              btn.setAttribute("aria-selected","true");
              btn.tabIndex = 0;
              panel.style.display = "block";
              panel.setAttribute("aria-hidden","false");
            }

            btn.addEventListener("click", activateTab);

            btn.addEventListener("keydown", function(e){
              var all = Array.prototype.slice.call(tabNav.querySelectorAll(".gorvita-tab-btn"));
              var idx = all.indexOf(btn);
              if (e.key === "ArrowRight") { e.preventDefault(); all[(idx+1)%all.length].click(); all[(idx+1)%all.length].focus(); }
              if (e.key === "ArrowLeft")  { e.preventDefault(); all[(idx-1+all.length)%all.length].click(); all[(idx-1+all.length)%all.length].focus(); }
              if (e.key === "Home")       { e.preventDefault(); all[0].click(); all[0].focus(); }
              if (e.key === "End")        { e.preventDefault(); all[all.length-1].click(); all[all.length-1].focus(); }
            });

            tabNav.appendChild(btn);
            tabPanels.appendChild(panel);
            section.header.remove();
          });

          descPanel.appendChild(tabNav);
          descPanel.appendChild(tabPanels);
        }

        var currentMode = isMobile() ? "mobile" : "desktop";
        currentMode === "mobile" ? initAccordion() : initTabs();

        var resizeTimer = null;
        window.addEventListener("resize", function(){
          clearTimeout(resizeTimer);
          resizeTimer = setTimeout(function(){
            var newMode = isMobile() ? "mobile" : "desktop";
            if (newMode !== currentMode) {
              currentMode = newMode;
              currentMode === "mobile" ? initAccordion() : initTabs();
            }
          }, 150);
        });
      });
    })();
    </script>';
}
add_action("wp_footer", "gorvita_product_accordion_js");

/**
 * Polonize the Blocksy Pro wish-list endpoint slug: woo-wish-list → lista-zyczen.
 * Blocksy registers the endpoint on `init` via add_rewrite_endpoint(), so this
 * filter has to be in place before then — top-level in functions.php is fine.
 * After deploy, run once on the VPS:
 *   wp rewrite flush --hard --allow-root
 */
add_filter( 'blocksy:pro:woocommerce-extra:wish-list:slug', function () {
    return 'lista-zyczen';
} );

/**
 * 301 any wish-list URL to the canonical My Account endpoint.
 * The Blocksy Pro endpoint only renders content under WC My Account
 * (woocommerce_account_<slug>_endpoint), so standalone or page-nested
 * URLs (/woo-wish-list/, /promocje/woo-wish-list/, /lista-zyczen/) all
 * fall through to the blog/parent page. Fold them into the one URL
 * that actually shows the wishlist.
 */
function gorvita_redirect_old_wishlist_slug() {
    if ( empty( $_SERVER['REQUEST_URI'] ) ) {
        return;
    }
    $uri  = (string) $_SERVER['REQUEST_URI'];
    $path = (string) parse_url( $uri, PHP_URL_PATH );

    // Standalone /lista-zyczen/ (not nested under /moje-konto/) → canonicalize.
    $is_standalone_new = preg_match( '#^/lista-zyczen/?$#', $path );
    $is_old_slug       = (bool) preg_match( '#(^|/)woo-wish-list(/|$)#', $path );

    if ( ! $is_old_slug && ! $is_standalone_new ) {
        return;
    }

    $target = function_exists( 'wc_get_account_endpoint_url' )
        ? wc_get_account_endpoint_url( 'lista-zyczen' )
        : home_url( '/moje-konto/lista-zyczen/' );

    wp_safe_redirect( $target, 301 );
    exit;
}
add_action( 'template_redirect', 'gorvita_redirect_old_wishlist_slug', 1 );

/**
 * Polish labels for the Blocksy Companion account dropdown header item.
 * No PL .mo file ships for blocksy-companion text-domain, and Loco Translate
 * is not installed, so we override via gettext filter — survives plugin updates.
 */
add_filter( 'gettext', function ( $translation, $text, $domain ) {
    if ( $domain !== 'blocksy-companion' ) {
        return $translation;
    }
    static $map = null;
    if ( $map === null ) {
        $map = [
            'Dashboard'    => 'Kokpit',
            'My Account'   => 'Moje konto',
            'Wishlist'     => 'Lista życzeń',
            'Edit Profile' => 'Edytuj profil',
            'Log Out'      => 'Wyloguj się',
            'Welcome'      => 'Witaj',
            'Hello'        => 'Cześć',
            'Login'        => 'Logowanie',
            'Register'     => 'Zarejestruj się',
            'Logout'       => 'Wyloguj się',
        ];
    }
    return $map[ $text ] ?? $translation;
}, 10, 3 );

// [gorvita-usp-bar] — pasek 4 wartości (USP) z tłem górskim + overlay
add_shortcode( 'gorvita-usp-bar', function() {
    ob_start(); ?>
    <div class="gv-usp-bar">
        <div class="gv-usp-bar__bg"></div>
        <div class="gv-usp-bar__overlay"></div>
        <div class="gv-usp-bar__grid">
            <div class="gv-usp-item">
                <div class="gv-usp-item__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="12" cy="8" r="6"/><path d="M12 14v7M9 17l3 4 3-4"/></svg>
                </div>
                <div class="gv-usp-item__text">
                    <strong>Tradycja od 1989</strong>
                    <span>Rodzinna manufaktura w Gorcach</span>
                </div>
            </div>
            <div class="gv-usp-item">
                <div class="gv-usp-item__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div class="gv-usp-item__text">
                    <strong>Polskie zioła</strong>
                    <span>Z Gorców i upraw ekologicznych</span>
                </div>
            </div>
            <div class="gv-usp-item">
                <div class="gv-usp-item__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="gv-usp-item__text">
                    <strong>GMP + ISO 9001</strong>
                    <span>Każda partia badana</span>
                </div>
            </div>
            <div class="gv-usp-item">
                <div class="gv-usp-item__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8zM1 16h15M5.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM18.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                </div>
                <div class="gv-usp-item__text">
                    <strong>Dostawa 24H</strong>
                    <span>Gratis od 250 zł</span>
                </div>
            </div>
        </div>
    </div>
    <style>
    .gv-usp-bar {
        position: relative;
        overflow: hidden;
    }
    .gv-usp-bar__bg {
        position: absolute;
        inset: 0;
        background-image: url('https://sklep.gorvita.pl/wp-content/uploads/2026/04/korzysci-bg.png');
        background-size: cover;
        background-position: center;
        filter: brightness(0.45);
    }
    .gv-usp-bar__overlay {
        position: absolute;
        inset: 0;
        background: rgba(20, 45, 30, 0.55);
    }
    .gv-usp-bar__grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 32px;
        gap: 0;
    }
    .gv-usp-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 0 24px;
        border-right: 1px solid rgba(255,255,255,0.15);
    }
    .gv-usp-item:first-child { padding-left: 0; }
    .gv-usp-item:last-child { border-right: none; padding-right: 0; }
    .gv-usp-item__icon {
        color: rgba(255,255,255,0.7);
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .gv-usp-item__text strong {
        display: block;
        color: #fff;
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 4px;
        white-space: nowrap;
    }
    .gv-usp-item__text span {
        color: rgba(255,255,255,0.65);
        font-size: 0.8rem;
        line-height: 1.4;
    }
    @media (max-width: 900px) {
        .gv-usp-bar__grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .gv-usp-item {
            border-right: none;
            padding: 0 !important;
        }
    }
    @media (max-width: 560px) {
        .gv-usp-bar__grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php return ob_get_clean();
} );

/* ============================================================
   GORVITA — ARCHIVE HERO
   Hero banner on: product_tag 'nowosc' archive ("Nowości")
   and the /promocje/ page (ID 116). No WC template overrides.
   ============================================================ */

/** Contexts that should show the archive hero: /promocje/ (116) and /nowosci/ (114). */
function gorvita_is_archive_hero_context() {
    return is_page( 116 ) || is_page( 114 );
}

/** Enqueue hero CSS (+ Cormorant for the serif title) only where it shows. */
function gorvita_enqueue_archive_hero() {
    if ( ! gorvita_is_archive_hero_context() ) {
        return;
    }
    wp_enqueue_style(
        'gorvita-cormorant',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&display=swap',
        [],
        null
    );
    $path = get_stylesheet_directory() . '/css/gorvita-archive-hero.css';
    if ( file_exists( $path ) ) {
        wp_enqueue_style(
            'gorvita-archive-hero',
            get_stylesheet_directory_uri() . '/css/gorvita-archive-hero.css',
            [],
            filemtime( $path )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'gorvita_enqueue_archive_hero' );

/** Build the hero markup. $variant = 'new' | 'promo'. */
function gorvita_archive_hero_html( $variant = 'new' ) {
    $is_promo   = ( 'promo' === $variant );
    $hero_class = $is_promo ? 'ga-archive-hero ga-archive-hero--promo' : 'ga-archive-hero';
    $eyebrow    = $is_promo ? 'Aktualne promocje' : 'Nowości w ofercie';
    $title      = $is_promo
        ? 'Lato 2026 — kojąca skóra po ukąszeniach komarów'
        : 'Nowości';
    $desc       = $is_promo
        ? 'Naturalne suplementy i kosmetyki na lato — zestaw Mosqitos (płyn + żel po ukąszeniach), aloes po opalaniu, maść rumiankowa z CBD i olejek pichtowy odstraszający komary. <strong>-10% na cały zestaw do 31 sierpnia 2026.</strong>'
        : 'Najnowsze produkty dodane do oferty Gorvita — suplementy, maści, żele i kosmetyki naturalne na bazie <strong>polskich surowców naturalnych</strong>.';

    ob_start();
    ?>
    <div class="<?php echo esc_attr( $hero_class ); ?>">
        <div class="ga-archive-hero__inner">
            <div class="ga-archive-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
            <h1 class="ga-archive-hero__title"><?php echo esc_html( $title ); ?></h1>
            <p class="ga-archive-hero__desc"><?php echo wp_kses_post( $desc ); ?></p>
            <?php if ( $is_promo ) : ?>
                <div class="ga-promo-badge">
                    <span class="ga-promo-badge__pill">-10%</span>
                    Promocja ważna do 31 sierpnia 2026
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/** /promocje/ (116) and /nowosci/ (114) render products via shortcodes
 *  ([gorvita_sale_products] / [products]), which don't fire
 *  woocommerce_before_shop_loop, so prepend the hero to the page content. */
add_filter( 'the_content', 'gorvita_archive_hero_on_page', 8 );
function gorvita_archive_hero_on_page( $content ) {
    if ( ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }
    if ( is_page( 116 ) ) {
        return gorvita_archive_hero_html( 'promo' ) . $content;
    }
    if ( is_page( 114 ) ) {
        return gorvita_archive_hero_html( 'new' ) . $content;
    }
    return $content;
}

/* ============================================================
   GORVITA — /b2b/ (Hurt/B2B) landing assets (page 120 only)
   Scoped CSS (.gorvita-b2b) + Cormorant/Inter fonts.
   ============================================================ */
function gorvita_enqueue_b2b_landing_assets() {
    if ( ! is_page( 120 ) ) {
        return;
    }
    wp_enqueue_style(
        'gorvita-b2b-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap',
        [],
        null
    );
    $path = get_stylesheet_directory() . '/css/hurt-b2b.css';
    if ( file_exists( $path ) ) {
        wp_enqueue_style(
            'gorvita-hurt-b2b',
            get_stylesheet_directory_uri() . '/css/hurt-b2b.css',
            [],
            filemtime( $path )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'gorvita_enqueue_b2b_landing_assets' );

/* ============================================================
   GORVITA — Polish pluralization for Blocksy term-count
   The "Shop Archive - Taxonomies Section" content block (post 702)
   renders a wp:term_count dynamic field with a static " products"
   suffix on every product-category archive. Localize it to the
   correct Polish plural form (the count is dynamic, so a single
   static word can't be grammatical — we inflect at render time).
   ============================================================ */

/** Polish noun form for "produkt" given a count: 1 produkt / 2-4 produkty / produktów. */
function gorvita_plural_produkt( $n ) {
    $n = (int) $n;
    if ( 1 === $n ) {
        return 'produkt';
    }
    $mod10  = $n % 10;
    $mod100 = $n % 100;
    if ( $mod10 >= 2 && $mod10 <= 4 && ( $mod100 < 12 || $mod100 > 14 ) ) {
        return 'produkty';
    }
    return 'produktów';
}

/** Replace the English " products" suffix on the Blocksy wp:term_count field
 *  with the correctly inflected Polish noun, derived from the rendered count. */
add_filter( 'render_block', 'gorvita_localize_term_count', 10, 2 );
function gorvita_localize_term_count( $block_content, $block ) {
    if ( empty( $block['blockName'] ) || 'blocksy/dynamic-data' !== $block['blockName'] ) {
        return $block_content;
    }
    if ( empty( $block['attrs']['field'] ) || 'wp:term_count' !== $block['attrs']['field'] ) {
        return $block_content;
    }
    if ( false === strpos( $block_content, 'products' ) ) {
        return $block_content;
    }
    // Inner text is just the rendered number + suffix, e.g. "83  products".
    $digits = preg_replace( '/\D/', '', wp_strip_all_tags( $block_content ) );
    if ( '' === $digits ) {
        return $block_content;
    }
    $word = gorvita_plural_produkt( (int) $digits );
    return preg_replace( '/\bproducts\b/', $word, $block_content, 1 );
}

/* ============================================================
   GORVITA — legacy /product-category/ → /kategorie/ 301 redirect
   The WooCommerce product-category base was renamed to "kategorie".
   Permanently redirect old-base URLs to the new base so lingering
   links / prior indexing don't hit a 404.
   ============================================================ */
add_action( 'template_redirect', 'gorvita_redirect_legacy_product_category', 0 );
function gorvita_redirect_legacy_product_category() {
    if ( empty( $_SERVER['REQUEST_URI'] ) ) {
        return;
    }
    $uri    = wp_unslash( $_SERVER['REQUEST_URI'] );
    $prefix = '/product-category/';
    if ( 0 !== strpos( $uri, $prefix ) ) {
        return;
    }
    $target = home_url( '/kategorie/' . substr( $uri, strlen( $prefix ) ) );
    wp_safe_redirect( esc_url_raw( $target ), 301 );
    exit;
}

/* ============================================================
   GORVITA — sharper grid thumbnails on 1x screens
   WP 7.0 auto-sizes (sizes="auto, …") makes the browser pick the
   ~300px candidate for the ~280px card slot on 1x displays. Opting
   out of auto-sizes lets grid images fall back to WooCommerce's
   computed sizes ("(max-width: 500px) 100vw, 500px"), so the 500px
   candidate is chosen on 1x too (retina already used 768/800).
   This reverts to the classic pre-6.7 responsive behavior site-wide.
   ============================================================ */
add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
