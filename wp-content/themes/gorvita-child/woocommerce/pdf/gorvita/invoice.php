<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Gorvita invoice template (based on WCPDF Simple).
 * All item amounts are shown net, with VAT rate/amount and gross per line,
 * and totals broken down as: items net -> fees/discounts (net) -> net total
 * -> VAT per rate -> gross total. B2BKing cart discounts are stored as
 * negative fees whose total is already the net amount.
 */

// Plain-text price for PDF engines (wc_price markup would be escaped literally).
$gorvita_pdf_price = function( $amount ) {
	$formatted = wc_price( $amount, array( 'currency' => $this->order->get_currency() ) );
	return html_entity_decode( wp_strip_all_tags( $formatted ), ENT_QUOTES, 'UTF-8' );
};
?>

<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>

<table class="head container">
	<tr>
		<td class="header">
			<?php if ( $this->has_header_logo() ) : ?>
				<?php do_action( 'wpo_wcpdf_before_shop_logo', $this->get_type(), $this->order ); ?>
				<?php $this->header_logo(); ?>
				<?php do_action( 'wpo_wcpdf_after_shop_logo', $this->get_type(), $this->order ); ?>
			<?php else : ?>
				<?php $this->title(); ?>
			<?php endif; ?>
		</td>
		<td class="shop-info">
			<?php do_action( 'wpo_wcpdf_before_shop_name', $this->get_type(), $this->order ); ?>
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<?php do_action( 'wpo_wcpdf_after_shop_name', $this->get_type(), $this->order ); ?>
			<?php do_action( 'wpo_wcpdf_before_shop_address', $this->get_type(), $this->order ); ?>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
			<?php do_action( 'wpo_wcpdf_after_shop_address', $this->get_type(), $this->order ); ?>
			<?php do_action( 'wpo_wcpdf_before_shop_phone_number', $this->get_type(), $this->order ); ?>
			<?php if ( ! empty( $this->get_shop_phone_number() ) ) : ?>
				<div class="shop-phone-number"><?php $this->shop_phone_number(); ?></div>
			<?php endif; ?>
			<?php do_action( 'wpo_wcpdf_after_shop_phone_number', $this->get_type(), $this->order ); ?>
			<?php if ( ! empty( $this->get_shop_email_address() ) ) : ?>
				<div class="shop-email-address"><?php $this->shop_email_address(); ?></div>
			<?php endif; ?>
			<?php do_action( 'wpo_wcpdf_after_shop_email_address', $this->get_type(), $this->order ); ?>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>

<?php if ( $this->has_header_logo() ) : ?>
	<h1 class="document-type-label"><?php $this->title(); ?></h1>
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

