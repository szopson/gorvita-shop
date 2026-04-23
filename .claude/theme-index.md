# gorvita-child — indeks plików i funkcji

## Pliki główne
| Plik | Opis |
|------|------|
| `style.css` | CSS variables, design tokens, komponenty BEM |
| `functions.php` | Enqueue, theme setup, SVG icon helper |
| `front-page.php` | Szablon strony głównej |
| `footer.php` | Stopka |

## inc/ — moduły PHP
| Plik | Opis |
|------|------|
| `b2b.php` | B2B: rola b2b_customer, hidden pricing, meta _b2b_price, formularz rejestracji |
| `mega-menu.php` | Mega menu z kategoriami i featured products |
| `mobile-ux.php` | Mobile: dolna nawigacja, sticky ATC, progress checkout, shipping hint |
| `performance.php` | Optymalizacje wydajności |
| `quick-views.php` | Shortcodes: new products, bestsellers, sale, featured, category tiles |
| `search.php` | AJAX live search z formatowaniem wyników |
| `translations.php` | Polskie tłumaczenia stringów WooCommerce/WP |
| `wishlist.php` | Lista życzeń (session + user meta), AJAX toggle, shortcode |
| `woocommerce.php` | Customizacje WooCommerce (karty produktów, checkout, etc.) |

## Kluczowe funkcje — szybka referencja
```
gorvita_icon($name, $size)          — SVG icon helper (functions.php:140)
gorvita_enqueue_styles()            — rejestracja CSS/JS (functions.php:18)

gorvita_b2b_create_role()           — tworzy rolę b2b_customer (b2b.php:14)
gorvita_is_b2b_user($user_id)       — sprawdza czy user = B2B (b2b.php:26)
gorvita_get_b2b_price($product_id)  — pobiera cenę B2B z meta (b2b.php:43)
gorvita_filter_price_html()         — ukrywa cenę dla gości (b2b.php:51)
gorvita_b2b_registration_shortcode()— formularz rejestracji B2B (b2b.php:149)

gorvita_search_ajax()               — endpoint AJAX search (search.php:17)
gorvita_toggle_wishlist()           — dodaj/usuń z wishlisty (wishlist.php:28)
gorvita_render_mobile_bottom_nav()  — dolna nawigacja mobile (mobile-ux.php:20)
```

## assets/
```
assets/css/         — dodatkowe CSS (jeśli są)
assets/js/          — dodatkowe JS (jeśli są)
assets/images/      — obrazy motywu
```

## woocommerce/
Overrides szablonów WooCommerce (zgodnie ze strukturą /woocommerce/).
