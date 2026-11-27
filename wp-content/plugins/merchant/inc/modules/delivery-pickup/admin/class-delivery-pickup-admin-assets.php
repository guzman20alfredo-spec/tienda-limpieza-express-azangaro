<?php
/**
 * Delivery & Pickup — Admin Assets.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Enqueues the module's admin-side styles.
 */
class Merchant_Delivery_Pickup_Admin_Assets {

	/**
	 * Module ID.
	 *
	 * @var string
	 */
	private $module_id;

	/**
	 * Constructor.
	 *
	 * @param string $module_id
	 */
	public function __construct( string $module_id ) {
		$this->module_id = $module_id;
	}

	/**
	 * Enqueue admin CSS.
	 *
	 * @return void
	 */
	public function enqueue_css(): void {
		wp_enqueue_style(
			'merchant-admin-' . $this->module_id,
			MERCHANT_URI . 'assets/css/modules/' . $this->module_id . '/admin/preview.min.css',
			array(),
			MERCHANT_VERSION
		);
	}
}
