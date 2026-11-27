<?php
/**
 * Merchant Field: Number
 *
 * @package Merchant
 * @since   2.2.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Merchant_Field_Number
 *
 * @since 2.2.5
 */
class Merchant_Field_Number extends Merchant_Abstract_Field {

	/**
	 * Render the number field.
	 *
	 * @since 2.2.5
	 * @return void
	 */
	public function render() {
		$this->get_template_part();
	}

	/**
	 * Sanitize the number field value.
	 *
	 * Only `step`, `min` and `max` are imposed; the sign is never stripped, because
	 * fields like `location_fee` document negative amounts as discounts.
	 *
	 * @since 2.2.5
	 *
	 * @param mixed $value The raw submitted value.
	 *
	 * @return int|float
	 */
	protected function sanitize_value( $value ) {
		if ( ! is_numeric( $value ) ) {
			return 0;
		}

		return $this->narrow( $this->clamp( $this->apply_step( $value + 0 ) ) );
	}

	/**
	 * Store a whole number as an int.
	 *
	 * Keeps the stored type unchanged for settings that never needed a float, and
	 * leaves values beyond the integer range alone, where the cast is not reversible.
	 *
	 * @param int|float $number The bounded number.
	 *
	 * @return int|float
	 */
	private function narrow( $number ) {
		if ( is_int( $number ) || abs( $number ) > PHP_INT_MAX ) {
			return $number;
		}

		return floor( $number ) === (float) $number ? (int) $number : $number;
	}

	/**
	 * Round to the precision the field declared.
	 *
	 * No `step` means no declared granularity, so the value is left as it came.
	 *
	 * @param int|float $number The submitted number.
	 *
	 * @return int|float
	 */
	private function apply_step( $number ) {
		$precision = $this->step_precision();

		if ( null === $precision ) {
			return $number;
		}

		$rounded = round( (float) $number, $precision );

		// A whole-number step describes an integer field, so store an int.
		return 0 === $precision ? (int) $rounded : $rounded;
	}

	/**
	 * How many decimal places the declared `step` allows.
	 *
	 * Read off the string, since deriving a decimal count from a float loses precision.
	 *
	 * @return int|null Null when the field declares no usable step.
	 */
	private function step_precision() {
		$step = $this->field['step'] ?? null;

		if ( ! is_numeric( $step ) ) {
			return null;
		}

		$decimals = strstr( (string) $step, '.' );

		return false === $decimals ? 0 : strlen( $decimals ) - 1;
	}

	/**
	 * Hold the number inside whichever bounds the field declared.
	 *
	 * A non-numeric bound is ignored: an empty `min` read as zero would forbid the
	 * negatives some fields document.
	 *
	 * @param int|float $number The number so far.
	 *
	 * @return int|float
	 */
	private function clamp( $number ) {
		$min = $this->field['min'] ?? null;
		$max = $this->field['max'] ?? null;

		if ( is_numeric( $min ) && $number < $min ) {
			return $min + 0;
		}

		if ( is_numeric( $max ) && $number > $max ) {
			return $max + 0;
		}

		return $number;
	}
}
