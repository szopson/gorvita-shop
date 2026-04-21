<?php
/**
 * Front Page Template — Gorvita
 *
 * 1:1 port of the Claude Design React prototype to PHP.
 * All markup is scoped under .gorvita-homepage-root so legacy
 * Blocksy / WooCommerce styles never leak in.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

get_header();

/**
 * Pull bestsellers from WooCommerce. If the store has no products yet
 * (e.g. fresh staging), fall back to the curated design sample so the
 * layout always renders.
 */
$gorvita_products = [];
if (class_exists('WC_Product_Query')) {
    $query = new WC_Product_Query([
        'limit'    => 8,
        'status'   => 'publish',
        'orderby'  => 'popularity',
        'order'    => 'DESC',
        'stock_status' => 'instock',
    ]);
    $gorvita_products = $query->get_products();
}

$gorvita_product_fallback = [
    ['id'=>'mascz','name'=>'Maść Żywokostowa z Jałowcem i MSM','tag'=>'Stawy · Maść','desc'=>'Legendarna receptura. Woda mineralna z Rabki + ekstrakt żywokostu.','price'=>'32,50','old'=>null,'badge'=>'best','rating'=>4.9,'reviews'=>891,'shade'=>'#b8a078','img'=>'mascz.jpg'],
    ['id'=>'alantoin','name'=>'Alantoin maść A, E, F','tag'=>'Skóra · Maść','desc'=>'Kompleks witaminowy z alantoiną i panthenolem. Dla dzieci i dorosłych z problemami skórnymi.','price'=>'18,90','old'=>null,'badge'=>'best','rating'=>4.8,'reviews'=>612,'shade'=>'#9cb5c7','img'=>'alantoin.jpg'],
    ['id'=>'rabka-spray','name'=>'Rabka SPA Minerale Spray','tag'=>'Skóra · Spray','desc'=>'Mgiełka solankowa z wody leczniczej Uzdrowiska Rabka z wyciągami ziołowymi.','price'=>'36,00','old'=>null,'badge'=>'new','rating'=>4.7,'reviews'=>187,'shade'=>'#7fa8c9','img'=>'rabka-spray.jpg'],
    ['id'=>'zyworodka','name'=>'Żyworódka w płynie','tag'=>'Skóra · Atomizer','desc'=>'Wzbogacona ekstraktem z aloesu. Zawiera leczniczą wodę mineralną z Rabki.','price'=>'42,00','old'=>null,'badge'=>null,'rating'=>4.8,'reviews'=>341,'shade'=>'#8db87a','img'=>'zyworodka.jpg'],
    ['id'=>'olejek-pichtowy','name'=>'Olejek pichtowy w żelu','tag'=>'Relaks · Żel','desc'=>'SPA Program — mikroelementy i biopierwiastki z jodły syberyjskiej oraz minerały wody leczniczej z Rabki.','price'=>'38,00','old'=>null,'badge'=>null,'rating'=>4.7,'reviews'=>223,'shade'=>'#8fa878','img'=>'olejek-pichtowy.jpg'],
    ['id'=>'spirulina','name'=>'Spirulina 60 kapsułek','tag'=>'Superfood · Kapsułki','desc'=>'Naturalna spirulina — chlorofil, białko roślinne, żelazo. Fitoterapia Gorvita.','price'=>'29,90','old'=>null,'badge'=>null,'rating'=>4.6,'reviews'=>128,'shade'=>'#6b8e5f','img'=>'spirulina.png'],
    ['id'=>'czystek','name'=>'Czystek 60 kapsułek','tag'=>'Odporność · Kapsułki','desc'=>'Cistus incanus — polifenole i antyoksydanty. Tradycyjne wsparcie odporności.','price'=>'24,90','old'=>'29,90','badge'=>'sale','rating'=>4.7,'reviews'=>267,'shade'=>'#a89072','img'=>'czystek.jpg'],
    ['id'=>'resveratrol','name'=>'Resveratrol 60 kapsułek','tag'=>'Anti-aging · Kapsułki','desc'=>'Źródło cynku oraz witaminy B6. Ekstrakt z czerwonych winogron dla młodzieńczej witalności.','price'=>'39,90','old'=>null,'badge'=>'new','rating'=>4.8,'reviews'=>154,'shade'=>'#6b4e7a','img'=>'resveratrol.png'],
];

