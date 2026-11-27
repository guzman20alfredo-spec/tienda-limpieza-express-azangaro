<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Bricks Builder compatibility layer
 */
if ( ! class_exists( 'Merchant_Bricks_Builder' ) ) {
	class Merchant_Bricks_Builder {

		/**
		 * Constructor.
		 */
		public function __construct() {

			if ( ( is_admin() && ! wp_doing_ajax() ) || ! merchant_is_bricks_builder_active() ) {
				return;
			}

			add_filter( 'merchant_enqueue_module_scripts', array( $this, 'should_enqueue_scripts' ), 10, 2 );

			// Custom CSS.
			add_filter( 'merchant_custom_css', array( $this, 'frontend_custom_css' ) );
		}

		/**
		 * Determines whether to enqueue scripts for modules.
		 *
		 * @param $enqueue
		 * @param $module
		 *
		 * @return mixed|true
		 */
		public function should_enqueue_scripts( $enqueue, $module ) {
			$module_id = $module->module_id ?? '';

			if ( $module_id === 'product-swatches' ) {
				return true;
			}

			return $enqueue;
		}

		/**
		 * Frontend custom CSS.
		 *
		 * @param string $css The custom CSS.
		 * @return string $css The custom CSS.
		 */
		public function frontend_custom_css( $css ) {

			// Wishlist
			if ( Merchant_Modules::is_module_active( Merchant_Wishlist::MODULE_ID ) && Merchant_Admin_Options::get( 'wishlist', 'display_on_cart_page', false ) ) {
				$css .= '
					.merchant-wishlist-items-cart ul.products li.product {
						display: flex;
						flex-direction: column;
						gap: 10px;
					}
				';
			}

			// Delivery & Pickup
			if ( Merchant_Modules::is_module_active( Merchant_Delivery_Pickup::MODULE_ID ) ) {
				$css .= $this->delivery_pickup_css();
			}

			return $css;
		}

		/**
		 * Delivery & Pickup sizes and edges, restated where Bricks' own CSS changes them.
		 *
		 * `html { font-size: 62.5% }` renders every `rem` at 10px, the checkout table pads
		 * cells 20px and separates its product rows only, and the order table makes every
		 * cell 50% wide and hides the head. Bricks also dequeues every WooCommerce
		 * stylesheet, so the tables share an edge and nothing stacks the summary on a phone,
		 * which is why the head shows at every width. Its own CSS sits in `@layer bricks`,
		 * but a store can switch that off, so each selector wins on specificity as well.
		 *
		 * @return string
		 */
		private function delivery_pickup_css() {
			return '
				body .merchant-dp-line,
				body .merchant-dp-line-hint,
				body .merchant-dp-section .merchant-dp-shipments {
					font-size: 13px;
				}

				body .merchant-dp-line-label {
					font-size: 11px;
				}

				body .merchant-dp-section .merchant-dp-shipments-title,
				body .merchant-dp-change-panel > summary,
				body .merchant-dp-part-heading {
					font-size: 16px;
				}

				body .merchant-dp-section,
				body .merchant-dp-order-actions {
					--merchant-dp-gap: 8px;
				}

				body .merchant-dp-reschedule .merchant-dp-line-label {
					font-size: 12px;
				}

				body .merchant-dp-reschedule .merchant-dp-line-select {
					padding: 6px 28px 6px 10px;
					font-size: 15px;
				}

				body .merchant-dp-reschedule .merchant-dp-submit {
					padding: 10px 20px;
					font-size: 14px;
				}

				body .wp-block-woocommerce-checkout .merchant-dp-line-mount,
				body .wp-block-woocommerce-cart .merchant-dp-line-mount,
				body .wp-block-woocommerce-checkout .merchant-dp-line-hint,
				body .wp-block-woocommerce-cart .merchant-dp-line-hint,
				body .wp-block-woocommerce-checkout .merchant-dp-shipment {
					font-size: 13px;
				}

				body .wp-block-woocommerce-checkout .merchant-dp-line-label,
				body .wp-block-woocommerce-cart .merchant-dp-line-label {
					font-size: 11px;
				}

				body .woocommerce-checkout-review-order-table .merchant-dp-checkout-row > td {
					padding-inline: 20px;
					border-top: 1px solid var(--bricks-border-color, #dddedf);
					border-bottom: 1px solid var(--bricks-border-color, #dddedf);
				}

				body .brxe-woocommerce-checkout-order-table .woocommerce-checkout-review-order-table .merchant-dp-checkout-row > td {
					padding-inline: 0;
				}

				body .woocommerce-order-details .merchant-dp-summary {
					margin-top: 30px;
				}

				body .woocommerce-order-details .merchant-dp-summary th,
				body .woocommerce-order-details .merchant-dp-summary td {
					width: auto;
					padding: 12px 20px;
					line-height: 1.4;
				}

				body .woocommerce-order-details .merchant-dp-summary thead {
					display: table-header-group;
				}
			';
		}
	}

	add_action( 'init', function() {
		new Merchant_Bricks_Builder();
	} );
}
