<?php
/**
 * Gorvita Child — custom footer template.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

$account_url  = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : '/moje-konto/';
$orders_url   = $account_url . 'orders/';
$account_edit = $account_url . 'edit-account/';
?>
</main><!-- #main -->

<footer class="gorvita-footer" role="contentinfo">
    <div class="gorvita-footer__inner">

        <!-- Brand column -->
        <div class="gorvita-footer__brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="gorvita-footer__logo" aria-label="Gorvita — strona główna">
                <?php
                $logo_id = get_theme_mod('custom_logo');
                if ($logo_id) {
                    echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'gorvita-footer__logo-img', 'loading' => 'lazy']);
                } else {
                    echo '<span class="gorvita-footer__logo-text">Gorvita</span>';
                }
                ?>
            </a>
            <p class="gorvita-footer__tagline">Naturalne preparaty ziołowe od 1989&nbsp;roku. Receptury dopracowane przez trzy pokolenia.</p>
            <div class="gorvita-footer__socials" aria-label="Social media">
                <a href="https://www.facebook.com/gorvita" class="gorvita-footer__social" target="_blank" rel="noopener noreferrer" aria-label="Facebook Gorvita">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.884v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
                </a>
                <a href="https://www.instagram.com/gorvita_pl" class="gorvita-footer__social" target="_blank" rel="noopener noreferrer" aria-label="Instagram Gorvita">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                </a>
            </div>
        </div>

        <!-- Informacje -->
        <nav class="gorvita-footer__col" aria-label="Informacje">
            <h3 class="gorvita-footer__heading">Informacje</h3>
            <ul class="gorvita-footer__links">
                <li><a href="<?php echo esc_url(home_url('/o-marce/')); ?>">O firmie</a></li>
                <li><a href="<?php echo esc_url(home_url('/regulamin/')); ?>">Regulamin</a></li>
                <li><a href="<?php echo esc_url(home_url('/polityka-prywatnosci/')); ?>">Polityka prywatności</a></li>
                <li><a href="<?php echo esc_url(home_url('/dostawa/')); ?>">Dostawa</a></li>
                <li><a href="<?php echo esc_url(home_url('/platnosc/')); ?>">Płatność</a></li>
                <li><a href="<?php echo esc_url(home_url('/kontakt/')); ?>">Kontakt</a></li>
            </ul>
        </nav>

        <!-- Twoje konto -->
        <nav class="gorvita-footer__col" aria-label="Twoje konto">
            <h3 class="gorvita-footer__heading">Twoje konto</h3>
            <ul class="gorvita-footer__links">
                <li><a href="<?php echo esc_url($account_url); ?>">Logowanie</a></li>
                <li><a href="<?php echo esc_url($account_url); ?>">Rejestracja</a></li>
                <li><a href="<?php echo esc_url($orders_url); ?>">Twoje zamówienia</a></li>
                <li><a href="<?php echo esc_url($account_edit); ?>">Edycja danych</a></li>
            </ul>
        </nav>

        <!-- Kontakt -->
        <div class="gorvita-footer__col gorvita-footer__col--contact">
            <h3 class="gorvita-footer__heading">Kontakt z nami</h3>
            <address class="gorvita-footer__address">
                <p>
                    <a href="tel:+48183324181">18 332 41 81</a> /
                    <a href="tel:+48500207239">500 207 239</a>
                </p>
                <p><a href="mailto:sklep@gorvita.com.pl">sklep@gorvita.com.pl</a></p>
                <p class="gorvita-footer__hours">Poniedziałek – Piątek: 8:00–16:00</p>
            </address>

            <h3 class="gorvita-footer__heading" style="margin-top: 24px">Sklep firmowy</h3>
            <address class="gorvita-footer__address">
                <p>PPUH GORVITA</p>
                <p>mgr Paweł Domek</p>
                <p>Szczawa 106, 34-607 Szczawa</p>
                <p>Małopolskie, Polska</p>
                <p class="gorvita-footer__hours">Poniedziałek – Piątek: 8:00–16:00</p>
            </address>
        </div>

    </div><!-- .gorvita-footer__inner -->

    <div class="gorvita-footer__bottom">
        <div class="gorvita-footer__bottom-inner">
            <span>&copy; <?php echo esc_html(gmdate('Y')); ?> PPUH Gorvita. Wszelkie prawa zastrzeżone.</span>
            <span class="gorvita-footer__attribution">WordPress Theme by <a href="https://nexoperandi.cloud" rel="nofollow">NexOperandi</a></span>
        </div>
    </div>

</footer><!-- .gorvita-footer -->

<?php wp_footer(); ?>
</body>
</html>