/**
 * Render one product card.
 * Accepts either a WC_Product or a fallback array.
 */
function gorvita_render_product_card($p) {
    $is_wc = is_a($p, 'WC_Product');

    if ($is_wc) {
        $id      = $p->get_id();
        $name    = $p->get_name();
        $desc    = $p->get_short_description() ?: wp_trim_words(wp_strip_all_tags($p->get_description()), 18);
        $price_h = $p->get_price_html();
        $link    = get_permalink($id);
        $img     = get_the_post_thumbnail_url($id, 'woocommerce_single') ?: wc_placeholder_img_src('woocommerce_single');
        $shade   = get_post_meta($id, '_gorvita_shade', true) ?: '#8db87a';
        $badge   = get_post_meta($id, '_gorvita_badge', true) ?: ( $p->is_on_sale() ? 'sale' : '' );
        $cats    = wp_get_post_terms($id, 'product_cat', ['fields'=>'names']);
        $tag     = $cats ? implode(' · ', array_slice($cats, 0, 2)) : '';
        $rating  = $p->get_average_rating();
        $reviews = $p->get_review_count();
        $add_url = $p->add_to_cart_url();
    } else {
        $id      = $p['id'];
        $name    = $p['name'];
        $desc    = $p['desc'];
        $price_h = '<span class="gorvita-prod__price">' . esc_html($p['price']) . ' zł'
                 . ($p['old'] ? '<span class="gorvita-prod__price-old">' . esc_html($p['old']) . ' zł</span>' : '')
                 . '</span>';
        $link    = '#';
        $img     = get_stylesheet_directory_uri() . '/assets/images/' . $p['img'];
        // If local asset missing, fall back to design source path used during dev.
        $local_path = get_stylesheet_directory() . '/assets/images/' . $p['img'];
        if (!file_exists($local_path)) {
            $img = ''; // let CSS placeholder handle it
        }
        $shade   = $p['shade'];
        $badge   = $p['badge'];
        $tag     = $p['tag'];
        $rating  = $p['rating'];
        $reviews = $p['reviews'];
        $add_url = '#';
    }

    $bg = sprintf(
        'background: radial-gradient(ellipse at 50%% 40%%, color-mix(in oklab, %s 28%%, #F9F7F3) 0%%, #F9F7F3 70%%);',
        esc_attr($shade)
    );

    $badge_label = [
        'best' => 'Bestseller',
        'new'  => 'Nowość',
        'sale' => 'Promocja',
        'cbd'  => 'CBD Gold',
    ];
    ?>
    <article class="gorvita-prod" data-product-id="<?php echo esc_attr($id); ?>">
        <div class="gorvita-prod__media" style="<?php echo $bg; // phpcs:ignore ?>">
            <?php if ($img): ?>
                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>" class="gorvita-prod__img" loading="lazy" width="400" height="400">
            <?php endif; ?>

            <?php if ($badge && isset($badge_label[$badge])): ?>
                <div class="gorvita-prod__badges">
                    <span class="gorvita-badge gorvita-badge--<?php echo esc_attr($badge); ?>"><?php echo esc_html($badge_label[$badge]); ?></span>
                </div>
            <?php endif; ?>

            <button type="button" class="gorvita-prod__wish" aria-label="Dodaj do ulubionych" data-wish="<?php echo esc_attr($id); ?>">
                <?php gorvita_icon('heart', 16); ?>
            </button>

            <?php if ($is_wc && $p->is_purchasable() && $p->is_in_stock()): ?>
                <a class="gorvita-prod__quickadd" href="<?php echo esc_url($add_url); ?>" data-product_id="<?php echo esc_attr($id); ?>" data-quantity="1" rel="nofollow">
                    <?php gorvita_icon('plus', 16); ?> Do koszyka
                </a>
            <?php endif; ?>
        </div>

        <div class="gorvita-prod__body">
            <?php if ($tag): ?><span class="gorvita-prod__tag"><?php echo esc_html($tag); ?></span><?php endif; ?>
            <h3 class="gorvita-prod__title"><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($name); ?></a></h3>
            <p class="gorvita-prod__desc"><?php echo esc_html($desc); ?></p>
            <div class="gorvita-prod__foot">
                <?php if ($is_wc): ?>
                    <div class="gorvita-prod__price"><?php echo $price_h; // phpcs:ignore ?></div>
                <?php else: ?>
                    <?php echo $price_h; // phpcs:ignore ?>
                <?php endif; ?>
                <?php if ($rating): ?>
                    <div class="gorvita-prod__rating">
                        <?php gorvita_icon('star', 13); ?> <?php echo esc_html(number_format((float)$rating, 1, '.', '')); ?>
                        <?php if ($reviews): ?><span style="opacity:.5">(<?php echo esc_html($reviews); ?>)</span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

/**
 * Inline SVG icon helper — lucide-style, 1.5px stroke.
 */
function gorvita_icon($name, $size = 20) {
    $s = sprintf('width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"', $size, $size);
    $paths = [
        'search'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'cart'        => '<path d="M3 4h3l2 12h11l2-8H7"/><circle cx="9" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
        'heart'       => '<path d="M12 20s-7-4.5-9-9a4.5 4.5 0 0 1 9-2.5 4.5 4.5 0 0 1 9 2.5c-2 4.5-9 9-9 9z"/>',
        'chevron'     => '<path d="m6 9 6 6 6-6"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left'  => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'plus'        => '<path d="M12 5v14M5 12h14"/>',
        'check'       => '<path d="m5 12 5 5L20 7"/>',
        'truck'       => '<path d="M3 7h11v10H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/>',
        'leaf'        => '<path d="M20 4c0 8-6 14-14 14-1 0-2-.2-2-.2s0-8 6-13c3-2.5 7-2 10-.8z"/><path d="M4 18C8 14 12 10 20 4"/>',
        'shield'      => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/>',
        'return'      => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'droplet'     => '<path d="M12 3s7 7 7 12a7 7 0 0 1-14 0c0-5 7-12 7-12z"/>',
        'certificate' => '<circle cx="12" cy="10" r="5"/><path d="m9 14-2 7 5-3 5 3-2-7"/>',
        'mountain'    => '<path d="m2 20 6-10 4 6 3-4 7 8z"/>',
        'star'        => '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6L12 17l-5.4 2.8 1-6L3.3 9.4l6-.9L12 3z"/></svg>',
    ];
    if ($name === 'star') {
        echo $paths[$name]; // phpcs:ignore
        return;
    }
    if (!isset($paths[$name])) return;
    echo '<svg xmlns="http://www.w3.org/2000/svg" '.$s.'>'.$paths[$name].'</svg>'; // phpcs:ignore
}

$img_url = get_stylesheet_directory_uri() . '/assets/images';
?>

<div class="gorvita-homepage-root">

    <!-- VALUE STRIP (marquee) -->
    <div class="gorvita-vstrip" aria-hidden="true">
        <div class="gorvita-vstrip__track">
            <?php
            $vstrip_items = [
                ['icon'=>'truck',       'strong'=>'Dostawa 24H',        'text'=>'— od 149 zł gratis'],
                ['icon'=>'leaf',        'strong'=>'100% naturalne',     'text'=>'— ekstrakty roślinne'],
                ['icon'=>'certificate', 'strong'=>'Tradycja od 1989',   'text'=>'— polski producent'],
                ['icon'=>'return',      'strong'=>'30 dni na zwrot',    'text'=>'— bezpieczne zakupy'],
                ['icon'=>'shield',      'strong'=>'GMP + ISO 9001',     'text'=>'— farmaceutyczny standard'],
            ];
            // Duplicate to achieve seamless marquee loop.
            foreach (array_merge($vstrip_items, $vstrip_items) as $i => $it):
            ?>
                <div class="gorvita-vstrip__item">
                    <?php gorvita_icon($it['icon'], 18); ?>
                    <span><strong><?php echo esc_html($it['strong']); ?></strong> <?php echo esc_html($it['text']); ?></span>
                    <span class="dot"></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- HERO -->
    <section class="gorvita-hero">
        <div class="gorvita-hero__bg">
            <?php echo wp_get_attachment_image(249, 'full', false, [
                'class' => 'gorvita-hero__bg-img',
                'alt'   => '',
                'fetchpriority' => 'high',
                'decoding' => 'async',
            ]); ?>
            <div class="gorvita-hero__bg-fade"></div>
        </div>
        <div class="gorvita-wrap gorvita-hero__grid">
            <div class="gorvita-hero__copy">
                <div class="gorvita-eyebrow">EST. 1989 · SZCZAWA, GORCE</div>
                <h1 class="gorvita-hero__title">
                    Czysta natura,<br>
                    <em>zmierzona</em><br>
                    <span class="serif-soft">laboratoryjnie.</span>
                </h1>
                <p class="gorvita-hero__sub">
                    Suplementy i kosmetyki ziołowe tworzone w polskich górach — na bazie wody uzdrowiskowej z Rabki-Zdrój. Farmaceutyczny standard GMP, polska tradycja ziołolecznictwa.
                </p>
                <div class="gorvita-hero__cta">
                    <a class="gorvita-btn gorvita-btn--primary" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
                        Odkryj produkty <?php gorvita_icon('arrow-right', 16); ?>
                    </a>
                    <a class="gorvita-btn gorvita-btn--ghost" href="/o-marce/">Nasza historia</a>
                </div>
                <div class="gorvita-hero__stats">
                    <div>
                        <div class="gorvita-stat-num">37</div>
                        <div class="gorvita-stat-label">Lat tradycji</div>
                    </div>
                    <div>
                        <div class="gorvita-stat-num"><?php echo esc_html(wp_count_posts('product')->publish ?: '108'); ?></div>
                        <div class="gorvita-stat-label">Produktów</div>
                    </div>
                    <div>
                        <div class="gorvita-stat-num">14<sup>+</sup></div>
                        <div class="gorvita-stat-label">Minerałów</div>
                    </div>
                </div>
            </div>
            <div class="gorvita-hero__visual">
                <img class="gorvita-hero__visual-img" src="<?php echo esc_url($img_url . '/gorce2.webp'); ?>" alt="Gorce — szczyty nad mgłą">
                <span class="gorvita-hero__visual-label">SZCZAWA · 49°34'N 20°16'E</span>
                <div class="gorvita-hero__droplet" aria-hidden="true"></div>
                <div class="gorvita-hero__card">
                    <div class="gorvita-hero__card-dot"></div>
                    <div class="gorvita-hero__card-text">
                        <b>Źródło aktywne</b>
                        <span>Wypływ 14 m³/h · 8.4°C</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CATEGORIES -->
    <section class="gorvita-section gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-section__head">
                <div>
                    <div class="gorvita-eyebrow" style="margin-bottom:20px">POTRZEBY</div>
                    <h2>Wybierz według tego,<br><em>co Cię dotyczy.</em></h2>
                </div>
                <p>Nie suplement — rozwiązanie. Nawiguj po kategoriach problemów, które znasz z życia.</p>
            </div>
            <div class="gorvita-cat-band">
                <?php
                $cats = [
                    ['label'=>'Stawy i mięśnie',     'slug'=>'stawy-miesnie',        'count'=>18, 'big'=>true,  'shade'=>'linear-gradient(160deg,#3a4a28,#2d5016)', 'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/stawy_miesnie.jpg'],
                    ['label'=>'Odporność',           'slug'=>'odpornosc',            'count'=>14, 'big'=>false, 'shade'=>'linear-gradient(160deg,#8ea07a,#6B8E5F)',  'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/odpornosc.jpg'],
                    ['label'=>'Skóra i ciało',       'slug'=>'skora-cialo',          'count'=>22, 'big'=>false, 'shade'=>'linear-gradient(160deg,#a8bfa0,#8ea07a)',  'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/skora_cialo.jpg'],
                    ['label'=>'Krążenie',            'slug'=>'krazenie',             'count'=>11, 'big'=>false, 'shade'=>'linear-gradient(160deg,#3a4a5a,#4a7a94)',  'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/krazenie.jpg'],
                    ['label'=>'CBD · Konopie',       'slug'=>'cbd-konopie',          'count'=>9,  'big'=>false, 'shade'=>'linear-gradient(160deg,#3a3320,#6b5a33)',  'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/cbd.jpg'],
                    ['label'=>'Wątroba i trawienie', 'slug'=>'watroba-trawienie',    'count'=>12, 'big'=>false, 'shade'=>'linear-gradient(160deg,#6b5a33,#8B7355)',  'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/watroba_trawienie.jpg'],
                    ['label'=>'Energia i stres',     'slug'=>'energia-stres',        'count'=>8,  'big'=>false, 'shade'=>'linear-gradient(160deg,#5a6b4a,#8ea07a)',  'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/energia_stres.jpg'],
                    ['label'=>'Nos, gardło, usta',   'slug'=>'nos-gardlo-jama-ustna','count'=>7,  'big'=>false, 'shade'=>'linear-gradient(160deg,#c9a961,#d4bb7a)',  'img'=>'https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/nos_gardlo_spray.png'],
                ];
                foreach ($cats as $c):
                    $term  = get_term_by('slug', $c['slug'], 'product_cat');
                    $count = $term && !is_wp_error($term) && $term->count ? $term->count : $c['count'];
                    $link  = $term && !is_wp_error($term) ? get_term_link($term) : '/product-category/' . $c['slug'] . '/';
                    $ph_style = !empty($c['img'])
                        ? 'background-image: url(' . esc_url($c['img']) . ');'
                        : 'background: ' . esc_attr($c['shade']) . ';';
                ?>
                    <a href="<?php echo esc_url($link); ?>" class="gorvita-cat-tile <?php echo $c['big'] ? 'gorvita-cat-tile--big' : ''; ?>">
                        <div class="gorvita-cat-tile__ph" style="<?php echo $ph_style; // phpcs:ignore ?>"></div>
                        <div class="gorvita-cat-tile__label">
                            <span class="count"><?php echo esc_html(str_pad($count, 2, '0', STR_PAD_LEFT)); ?> · kategoria</span>
                            <?php echo esc_html($c['label']); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- BESTSELLERS -->
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-section__head">
                <div>
                    <div class="gorvita-eyebrow" style="margin-bottom:20px">BESTSELLERY</div>
                    <h2>Sprawdzone przez<br><em>trzy pokolenia.</em></h2>
                </div>
                <p>Produkty, po które wracają nasi klienci. Receptury dopracowane od 1989 roku.</p>
            </div>
            <div class="gorvita-carousel-wrap" data-carousel>
                <div class="gorvita-prod-grid">
                    <?php
                    $render_source = $gorvita_products ?: array_slice($gorvita_product_fallback, 0, 4);
                    $render_source = array_slice($render_source, 0, 4);
                    foreach ($render_source as $p) {
                        gorvita_render_product_card($p);
                    }
                    ?>
                </div>
                <div class="gorvita-carousel__arrows" aria-hidden="true">
                    <button class="gorvita-carousel__btn gorvita-carousel__btn--prev" aria-label="Poprzednie produkty"><?php gorvita_icon('arrow-left', 18); ?></button>
                    <button class="gorvita-carousel__btn gorvita-carousel__btn--next" aria-label="Następne produkty"><?php gorvita_icon('arrow-right', 18); ?></button>
                </div>
                <div class="gorvita-carousel__dots" role="tablist" aria-label="Produkty"></div>
            </div>
        </div>
    </section>

    <!-- SPRING / STORY SPLIT -->
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-spring">
                <div class="gorvita-spring__inner">
                    <div class="gorvita-spring__visual">
                        <img class="gorvita-spring__visual-img" src="<?php echo esc_url($img_url . '/strumien.png'); ?>" alt="Strumień w Gorcach" loading="lazy">
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
                        <div class="gorvita-eyebrow">ŹRÓDŁO</div>
                        <h2>Woda, która<br><em>leczy od wieków.</em></h2>
                        <p>W sercu Gorców, w uzdrowiskowej wsi Szczawa, wypływa woda mineralna bogata w wapń, magnez i żelazo. Ta sama, która przez wieki leczyła górali — dziś stanowi bazę naszych maści, żeli i sprayów.</p>
                        <a class="gorvita-link-arrow" style="color:var(--g-sage-light)" href="/o-marce/">
                            Poznaj historię źródła <?php gorvita_icon('arrow-right', 16); ?>
                        </a>
                        <div class="gorvita-spring__stats">
                            <div>
                                <div class="gorvita-spring__stat-num">8.4<sup>°C</sup></div>
                                <div class="gorvita-spring__stat-label">Temp. źródła</div>
                            </div>
                            <div>
                                <div class="gorvita-spring__stat-num">1420<sup>mg/l</sup></div>
                                <div class="gorvita-spring__stat-label">Mineralizacja</div>
                            </div>
                            <div>
                                <div class="gorvita-spring__stat-num">14<sup>min.</sup></div>
                                <div class="gorvita-spring__stat-label">Składników</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- USPs -->
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-usp-grid gorvita-usp-grid--photo">
                <?php echo wp_get_attachment_image(279, 'full', false, [
                    'class' => 'gorvita-usp-grid__bg',
                    'alt'   => '',
                    'loading' => 'lazy',
                    'decoding' => 'async',
                ]); ?>
                <div class="gorvita-usp-grid__overlay" aria-hidden="true"></div>
                <?php
                $usps = [
                    ['icon'=>'certificate','h'=>'Tradycja od 1989','p'=>'Trzy pokolenia ziołolecznictwa. Receptury sprawdzone przez tysiące polskich rodzin.'],
                    ['icon'=>'leaf',       'h'=>'Polskie zioła',    'p'=>'100% naturalne ekstrakty z ziół zbieranych w Gorcach i certyfikowanych upraw ekologicznych.'],
                    ['icon'=>'droplet',    'h'=>'Woda uzdrowiskowa','p'=>'Naturalna woda mineralna z Rabki-Zdrój — bogata w minerały, wykorzystywana w maściach i żelach.'],
                    ['icon'=>'shield',     'h'=>'GMP + ISO 9001',   'p'=>'Laboratorium certyfikowane farmaceutycznie. Każda partia badana — bez kompromisów.'],
                ];
                foreach ($usps as $u): ?>
                    <div class="gorvita-usp">
                        <div class="gorvita-usp__icon"><?php gorvita_icon($u['icon'], 22); ?></div>
                        <h4><?php echo esc_html($u['h']); ?></h4>
                        <p><?php echo esc_html($u['p']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- INGREDIENT EXPLORER -->
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-section__head">
                <div>
                    <div class="gorvita-eyebrow" style="margin-bottom:20px">WYBRANE SKŁADNIKI</div>
                    <h2>Poznaj to, co<br><em>wchłania Twoja skóra.</em></h2>
                </div>
                <p>Każdy składnik opisany, każda partia przebadana. Transparentność w pełni.</p>
            </div>
            <?php
            $ings = [
                ['key'=>'zywokost',    'name'=>'Żywokost lekarski',  'latin'=>'Symphytum officinale',  'desc'=>'Wielowiekowy składnik tradycyjnego zielarstwa polskiego. Zawiera alantoinę — naturalny regenerator tkanek. Wspiera regenerację stawów, ścięgien i skóry po urazach.', 'props'=>['Regeneracja','Anti-inflammatory','Alantoina 0.8%'], 'img_id'=>315],
                ['key'=>'cbd',         'name'=>'CBD Full-spectrum',  'latin'=>'Cannabis sativa L.',     'desc'=>'Kannabidiol z ekologicznych konopi siewnych. Zimnotłoczony, bez THC. Wspiera układ endokannabinoidowy — równowaga, sen, regeneracja.', 'props'=>['<0.2% THC','Full-spectrum','Certyfikat COA'],   'img_id'=>313],
                ['key'=>'kolagen',     'name'=>'Kolagen rybny',      'latin'=>'Type I Marine Collagen', 'desc'=>'Hydrolizat kolagenu typu I o niskiej masie cząsteczkowej — 90% biodostępność. Budulec skóry, stawów i chrząstki.', 'props'=>['Typ I','90% biodost.','10 kDa'],                            'img_id'=>312],
                ['key'=>'rokitnik',    'name'=>'Rokitnik',           'latin'=>'Hippophae rhamnoides',   'desc'=>'Superowoc północy — 15× więcej witaminy C niż cytryna. Bogaty w omega-7 z rzadkimi flawonoidami. Odporność i witalność.', 'props'=>['Wit. C 200mg','Omega-7','Flawonoidy'],                 'img_id'=>314],
            ];
            ?>
            <div class="gorvita-ingredients">
                <div class="gorvita-ingr-cards" role="list">
                    <?php foreach ($ings as $ing): ?>
                        <article class="gorvita-ingr-card" role="listitem">
                            <div class="gorvita-ingr-card__img">
                                <?php echo wp_get_attachment_image($ing['img_id'], 'medium_large', false, [
                                    'class' => 'gorvita-ingr-card__photo',
                                    'alt'   => $ing['name'] . ' — ' . $ing['latin'],
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                ]); ?>
                                <span class="gorvita-ingr-card__latin-overlay"><?php echo esc_html($ing['latin']); ?></span>
                            </div>
                            <div class="gorvita-ingr-card__body">
                                <h3><?php echo esc_html($ing['name']); ?></h3>
                                <div class="gorvita-ingr-card__latin"><?php echo esc_html($ing['latin']); ?></div>
                                <p><?php echo esc_html($ing['desc']); ?></p>
                                <div class="gorvita-ingr-card__props">
                                    <?php foreach ($ing['props'] as $pr): ?><span><?php echo esc_html($pr); ?></span><?php endforeach; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="gorvita-ingredients__hint">To tylko wybrane. Pełny leksykon — z certyfikatami i badaniami — znajdziesz na <strong>gorvita.pl</strong>.</p>
                <div class="gorvita-ingredients__cta">
                    <a class="gorvita-btn gorvita-btn--primary" href="https://www.gorvita.pl/leksykon#leksykon">
                        Zobacz pełny leksykon składników
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CBD CALLOUT -->
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-cbd-callout">
                <div class="gorvita-cbd-callout__grid">
                    <div class="gorvita-cbd-callout__copy">
                        <div class="gorvita-eyebrow">LINIA CBD GOLD · NOWOŚĆ</div>
                        <h2>Polska konopia,<br><em>premium extraction.</em></h2>
                        <p>Pierwsza polska linia CBD zimnotłoczonego w standardzie farmaceutycznym. Od ekologicznych upraw do laboratorium — pełen łańcuch pod kontrolą Gorvity.</p>
                        <div style="margin-top: 32px">
                            <a class="gorvita-btn gorvita-btn--gold" href="/cbd/">
                                Zobacz linię CBD <?php gorvita_icon('arrow-right', 16); ?>
                            </a>
                        </div>
                    </div>
                    <div class="gorvita-cbd-callout__visual">
                        <img class="gorvita-cbd-callout__visual-img" src="https://gorvita.srv1594477.hstgr.cloud/wp-content/uploads/2026/04/cbd.jpg" alt="Polska konopia CBD Gorvita" loading="lazy">
                        <div class="gorvita-cbd-callout__visual-grade"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CATALOG (remaining products) -->
    <?php if ($gorvita_products && count($gorvita_products) > 4): ?>
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-section__head">
                <div>
                    <div class="gorvita-eyebrow" style="margin-bottom:20px">KATALOG</div>
                    <h2>Więcej zaufanych<br><em>formuł Gorvita.</em></h2>
                </div>
                <a class="gorvita-link-arrow" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
                    Zobacz wszystkie produkty <?php gorvita_icon('arrow-right', 16); ?>
                </a>
            </div>
            <div class="gorvita-carousel-wrap" data-carousel>
                <div class="gorvita-prod-grid">
                    <?php
                    foreach (array_slice($gorvita_products, 4, 4) as $p) {
                        gorvita_render_product_card($p);
                    }
                    ?>
                </div>
                <div class="gorvita-carousel__arrows" aria-hidden="true">
                    <button class="gorvita-carousel__btn gorvita-carousel__btn--prev" aria-label="Poprzednie produkty"><?php gorvita_icon('arrow-left', 18); ?></button>
                    <button class="gorvita-carousel__btn gorvita-carousel__btn--next" aria-label="Następne produkty"><?php gorvita_icon('arrow-right', 18); ?></button>
                </div>
                <div class="gorvita-carousel__dots" role="tablist" aria-label="Produkty"></div>
            </div>
        </div>
    </section>
    <?php elseif (!$gorvita_products): ?>
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-section__head">
                <div>
                    <div class="gorvita-eyebrow" style="margin-bottom:20px">KATALOG</div>
                    <h2>Więcej zaufanych<br><em>formuł Gorvita.</em></h2>
                </div>
                <a class="gorvita-link-arrow" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
                    Zobacz wszystkie produkty <?php gorvita_icon('arrow-right', 16); ?>
                </a>
            </div>
            <div class="gorvita-carousel-wrap" data-carousel>
                <div class="gorvita-prod-grid">
                    <?php foreach (array_slice($gorvita_product_fallback, 4, 4) as $p) gorvita_render_product_card($p); ?>
                </div>
                <div class="gorvita-carousel__arrows" aria-hidden="true">
                    <button class="gorvita-carousel__btn gorvita-carousel__btn--prev" aria-label="Poprzednie produkty"><?php gorvita_icon('arrow-left', 18); ?></button>
                    <button class="gorvita-carousel__btn gorvita-carousel__btn--next" aria-label="Następne produkty"><?php gorvita_icon('arrow-right', 18); ?></button>
                </div>
                <div class="gorvita-carousel__dots" role="tablist" aria-label="Produkty"></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- TESTIMONIALS -->
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-section__head">
                <div>
                    <div class="gorvita-eyebrow" style="margin-bottom:20px">OPINIE</div>
                    <h2>Zaufanie, które<br><em>czuć codziennie.</em></h2>
                </div>
                <p>Ponad 12 000 zweryfikowanych opinii. Średnia ocena 4.8 / 5.</p>
            </div>
            <div class="gorvita-testim-grid">
                <?php
                $testims = [
                    ['q'=>'Maść żywokostowa uratowała mi kolana po sezonie biegowym. Nic z apteki tak nie zadziałało.','n'=>'Anna K.','m'=>'Kraków · Zweryfikowano','i'=>'AK'],
                    ['q'=>'Spray Rabka SPA — moja skóra atopowa wreszcie odpoczęła. Profesjonalna jakość, polska marka.','n'=>'Piotr W.','m'=>'Warszawa · Zweryfikowano','i'=>'PW'],
                    ['q'=>'Alantoin maść to codzienność mojej mamy. Najlepsze na problemy skórne — tylko Gorvita.','n'=>'Magda R.','m'=>'Gdańsk · Zweryfikowano','i'=>'MR'],
                ];
                foreach ($testims as $t): ?>
                    <div class="gorvita-testim">
                        <div class="gorvita-testim__stars">
                            <?php for ($s = 0; $s < 5; $s++) gorvita_icon('star', 14); ?>
                        </div>
                        <div class="gorvita-testim__quote">„<?php echo esc_html($t['q']); ?>"</div>
                        <div class="gorvita-testim__foot">
                            <div class="gorvita-testim__avatar"><?php echo esc_html($t['i']); ?></div>
                            <div>
                                <div class="gorvita-testim__name"><?php echo esc_html($t['n']); ?></div>
                                <div class="gorvita-testim__meta"><?php echo esc_html($t['m']); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- NEWSLETTER -->
    <section class="gorvita-section gorvita-section--tight gorvita-reveal">
        <div class="gorvita-wrap">
            <div class="gorvita-newsletter">
                <div class="gorvita-newsletter__inner">
                    <div class="gorvita-eyebrow" style="margin-bottom:20px">DOŁĄCZ DO KLUBU GORVITA</div>
                    <h2>−10% na pierwsze zakupy<br>+ leksykon <em>ziół polskich.</em></h2>
                    <p>Raz w miesiącu: nowości, porady fitoterapeutyczne i oferty dla subskrybentów.</p>
                    <form class="gorvita-newsletter__form" onsubmit="event.preventDefault(); this.querySelector('input').value='';">
                        <input type="email" placeholder="twoj@email.pl" aria-label="Adres e-mail" required>
                        <button class="gorvita-btn gorvita-btn--primary" type="submit">Zapisz się</button>
                    </form>
                    <p class="gorvita-newsletter__disclaimer">Bez spamu · Wypisujesz się jednym kliknięciem</p>
                </div>
            </div>
        </div>
    </section>

</div>

<?php
get_footer();
