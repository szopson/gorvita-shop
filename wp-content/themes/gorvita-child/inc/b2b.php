<?php
/**
 * B2B functionality: custom role, hidden pricing for guests on B2B products,
 * registration form with NIP/REGON, admin approval flow.
 *
 * @package GorvitaChild
 */

defined('ABSPATH') || exit;

/**
 * Create `b2b_customer` role on theme activation.
 */
function gorvita_b2b_create_role() {
    if (!get_role('b2b_customer')) {
        add_role('b2b_customer', __('Klient B2B', 'gorvita-child'), [
            'read' => true,
        ]);
    }
}
add_action('after_switch_theme', 'gorvita_b2b_create_role');

/**
 * Check if current user is B2B.
 */
function gorvita_is_b2b_user($user_id = null) {
    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
    if (!$user || !$user->exists()) return false;
    return in_array('b2b_customer', (array) $user->roles, true);
}

/**
 * Check if a product is flagged as B2B-only (hidden price for guests).
 */
function gorvita_is_b2b_only_product($product_id) {
    return (bool) get_post_meta($product_id, '_gorvita_b2b_only', true);
}

/**
 * Get B2B price override for a product (meta `_b2b_price`).
 * Returns null if not set.
 */
function gorvita_get_b2b_price($product_id) {
    $price = get_post_meta($product_id, '_b2b_price', true);
    return $price !== '' ? (float) $price : null;
}

/**
 * Override price HTML for B2B users / hide for guests on B2B products.
 */
function gorvita_filter_price_html($price_html, $product) {
    $product_id = $product->get_id();
    $is_b2b_only = gorvita_is_b2b_only_product($product_id);

    // Guest viewing B2B-only product → hide price
    if ($is_b2b_only && !is_user_logged_in()) {
        $login_url = esc_url(wp_login_url(get_permalink($product_id)));
        return sprintf(
            '<span class="gorvita-b2b-price-hidden">%s <a href="%s">%s</a></span>',
            esc_html__('Cena dla partnerów B2B.', 'gorvita-child'),
            $login_url,
            esc_html__('Zaloguj się', 'gorvita-child')
        );
    }

    // B2B user with override price
    if (gorvita_is_b2b_user()) {
        $b2b_price = gorvita_get_b2b_price($product_id);
        if ($b2b_price !== null) {
            return wc_price($b2b_price) . ' <span class="gorvita-b2b-badge">B2B</span>';
        }
    }

    return $price_html;
}
add_filter('woocommerce_get_price_html', 'gorvita_filter_price_html', 10, 2);

/**
 * Override actual cart price for B2B users.
 */
