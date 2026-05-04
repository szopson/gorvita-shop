<?php
/**
 * SEO schema augmentations for Gorvita.
 *
 * Plugged in from functions.php via require_once. Each function is a small,
 * RankMath-compatible filter that adds or removes structured-data nodes that
 * RankMath alone won't generate. LocalBusiness JSON-LD lives separately and
 * is enabled once Pawel provides the address/NIP/phone/hours data.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A. LocalBusiness JSON-LD on every page.
 *
 * GEO/local-search foundation for Gorvita. Type is HealthAndBeautyBusiness
 * + Store (broad enough for both supplements + cosmetics, narrow enough for
 * AI Overviews / Map Pack). Pharmacy was rejected — Gorvita is a producer,
 * not a licensed apteka.
 *
 * Data confirmed by client 2026-05-04 (Paweł Domek). Email TBD — placeholder
 * uses sklep@gorvita.pl pending confirmation. Social profiles also TBD.
 */
add_action( 'wp_head', 'gorvita_localbusiness_schema', 5 );
function gorvita_localbusiness_schema() {
    $schema = array(
        '@context'      => 'https://schema.org',
        '@type'         => array( 'HealthAndBeautyBusiness', 'Store' ),
        '@id'           => home_url( '/#localbusiness' ),
        'name'          => 'Gorvita',
        'legalName'     => 'PPUH Gorvita Sp. z o.o.',
        'alternateName' => 'Gorvita — manufaktura z Gorców',
        'url'           => home_url( '/' ),
        'description'   => 'Polska manufaktura naturalnych suplementów i kosmetyków ziołowych. Surowce z Gorców, woda lecznicza z Rabki w wybranych formułach. Tradycja od 1989, ISO 9001 + GMP.',
        'foundingDate'  => '1989',
        'founder'       => array(
            '@type' => 'Person',
            'name'  => 'mgr Paweł Domek',
        ),
        'address'       => array(
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Szczawa 106',
            'postalCode'      => '34-607',
            'addressLocality' => 'Szczawa',
            'addressRegion'   => 'małopolskie',
            'addressCountry'  => 'PL',
        ),
        'geo' => array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => 49.6072597,
            'longitude' => 20.2944911,
        ),
        'contactPoint' => array(
            '@type'             => 'ContactPoint',
            'telephone'         => '+48-18-332-41-81',
            'email'             => 'sklep@gorvita.pl',
            'contactType'       => 'customer service',
            'availableLanguage' => array( 'Polish' ),
            'areaServed'        => 'PL',
        ),
        'openingHoursSpecification' => array(
            array(
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
                'opens'     => '08:00',
                'closes'    => '16:00',
            ),
        ),
        'paymentAccepted' => 'PayU, Przelewy24, BLIK, karta płatnicza, przelew bankowy',
        'currenciesAccepted' => 'PLN',
        'award'  => array( 'ISO 9001', 'GMP — Good Manufacturing Practice' ),
        'taxID'  => 'PL7370006441',
        'vatID'  => 'PL7370006441',
        'iso6523Code' => '0009:7370006441',
        'identifier' => array(
            array( '@type' => 'PropertyValue', 'name' => 'NIP',    'value' => '7370006441' ),
            array( '@type' => 'PropertyValue', 'name' => 'REGON',  'value' => '490772290' ),
        ),
        'areaServed' => array( '@type' => 'Country', 'name' => 'Poland' ),
        'knowsAbout' => array(
            'ziołolecznictwo',
            'suplementy diety',
            'kosmetyki naturalne',
            'Gorce',
            'Beskidy Wyspowe',
            'woda lecznicza z Rabki',
        ),
    );
    echo "\n<script type=\"application/ld+json\">"
        . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
        . "</script>\n";
}

/**
 * F. Block Article schema on pages and home.
 *
 * RankMath emits an Article node by default for every singular content type,
 * which incorrectly tags landing pages (homepage, /promocje/, /kontakt/,
 * /o-marce/) as blog posts. Article schema there confuses search engines and
 * inflates content quality signals where they don't belong.
 */
add_filter( 'rank_math/snippet/rich_snippet_article_entity', 'gorvita_block_article_on_pages', 10, 1 );
function gorvita_block_article_on_pages( $entity ) {
    if ( is_singular( 'page' ) || is_front_page() || is_home() ) {
        return false;
    }
    return $entity;
}

/**
 * B. Brand + manufacturer in Product JSON-LD.
 *
 * RankMath omits brand for WooCommerce products. Google requires brand for
 * Product rich results and Merchant Center; without it WSJ-style structured
 * snippets don't render. Manufacturer mirrors the legal entity for AI search.
 */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', 'gorvita_add_brand_to_product', 10, 1 );
