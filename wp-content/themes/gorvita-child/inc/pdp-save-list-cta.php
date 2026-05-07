<?php
/**
 * PDP "Zapisz jako listę zakupów" CTA — labeled wishlist add button.
 *
 * Blocksy Pro renders a small heart icon (`.ct-wishlist-button-single`)
 * next to add-to-cart. User wanted a full-width labeled CTA for B2B
 * shopping-list use case ("save my entire cart selection so I can come
 * back to it later"), positioned BELOW the description tabs and BEFORE
 * the "Może Cię zainteresować…" Nowości row, styled like the
 * "Przejdź do płatności" pill button.
 *
 * Bridges to the existing Blocksy wishlist heart via JS click delegation
 * — keeps wishlist state, slug `lista-zyczen`, and Blocksy's offcanvas
 * panel intact.
 *
 * @package GorvitaChild
 */

defined( 'ABSPATH' ) || exit;

function gorvita_pdp_save_list_cta() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
	<div class="gorvita-save-list-wrap">
		<button type="button" class="gorvita-save-list-cta" data-gorvita-save-list>
			<svg viewBox="0 0 15 15" width="18" height="18" aria-hidden="true" focusable="false">
				<path d="M13.4,3.2c-0.9-0.8-2.3-1-3.5-0.8C8.9,2.6,8.1,3,7.5,3.7C6.9,3,6.1,2.6,5.2,2.4c-1.3-0.2-2.6,0-3.6,0.8C0.7,3.9,0.1,5,0,6.1c-0.1,1.3,0.3,2.6,1.3,3.7c1.2,1.4,5.6,4.7,5.8,4.8L7.5,15L8,14.6c0.2-0.1,4.5-3.5,5.7-4.8c1-1.1,1.4-2.4,1.3-3.7C14.9,5,14.3,3.9,13.4,3.2z"/>
			</svg>
			<span>Zapisz jako listę zakupów</span>
		</button>
	</div>
	<script>
	(function(){
		var ctas = document.querySelectorAll(".gorvita-save-list-cta[data-gorvita-save-list]");
		ctas.forEach(function(cta){
			if (cta.dataset.bound === "1") return;
			cta.dataset.bound = "1";
			cta.addEventListener("click", function(e){
				e.preventDefault();
				var heart = document.querySelector(".ct-wishlist-button-single:not(.gorvita-save-list-cta)");
				if (heart) {
					heart.click();
					// Reflect liked state visually on our CTA after Blocksy state settles
					setTimeout(function(){
						var liked = heart.classList.contains("ct-active") || heart.dataset.buttonState === "liked";
						cta.classList.toggle("gorvita-save-list-cta--liked", liked);
					}, 250);
				}
			});
		});
	})();
	</script>
	<?php
}
add_action( 'woocommerce_after_single_product_summary', 'gorvita_pdp_save_list_cta', 17 );
