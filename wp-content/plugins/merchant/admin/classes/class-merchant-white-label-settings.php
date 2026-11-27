<?php
/**
 * Merchant White Label settings section.
 *
 * Renders the White Label controls on the Global Settings page and keeps the
 * values in the aThemes White Label plugin's own option, so both UIs read and
 * write the same data.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Merchant_White_Label_Settings' ) ) {

	class Merchant_White_Label_Settings {

		/**
		 * Plugin slugs whose plugin-list rows are re-branded.
		 *
		 * One set of fields covers both, so every slug gets the same values.
		 *
		 * @var string[]
		 */
		const PLUGIN_SLUGS = array( 'merchant', 'merchant-pro' );

		/**
		 * The slug whose option keys the shared fields are stored under.
		 *
		 * Field IDs are AWL option keys verbatim, and AWL keys are per plugin, so
		 * the shared field borrows the free plugin's key and the save mirror copies
		 * the value out to the rest.
		 *
		 * @var string
		 */
		const PRIMARY_SLUG = self::PLUGIN_SLUGS[0];

		/**
		 * Settings module ID.
		 *
		 * @var string
		 */
		const MODULE = 'white-label';

		/**
		 * Where the plugin's switch, as the sync last saw it, is remembered.
		 *
		 * @var string
		 */
		const LAST_SEEN_OPTION = 'merchant_white_label_awl_last_seen';

		/**
		 * The row details an agency can hide, as the AWL key fragments.
		 *
		 * @var string[]
		 */
		const HIDE_TOGGLES = array( 'author', 'version', 'changelog', 'actions' );

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Priority 20 renders this after the License section (priority 10)
			// and before Analytics, which follows the same do_action() call.
			add_action( 'merchant_admin_settings_before_options', array( $this, 'create_settings' ), 20 );
			add_action( 'merchant_options_saved_' . self::MODULE, array( __CLASS__, 'mirror_to_plugin_option' ) );

			// Priority 20 runs after Merchant Pro's licence tier sync, so the
			// entitlement answer is settled before the switch is written.
			add_action( 'admin_init', array( __CLASS__, 'sync_switch_from_plugin' ), 20 );

			// The modal is a dialog, not a settings field: printing it in the
			// footer keeps it out of the locked section's disabling pass and out
			// of the settings form.
			add_action( 'admin_footer', array( $this, 'render_upsell_modal' ) );
		}

		/**
		 * Every option key this section owns.
		 *
		 * @return string[]
		 */
		public static function field_keys() {
			return array_merge( array( merchant_white_label_switch_key() ), self::shared_keys() );
		}

		/**
		 * Shared keys for the merchant plugin.
		 *
		 * @return string[]
		 */
		public static function shared_keys() {
			$keys = array(
				'awl_agency_name',
				'awl_agency_url',
				'awl_plugin_name_' . self::PRIMARY_SLUG,
				'awl_plugin_description_' . self::PRIMARY_SLUG,
			);

			foreach ( self::HIDE_TOGGLES as $toggle ) {
				$keys[] = self::hide_key( $toggle );
			}

			return $keys;
		}

		/**
		 * The field ID one hide toggle is posted under.
		 *
		 * @param string $toggle Toggle name.
		 *
		 * @return string
		 */
		private static function hide_key( $toggle ) {
			return 'awl_plugin_hide_' . $toggle . '_' . self::PRIMARY_SLUG;
		}

		/**
		 * Key prefixes whose single field fans out to a key per plugin slug.
		 *
		 * @return string[]
		 */
		private static function shared_prefixes() {
			$prefixes = array( 'awl_plugin_name_', 'awl_plugin_description_' );

			foreach ( self::HIDE_TOGGLES as $toggle ) {
				$prefixes[] = 'awl_plugin_hide_' . $toggle . '_';
			}

			return $prefixes;
		}

		/**
		 * The show-while-the-switch-is-on rule every dependent field carries.
		 *
		 * @return array<string, mixed>
		 */
		private static function toggled_on() {
			return array(
				'relation' => 'AND',
				'terms'    => array(
					array(
						'field'    => merchant_white_label_switch_key(),
						'operator' => '===',
						'value'    => true,
					),
				),
			);
		}

		/**
		 * The checkbox label for each hide toggle.
		 *
		 * @return array<string, string>
		 */
		private static function hide_labels() {
			return array(
				'author'    => esc_html__( 'Hide Author', 'merchant' ),
				'version'   => esc_html__( 'Hide Version', 'merchant' ),
				'changelog' => esc_html__( 'Hide Changelog', 'merchant' ),
				'actions'   => esc_html__( 'Hide Plugin Actions', 'merchant' ),
			);
		}

		/**
		 * The option keys one field ID is stored under.
		 *
		 * The plugin name, description and hide toggles are one field each in the
		 * UI and apply to every Merchant plugin, so they fan out to a key per slug.
		 *
		 * @param string $field_id Field ID.
		 *
		 * @return string[]
		 */
		private static function storage_keys( $field_id ) {
			foreach ( self::shared_prefixes() as $prefix ) {
				if ( $prefix . self::PRIMARY_SLUG !== $field_id ) {
					continue;
				}

				$keys = array();

				foreach ( self::PLUGIN_SLUGS as $slug ) {
					$keys[] = $prefix . $slug;
				}

				return $keys;
			}

			return array( $field_id );
		}

		/**
		 * Whether the aThemes White Label plugin is white-labelling this site.
		 *
		 * @param mixed $plugin_applying Flag from the caller, if any.
		 *
		 * @return bool
		 */
		private static function plugin_is_applying( $plugin_applying ) {
			return is_bool( $plugin_applying ) ? $plugin_applying : merchant_white_label_plugin_is_applying();
		}

		/**
		 * Whether the aThemes White Label plugin is installed, switched on or not.
		 *
		 * The sync follows the plugin's switch in both directions, so it runs on the
		 * weaker condition than the one the UI reads.
		 *
		 * @param mixed $plugin_active Flag from the caller, if any.
		 *
		 * @return bool
		 */
		private static function plugin_is_installed( $plugin_active ) {
			return is_bool( $plugin_active ) ? $plugin_active : merchant_white_label_plugin_available();
		}

		/**
		 * The switch field, in whichever of its two states applies.
		 *
		 * @param bool $plugin_drives Whether the plugin's switch is what this field reports.
		 *
		 * @return array<string, mixed>
		 */
		private static function switch_field( $plugin_drives ) {
			$field = array(
				'id'      => merchant_white_label_switch_key(),
				'type'    => 'switcher',
				'title'   => esc_html__( 'Enable White Label', 'merchant' ),
				'desc'    => esc_html__( 'Replace all Merchant branding and links with your own.', 'merchant' ),
				'default' => $plugin_drives ? merchant_white_label_plugin_is_switched_on() : merchant_white_label_is_switched_on(),
			);

			// Switching it off here would change nothing while the plugin is forcing
			// it on, so the control is shown rather than offered.
			if ( $plugin_drives ) {
				$field['locked'] = true;
			}

			return $field;
		}

		/**
		 * Field definitions, seeded from the plugin's stored option.
		 *
		 * @param bool|null $plugin_applying Whether the aThemes White Label plugin is applying
		 *                                   White Label. Detected when omitted.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_fields( $plugin_applying = null ) {
			$stored          = merchant_white_label_get_settings();
			$plugin_applying = self::plugin_is_applying( $plugin_applying );
			$plugin_drives   = $plugin_applying && merchant_white_label_is_available();

			$fields = array();

			$fields[] = self::switch_field( $plugin_drives );

			if ( $plugin_applying ) {
				$fields[] = array(
					'id'      => 'white_label_plugin_notice',
					'type'    => 'info',
					'content' => self::plugin_owner_notice( $plugin_drives ),
				);
			}

			$fields[] = array(
				'id'         => 'white_label_notice',
				'type'       => 'info',
				'content'    => self::bookmark_notice(),
				'conditions' => self::toggled_on(),
			);

			$text_fields = array(
				'awl_agency_name' => array( esc_html__( 'Your Agency Name', 'merchant' ), 'text' ),
				'awl_agency_url'  => array( esc_html__( 'Your Agency URL', 'merchant' ), 'url' ),
			);

			foreach ( $text_fields as $key => $field ) {
				$fields[] = array(
					'id'         => $key,
					'type'       => $field[1],
					'title'      => $field[0],
					'default'    => $stored[ $key ] ?? '',
					'conditions' => self::toggled_on(),
				);
			}

			// Follows the switch as well, or the rule floats above nothing.
			$fields[] = array(
				'type'       => 'divider',
				'conditions' => self::toggled_on(),
			);

			$fields[] = array(
				'id'         => 'awl_plugin_name_' . self::PRIMARY_SLUG,
				'type'       => 'text',
				'title'      => esc_html__( 'Plugin Name', 'merchant' ),
				'default'    => $stored[ 'awl_plugin_name_' . self::PRIMARY_SLUG ] ?? '',
				'conditions' => self::toggled_on(),
			);
			$fields[] = array(
				'id'         => 'awl_plugin_description_' . self::PRIMARY_SLUG,
				'type'       => 'textarea',
				'title'      => esc_html__( 'Plugin Description', 'merchant' ),
				'default'    => $stored[ 'awl_plugin_description_' . self::PRIMARY_SLUG ] ?? '',
				'conditions' => self::toggled_on(),
			);

			$first = true;
			foreach ( self::hide_labels() as $toggle => $label ) {
				$key = self::hide_key( $toggle );

				$field = array(
					'id'         => $key,
					'type'       => 'checkbox',
					'label'      => $label,
					'default'    => $stored[ $key ] ?? '',
					'conditions' => self::toggled_on(),
				);

				// One heading for the whole run, like the framework's other
				// grouped checkboxes.
				if ( $first ) {
					$field['title'] = esc_html__( 'Hide from the plugin row', 'merchant' );
					$first          = false;
				}

				$fields[] = $field;
			}

			// Tags every wrapper in this section, so the locked state can turn a
			// click anywhere on a field into the upgrade modal.
			foreach ( $fields as $index => $field ) {
				$fields[ $index ]['class'] = 'merchant-white-label-field';
			}

			return $fields;
		}

		/**
		 * Register the section.
		 *
		 * @return void
		 */
		public function create_settings() {
			if ( empty( $_POST['merchant_save'] ) && empty( $_POST['merchant_reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only branch decision, no data used.
				self::refresh_cache();
			}

			Merchant_Admin_Options::create(
				array(
					'module'   => self::MODULE,
					'title'    => esc_html__( 'White Label', 'merchant' ),
					'subtitle' => esc_html__( 'Replace Merchant branding with your own.', 'merchant' ),
					'locked'   => ! merchant_white_label_is_available(),
					'fields'   => self::get_fields(),
				)
			);
		}

		/**
		 * Re-seed the render cache from the White Label plugin's option.
		 *
		 * The framework renders from `merchant[white-label]`; that copy is a cache
		 * of `athemes_white_label_settings`, which stays canonical.
		 *
		 * @return void
		 */
		public static function refresh_cache() {
			$stored  = merchant_white_label_get_settings();
			$options = get_option( 'merchant', array() );
			$options = is_array( $options ) ? $options : array();
			$current = $options[ self::MODULE ] ?? array();
			$current = is_array( $current ) ? $current : array();

			$switch_key = merchant_white_label_switch_key();
			$cache      = array();

			// The switch is ours alone and is not in the shared option, so it is
			// carried over rather than reseeded — reading it from there would
			// silently switch White Label off on every page load.
			if ( array_key_exists( $switch_key, $current ) ) {
				$cache[ $switch_key ] = $current[ $switch_key ];
			}

			foreach ( self::shared_keys() as $key ) {
				if ( array_key_exists( $key, $stored ) ) {
					$cache[ $key ] = $stored[ $key ];
				}
			}

			if ( $current === $cache ) {
				return;
			}

			$options[ self::MODULE ] = $cache;
			update_option( 'merchant', $options );
		}

		/**
		 * Print the upgrade modal for sites that aren't entitled.
		 *
		 * @return void
		 */
		public function render_upsell_modal() {
			$page    = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$section = sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 'merchant' !== $page || 'settings' !== $section || merchant_white_label_is_available() ) {
				return;
			}

			require MERCHANT_DIR . 'admin/components/white-label-upsell-modal.php';
		}

		/**
		 * Carry the aThemes White Label plugin's switch into Merchant's own option.
		 *
		 * Keeps the two in step for as long as the plugin is installed, so removing
		 * it hands White Label over to Merchant's engine with nothing to migrate.
		 * The write goes one way only — the shared option stays the plugin's.
		 *
		 * @param bool|null $plugin_active Whether the aThemes White Label plugin is active.
		 *                                 Detected when omitted.
		 *
		 * @return void
		 */
		public static function sync_switch_from_plugin( $plugin_active = null ) {
			if ( ! self::plugin_is_installed( $plugin_active ) || ! merchant_white_label_is_available() ) {
				return;
			}

			$value     = merchant_white_label_plugin_is_switched_on() ? 1 : 0;
			$last_seen = get_option( self::LAST_SEEN_OPTION, '' );

			// Following the plugin's switch on every request would overwrite a value
			// the user set here, so only a change over there is carried across.
			if ( '' !== $last_seen && (int) $last_seen === $value ) {
				return;
			}

			update_option( self::LAST_SEEN_OPTION, $value );

			// First sight of a switched-off plugin has nothing to hand over, and
			// writing it would switch off a site that had turned this on already.
			if ( '' === $last_seen && 0 === $value ) {
				return;
			}

			self::store_switch( $value );
		}

		/**
		 * Write the switch into Merchant's own option, leaving the rest alone.
		 *
		 * @param int $value 1 or 0.
		 *
		 * @return void
		 */
		private static function store_switch( $value ) {
			$options = get_option( 'merchant', array() );
			$options = is_array( $options ) ? $options : array();
			$module  = $options[ self::MODULE ] ?? array();
			$module  = is_array( $module ) ? $module : array();

			$module[ merchant_white_label_switch_key() ] = $value;
			$options[ self::MODULE ]                     = $module;

			update_option( 'merchant', $options );
		}

		/**
		 * Say who is white-labelling the site while the aThemes White Label plugin is.
		 *
		 * @param bool $plugin_drives Whether the switch above reports the plugin's own state.
		 *
		 * @return string
		 */
		public static function plugin_owner_notice( $plugin_drives = true ) {
			$link = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'themes.php?page=athemes-white-label' ) ),
				esc_html__( 'Change it here', 'merchant' )
			);

			// An unentitled site still has to know why its branding is masked, even
			// though Merchant's own switch is not the thing doing it.
			$text = $plugin_drives
				/* translators: %s: a link reading "Change it here". */
				? esc_html__( 'Switched on by the aThemes White Label plugin. %s.', 'merchant' )
				/* translators: %s: a link reading "Change it here". */
				: esc_html__( 'The aThemes White Label plugin is white-labelling this site. %s.', 'merchant' );

			return sprintf( $text, $link );
		}

		/**
		 * Tell the user how to get back here once the Merchant menu is hidden.
		 *
		 * @return string
		 */
		public static function bookmark_notice() {
			$url = add_query_arg(
				array(
					'page'    => 'merchant',
					'section' => 'settings',
				),
				admin_url( 'admin.php' )
			);

			return '<strong>' . esc_html__( 'How do I change these settings after enabling White Label?', 'merchant' ) . '</strong><br>'
				. esc_html__( 'White Label hides Merchant branding, including its admin menu. Bookmark this address before you save — it is how you get back here:', 'merchant' ) . '<br>'
				. '<a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a>';
		}

		/**
		 * Copy the saved values into the White Label plugin's own option.
		 *
		 * Merges rather than replaces, so keys written by the aThemes White Label plugin
		 * survive, then refreshes Merchant's cache to match what was stored.
		 *
		 * @param array<string, mixed> $module_options Saved module options.
		 *
		 * @return void
		 */
		public static function mirror_to_plugin_option( $module_options ) {
			$stored     = merchant_white_label_get_settings();
			$shared     = self::shared_keys();
			$cache      = array();

			foreach ( self::field_keys() as $key ) {
				if ( ! array_key_exists( $key, $module_options ) ) {
					continue;
				}

				$value         = self::sanitize_value( $key, $module_options[ $key ] );
				$cache[ $key ] = $value;

				// The switch is cached for our own screen and engine only. Writing
				// it to the shared option would turn White Label on for every other
				// aThemes product on the site.
				if ( ! in_array( $key, $shared, true ) ) {
					continue;
				}

				foreach ( self::storage_keys( $key ) as $storage_key ) {
					$stored[ $storage_key ] = $value;
				}
			}

			update_option( 'athemes_white_label_settings', $stored );

			// Keep the render cache in step with what was just stored, otherwise
			// the render that follows this save shows the pre-save values.
			$options = get_option( 'merchant', array() );
			$options = is_array( $options ) ? $options : array();

			$options[ self::MODULE ] = $cache;
			update_option( 'merchant', $options );
		}

		/**
		 * Sanitize one value for storage.
		 *
		 * The plugin's own sanitize callback only runs on Settings API saves, so
		 * this write path has to do its own.
		 *
		 * @param string $key   Option key.
		 * @param mixed  $value Raw value.
		 *
		 * @return mixed
		 */
		private static function sanitize_value( $key, $value ) {
			if ( merchant_white_label_switch_key() === $key || 0 === strpos( $key, 'awl_plugin_hide_' ) ) {
				return empty( $value ) ? 0 : 1;
			}

			if ( 'awl_agency_url' === $key ) {
				return sanitize_url( $value );
			}

			if ( 0 === strpos( $key, 'awl_plugin_description_' ) ) {
				return sanitize_textarea_field( $value );
			}

			return sanitize_text_field( $value );
		}
	}

	new Merchant_White_Label_Settings();
}
