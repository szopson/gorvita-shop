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
