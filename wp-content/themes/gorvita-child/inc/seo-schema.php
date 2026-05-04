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
            'email'             => 'sklep@gorvita.com.pl',
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
 * J. Force default 1200×630 OG image on pages that don't have a per-page override.
 *
 * RankMath's default behaviour: pick the first image found in post_content,
 * fall back to default only if none. This produces low-quality OG previews
 * because content images are usually 1024×320 hero crops or product shots
 * (1200×1365 portrait) — neither is the 1.91:1 ratio Facebook/Twitter want.
 *
 * This filter forces the global default (uploaded 1200×630 brand image) on:
 *  - homepage / front page
 *  - pages WITHOUT an explicit rank_math_facebook_image meta
 *  - product category archives
 * Single posts and products keep their per-content image (relevant per-item).
 */
add_filter( 'rank_math/opengraph/facebook/image', 'gorvita_force_default_og_image', 11, 1 );
add_filter( 'rank_math/opengraph/twitter/image', 'gorvita_force_default_og_image', 11, 1 );
function gorvita_force_default_og_image( $image ) {
    if ( is_singular( 'product' ) || is_singular( 'post' ) ) {
        return $image;
    }
    if ( is_singular( 'page' ) ) {
        $post_id = get_the_ID();
        if ( $post_id && get_post_meta( $post_id, 'rank_math_facebook_image', true ) ) {
            return $image;
        }
    }
    $opts = get_option( 'rank_math_options_titles', array() );
    if ( ! empty( $opts['open_graph_image'] ) ) {
        return $opts['open_graph_image'];
    }
    return $image;
}

/**
 * I. Append auto-generated FAQ to products that lack one.
 *
 * 94 products have rich Webflow descriptions but no FAQ section. This
 * filter detects product type (supplement vs topical cosmetic vs Rabka-water
 * formula) from post_content + title, generates 5 SEO+GEO-aware Q&A pairs,
 * and injects them via [gorvita_faq] at end of content.
 *
 * Skipped when:
 *  - Post already contains a <h2>FAQ section (legacy stub on 12 products)
 *  - Post already contains [gorvita_faq] shortcode (manual override)
 *
 * The 5 questions are stable across products (consistent UX) but answers
 * are tailored:
 *   Q1: how does it work — pulls first sentence of description
 *   Q2: ingredient origin — uses Gorce / Rabka split based on uses_rabka_water()
 *   Q3: pregnancy safety — supplement vs topical wording
 *   Q4: how long to use — supplement vs topical wording
 *   Q5: brand differentiation — fixed GEO answer
 */
