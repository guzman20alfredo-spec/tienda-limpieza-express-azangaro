<?php
/**
 * Delivery & Pickup — Translation Registration.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the module's merchant-authored strings for translation.
 */
class Merchant_Delivery_Pickup_Translations {

	/**
	 * Callable used to register a translatable string.
	 *
	 * @var callable
	 */
	private $register_callback;

	/**
	 * Constructor.
	 *
	 * @param callable|null $register_callback Optional. Defaults to Merchant_Translator::register_string().
	 */
	public function __construct( ?callable $register_callback = null ) {
		$this->register_callback = $register_callback ?? array( 'Merchant_Translator', 'register_string' );
	}

	/**
	 * Settings keys that hold a single translatable string.
	 *
	 * @return array<string, string> Setting key => translator label.
	 */
	private function single_strings(): array {
		return array(
			'required_message'         => 'Delivery & pickup: required message',
			'email_block_heading'      => 'Delivery & pickup: order email block heading',
			'reminder_email_subject'   => 'Delivery & pickup: reminder email subject',
			'reminder_email_body'      => 'Delivery & pickup: reminder email message',
			'ready_email_subject'      => 'Delivery & pickup: ready to collect email subject',
			'ready_email_body'         => 'Delivery & pickup: ready to collect email message',
			'reschedule_email_subject' => 'Delivery & pickup: changed slot email subject',
			'reschedule_email_body'    => 'Delivery & pickup: changed slot email message',
			'unsubscribe_message'      => 'Delivery & pickup: unsubscribe confirmation message',
		);
	}

	/**
	 * Register every translatable string found in the module settings.
	 *
	 * @param array<string, mixed> $settings The module settings.
	 *
	 * @return void
	 */
	public function register( array $settings ): void {
		foreach ( $this->single_strings() as $key => $label ) {
			if ( ! empty( $settings[ $key ] ) ) {
				( $this->register_callback )( $settings[ $key ], $label );
			}
		}

		$this->register_repeater_strings(
			$settings['schedules'] ?? null,
			array( 'schedule_title' => 'Delivery & pickup: schedule name' )
		);

		$this->register_repeater_strings(
			$settings['pickup_locations'] ?? null,
			array(
				'location_name'    => 'Delivery & pickup: location name',
				'collection_notes' => 'Delivery & pickup: collection notes',
			)
		);
	}

	/**
	 * Register the named keys of every row in a repeater setting.
	 *
	 * @param mixed                 $rows The repeater rows, as stored in the settings.
	 * @param array<string, string> $keys Row key => translator label.
	 *
	 * @return void
	 */
	private function register_repeater_strings( $rows, array $keys ): void {
		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			foreach ( $keys as $key => $label ) {
				if ( ! empty( $row[ $key ] ) ) {
					( $this->register_callback )( $row[ $key ], $label );
				}
			}
		}
	}
}
