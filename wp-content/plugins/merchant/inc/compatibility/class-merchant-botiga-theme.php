<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Botiga theme compatibility layer
 */
if ( ! class_exists( 'Merchant_Botiga_Theme' ) ) {
	class Merchant_Botiga_Theme {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );

			add_filter( 'merchant_admin_module_field_wrapper_classes', array( $this, 'size_chart_placement_field_classes' ), 10, 4 );
			add_filter( 'merchant_admin_module_field_description', array( $this, 'size_chart_placement_field_description' ), 10, 4 );
		}

		/**
		 * Load compatibility styles if the Botiga theme is installed and active.
		 *
		 * @return void
		 */
		public function styles() {
			if ( ! merchant_is_botiga_active() ) {
				return;
			}

			wp_enqueue_style(
				'merchant-botiga-compatibility',
				MERCHANT_URI . 'assets/css/compatibility/botiga/style.min.css',
				array(),
				MERCHANT_VERSION
			);
		}

		/**
		 * Grey out the Size Chart display position field while Botiga owns the placement.
		 *
		 * @param array<int, string>   $classes   Field wrapper classes.
		 * @param array<string, mixed> $settings  Field configuration.
		 * @param mixed                $value     Current field value.
		 * @param string               $module_id Module ID.
		 *
		 * @return array<int, string>
		 */
		public function size_chart_placement_field_classes( $classes, $settings, $value, $module_id ) {
			if ( ! $this->is_size_chart_placement_field( $settings, $module_id ) || ! $this->botiga_owns_placement() ) {
				return $classes;
			}

			$classes[] = 'botiga-merchant-module-field-disabled';

			return $classes;
		}

		/**
		 * Tell the merchant where the placement is actually coming from.
		 *
		 * @param string               $desc      Field description.
		 * @param array<string, mixed> $settings  Field configuration.
		 * @param mixed                $value     Current field value.
		 * @param string               $module_id Module ID.
		 *
		 * @return string
		 */
		public function size_chart_placement_field_description( $desc, $settings, $value, $module_id ) {
			if ( ! $this->is_size_chart_placement_field( $settings, $module_id ) || ! $this->botiga_owns_placement() ) {
				return $desc;
			}

			// The renderer runs the description through wp_kses_post(), so the link and
			// the angle brackets in the breadcrumb survive without being escaped here.
			return sprintf(
				/* Translators: 1. Link open tag 2. Link close tag */
				__( 'The display is being controlled by Botiga under %1$sCustomizer > WooCommerce > Single Product > Layout (Elements)%2$s', 'merchant' ),
				'<a href="' . esc_url( admin_url( 'customize.php?autofocus[section]=botiga_section_single_product_layout' ) ) . '" target="_blank">',
				'</a>'
			);
		}

		/**
		 * Whether the given field is the Size Chart display position picker.
		 *
		 * @param array<string, mixed> $settings  Field configuration.
		 * @param string               $module_id Module ID.
		 *
		 * @return bool
		 */
		private function is_size_chart_placement_field( $settings, $module_id ) {
			return 'size-chart' === $module_id && isset( $settings['id'] ) && 'display_hook' === $settings['id'];
		}

		/**
		 * Whether Botiga's single product elements decide where the size chart renders.
		 *
		 * Botiga greys out the placement fields of the modules it takes over, but its
		 * map does not know about the size chart picker yet. Stand in until it does.
		 *
		 * @return bool
		 */
		private function botiga_owns_placement() {
			if ( ! merchant_is_botiga_active() || ! class_exists( 'Botiga_Merchant_Single_Product_Elements' ) ) {
				return false;
			}

			// The Merchant integration is the only thing hooking this action, and it does
			// so after its own opt-out checks, so it doubles as an "integration is live" flag.
			if ( ! has_action( 'botiga_before_render_single_product_elements' ) ) {
				return false;
			}

			// On the full width gallery the theme leaves the placement to the module.
			if ( 'gallery-full-width' === get_theme_mod( 'single_product_gallery', 'gallery-default' ) ) {
				return false;
			}

			$theme_fields = Botiga_Merchant_Single_Product_Elements::get_module_admin_field_descriptions();

			return ! isset( $theme_fields['size-chart']['display_hook'] );
		}
	}

	/**
	 * The class object can be accessed with "global $botiga_compatibility", to allow removing actions.
	 * Improving Third-party integrations.
	 */
	$merchant_botiga_compatibility = new Merchant_Botiga_Theme();
}