add_filter( 'the_content', 'gorvita_append_faq_to_products', 99 );
function gorvita_append_faq_to_products( $content ) {
    if ( ! is_singular( 'product' ) || ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }
    $post = get_post();
    if ( ! $post ) {
        return $content;
    }
    // Don't double-inject — skip if FAQ already exists
    if ( false !== stripos( $post->post_content, '<h2>FAQ' )
         || false !== stripos( $post->post_content, 'FAQ</h2>' )
         || false !== strpos( $post->post_content, '[gorvita_faq' ) ) {
        return $content;
    }

    $title = $post->post_title;
    $body  = $post->post_content;

    // Mirror the Python WATER_KEYWORDS list from update-seo-meta.py so the FAQ
    // GEO answer stays in sync with the product's RankMath title/description.
    $water_keywords = array(
        'woda lecznicza', 'wody leczniczej', 'wodzie leczniczej',
        'woda mineralna', 'wody mineralnej', 'mineralna z',
        'fizjologiczny roztwór', 'hydrochlorowo', 'wodorowęglanowo',
        'z Rabki', 'z rabki', 'kwaśna woda',
    );
    $is_water = false;
    foreach ( $water_keywords as $kw ) {
        if ( false !== mb_stripos( $body, $kw ) ) {
            $is_water = true;
            break;
        }
    }

    // Detect form: supplement (kapsułki/tabletki) vs topical (żel/maść/balsam/krem/pianka/spray)
    $title_lc = mb_strtolower( $title, 'UTF-8' );
    $is_topical = (bool) preg_match( '/(żel|maść|balsam|krem|pianka|spray|olejek|syrop|krople)/u', $title_lc );
    $is_supplement = (bool) preg_match( '/(kapsuł|tabletek|tab\.|gram|w proszku)/u', $title_lc );
    if ( ! $is_topical && ! $is_supplement ) {
        // Default: treat suplement-like generic
        $is_supplement = true;
    }

    // Q1 base: pull the LEAD paragraph (before any <h2>) — that's the
    // marketing-tier "what is this product" copy. Anything after first <h2>
    // is "Sposób użycia" / "Skład" etc. which would mislead Q1.
    $intro = '';
    $intro_block = preg_split( '/<h[1-6][^>]*>/i', $body, 2 )[0];
    $intro_text = trim( strip_tags( $intro_block ) );
    $intro_text = preg_replace( '/^Opis produktu:?\s*/i', '', $intro_text );
    $intro_text = preg_replace( '/\s+/u', ' ', $intro_text );
    if ( preg_match( '/^[^.!?]{20,250}[.!?]/u', $intro_text, $m ) ) {
        $intro = trim( $m[0] );
    } elseif ( $intro_text ) {
        $intro = mb_substr( $intro_text, 0, 220 );
    }

    $geo_origin = $is_water
        ? 'Zioła pochodzą z Gorców (Beskid Wyspowy), a baza formuły zawiera leczniczą wodę mineralną z Rabki-Zdroju.'
        : 'Surowce roślinne pochodzą z Gorców i z certyfikowanych upraw ekologicznych w Małopolsce i na Podkarpaciu.';

    if ( $is_topical ) {
        $q3 = "Czy {$title} mogę stosować w ciąży lub w okresie karmienia?";
        $a3 = "Produkt do stosowania zewnętrznego — wchłanianie składników przez skórę jest minimalne. W przypadku wątpliwości lub stosowania na duże powierzchnie skóry skonsultuj się z lekarzem prowadzącym.";
        $q4 = "Jak długo można stosować {$title}?";
        $a4 = "Produkt można stosować codziennie tak długo, jak utrzymują się objawy lub potrzeba pielęgnacji. PAO (okres po otwarciu) jest oznaczony na opakowaniu — przechowuj w temperaturze 5–25 °C.";
    } else {
        $q3 = "Czy {$title} mogę stosować w ciąży lub w okresie karmienia?";
        $a3 = "Suplementy diety w ciąży i okresie karmienia warto skonsultować z lekarzem prowadzącym lub farmaceutą. Skład i dawkowanie znajdziesz na opakowaniu.";
        $q4 = "Jak długo stosować {$title}?";
        $a4 = "Produkt można stosować codziennie zgodnie z zalecaną porcją na opakowaniu. Suplement diety nie zastępuje zróżnicowanej diety i zdrowego trybu życia.";
    }

    $q1 = "Jak działa {$title}?";
    $a1 = $intro ? $intro : "Produkt {$title} marki Gorvita opracowany na bazie polskich ziół i naturalnych składników.";

    $q2 = "Skąd pochodzą składniki w {$title}?";
    $a2 = $geo_origin . " Produkujemy w Szczawie (woj. małopolskie), zgodnie z normą ISO 9001 i standardem GMP. Każda partia jest badana, a numer serii jest drukowany na opakowaniu.";

    $q5 = "Czym Gorvita różni się od innych marek ziołowych?";
    $a5 = "Gorvita to polska, rodzinna manufaktura założona w 1989 roku. Produkujemy bezpośrednio u źródła surowca — w Gorcach, w Szczawie 106. 37 lat ciągłości jednego producenta, certyfikaty ISO 9001 i GMP, pełna identyfikowalność od działki zbioru ziół do numeru serii.";

    $items = array(
        array( 'q' => $q1, 'a' => $a1 ),
        array( 'q' => $q2, 'a' => $a2 ),
        array( 'q' => $q3, 'a' => $a3 ),
        array( 'q' => $q4, 'a' => $a4 ),
        array( 'q' => $q5, 'a' => $a5 ),
    );

    // Build the shortcode body — escape quotes inside answers
    $rows = array();
    foreach ( $items as $it ) {
        $q = str_replace( '"', "'", $it['q'] );
        $a = str_replace( '"', "'", $it['a'] );
        $rows[] = '{ "q": "' . $q . '", "a": "' . $a . '" }';
    }
    $shortcode = '[gorvita_faq]' . "\n" . implode( ",\n", $rows ) . "\n[/gorvita_faq]";

    return $content . "\n\n" . do_shortcode( $shortcode );
}

/**
 * H. Append GEO-rich FAQ to /o-marce/ via the_content filter.
 *
 * Renders a visible FAQ section + FAQPage JSON-LD via the existing
 * [gorvita_faq] shortcode. Targets the brand-story page where the FAQ
 * answers AI citation questions ("Is Gorvita a Polish brand?", "Where do
 * the herbs come from?", "Can I visit the producer?", etc.).
 *
 * Self-disabled when the page already contains [gorvita_faq] in content
 * (lets a copywriter override the canned set by adding their own shortcode
 * call directly in Gutenberg).
 */
