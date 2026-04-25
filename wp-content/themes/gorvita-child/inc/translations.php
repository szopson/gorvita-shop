<?php
/**
 * Polish string overrides for WooCommerce + WordPress UI.
 * Uses gettext filter — works regardless of locale setting.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

add_filter('gettext', 'gorvita_polish_strings', 20, 3);
add_filter('ngettext', 'gorvita_polish_strings_plural', 20, 5);

function gorvita_polish_strings(string $translated, string $original, string $domain): string {
    // Only override WooCommerce + default WP domains
    if (!in_array($domain, ['woocommerce', 'default', 'blocksy'], true)) {
        return $translated;
    }

    static $map = null;
    if ($map === null) {
        $map = [
            // ── Navigation ────────────────────────────────────────────────
            'More'                                   => 'Więcej',
            'Filter by price'                        => 'Filtruj po cenie',
            'Filter by category'                     => 'Filtruj po kategorii',
            'Filter'                                 => 'Filtruj',
            'Price'                                  => 'Cena',
            'Min price'                              => 'Cena min',
            'Max price'                              => 'Cena maks',
            'Reset'                                  => 'Resetuj',
            'Best selling products'                  => 'Najlepiej sprzedające się produkty',

            // ── Cart ──────────────────────────────────────────────────────
            'Cart'                                   => 'Koszyk',
            'Cart totals'                            => 'Podsumowanie koszyka',
            'Update cart'                            => 'Aktualizuj koszyk',
            'Apply coupon'                           => 'Zastosuj kupon',
            'Coupon code'                            => 'Kod kuponu',
            'Coupon:'                                => 'Kupon:',
            'Proceed to checkout'                    => 'Przejdź do kasy',
            'Continue shopping'                      => 'Kontynuuj zakupy',
            'View cart'                              => 'Zobacz koszyk',
            'Empty cart'                             => 'Opróżnij koszyk',
            'Your cart is currently empty.'          => 'Twój koszyk jest pusty.',
            'Return to shop'                         => 'Wróć do sklepu',
            'Free!'                                  => 'Bezpłatnie!',
            'Shipping'                               => 'Dostawa',
            'Enter a coupon code'                    => 'Wpisz kod kuponu',

            // ── Checkout ──────────────────────────────────────────────────
            'Checkout'                               => 'Kasa',
            'Place order'                            => 'Złóż zamówienie',
            'Billing details'                        => 'Dane do faktury',
            'Shipping address'                       => 'Adres dostawy',
            'Ship to a different address?'           => 'Wysłać na inny adres?',
            'Your order'                             => 'Twoje zamówienie',
            'Payment'                                => 'Płatność',
            'Order summary'                          => 'Podsumowanie zamówienia',
            'Order Total'                            => 'Razem',
            'Order total'                            => 'Razem',
            'Have a coupon?'                         => 'Masz kupon?',
            'Click here to enter your coupon code'   => 'Kliknij, aby wpisać kod kuponu',
            'Order notes'                            => 'Uwagi do zamówienia',
            'Notes about your order, e.g. special notes for delivery.' => 'Uwagi, np. wskazówki dla kuriera.',
            'I have read and agree to the website %s'    => 'Przeczytałem/am i akceptuję %s',
            'terms and conditions'                   => 'regulamin',
            'Create an account?'                     => 'Chcesz założyć konto?',
            'Create account password'                => 'Hasło do konta',

            // ── Order received ─────────────────────────────────────────────
            'Thank you. Your order has been received.' => 'Dziękujemy. Twoje zamówienie zostało przyjęte.',
            'Order number:'                          => 'Numer zamówienia:',
            'Date:'                                  => 'Data:',
            'Total:'                                 => 'Razem:',
            'Payment method:'                        => 'Metoda płatności:',

            // ── Shop / loop ────────────────────────────────────────────────
            'Shop'                                   => 'Sklep',
            'Products'                               => 'Produkty',
            'Product'                                => 'Produkt',
            'Price'                                  => 'Cena',
            'Quantity'                               => 'Ilość',
            'Subtotal'                               => 'Suma',
            'Total'                                  => 'Razem',
            'Sale!'                                  => 'Promocja!',
            'Sort by'                                => 'Sortuj według',
            'Default sorting'                        => 'Domyślne sortowanie',
            'Sort by popularity'                     => 'Popularność',
            'Sort by average rating'                 => 'Ocena',
            'Sort by latest'                         => 'Najnowsze',
            'Sort by price: low to high'             => 'Cena: rosnąco',
            'Sort by price: high to low'             => 'Cena: malejąco',
            'Showing all %d results'                 => 'Znaleziono %d produktów',
            'No products were found matching your selection.' => 'Nie znaleziono produktów.',
            'Showing the single result'              => 'Znaleziono 1 produkt',
            'Showing %1$d–%2$d of %3$d results'      => 'Wyniki %1$d–%2$d z %3$d',

            // ── Single product ─────────────────────────────────────────────
            'Add to cart'                            => 'Do koszyka',
            'Added to cart'                          => 'Dodano do koszyka',
            'Read more'                              => 'Szczegóły',
            'SKU:'                                   => 'Symbol:',
            'SKU'                                    => 'Symbol',
            'Category:'                              => 'Kategoria:',
            'Categories:'                            => 'Kategorie:',
            'Tag:'                                   => 'Tag:',
            'Tags:'                                  => 'Tagi:',
            'Description'                            => 'Opis',
            'Additional information'                 => 'Informacje dodatkowe',
            'Reviews'                                => 'Opinie',
            'In stock'                               => 'Dostępny',
            'Out of stock'                           => 'Niedostępny',
            'In Stock'                               => 'Dostępny',
            'Out of Stock'                           => 'Niedostępny',
            'Related products'                       => 'Powiązane produkty',
            'You may also like&hellip;'              => 'Może Cię zainteresować…',
            'You may also like...'                   => 'Może Cię zainteresować…',

            // ── Reviews ────────────────────────────────────────────────────
            'Be the first to review &ldquo;%s&rdquo;'      => 'Napisz pierwszą opinię o &ldquo;%s&rdquo;',
            'Be the first to review "%s"'            => 'Napisz pierwszą opinię o "%s"',
            'There are no reviews yet.'              => 'Brak opinii.',
            'Add a review'                           => 'Dodaj opinię',
            'Submit'                                 => 'Wyślij',
            'Your review'                            => 'Twoja opinia',
            'Your rating'                            => 'Twoja ocena',
            'Your name'                              => 'Twoje imię',
            'Your email'                             => 'Twój adres e-mail',
            'Logged in as %s.'                       => 'Zalogowano jako %s.',
            'Log out?'                               => 'Wylogować się?',
            'review'                                 => 'opinia',
            'reviews'                                => 'opinie',
            'Verified owner'                         => 'Zweryfikowany kupujący',
            'rated'                                  => 'ocenił/a',

            // ── My account ─────────────────────────────────────────────────
            'My account'                             => 'Moje konto',
            'Orders'                                 => 'Zamówienia',
            'Downloads'                              => 'Pobrane pliki',
            'Addresses'                              => 'Adresy',
            'Account details'                        => 'Dane konta',
            'Log out'                                => 'Wyloguj się',
            'Logout'                                 => 'Wyloguj się',
            'Login'                                  => 'Zaloguj się',
            'Log in'                                 => 'Zaloguj się',
            'Username or email address'              => 'Nazwa użytkownika lub adres e-mail',
            'Password'                               => 'Hasło',
            'Remember me'                            => 'Zapamiętaj mnie',
            'Lost your password?'                    => 'Nie pamiętasz hasła?',
            'Register'                               => 'Zarejestruj się',
            'Register Account'                       => 'Zarejestruj konto',
            'Username'                               => 'Nazwa użytkownika',
            'Email address'                          => 'Adres e-mail',
            'First name'                             => 'Imię',
            'Last name'                              => 'Nazwisko',
            'Company name (optional)'                => 'Firma (opcjonalnie)',
            'Country / Region'                       => 'Kraj / Region',
            'Street address'                         => 'Ulica i numer',
            'Apartment, suite, unit, etc. (optional)' => 'Mieszkanie / lokal (opcjonalnie)',
            'Town / City'                            => 'Miasto',
            'State / County'                         => 'Województwo',
            'Postcode / ZIP'                         => 'Kod pocztowy',
            'Phone'                                  => 'Telefon',
            'Save address'                           => 'Zapisz adres',
            'Edit'                                   => 'Edytuj',
            'Add'                                    => 'Dodaj',
            'No %s set.'                             => 'Brak adresu %s.',
            'View'                                   => 'Podgląd',
            'Change password'                        => 'Zmień hasło',
            'Save changes'                           => 'Zapisz zmiany',
            'Current password (leave blank to leave unchanged)' => 'Aktualne hasło (pozostaw puste, by nie zmieniać)',
            'New password (leave blank to leave unchanged)'     => 'Nowe hasło',
            'Confirm new password'                   => 'Potwierdź nowe hasło',

            // ── Search ─────────────────────────────────────────────────────
            'Search'                                 => 'Szukaj',
            'Search for:'                            => 'Szukaj:',
            'Search results for: &ldquo;%s&rdquo;'  => 'Wyniki wyszukiwania: &ldquo;%s&rdquo;',
            'Nothing found'                          => 'Brak wyników',
            'Sorry, but nothing matched your search terms.' => 'Brak wyników dla podanej frazy.',

            // ── Pagination ─────────────────────────────────────────────────
            'Previous'                               => 'Poprzednia',
            'Next'                                   => 'Następna',
            'Older posts'                            => 'Starsze wpisy',
            'Newer posts'                            => 'Nowsze wpisy',

            // ── Notices ────────────────────────────────────────────────────
            'Added &ldquo;%s&rdquo; to your cart.'  => 'Dodano &ldquo;%s&rdquo; do koszyka.',
            'Coupon code applied successfully.'      => 'Kupon został zastosowany.',
            'Coupon code already applied!'           => 'Kupon jest już zastosowany!',
            'Your cart has been updated.'            => 'Koszyk został zaktualizowany.',

            // ── Breadcrumbs ────────────────────────────────────────────────
            'Home'                                   => 'Strona główna',
            'Breadcrumb'                             => 'Nawigacja',

            // ── Coming Soon page (WooCommerce 8.2+) ───────────────────────
            'Great things are on the horizon'        => 'Coś wyjątkowego już wkrótce',
            'Something big is brewing! Our store is in the works and will be launching soon!' => 'Pracujemy nad czymś wyjątkowym! Nasz sklep będzie wkrótce dostępny.',
            'Be the first to know when we launch'    => 'Bądź pierwszym, który się dowie o otwarciu',
            'Coming Soon'                            => 'Wkrótce',
            'Store is currently unavailable.'        => 'Sklep jest chwilowo niedostępny.',

            // ── Newsletter ─────────────────────────────────────────────────
            'Important updates waiting for you!'     => 'Ważne aktualizacje czekają na Ciebie!',
            'Subscribe and grab 20% OFF!'            => 'Subskrybuj i otrzymaj 20% RABATU!',

            // ── Misc ───────────────────────────────────────────────────────
            'Required fields are marked'             => 'Pola wymagane są oznaczone',
            'Optional'                               => 'Opcjonalnie',
            'Loading&hellip;'                        => 'Ładowanie…',
            'Processing&hellip;'                     => 'Przetwarzanie…',
        ];
    }

    return $map[$original] ?? $translated;
}

/**
 * Plural forms — WooCommerce review count strings.
 */
function gorvita_polish_strings_plural(string $translated, string $single, string $plural, int $number, string $domain): string {
    if (!in_array($domain, ['woocommerce'], true)) {
        return $translated;
    }

    if ($single === '%s review' || $single === '%s Review') {
        return $number === 1 ? '%s opinia' : '%s opinii';
    }
    if ($single === '%s customer review' || $single === '%s Customer Review') {
        return $number === 1 ? '%s opinia klienta' : '%s opinii klientów';
    }

    return $translated;
}

/**
 * Fix navigation menu items with /shop → WooCommerce shop page URL.
 * Covers menus built in WP Admin where the shop page slug may be "shop" (English default).
 */
add_filter('wp_nav_menu_objects', function (array $items): array {
    if (!function_exists('wc_get_page_permalink')) {
        return $items;
    }
    $shop_url = trailingslashit(wc_get_page_permalink('shop'));
    $home     = trailingslashit(home_url());

    foreach ($items as $item) {
        $url = trailingslashit($item->url);
        // Replace bare /shop/ with actual shop permalink
        if ($url === $home . 'shop/') {
            $item->url = $shop_url;
        }
    }
    return $items;
}, 10, 1);
