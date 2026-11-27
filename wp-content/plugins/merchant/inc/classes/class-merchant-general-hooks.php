<?php
/**
 * Merchant_Option Class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Merchant_General_Hooks' ) ) {
	class Merchant_General_Hooks {

		/**
		 * The single class instance.
		 */
		private static $instance = null;

		/**
		 * Instance.
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
		}

		/**
		 * Attach functions to WordPress
		 *
		 * @return void
		 */
		public function load_hooks() {
			add_action( 'init', array( $this, 'register_weekly_schedule' ) );
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_order_item_meta' ) );
			add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'strip_customer_facing_meta' ) );
		}

		/**
		 * Hide Merchant internal order item meta on the admin order screen.
		 *
		 * @param array $hidden Keys WooCommerce already hides.
		 *
		 * @return array
		 */
		public function hidden_order_item_meta( $hidden ) {
			/**
			 * Filter the hidden Merchant order item meta keys.
			 *
			 * @param array $keys Meta keys to hide.
			 *
			 * @since 2.3.2
			 */
			$keys = apply_filters( 'merchant_hidden_order_item_meta', $this->order_item_meta_keys() );

			return array_merge( (array) $hidden, $keys );
		}

		/**
		 * Drop non-underscore keys from the order details page and emails.
		 *
		 * @param object[]|mixed $formatted_meta Formatted meta objects keyed by meta id.
		 *
		 * @return object[]|mixed
		 */
		public function strip_customer_facing_meta( $formatted_meta ) {
			if ( ! is_array( $formatted_meta ) ) {
				return $formatted_meta;
			}

			foreach ( $formatted_meta as $id => $meta ) {
				if ( in_array( $meta->key, $this->customer_facing_meta_keys(), true ) ) {
					unset( $formatted_meta[ $id ] );
				}
			}

			return $formatted_meta;
		}

		/**
		 * Free and Pro order item meta keys. Listed here so they stay hidden even with Pro off.
		 *
		 * @return array
		 */
		private function order_item_meta_keys() {
			return array_merge(
				array(
					// pre-orders
					'_merchant_pre_order',
					'_merchant_is_pre_order_product',
					'_merchant_pre_order_shipping_date',
					'_pre_order_add_to_cart_event_id',

					// product-bundles
					'_mppb_parent_id',
					'_mppb_ids',
					'_mppb_price',
					'_mppb_analytics_id',

					// buy-x-get-y
					'_merchant_bogo_offer_key',
					'_merchant_bogo_analytics_id',

					// analytics event ids
					'_fbt_analytics_id',
					'_cp_analytics_id',
					'_recently_viewed_add_to_cart_event_id',
					'_sticky_add_to_cart_event_id',
					'_storewide_sale_analytics_id',
					'_volume_discounts_analytics_id',
					'_waitlist_back_in_stock_event_id',

					// advanced-reviews
					'_merchant_review_status',
					'_merchant_review_images_added',

					// delivery-pickup
					'_merchant_delivery_pickup_group',
				),
				$this->customer_facing_meta_keys()
			);
		}

		/**
		 * Non-underscore keys that also leak on the order details page and emails.
		 *
		 * @return array
		 */
		private function customer_facing_meta_keys() {
			return array(
				// thankyou-offers
				'merchant_added_via',
				'merchant_module_id',

				// product-bundles (legacy)
				'mppb_parent_id',
				'mppb_ids',
				'mppb_price',
			);
		}

		/**
		 * Register weekly schedule.
		 *
		 * @return void
		 */
		public function register_weekly_schedule() {
			if ( function_exists( 'as_schedule_recurring_action' ) ) {
				if ( ! as_next_scheduled_action( 'merchant_weekly_schedule' ) ) {
					as_schedule_recurring_action( time(), WEEK_IN_SECONDS, 'merchant_weekly_schedule' );
				}

				return;
			}

			if ( ! wp_next_scheduled( 'merchant_weekly_schedule' ) ) {
				wp_schedule_event( time(), 'weekly', 'merchant_weekly_schedule' );
			}
		}
	}
}

$merchant_general_hooks = Merchant_General_Hooks::instance();
$merchant_general_hooks->load_hooks();