<?php
/**
 * Delivery & Pickup.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Delivery & Pickup class.
 */
class Merchant_Delivery_Pickup extends Merchant_Add_Module {

	/**
	 * Module ID.
	 */
	const MODULE_ID = 'delivery-pickup';

	/**
	 * Is module preview.
	 *
	 * @var bool
	 */
	public static $is_module_preview = false;

	/**
	 * Admin assets handler.
	 *
	 * @var Merchant_Delivery_Pickup_Admin_Assets
	 */
	private $admin_assets;

	/**
	 * Admin preview handler.
	 *
	 * @var Merchant_Delivery_Pickup_Admin_Preview
	 */
	private $admin_preview;

	/**
	 * Translation handler.
	 *
	 * @var Merchant_Delivery_Pickup_Translations
	 */
	private $translations;

	/**
	 * Initialize the module's option group definitions.
	 *
	 * @return array<int, array<string, mixed>> Array of option group arrays.
	 */
	protected function init_option_groups() {
		return require MERCHANT_DIR . 'inc/modules/' . self::MODULE_ID . '/admin/options.php';
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->module_id = self::MODULE_ID;
		$this->wc_only   = true;

		parent::__construct();

		$this->setup_module();
		$this->create_collaborators();
		$this->register_hooks();
		$this->init_translations();
	}

	/**
	 * Configure module properties required by the parent class.
	 *
	 * @return void
	 */
	private function setup_module(): void {
		$this->module_section      = 'convert-more';
		$this->module_data         = Merchant_Admin_Modules::$modules_data[ self::MODULE_ID ];
		$this->module_options_path = MERCHANT_DIR . 'inc/modules/' . self::MODULE_ID . '/admin/options.php';

		// Mirrors options.php defaults; the parent won't read them itself.
		// Repeater rows keep their own defaults instead.
		$this->module_default_settings = array(
			'suppress_core_pickup'     => 1,

			'date_format'              => 'weekday_day_month',
			'time_format'              => 'site_default',
			'show_slot_price'          => 1,
			'show_free_delivery_hint'  => 1,
			'is_required'              => 1,
			'required_message'         => __( 'Please choose when you would like your order.', 'merchant' ),

			'email_to_customer'        => 1,
			'email_to_admin'           => 1,
			'email_block_heading'      => __( 'Delivery details', 'merchant' ),
			'email_details'            => array( 'fulfilment', 'date', 'slot', 'location', 'notes' ),
			'email_position'           => 'after_order_table',

			'reminder_email'           => 0,
			'reminder_hours_before'    => 24,
			'reminder_email_subject'   => __( 'Your order arrives {delivery_date}', 'merchant' ),
			'reminder_email_body'      => __( 'Hello {customer_name}, a quick reminder that order {order_number} is on its way {delivery_date} between {delivery_slot}.', 'merchant' ),
			'ready_email'              => 1,
			'ready_email_subject'      => __( 'Your order is ready at {location_name}', 'merchant' ),
			'ready_email_body'         => __( 'Hello {customer_name}, order {order_number} is packed and waiting for you at {location_name}, {location_address}. {collection_notes}', 'merchant' ),
			'reschedule_email'         => 1,
			'reschedule_email_subject' => __( 'A new time for order {order_number}', 'merchant' ),
			'reschedule_email_body'    => __( "Hello {customer_name}, {moved_booking} has been rescheduled to {new_delivery}.\n\n{booking_details}\n\n{order_details}", 'merchant' ),
			'unsubscribe_link'         => 1,
			'unsubscribe_message'      => __( 'You will no longer receive delivery and collection emails from us.', 'merchant' ),
			'reschedule_staff_email'   => 1,
			'cancellation_staff_email' => 1,
			'allow_reschedule'         => 0,
			'reschedule_cutoff_hours'  => 24,
			'customer_ics'             => 0,

			'orders_list_column'       => 1,
			'delivery_calendar'        => 1,
			'capacity_unit'            => 'orders',
		);

		$this->ai_examples = array(
			'blurb'   => esc_html__( 'See what AI can do for your delivery schedules, from opening a new time slot to charging more for a weekend delivery.', 'merchant' ),
			'prompts' => array(
				esc_html__( 'Explore the abilities WPVibe gives you access to, then create a delivery schedule called Standard delivery that runs Monday to Friday with two-hour slots between 09:00 and 18:00', 'merchant' ),
				esc_html__( 'Check what abilities you have available through WPVibe, then charge £6 for Saturday delivery and make it free once the cart reaches £60', 'merchant' ),
				esc_html__( 'Look at your available WPVibe abilities, then limit the Standard delivery schedule to 12 orders per day and 4 orders per slot', 'merchant' ),
				esc_html__( 'See what abilities WPVibe exposes, then add a High Street shop pickup location open Monday to Saturday from 09:00 to 17:30 with two hours preparation time', 'merchant' ),
			),
		);
	}

	/**
	 * Instantiate collaborator objects.
	 *
	 * @return void
	 */
	private function create_collaborators(): void {
		$this->admin_assets  = new Merchant_Delivery_Pickup_Admin_Assets( self::MODULE_ID );
		$this->admin_preview = new Merchant_Delivery_Pickup_Admin_Preview( self::MODULE_ID, array( $this, 'get_module_settings' ) );
		$this->translations  = new Merchant_Delivery_Pickup_Translations();
	}

	/**
	 * Register the admin-side hooks. Front-end behaviour ships with Merchant Pro.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		if ( is_admin() && parent::is_module_settings_page() ) {
			self::$is_module_preview = true;

			add_action( 'admin_enqueue_scripts', array( $this->admin_assets, 'enqueue_css' ) );
			add_filter( 'merchant_module_preview', array( $this->admin_preview, 'render' ), 10, 2 );
		}
	}

	/**
	 * Register translatable strings when the module is active.
	 *
	 * @return void
	 */
	private function init_translations(): void {
		if ( is_admin() && Merchant_Modules::is_module_active( self::MODULE_ID ) ) {
			$this->translations->register( $this->get_module_settings() );
		}
	}
}

require_once __DIR__ . '/class-delivery-pickup-translations.php';
require_once __DIR__ . '/class-delivery-pickup-tables.php';
require_once __DIR__ . '/admin/class-delivery-pickup-choices.php';
require_once __DIR__ . '/admin/class-delivery-pickup-admin-assets.php';
require_once __DIR__ . '/admin/class-delivery-pickup-admin-preview.php';

// Outside the module, so tables exist even if it's off.
( new Merchant_Delivery_Pickup_Tables() )->register_hooks();

add_action( 'init', function () {
	Merchant_Modules::create_module( new Merchant_Delivery_Pickup() );
} );
