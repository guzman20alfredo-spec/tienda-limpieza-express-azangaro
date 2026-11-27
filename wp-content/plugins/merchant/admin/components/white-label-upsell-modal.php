<?php
/**
 * White Label upsell modal.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$merchant_wl_upgrade_url = merchant_admin_upgrade_link(
	'https://athemes.com/merchant-upgrade',
	array( 'utm_content' => 'white-label-settings' ),
	'white-label-upgrade-link'
);

$merchant_wl_license_url = add_query_arg(
	array(
		'page'    => 'merchant',
		'section' => 'settings',
	),
	admin_url( 'admin.php' )
);

$merchant_wl_body = sprintf(
	/* translators: %s: the Agency plan name, in bold. */
	esc_html__( 'White Label is not available on your plan. Please upgrade to the %s to unlock this option.', 'merchant' ),
	'<strong>' . esc_html__( 'Agency plan', 'merchant' ) . '</strong>'
);

$merchant_wl_bonus = sprintf(
	/* translators: 1: the word "Bonus:" in bold, 2: the discount, in bold. */
	esc_html__( '%1$s Merchant users get %2$s regular price, automatically applied at checkout.', 'merchant' ),
	'<strong>' . esc_html__( 'Bonus:', 'merchant' ) . '</strong>',
	'<strong class="merchant-white-label-modal-bonus-highlight">' . esc_html__( '20% off', 'merchant' ) . '</strong>'
);

$merchant_wl_footer = sprintf(
	/* translators: %s: a link reading "Activate your license". */
	esc_html__( 'Already purchased? %s to unlock this feature', 'merchant' ),
	'<a href="' . esc_url( $merchant_wl_license_url ) . '">' . esc_html__( 'Activate your license', 'merchant' ) . '</a>'
);

?>

<div id="merchant-white-label-modal" class="merchant-white-label-modal-overlay" aria-hidden="true">
	<div class="merchant-white-label-modal-box" role="dialog" aria-modal="true" aria-labelledby="merchant-white-label-modal-title">

		<button type="button" class="merchant-white-label-modal-close" aria-label="<?php esc_attr_e( 'Close', 'merchant' ); ?>">
			<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M2 2L16 16M16 2L2 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
			</svg>
		</button>

		<div class="merchant-white-label-modal-lock">
			<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
				<path d="M24 4c-6.075 0-11 4.925-11 11v5h5v-5a6 6 0 0 1 12 0v5h5v-5c0-6.075-4.925-11-11-11Z" fill="currentColor"/>
				<rect x="9" y="20" width="30" height="24" rx="3" fill="currentColor"/>
			</svg>
		</div>

		<h3 id="merchant-white-label-modal-title" class="merchant-white-label-modal-title">
			<?php esc_html_e( 'This White Label is in Agency Plan', 'merchant' ); ?>
		</h3>

		<p class="merchant-white-label-modal-body">
			<?php echo wp_kses_post( $merchant_wl_body ); ?>
		</p>

		<a class="merchant-white-label-modal-cta button button-primary" href="<?php echo esc_url( $merchant_wl_upgrade_url ); ?>" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Upgrade to Agency', 'merchant' ); ?>
		</a>

		<div class="merchant-white-label-modal-bonus">
			<span class="merchant-white-label-modal-bonus-badge" aria-hidden="true">
				<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
					<path d="M13.2 4.4 6.4 11.2 2.8 7.6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
			<?php echo wp_kses_post( $merchant_wl_bonus ); ?>
		</div>

		<div class="merchant-white-label-modal-footer">
			<?php echo wp_kses_post( $merchant_wl_footer ); ?>
		</div>

	</div>
</div>