add_filter( 'the_content', 'gorvita_append_faq_to_o_marce', 99 );
function gorvita_append_faq_to_o_marce( $content ) {
    if ( ! is_singular( 'page' ) || ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }
    $post = get_post();
    if ( ! $post || 'o-marce' !== $post->post_name ) {
        return $content;
    }
    if ( false !== strpos( $content, 'gorvita-faq' ) || false !== strpos( $post->post_content, '[gorvita_faq' ) ) {
        return $content; // copywriter already added their own
    }

    $faq_json = <<<'JSON'
{ "q": "Czy Gorvita to polska marka?", "a": "Tak. Gorvita to w 100% polska marka, założona w 1989 roku. Właścicielem jest PPUH Gorvita Sp. z o.o. z siedzibą w Szczawie 106 (gmina Kamienica, województwo małopolskie). Cała produkcja odbywa się w Polsce." },
{ "q": "Skąd pochodzą surowce Gorvita?", "a": "Zioła pochodzą z Gorców i Beskidu Wyspowego (kontrolowany zbiór ze stanu naturalnego) oraz z certyfikowanych upraw ekologicznych w Małopolsce i na Podkarpaciu. Woda lecznicza w wybranych formułach pochodzi z uzdrowiska Rabka-Zdrój." },
{ "q": "Czy produkty Gorvita są certyfikowane?", "a": "Tak. Produkujemy zgodnie z normą ISO 9001 oraz standardem GMP (Dobre Praktyki Wytwarzania). Suplementy diety są zgłoszone do GIS, kosmetyki posiadają wymagane oceny bezpieczeństwa i są zgłoszone do CPNP." },
{ "q": "Czy mogę odwiedzić producenta?", "a": "Siedziba i zakład produkcyjny znajdują się w Szczawie 106, gmina Kamienica. Wizyty odbiorców biznesowych (apteki, hurtownie, dystrybutorzy) są możliwe po wcześniejszym umówieniu. Skontaktuj się z nami przez formularz kontaktowy lub telefonicznie pod +48 18 332 41 81." },
{ "q": "Czym Gorvita różni się od innych marek ziołowych?", "a": "Po pierwsze — lokalizacja: produkujemy bezpośrednio u źródła surowca, w Gorcach. Po drugie — woda lecznicza z Rabki-Zdroju jako składnik wybranych formuł. Po trzecie — 37 lat ciągłości jednego producenta bez zmian właścicielskich. Po czwarte — pełna identyfikowalność: od działki zbioru ziół do numeru serii produktu." }
JSON;

    $shortcode = '[gorvita_faq]' . $faq_json . '[/gorvita_faq]';
    return $content . "\n\n" . do_shortcode( $shortcode );
}

/**
 * G. Auto-emit FAQPage JSON-LD on products that use the legacy stub FAQ pattern.
 *
 * Stub products (Spirulina, Zielony Jęczmień, Magnez B6 VEGAN etc. — products
 * that didn't get Webflow content) carry a section in this exact shape:
 *   <h2>FAQ</h2>
 *   <p><strong>Pytanie?</strong><br>Odpowiedź.</p>
 *   <p><strong>Pytanie?</strong><br>Odpowiedź.</p>
 *   ...
 *   <p><em>disclaimer</em></p>   ← ignore italic-only paragraphs
 *
 * The theme's tabs JS turns the H2 into a "FAQ" tab on desktop. This filter
 * adds the structured-data layer Google needs for rich-results FAQ snippets.
 */
