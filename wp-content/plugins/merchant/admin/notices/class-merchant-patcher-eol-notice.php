<?php
/**
 * aThemes Patcher end-of-life notice.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Merchant_Patcher_EOL_Notice {

	const PLUGIN_FILE   = 'athemes-patcher/athemes-patcher.php';
	const SESSION_META  = 'merchant_patcher_eol_dismissed_session';
	const DISMISS_ARG   = 'merchant_patcher_eol_dismiss';
	const DISMISS_NONCE = 'merchant_patcher_eol_nonce';
	const RESULT_ARG    = 'merchant_patcher_removed';
	const REMOVE_ACTION = 'merchant_remove_patcher';

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'maybe_dismiss' ), 0 );
		add_action( 'admin_post_' . self::REMOVE_ACTION, array( $this, 'remove_patcher' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_notices', array( $this, 'plugins_screen_notice' ) );
		add_action( 'network_admin_notices', array( $this, 'plugins_screen_notice' ) );
		add_action( 'admin_notices', array( $this, 'result_notice' ) );
		add_action( 'network_admin_notices', array( $this, 'result_notice' ) );
		add_action( 'in_admin_header', array( $this, 'merchant_screen_bar' ), 20 );
	}

	/**
	 * Enqueue the notices stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! $this->is_plugins_screen() && ! $this->is_merchant_screen() ) {
			return;
		}

		wp_enqueue_style( 'merchant-notices', MERCHANT_URI . 'assets/css/admin/notices.min.css', array(), MERCHANT_VERSION, 'all' );
	}

	/**
	 * Render the notice on the plugins screen.
	 *
	 * @return void
	 */
	public function plugins_screen_notice() {
		if ( ! $this->is_plugins_screen() || $this->sibling_owns_plugins_notice() || ! $this->should_show() ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible merchant-patcher-eol">
			<?php $this->notice_content(); ?>
		</div>
		<?php
	}

	/**
	 * Render the notice as a bar on Merchant screens.
	 *
	 * @return void
	 */
	public function merchant_screen_bar() {
		if ( ! $this->is_merchant_screen() || $this->is_white_label_applied() || ! $this->should_show() ) {
			return;
		}

		?>
		<div class="notice notice-info is-dismissible merchant-patcher-eol merchant-patcher-eol-bar">
			<?php $this->notice_content(); ?>
		</div>
		<?php
	}

	/**
	 * Render the outcome of a removal attempt.
	 *
	 * @return void
	 */
	public function result_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $this->is_plugins_screen() || ! isset( $_GET[ self::RESULT_ARG ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '1' === $_GET[ self::RESULT_ARG ] ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'The aThemes Patcher has been deactivated and deleted.', 'merchant' ); ?></p>
			</div>
			<?php

			return;
		}

		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Could not remove the aThemes Patcher. Delete it from the plugins list below, or contact support.', 'merchant' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render the shared notice content.
	 *
	 * @return void
	 */
	private function notice_content() {
		?>
		<p>
			<strong><?php esc_html_e( 'Patcher update', 'merchant' ); ?></strong>
			&mdash;
			<?php esc_html_e( 'The Patcher plugin is no longer needed. You can safely remove it from your site.', 'merchant' ); ?>
			<?php if ( $this->can_delete_patcher() ) : ?>
				<a href="<?php echo esc_url( $this->get_remove_url() ); ?>"><strong><?php esc_html_e( 'Deactivate & Delete', 'merchant' ); ?></strong></a>
			<?php elseif ( is_multisite() ) : ?>
				<?php esc_html_e( 'Ask your network administrator to remove it.', 'merchant' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Plugin deletion is disabled on this site, so it has to be removed manually.', 'merchant' ); ?>
			<?php endif; ?>
		</p>
		<a class="notice-dismiss" href="<?php echo esc_url( $this->get_dismiss_url() ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'merchant' ); ?></span>
		</a>
		<?php
	}

	/**
	 * Deactivate and delete the Patcher.
	 *
	 * @return void
	 */
	public function remove_patcher() {
		check_admin_referer( self::REMOVE_ACTION );

		if ( ! current_user_can( 'delete_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete plugins.', 'merchant' ) );
		}

		if ( ! $this->is_patcher_installed() ) {
			$this->redirect_to_plugins_screen( true );
		}

		$this->load_plugin_api();

		$network_wide = is_multisite() && is_plugin_active_for_network( self::PLUGIN_FILE );

		if ( is_plugin_active( self::PLUGIN_FILE ) || $network_wide ) {
			deactivate_plugins( self::PLUGIN_FILE, false, $network_wide );
		}

		// delete_plugins() returns null when it asks for filesystem credentials instead.
		$deleted = true === delete_plugins( array( self::PLUGIN_FILE ) ) && ! $this->is_patcher_installed();

		if ( $deleted ) {
			delete_metadata( 'user', 0, self::SESSION_META, '', true );
		}

		$this->redirect_to_plugins_screen( $deleted );
	}

	/**
	 * Dismiss the notice for the current login session.
	 *
	 * @return void
	 */
	public function maybe_dismiss() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::DISMISS_ARG ] ) || '1' !== $_GET[ self::DISMISS_ARG ] ) {
			return;
		}

		// Own nonce name, so dismissing does not overwrite a core nonce already in the URL.
		$nonce = isset( $_GET[ self::DISMISS_NONCE ] ) && is_string( $_GET[ self::DISMISS_NONCE ] )
			? sanitize_text_field( wp_unslash( $_GET[ self::DISMISS_NONCE ] ) )
			: '';

		// A stale tab fails quietly instead of hitting the expired link screen.
		if ( ! wp_verify_nonce( $nonce, self::DISMISS_ARG ) ) {
			return;
		}

		$session_id = $this->get_current_session_id();

		if ( '' !== $session_id ) {
			update_user_meta( get_current_user_id(), self::SESSION_META, $session_id );
		}

		wp_safe_redirect( remove_query_arg( array( self::DISMISS_ARG, self::DISMISS_NONCE ) ) );
		exit;
	}

	/**
	 * Check whether the notice should be rendered.
	 *
	 * @return bool
	 */
	private function should_show() {
		return current_user_can( 'activate_plugins' )
			&& $this->is_patcher_installed()
			&& ! $this->is_dismissed_for_current_session();
	}

	/**
	 * Check whether the current user can act on the removal link.
	 *
	 * @return bool
	 */
	private function can_delete_patcher() {
		if ( ! current_user_can( 'delete_plugins' ) ) {
			return false;
		}

		// Only the network screen deletes without breaking sibling sites.
		return ! is_multisite() || is_network_admin();
	}

	/**
	 * Check whether the Patcher plugin is present.
	 *
	 * @return bool
	 */
	private function is_patcher_installed() {
		return file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE );
	}

	/**
	 * Check whether a sibling aThemes product owns the plugins screen notice.
	 *
	 * @return bool
	 */
	private function sibling_owns_plugins_notice() {
		/**
		 * Filters the notice classes of sibling aThemes products.
		 *
		 * @since 2.3.2
		 *
		 * @param array $siblings Class names to look for.
		 */
		$siblings = apply_filters(
			'merchant_patcher_eol_sibling_notices',
			array(
				'Botiga_Patcher_EOL_Notice',
				'Sydney_Patcher_EOL_Notice',
			)
		);

		foreach ( (array) $siblings as $sibling ) {
			if ( class_exists( $sibling, false ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether the notice was dismissed for the current login session.
	 *
	 * @return bool
	 */
	private function is_dismissed_for_current_session() {
		$session_id = $this->get_current_session_id();

		if ( '' === $session_id ) {
			return false;
		}

		$dismissed = (string) get_user_meta( get_current_user_id(), self::SESSION_META, true );

		return '' !== $dismissed && hash_equals( $dismissed, $session_id );
	}

	/**
	 * Get a hashed identifier for the current login session.
	 *
	 * @return string
	 */
	private function get_current_session_id() {
		$token = wp_get_session_token();

		return '' === $token ? '' : wp_hash( $token );
	}

	/**
	 * Check whether the current screen is the plugins screen.
	 *
	 * @return bool
	 */
	private function is_plugins_screen() {
		$screen = get_current_screen();

		return $screen && in_array( $screen->base, array( 'plugins', 'plugins-network' ), true );
	}

	/**
	 * Check whether the current screen belongs to Merchant.
	 *
	 * @return bool
	 */
	private function is_merchant_screen() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		return 'toplevel_page_merchant' === $screen->id || 0 === strpos( $screen->id, 'merchant_page_' );
	}

	/**
	 * Check whether White Label is hiding the aThemes branding.
	 *
	 * @return bool
	 */
	private function is_white_label_applied() {
		return function_exists( 'merchant_white_label_is_applied' ) && merchant_white_label_is_applied();
	}

	/**
	 * Get the removal action URL.
	 *
	 * @return string
	 */
	private function get_remove_url() {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::REMOVE_ACTION ),
			self::REMOVE_ACTION
		);
	}

	/**
	 * Get the dismissal URL for the current request.
	 *
	 * @return string
	 */
	private function get_dismiss_url() {
		return wp_nonce_url( add_query_arg( self::DISMISS_ARG, '1' ), self::DISMISS_ARG, self::DISMISS_NONCE );
	}

	/**
	 * Redirect to the plugins screen carrying the removal result.
	 *
	 * @param bool $removed Whether the plugin was removed.
	 *
	 * @return void
	 */
	private function redirect_to_plugins_screen( $removed ) {
		$url = is_multisite() && current_user_can( 'manage_network_plugins' )
			? network_admin_url( 'plugins.php' )
			: admin_url( 'plugins.php' );

		wp_safe_redirect( add_query_arg( self::RESULT_ARG, $removed ? '1' : '0', $url ) );
		exit;
	}

	/**
	 * Load the WordPress plugin API.
	 *
	 * @return void
	 */
	private function load_plugin_api() {
		if ( ! function_exists( 'delete_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}

new Merchant_Patcher_EOL_Notice();