function gorvita_apply_b2b_cart_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!gorvita_is_b2b_user()) return;

    foreach ($cart->get_cart() as $item) {
        $b2b_price = gorvita_get_b2b_price($item['product_id']);
        if ($b2b_price !== null) {
            $item['data']->set_price($b2b_price);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'gorvita_apply_b2b_cart_price', 20);

/**
 * Hide Add to Cart for guests on B2B-only products.
 */
function gorvita_b2b_hide_add_to_cart($purchasable, $product) {
    if (gorvita_is_b2b_only_product($product->get_id()) && !is_user_logged_in()) {
        return false;
    }
    return $purchasable;
}
add_filter('woocommerce_is_purchasable', 'gorvita_b2b_hide_add_to_cart', 10, 2);

/**
 * Add B2B meta fields to product edit screen.
 */
function gorvita_b2b_product_fields() {
    echo '<div class="options_group">';

    woocommerce_wp_checkbox([
        'id' => '_gorvita_b2b_only',
        'label' => __('Produkt tylko B2B', 'gorvita-child'),
        'description' => __('Ukryj cenę dla niezalogowanych gości. Wymaga zalogowania, aby zobaczyć cenę i kupić.', 'gorvita-child'),
    ]);

    woocommerce_wp_text_input([
        'id' => '_b2b_price',
        'label' => __('Cena B2B (PLN)', 'gorvita-child'),
        'description' => __('Pozostaw puste, aby używać ceny zwykłej.', 'gorvita-child'),
        'desc_tip' => true,
        'data_type' => 'price',
    ]);

    echo '</div>';
}
add_action('woocommerce_product_options_pricing', 'gorvita_b2b_product_fields');

/**
 * Save B2B meta fields.
 */
function gorvita_b2b_save_product_fields($post_id) {
    update_post_meta(
        $post_id,
        '_gorvita_b2b_only',
        isset($_POST['_gorvita_b2b_only']) ? 'yes' : ''
    );

    if (isset($_POST['_b2b_price'])) {
        $price = wc_clean(wp_unslash($_POST['_b2b_price']));
        update_post_meta($post_id, '_b2b_price', $price);
    }
}
add_action('woocommerce_process_product_meta', 'gorvita_b2b_save_product_fields');

/**
 * Registration form shortcode: [gorvita_b2b_registration]
 */
function gorvita_b2b_registration_shortcode() {
    if (is_user_logged_in()) {
        return '<p>' . esc_html__('Jesteś już zalogowany.', 'gorvita-child') . '</p>';
    }

    ob_start();

    if (isset($_POST['gorvita_b2b_register_nonce'])
        && wp_verify_nonce($_POST['gorvita_b2b_register_nonce'], 'gorvita_b2b_register')) {
        $result = gorvita_b2b_process_registration($_POST);
        if (is_wp_error($result)) {
            echo '<div class="woocommerce-error">' . esc_html($result->get_error_message()) . '</div>';
        } else {
            echo '<div class="woocommerce-message">' . esc_html__(
                'Dziękujemy! Twoje konto B2B zostało utworzone. Administrator zweryfikuje dane w ciągu 1-2 dni roboczych.',
                'gorvita-child'
            ) . '</div>';
            return ob_get_clean();
        }
    }
    ?>
    <form method="post" class="gorvita-b2b-form">
        <?php wp_nonce_field('gorvita_b2b_register', 'gorvita_b2b_register_nonce'); ?>

        <h3><?php esc_html_e('Dane firmy', 'gorvita-child'); ?></h3>

        <p>
            <label for="company_name"><?php esc_html_e('Nazwa firmy', 'gorvita-child'); ?> *</label>
            <input type="text" id="company_name" name="company_name" required>
        </p>

        <p>
            <label for="nip"><?php esc_html_e('NIP', 'gorvita-child'); ?> *</label>
            <input type="text" id="nip" name="nip" pattern="[0-9]{10}" maxlength="10" required>
        </p>

        <p>
            <label for="regon"><?php esc_html_e('REGON', 'gorvita-child'); ?></label>
            <input type="text" id="regon" name="regon" pattern="[0-9]{9,14}">
        </p>

        <p>
            <label for="company_address"><?php esc_html_e('Adres firmy', 'gorvita-child'); ?> *</label>
            <input type="text" id="company_address" name="company_address" required>
        </p>

        <h3><?php esc_html_e('Osoba kontaktowa', 'gorvita-child'); ?></h3>

        <p>
            <label for="first_name"><?php esc_html_e('Imię', 'gorvita-child'); ?> *</label>
            <input type="text" id="first_name" name="first_name" required>
        </p>

        <p>
            <label for="last_name"><?php esc_html_e('Nazwisko', 'gorvita-child'); ?> *</label>
            <input type="text" id="last_name" name="last_name" required>
        </p>

        <p>
            <label for="email"><?php esc_html_e('Email', 'gorvita-child'); ?> *</label>
            <input type="email" id="email" name="email" required>
        </p>

        <p>
            <label for="phone"><?php esc_html_e('Telefon', 'gorvita-child'); ?> *</label>
            <input type="tel" id="phone" name="phone" required>
        </p>

        <p>
            <label for="password"><?php esc_html_e('Hasło', 'gorvita-child'); ?> *</label>
            <input type="password" id="password" name="password" minlength="8" required>
        </p>

        <p>
            <label>
                <input type="checkbox" name="consent" required>
                <?php esc_html_e('Akceptuję regulamin i politykę prywatności.', 'gorvita-child'); ?>
            </label>
        </p>

        <p>
            <button type="submit" class="button">
                <?php esc_html_e('Zarejestruj konto B2B', 'gorvita-child'); ?>
            </button>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('gorvita_b2b_registration', 'gorvita_b2b_registration_shortcode');

/**
 * Process B2B registration submission.
 */
function gorvita_b2b_process_registration($data) {
    $email = sanitize_email($data['email'] ?? '');
    $nip = preg_replace('/\D/', '', $data['nip'] ?? '');
    $company = sanitize_text_field($data['company_name'] ?? '');
    $first = sanitize_text_field($data['first_name'] ?? '');
    $last = sanitize_text_field($data['last_name'] ?? '');
    $password = $data['password'] ?? '';

    if (!is_email($email)) return new WP_Error('invalid_email', __('Nieprawidłowy adres email.', 'gorvita-child'));
    if (email_exists($email)) return new WP_Error('email_exists', __('Konto z tym emailem już istnieje.', 'gorvita-child'));
    if (strlen($nip) !== 10) return new WP_Error('invalid_nip', __('NIP musi mieć 10 cyfr.', 'gorvita-child'));
    if (!gorvita_validate_nip_checksum($nip)) return new WP_Error('invalid_nip', __('NIP nieprawidłowy (błędna suma kontrolna).', 'gorvita-child'));
    if (strlen($password) < 8) return new WP_Error('weak_password', __('Hasło musi mieć min. 8 znaków.', 'gorvita-child'));

    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'user_pass' => $password,
        'first_name' => $first,
        'last_name' => $last,
        'role' => 'subscriber',
    ]);

    if (is_wp_error($user_id)) return $user_id;

    update_user_meta($user_id, '_gorvita_b2b_pending', '1');
    update_user_meta($user_id, '_gorvita_company_name', $company);
    update_user_meta($user_id, '_gorvita_nip', $nip);
    update_user_meta($user_id, '_gorvita_regon', sanitize_text_field($data['regon'] ?? ''));
    update_user_meta($user_id, '_gorvita_company_address', sanitize_text_field($data['company_address'] ?? ''));
    update_user_meta($user_id, '_gorvita_phone', sanitize_text_field($data['phone'] ?? ''));
    update_user_meta($user_id, 'billing_company', $company);
    update_user_meta($user_id, 'billing_email', $email);
    update_user_meta($user_id, 'billing_first_name', $first);
    update_user_meta($user_id, 'billing_last_name', $last);

    // Notify admin
    $admin_email = get_option('admin_email');
    $admin_url = admin_url('user-edit.php?user_id=' . $user_id);
    wp_mail(
        $admin_email,
        sprintf(__('[Gorvita] Nowa rejestracja B2B: %s', 'gorvita-child'), $company),
        sprintf(
            "Firma: %s\nNIP: %s\nEmail: %s\nOsoba: %s %s\n\nZatwierdź: %s",
            $company, $nip, $email, $first, $last, $admin_url
        )
    );

    return true;
}

