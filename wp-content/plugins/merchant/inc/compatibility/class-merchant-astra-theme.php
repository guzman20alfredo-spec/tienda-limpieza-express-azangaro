<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Astra theme compatibility layer
 */
if ( ! class_exists( 'Merchant_Astra_Theme' ) ) {
	class Merchant_Astra_Theme {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'astra_woo_shop_product_structure', array( $this, 'fix_astra_shop_structure' ) );

			if ( is_admin() && ! wp_doing_ajax() ) {
				return;
			}

			// Custom CSS.
			add_filter( 'merchant_custom_css', array( $this, 'frontend_custom_css' ) );
		}

		/**
		 * Frontend custom CSS.
		 *
		 * Names the theme here, not in the constructor: this class is built before the
		 * theme loads, so `ASTRA_THEME_VERSION` does not exist yet.
		 *
		 * @param string $css The custom CSS.
		 * @return string $css The custom CSS.
		 */
		public function frontend_custom_css( $css ) {
			if ( ! merchant_is_astra_active() ) {
				return $css;
			}

			// Delivery & Pickup
			if ( Merchant_Modules::is_module_active( Merchant_Delivery_Pickup::MODULE_ID ) ) {
				// Astra makes `#order_review td.product-name` a wrapping flex row, where a
				// collapsed control fits beside the product name.
				$css .= '
					body .merchant-dp-line {
						width: 100%;
					}
				';
			}

			return $css;
		}

		/**
		 * Disable the add to cart button in the shop loop while product swatches module is active.
		 *
		 * @param $shop_structure
		 *
		 * @return void
		 */
		public function fix_astra_shop_structure( $shop_structure ) {
			if ( ! Merchant_Modules::is_module_active( 'product-swatches' ) ) {
				return $shop_structure;
			}
			if ( is_array( $shop_structure ) && ! empty( $shop_structure ) ) {
				foreach ( $shop_structure as $key => $value ) {
					if ( $value === 'add_cart' ) {
						unset( $shop_structure[ $key ] );
					}
				}
			}

			return $shop_structure;
		}
	}

	/**
	 * The class object can be accessed with "global $merchant_astra_compatibility", to allow removing actions.
	 * Improving Third-party integrations.
	 */
	$merchant_astra_compatibility = new Merchant_Astra_Theme();
}
