<?php
// Gorvita Child — naked mode. All enqueues and modules disabled for Blocksy configuration.

function gorvita_theme_setup() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'gorvita_theme_setup' );

function gorvita_enqueue_styles() {
    wp_enqueue_style( 'gorvita-child-style', get_stylesheet_uri() );
    if ( is_front_page() ) {
        wp_enqueue_style( 'gorvita-homepage', get_stylesheet_directory_uri() . '/assets/css/homepage.css', [], '1.0' );
        wp_enqueue_script( 'gorvita-animations', get_stylesheet_directory_uri() . '/assets/js/animations.js', [], '1.0', true );
    }
}
add_action( 'wp_enqueue_scripts', 'gorvita_enqueue_styles' );

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
    ];
    if ( ! isset( $paths[ $name ] ) ) {
        return;
    }
    echo '<svg xmlns="http://www.w3.org/2000/svg" ' . $s . '>' . $paths[ $name ] . '</svg>'; // phpcs:ignore
}

function gorvita_usp_shortcode() {
    $usps = [
        [
            'icon' => 'certificate',
            'h'    => 'Tradycja od 1989',
            'p'    => 'Trzy pokolenia ziołolecznictwa. Receptury sprawdzone przez tysiące polskich rodzin.',
        ],
        [
            'icon' => 'leaf',
            'h'    => 'Polskie zioła',
            'p'    => '100% naturalne ekstrakty z ziół zbieranych w Gorcach i certyfikowanych upraw ekologicznych.',
        ],
        [
            'icon' => 'droplet',
            'h'    => 'Woda uzdrowiskowa',
            'p'    => 'Naturalna woda mineralna z Rabki-Zdrój — bogata w minerały, wykorzystywana w maściach i żelach.',
        ],
        [
            'icon' => 'shield',
            'h'    => 'GMP + ISO 9001',
            'p'    => 'Laboratorium certyfikowane farmaceutycznie. Każda partia badana — bez kompromisów.',
        ],
    ];

    ob_start();
    ?>
    <div class="gorvita-usp-grid gorvita-usp-grid--photo">
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
