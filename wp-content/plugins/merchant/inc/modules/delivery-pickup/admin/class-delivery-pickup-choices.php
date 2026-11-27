<?php
/**
 * Delivery & Pickup — Settings choices.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Builds the option lists specific to this module's settings fields.
 */
class Merchant_Delivery_Pickup_Choices {

	/**
	 * Stands in for WooCommerce's standard tax class, which it keys as the empty string.
	 *
	 * `Merchant_Settings_Renderer::resolve_field_value()` swaps a falsy stored value for the
	 * field's default, so an empty slug reads back as `inherit` on every reload.
	 */
	const STANDARD_TAX_CLASS = 'standard';

	/**
	 * Slot durations offered by the slot generator.
	 *
	 * @return array<int, string> Minutes => translated label.
	 */
	public static function slot_durations(): array {
		return array(
			15  => esc_html__( '15 minutes', 'merchant' ),
			30  => esc_html__( '30 minutes', 'merchant' ),
			60  => esc_html__( '1 hour', 'merchant' ),
			120 => esc_html__( '2 hours', 'merchant' ),
			180 => esc_html__( '3 hours', 'merchant' ),
			240 => esc_html__( '4 hours', 'merchant' ),
		);
	}

	/**
	 * Tax classes a delivery fee can use.
	 *
	 * @return array<string, string> Tax class slug => label.
	 */
	public static function tax_classes(): array {
		$classes = array( 'inherit' => esc_html__( 'Same as shipping', 'merchant' ) );

		if ( ! function_exists( 'wc_get_product_tax_class_options' ) ) {
			$classes[ self::STANDARD_TAX_CLASS ] = esc_html__( 'Standard', 'merchant' );

			return $classes;
		}

		foreach ( wc_get_product_tax_class_options() as $slug => $label ) {
			$key = '' === $slug ? self::STANDARD_TAX_CLASS : $slug;

			// First writer wins, so a store class can never displace `inherit` or the stand-in.
			if ( ! isset( $classes[ $key ] ) ) {
				$classes[ $key ] = $label;
			}
		}

		return $classes;
	}
}