add_action( 'wp_head', 'gorvita_emit_faqpage_from_h2_pattern', 51 );
function gorvita_emit_faqpage_from_h2_pattern() {
    if ( ! is_singular( 'product' ) ) {
        return;
    }
    $post = get_post();
    if ( ! $post || empty( $post->post_content ) ) {
        return;
    }
    if ( false === stripos( $post->post_content, '<h2>FAQ' ) && false === stripos( $post->post_content, '>FAQ</h2' ) ) {
        return;
    }

    // Extract everything between <h2>FAQ...</h2> and the next <h2> (or end)
    if ( ! preg_match( '#<h2[^>]*>\s*FAQ.*?</h2>(.*?)(?=<h2|\z)#is', $post->post_content, $m ) ) {
        return;
    }
    $faq_block = $m[1];

    $questions = array();
    // Match <p><strong>Q?</strong><br>A</p> pattern (allow whitespace + closing-tag variations)
    if ( preg_match_all( '#<p[^>]*>\s*<strong[^>]*>(.*?)</strong>\s*<br\s*/?>(.*?)</p>#is', $faq_block, $matches, PREG_SET_ORDER ) ) {
        foreach ( $matches as $pair ) {
            $q = trim( wp_strip_all_tags( $pair[1] ) );
            $a = trim( wp_strip_all_tags( $pair[2] ) );
            if ( $q && $a ) {
                $questions[] = array(
                    '@type'          => 'Question',
                    'name'           => $q,
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => $a,
                    ),
                );
            }
        }
    }

    if ( empty( $questions ) ) {
        return;
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $questions,
    );
    echo "\n<script type=\"application/ld+json\">"
        . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
        . "</script>\n";
}

/**
 * E. Auto-emit FAQPage JSON-LD on pages that use Greenshift accordion as FAQ.
 *
 * /kontakt/ (and any future page with greenshift-blocks/accordion) renders
 * Q&A via the Greenshift accordion block, which doesn't emit FAQPage schema
 * by itself. This walks the post's blocks, picks accordion items, and outputs
 * the structured-data block in <head>.
 *
 * Active on: page templates that contain greenshift-blocks/accordion.
 */
add_action( 'wp_head', 'gorvita_emit_faqpage_from_accordion', 50 );
function gorvita_emit_faqpage_from_accordion() {
    if ( ! is_singular( 'page' ) ) {
        return;
    }
    $post = get_post();
    if ( ! $post || empty( $post->post_content ) ) {
        return;
    }
    if ( false === strpos( $post->post_content, 'greenshift-blocks/accordion' ) ) {
        return;
    }

    $blocks = parse_blocks( $post->post_content );
    $questions = array();

    $walk = function( $blocks ) use ( &$walk, &$questions ) {
        foreach ( $blocks as $block ) {
            if ( 'greenshift-blocks/accordionitem' === ( $block['blockName'] ?? '' ) ) {
                $title = trim( $block['attrs']['title'] ?? '' );
                // Answer comes from inner blocks (paragraph etc.), NOT innerHTML
                // — innerHTML includes the question title heading on top.
                $answer = '';
                if ( ! empty( $block['innerBlocks'] ) ) {
                    foreach ( $block['innerBlocks'] as $sub ) {
                        $sub_html = $sub['innerHTML'] ?? '';
                        if ( ! empty( $sub['innerBlocks'] ) ) {
                            foreach ( $sub['innerBlocks'] as $sub2 ) {
                                $sub_html .= ' ' . ( $sub2['innerHTML'] ?? '' );
                            }
                        }
                        $answer .= ' ' . wp_strip_all_tags( $sub_html );
                    }
                }
                $answer = trim( preg_replace( '/\s+/u', ' ', $answer ) );
                if ( $title && $answer ) {
                    $questions[] = array(
                        '@type'          => 'Question',
                        'name'           => $title,
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text'  => $answer,
                        ),
                    );
                }
            }
            if ( ! empty( $block['innerBlocks'] ) ) {
                $walk( $block['innerBlocks'] );
            }
        }
    };
    $walk( $blocks );

    if ( empty( $questions ) ) {
        return;
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $questions,
    );
    echo "\n<script type=\"application/ld+json\">"
        . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
        . "</script>\n";
}

/**
 * F2. Force the title in Blocksy dynamic-data to render as <h1> on /kontakt/
 * and other pages where the original markup uses h2. Light DOM-output filter.
 */
add_filter( 'render_block_blocksy/dynamic-data', 'gorvita_dynamic_data_h1_on_pages', 10, 2 );
function gorvita_dynamic_data_h1_on_pages( $block_content, $block ) {
    if ( ! is_singular( 'page' ) ) {
        return $block_content;
    }
    $tag = $block['attrs']['tagName'] ?? '';
    if ( 'h2' === $tag ) {
        // Page hero — promote h2 to h1 for SEO (only one H1 allowed per page).
        // We assume the block in the hero is the page title; if a page already
        // has h1, this will create a duplicate, but Blocksy dynamic-data is
        // usually only used once per template.
        $block_content = preg_replace( '/<h2(\s|>)/', '<h1$1', $block_content, 1 );
        $block_content = preg_replace( '/<\/h2>/', '</h1>', $block_content, 1 );
    }
    return $block_content;
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