function gorvita_add_brand_to_product( $entity ) {
    if ( ! is_array( $entity ) ) {
        return $entity;
    }
    $logo_url = function_exists( 'get_theme_mod' ) ? get_theme_mod( 'custom_logo' ) : 0;
    $logo_src = $logo_url ? wp_get_attachment_image_url( $logo_url, 'full' ) : '';

    $entity['brand'] = array(
        '@type' => 'Brand',
        'name'  => 'Gorvita',
    );
    if ( $logo_src ) {
        $entity['brand']['logo'] = $logo_src;
    }
    $entity['manufacturer'] = array(
        '@type' => 'Organization',
        'name'  => 'PPUH Gorvita Sp. z o.o.',
        'url'   => home_url( '/' ),
    );
    return $entity;
}

/**
 * C. hasMerchantReturnPolicy + shippingDetails on every Offer.
 *
 * Required by Google Merchant Center from 2025; without these two fields the
 * product feed degrades and Shopping listings hide the offer. Reflects current
 * Gorvita policy: 14-day returns by mail (free), 24-48h shipping inside PL,
 * free shipping above 250 PLN. Update the threshold here if the policy changes.
 */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', 'gorvita_add_offer_policies', 20, 1 );
function gorvita_add_offer_policies( $entity ) {
    if ( ! is_array( $entity ) || empty( $entity['offers'] ) ) {
        return $entity;
    }

    $return_policy = array(
        '@type'                => 'MerchantReturnPolicy',
        'applicableCountry'    => 'PL',
        'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
        'merchantReturnDays'   => 14,
        'returnMethod'         => 'https://schema.org/ReturnByMail',
        'returnFees'           => 'https://schema.org/FreeReturn',
    );

    $shipping = array(
        '@type'        => 'OfferShippingDetails',
        'shippingRate' => array(
            '@type'    => 'MonetaryAmount',
            'value'    => '0',
            'currency' => 'PLN',
        ),
        'shippingDestination' => array(
            '@type'          => 'DefinedRegion',
            'addressCountry' => 'PL',
        ),
        'deliveryTime' => array(
            '@type'        => 'ShippingDeliveryTime',
            'handlingTime' => array(
                '@type'    => 'QuantitativeValue',
                'minValue' => 0,
                'maxValue' => 1,
                'unitCode' => 'DAY',
            ),
            'transitTime'  => array(
                '@type'    => 'QuantitativeValue',
                'minValue' => 1,
                'maxValue' => 2,
                'unitCode' => 'DAY',
            ),
        ),
    );

    if ( isset( $entity['offers']['@type'] ) && 'Offer' === $entity['offers']['@type'] ) {
        $entity['offers']['hasMerchantReturnPolicy'] = $return_policy;
        $entity['offers']['shippingDetails']         = $shipping;
    } elseif ( isset( $entity['offers']['@type'] ) && 'AggregateOffer' === $entity['offers']['@type'] ) {
        if ( ! empty( $entity['offers']['offers'] ) && is_array( $entity['offers']['offers'] ) ) {
            foreach ( $entity['offers']['offers'] as $idx => $offer ) {
                $entity['offers']['offers'][ $idx ]['hasMerchantReturnPolicy'] = $return_policy;
                $entity['offers']['offers'][ $idx ]['shippingDetails']         = $shipping;
            }
        }
    }

    return $entity;
}

/**
 * D. Shortcode [gorvita_faq] — renders an FAQ section AND emits FAQPage JSON-LD.
 *
 * Body content is a JSON array literal of {q, a} objects. Wrap with the shortcode:
 *
 *   [gorvita_faq]
 *   { "q": "Pytanie 1?", "a": "Odpowiedź 1." },
 *   { "q": "Pytanie 2?", "a": "Odpowiedź 2." }
 *   [/gorvita_faq]
 *
 * Renders an HTML <section> with <dl> and a sibling <script type="application/ld+json">
 * containing FAQPage schema. Output is escaped, so question/answer text may include
 * basic Polish characters but no HTML tags.
 */
add_shortcode( 'gorvita_faq', 'gorvita_faq_shortcode' );
function gorvita_faq_shortcode( $atts, $content = '' ) {
    $raw   = trim( wp_strip_all_tags( html_entity_decode( $content, ENT_QUOTES, 'UTF-8' ) ) );
    $items = json_decode( '[' . $raw . ']', true );
    if ( ! is_array( $items ) || empty( $items ) ) {
        return '';
    }

    $html = '<section class="gorvita-faq"><h2>Najczęściej zadawane pytania</h2><dl class="gorvita-faq__list">';
    $main_entity = array();
    foreach ( $items as $item ) {
        $q = isset( $item['q'] ) ? trim( $item['q'] ) : '';
        $a = isset( $item['a'] ) ? trim( $item['a'] ) : '';
        if ( '' === $q || '' === $a ) {
            continue;
        }
        $html .= '<dt class="gorvita-faq__q">' . esc_html( $q ) . '</dt>';
        $html .= '<dd class="gorvita-faq__a">' . esc_html( $a ) . '</dd>';
        $main_entity[] = array(
            '@type'          => 'Question',
            'name'           => $q,
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $a,
            ),
        );
    }
    $html .= '</dl></section>';

    if ( ! empty( $main_entity ) ) {
        $schema = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $main_entity,
        );
        $html .= "\n<script type=\"application/ld+json\">"
            . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
            . "</script>\n";
    }

    return $html;
}