/**
 * Validate Polish NIP checksum.
 */
function gorvita_validate_nip_checksum($nip) {
    if (strlen($nip) !== 10 || !ctype_digit($nip)) return false;
    $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += intval($nip[$i]) * $weights[$i];
    }
    $check = $sum % 11;
    return $check !== 10 && $check === intval($nip[9]);
}

/**
 * Admin: add "Zatwierdź B2B" button on user profile.
 */
function gorvita_b2b_admin_approve_field($user) {
    if (!current_user_can('edit_users')) return;
    $pending = get_user_meta($user->ID, '_gorvita_b2b_pending', true);
    $nip = get_user_meta($user->ID, '_gorvita_nip', true);
    $company = get_user_meta($user->ID, '_gorvita_company_name', true);
    if (!$nip && !$company) return;
    ?>
    <h2><?php esc_html_e('Dane B2B', 'gorvita-child'); ?></h2>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Status', 'gorvita-child'); ?></th>
            <td>
                <?php if ($pending): ?>
                    <strong style="color:#D97706">⏳ <?php esc_html_e('Oczekuje na zatwierdzenie', 'gorvita-child'); ?></strong>
                    <p><label>
                        <input type="checkbox" name="gorvita_b2b_approve" value="1">
                        <?php esc_html_e('Zatwierdź jako klient B2B', 'gorvita-child'); ?>
                    </label></p>
                <?php elseif (in_array('b2b_customer', (array) $user->roles, true)): ?>
                    <strong style="color:#16A34A">✓ <?php esc_html_e('Zatwierdzony klient B2B', 'gorvita-child'); ?></strong>
                <?php else: ?>
                    <?php esc_html_e('Nie jest klientem B2B', 'gorvita-child'); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e('Firma', 'gorvita-child'); ?></th>
            <td><?php echo esc_html($company); ?></td>
        </tr>
        <tr>
            <th>NIP</th>
            <td><?php echo esc_html($nip); ?></td>
        </tr>
        <tr>
            <th>REGON</th>
            <td><?php echo esc_html(get_user_meta($user->ID, '_gorvita_regon', true)); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Adres firmy', 'gorvita-child'); ?></th>
            <td><?php echo esc_html(get_user_meta($user->ID, '_gorvita_company_address', true)); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Telefon', 'gorvita-child'); ?></th>
            <td><?php echo esc_html(get_user_meta($user->ID, '_gorvita_phone', true)); ?></td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'gorvita_b2b_admin_approve_field');
add_action('edit_user_profile', 'gorvita_b2b_admin_approve_field');

/**
 * Save admin approval.
 */
function gorvita_b2b_admin_save_approval($user_id) {
    if (!current_user_can('edit_users')) return;
    if (!empty($_POST['gorvita_b2b_approve'])) {
        $user = new WP_User($user_id);
        $user->set_role('b2b_customer');
        delete_user_meta($user_id, '_gorvita_b2b_pending');

        // Notify user
        $email = $user->user_email;
        wp_mail(
            $email,
            __('[Gorvita] Twoje konto B2B zostało zatwierdzone', 'gorvita-child'),
            __("Witamy w programie B2B Gorvita!\n\nTwoje konto zostało zatwierdzone. Od teraz widzisz ceny hurtowe i możesz składać zamówienia.", 'gorvita-child')
        );
    }
}
add_action('personal_options_update', 'gorvita_b2b_admin_save_approval');
add_action('edit_user_profile_update', 'gorvita_b2b_admin_save_approval');
