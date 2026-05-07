<?php
/**
 * Server-side normalization of product descriptions on PDP only.
 *
 * Some products (~15 / 106 — e.g. Colafit, Apleplus, Artrofit, Calcium
 * Natural, CBD oils) ship a "bullet-paragraphs" pattern coming from a
 * legacy CMS migration:
 *
 *   <p>- pomaga w prawidłowym funkcjonowaniu układu odpornościowego</p>
 *   <p>- przyczynia się do zmniejszenia uczucia zmęczenia</p>
 *
 * Visually this renders as cramped, undifferentiated paragraphs.
 * We promote consecutive bullet-style paragraphs into a real
 * <ul class="gorvita-prod-bullets"><li>…</li></ul> so the styling
 * can do its job. Bullet markers stripped: -, –, —, •, ●, ▪, ▫.
 *
 * Also collapses 3+ consecutive empty paragraphs (Colafit had a
 * trailing wall of <p></p>).
 *
 * @package GorvitaChild
 */

defined( 'ABSPATH' ) || exit;

function gorvita_normalize_product_bullets( $content ) {
    if ( ! is_singular( 'product' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }
    if ( strpos( $content, '<p' ) === false ) {
        return $content;
    }

    // Collapse runs of empty paragraphs (whitespace, &nbsp;, <br>, or empty)
    // down to one. Common with old CMS exports.
    $content = preg_replace(
        '#(?:<p[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</p>\s*){2,}#i',
        '',
        $content
    );

    // Match runs of consecutive bullet-style paragraphs and replace them
    // with a single <ul>. Pattern: <p>{marker}{space}{any}</p> repeated.
    $bullet_marker = '[\-\x{2013}\x{2014}\x{2022}\x{25CF}\x{25AA}\x{25AB}]';
    $content = preg_replace_callback(
        '#(?:<p[^>]*>\s*' . $bullet_marker . '\s*[^<]+?</p>\s*){2,}#u',
        function ( $matches ) use ( $bullet_marker ) {
            $items = '';
            if ( preg_match_all(
                '#<p[^>]*>\s*' . $bullet_marker . '\s*([^<]+?)\s*</p>#u',
                $matches[0],
                $found
            ) ) {
                foreach ( $found[1] as $text ) {
                    $clean = trim( rtrim( $text, ',;' ) );
                    if ( $clean === '' ) {
                        continue;
                    }
                    $items .= '<li>' . esc_html( $clean ) . '</li>';
                }
            }
            return $items
                ? '<ul class="gorvita-prod-bullets">' . $items . '</ul>'
                : $matches[0];
        },
        $content
    );

    return $content;
}
add_filter( 'the_content', 'gorvita_normalize_product_bullets', 12 );