<table class="order-data-addresses">
	<tr>
		<td class="address billing-address">
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
			<p><?php $this->billing_address(); ?></p>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php endif; ?>
			<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
				<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php endif; ?>
		</td>
		<td class="address shipping-address">
			<?php if ( $this->show_shipping_address() ) : ?>
				<h3><?php $this->shipping_address_title(); ?></h3>
				<?php do_action( 'wpo_wcpdf_before_shipping_address', $this->get_type(), $this->order ); ?>
				<p><?php $this->shipping_address(); ?></p>
				<?php do_action( 'wpo_wcpdf_after_shipping_address', $this->get_type(), $this->order ); ?>
				<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
					<div class="shipping-phone"><?php $this->shipping_phone(); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
				<?php if ( isset( $this->settings['display_number'] ) ) : ?>
					<tr class="invoice-number">
						<th><?php $this->number_title(); ?></th>
						<td><?php $this->number( $this->get_type() ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( isset( $this->settings['display_date'] ) ) : ?>
					<tr class="invoice-date">
						<th><?php $this->date_title(); ?></th>
						<td><?php $this->date( $this->get_type() ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $this->show_due_date() ) : ?>
					<tr class="due-date">
						<th><?php $this->due_date_title(); ?></th>
						<td><?php $this->due_date(); ?></td>
					</tr>
				<?php endif; ?>
				<tr class="order-number">
					<th><?php $this->order_number_title(); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php $this->order_date_title(); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
				<?php if ( $this->get_payment_method() ) : ?>
					<tr class="payment-method">
						<th><?php $this->payment_method_title(); ?></th>
						<td><?php $this->payment_method(); ?></td>
					</tr>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
			</table>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

<table class="order-details">
	<thead>
		<tr>
			<th class="product"><?php esc_html_e( 'Produkt', 'gorvita' ); ?></th>
			<th class="quantity"><?php esc_html_e( 'Ilość', 'gorvita' ); ?></th>
			<th class="unit-price"><?php esc_html_e( 'Cena netto', 'gorvita' ); ?></th>
			<th class="net-total"><?php esc_html_e( 'Wartość netto', 'gorvita' ); ?></th>
			<th class="vat-rate"><?php esc_html_e( 'VAT', 'gorvita' ); ?></th>
			<th class="vat-amount"><?php esc_html_e( 'Kwota VAT', 'gorvita' ); ?></th>
			<th class="gross-total"><?php esc_html_e( 'Wartość brutto', 'gorvita' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $this->get_order_items() as $item_id => $item ) : ?>
			<?php
			$order_item = $item['item']; // WC_Order_Item_Product
			$qty        = max( 1, abs( (float) $item['quantity'] ) );
			$line_net   = round( (float) $order_item->get_total(), 2 );
			$line_tax   = round( (float) $order_item->get_total_tax(), 2 );
			$line_gross = round( $line_net + $line_tax, 2 );
			$unit_net   = round( (float) $order_item->get_total() / $qty, 2 );
			$tax_rates  = ! empty( $item['tax_rates'] ) ? $item['tax_rates'] : '-';
			?>
			<tr class="<?php echo esc_html( $item['row_class'] ); ?>">
				<td class="product">
					<p class="item-name"><?php echo esc_html( $item['name'] ); ?></p>
					<?php do_action( 'wpo_wcpdf_before_item_meta', $this->get_type(), $item, $this->order ); ?>
					<div class="item-meta">
						<?php if ( ! empty( $item['sku'] ) ) : ?>
							<p class="sku"><span class="label"><?php $this->sku_title(); ?></span> <?php echo esc_attr( $item['sku'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $item['meta'] ) ) : ?>
							<?php echo wp_kses_post( $item['meta'] ); ?>
						<?php endif; ?>
					</div>
					<?php do_action( 'wpo_wcpdf_after_item_meta', $this->get_type(), $item, $this->order ); ?>
				</td>
				<td class="quantity"><?php echo esc_html( $item['quantity'] ); ?></td>
				<td class="unit-price"><?php echo esc_html( $gorvita_pdf_price( $unit_net ) ); ?></td>
				<td class="net-total"><?php echo esc_html( $gorvita_pdf_price( $line_net ) ); ?></td>
				<td class="vat-rate"><?php echo esc_html( $tax_rates ); ?></td>
				<td class="vat-amount"><?php echo esc_html( $gorvita_pdf_price( $line_tax ) ); ?></td>
				<td class="gross-total"><?php echo esc_html( $gorvita_pdf_price( $line_gross ) ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<table class="notes-totals">
	<tbody>
		<tr class="no-borders">
			<td class="no-borders notes-cell">
				<?php do_action( 'wpo_wcpdf_before_document_notes', $this->get_type(), $this->order ); ?>
				<?php if ( $this->get_document_notes() ) : ?>
					<div class="document-notes">
						<h3><?php $this->notes_title(); ?></h3>
						<?php $this->document_notes(); ?>
					</div>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_document_notes', $this->get_type(), $this->order ); ?>
				<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>
				<?php if ( $this->get_shipping_notes() ) : ?>
					<div class="customer-notes">
						<h3><?php $this->customer_notes_title(); ?></h3>
						<?php $this->shipping_notes(); ?>
					</div>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>
			</td>
			<td class="no-borders totals-cell">
				<?php
				$items_net = 0.0;
				foreach ( $this->order->get_items() as $order_item ) {
					$items_net += (float) $order_item->get_total();
				}
				$order_net    = round( (float) $this->order->get_total() - (float) $this->order->get_total_tax(), 2 );
				$shipping_net = round( (float) $this->order->get_shipping_total(), 2 );
				?>
				<table class="totals">
					<tfoot>
						<tr class="items-net">
							<th class="description"><?php esc_html_e( 'Wartość pozycji netto', 'gorvita' ); ?></th>
							<td class="price"><span class="totals-price"><?php echo esc_html( $gorvita_pdf_price( round( $items_net, 2 ) ) ); ?></span></td>
						</tr>
						<?php foreach ( $this->order->get_items( 'fee' ) as $fee ) : ?>
							<tr class="fee">
								<th class="description"><?php echo esc_html( $fee->get_name() ); ?> <?php esc_html_e( '(netto)', 'gorvita' ); ?></th>
								<td class="price"><span class="totals-price"><?php echo esc_html( $gorvita_pdf_price( round( (float) $fee->get_total(), 2 ) ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
						<?php if ( $shipping_net > 0 || count( $this->order->get_shipping_methods() ) > 0 ) : ?>
							<tr class="shipping">
								<th class="description"><?php esc_html_e( 'Dostawa (netto)', 'gorvita' ); ?></th>
								<td class="price"><span class="totals-price"><?php echo esc_html( $gorvita_pdf_price( $shipping_net ) ); ?></span></td>
							</tr>
						<?php endif; ?>
						<tr class="net-total-row">
							<th class="description"><?php esc_html_e( 'Razem netto', 'gorvita' ); ?></th>
							<td class="price"><span class="totals-price"><?php echo esc_html( $gorvita_pdf_price( $order_net ) ); ?></span></td>
						</tr>
						<?php foreach ( $this->order->get_items( 'tax' ) as $tax_item ) : ?>
							<?php
							$rate_percent = $tax_item->get_rate_percent();
							if ( '' !== $rate_percent && null !== $rate_percent ) {
								$rate_float = (float) $rate_percent;
								$rate_text  = ( $rate_float == (int) $rate_float ) ? (string) (int) $rate_float : (string) $rate_float;
								$vat_label  = sprintf( __( 'VAT %s%%', 'gorvita' ), $rate_text );
							} else {
								$vat_label = $tax_item->get_label();
							}
							$vat_amount   = round( (float) $tax_item->get_tax_total() + (float) $tax_item->get_shipping_tax_total(), 2 );
							?>
							<tr class="tax-rate">
								<th class="description"><?php echo esc_html( $vat_label ); ?></th>
								<td class="price"><span class="totals-price"><?php echo esc_html( $gorvita_pdf_price( $vat_amount ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
						<tr class="vat-total-row">
							<th class="description"><?php esc_html_e( 'Razem VAT', 'gorvita' ); ?></th>
							<td class="price"><span class="totals-price"><?php echo esc_html( $gorvita_pdf_price( round( (float) $this->order->get_total_tax(), 2 ) ) ); ?></span></td>
						</tr>
						<tr class="order_total">
							<th class="description"><?php esc_html_e( 'Razem brutto (do zapłaty)', 'gorvita' ); ?></th>
							<td class="price"><span class="totals-price"><?php echo esc_html( $gorvita_pdf_price( round( (float) $this->order->get_total(), 2 ) ) ); ?></span></td>
						</tr>
						<?php if ( (float) $this->order->get_total_refunded() > 0 ) : ?>
							<tr class="refunded">
								<th class="description"><?php esc_html_e( 'Zwrócono', 'gorvita' ); ?></th>
								<td class="price"><span class="totals-price">-<?php echo esc_html( $gorvita_pdf_price( round( (float) $this->order->get_total_refunded(), 2 ) ) ); ?></span></td>
							</tr>
						<?php endif; ?>
					</tfoot>
				</table>
			</td>
		</tr>
	</tbody>
</table>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>

<div class="bottom-spacer"></div>

<?php if ( $this->get_footer() ) : ?>
	<htmlpagefooter name="docFooter"><!-- required for mPDF engine -->
		<div id="footer">
			<!-- hook available: wpo_wcpdf_before_footer -->
			<?php $this->footer(); ?>
			<!-- hook available: wpo_wcpdf_after_footer -->
		</div>
	</htmlpagefooter><!-- required for mPDF engine -->
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>
