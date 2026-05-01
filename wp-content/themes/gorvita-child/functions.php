<?php
require_once get_stylesheet_directory() . '/inc/translations.php';

function gorvita_preload_hero() {
    echo '<link rel="preload" as="image" href="' . esc_url( get_stylesheet_directory_uri() . '/assets/images/gorce2.webp' ) . '" fetchpriority="high">' . "\n";
}
add_action( 'wp_head', 'gorvita_preload_hero', 1 );

function gorvita_theme_setup() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'gorvita_theme_setup' );

function gorvita_enqueue_styles() {
    wp_enqueue_style( 'gorvita-child-style', get_stylesheet_uri() );
    wp_enqueue_script( 'gorvita-animations', get_stylesheet_directory_uri() . '/assets/js/animations.js', [], '1.0', true );
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
        [ 'icon' => 'droplet',     'h' => 'Woda uzdrowiskowa',   'p' => 'Naturalna woda mineralna z Rabki-Zdrój — bogata w minerały, wykorzystywana w maściach i żelach.' ],
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
        [ 'icon' => 'truck',       'strong' => 'Dostawa 24H',      'text' => '— od 249 zł gratis' ],
        [ 'icon' => 'leaf',        'strong' => '100% naturalne',    'text' => '— ekstrakty roślinne' ],
        [ 'icon' => 'certificate', 'strong' => 'Tradycja od 1989',  'text' => '— polski producent' ],
        [ 'icon' => 'return',      'strong' => '14 dni na zwrot',   'text' => '— bezpieczne zakupy' ],
        [ 'icon' => 'shield',      'strong' => 'GMP + ISO 9001',    'text' => '— farmaceutyczny standard' ],
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
                    <div class="gorvita-eyebrow">ŹRÓDŁO</div>
                    <h2>Woda, która<br><em>leczy od wieków.</em></h2>
                    <p>W sercu Gorców, w uzdrowiskowej wsi Szczawa, wypływa woda mineralna bogata w wapń, magnez i żelazo. Ta sama, która przez wieki leczyła górali — dziś stanowi bazę naszych maści, żeli i sprayów.</p>
                    <a class="gorvita-link-arrow" style="color:var(--gorvita-sage)" href="/o-marce/">
                        Poznaj historię źródła <?php gorvita_icon( 'arrow-right', 16 ); ?>
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
            <?php echo wp_get_attachment_image( 249, 'full', false, [
                'class'         => 'gorvita-hero__bg-img',
                'alt'           => '',
                'fetchpriority' => 'high',
                'loading'       => 'eager',
                'decoding'      => 'sync',
            ] ); // phpcs:ignore ?>
            <div class="gorvita-hero__bg-fade"></div>
        </div>
        <div class="gorvita-wrap gorvita-hero__grid">
            <div class="gorvita-hero__copy">
                <span class="gorvita-hero__eyebrow">EST. 1989 · SZCZAWA, GORCE</span>
                <h1 class="gorvita-hero__title">
                    Z Gorców,<br>
                    <em>od rodziny</em><br>
                    Gorvita.
                </h1>
                <p class="gorvita-hero__sub">
                    Suplementy i kosmetyki ziołowe ręcznie wytwarzane w Szczawie nieprzerwanie od 1989 roku. Woda z Rabki-Zdrój, polskie zioła, farmaceutyczny standard GMP.
                </p>
                <div class="gorvita-hero__cta">
                    <a class="gorvita-hero__btn gorvita-hero__btn--primary" href="<?php echo esc_url( $shop_url ); ?>">
                        Odkryj produkty <?php gorvita_icon( 'arrow-right', 16 ); ?>
                    </a>
                    <a class="gorvita-hero__btn gorvita-hero__btn--ghost" href="/o-marce/">Nasza historia</a>
                </div>
                <div class="gorvita-hero__stats gorvita-hero__stats--anchors">
                    <div class="gorvita-hero__stat">
                        <div class="gorvita-hero__stat-text">od 1989</div>
                        <div class="gorvita-hero__stat-label">rodzinna manufaktura w Gorcach</div>
                    </div>
                    <div class="gorvita-hero__stat">
                        <div class="gorvita-hero__stat-text">Rabka-Zdrój</div>
                        <div class="gorvita-hero__stat-label">źródło wody uzdrowiskowej</div>
                    </div>
                    <div class="gorvita-hero__stat">
                        <div class="gorvita-hero__stat-text">GMP · ISO</div>
                        <div class="gorvita-hero__stat-label">farmaceutyczny standard</div>
                    </div>
                </div>
            </div>
            <div class="gorvita-hero__visual">
                <img class="gorvita-hero__visual-img" src="<?php echo esc_url( $gorce_url ); ?>" alt="Gorce — szczyty nad mgłą" loading="eager" fetchpriority="high" decoding="async">
                <span class="gorvita-hero__visual-label">SZCZAWA · 49°34'N 20°16'E</span>
                <div class="gorvita-hero__card">
                    <div class="gorvita-hero__card-dot"></div>
                    <div class="gorvita-hero__card-text">
                        <b>Źródło aktywne</b>
                        <span>Wypływ 14 m³/h · 8.4°C</span>
                    </div>
                </div>
                <div class="gorvita-hero__droplet" aria-hidden="true"></div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'gorvita-hero', 'gorvita_hero_shortcode' );

function gorvita_hover_image_css() {
    echo '<style>
    .gorvita-hover-img {
        position: absolute !important;
        opacity: 0;
        pointer-events: none;
    }
    .woocommerce ul.products li.product .woocommerce-loop-product__link {
        position: relative;
        display: block;
        overflow: hidden;
    }
    .woocommerce ul.products li.product .woocommerce-loop-product__link img {
        transition: opacity 0.3s ease;
        display: block;
        width: 100%;
    }
    .woocommerce ul.products li.product .gorvita-hover-img {
        position: absolute !important;
        top: 0 !important; left: 0 !important;
        width: 100% !important; height: 100% !important;
        object-fit: contain;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .woocommerce ul.products li.product:hover .woocommerce-loop-product__link img:first-child {
        opacity: 0;
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

        function collectSections() {
          var secs = [];
          headings.forEach(function(h2){
            var content = [];
            var next = h2.nextElementSibling;
            while(next && next.tagName !== "H2") {
              content.push(next);
              next = next.nextElementSibling;
            }
            secs.push({ title: (h2.textContent||"").trim(), header: h2, content: content });
          });
          return secs;
        }

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
