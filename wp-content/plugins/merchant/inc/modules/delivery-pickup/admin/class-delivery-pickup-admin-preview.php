<?php
/**
 * Delivery & Pickup — Admin Preview.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Builds the checkout mock-up shown on the module settings page.
 *
 * Mirrors the module's own markup and class names.
 */
class Merchant_Delivery_Pickup_Admin_Preview {

	/**
	 * Module ID.
	 *
	 * @var string
	 */
	private $module_id;

	/**
	 * Callable returning the module settings, defaults included.
	 *
	 * @var callable|null
	 */
	private $settings_provider;

	/**
	 * Constructor.
	 *
	 * @param string        $module_id
	 * @param callable|null $settings_provider Optional. Returns the module settings.
	 */
	public function __construct( string $module_id, ?callable $settings_provider = null ) {
		$this->module_id         = $module_id;
		$this->settings_provider = $settings_provider;
	}

	/**
	 * Render the admin preview. Hooked to `merchant_module_preview`.
	 *
	 * @param Merchant_Admin_Preview $preview The preview instance.
	 * @param string                 $module  The current module ID.
	 *
	 * @return Merchant_Admin_Preview
	 */
	public function render( $preview, string $module ) {
		if ( $this->module_id !== $module ) {
			return $preview;
		}

		$settings = null !== $this->settings_provider ? (array) call_user_func( $this->settings_provider ) : array();

		$preview->set_html( $this->get_preview_html( $settings ) );
		$this->register_class_manipulators( $preview );

		return $preview;
	}

	/**
	 * Toggle the two parts of the block a switcher still controls.
	 *
	 * @param Merchant_Admin_Preview $preview The preview instance.
	 *
	 * @return void
	 */
	private function register_class_manipulators( $preview ): void {
		// Renders as a checkbox, so classes name the checked state.
		$preview->set_class( 'show_slot_price', '.merchant-dp-preview', array(), 'merchant-dp-show-prices' );
		$preview->set_class( 'show_free_delivery_hint', '.merchant-dp-preview', array(), 'merchant-dp-show-hint' );
	}

	/**
	 * Get the preview markup.
	 *
	 * @param array<string, mixed> $settings The module settings.
	 *
	 * @return string
	 */
	public function get_preview_html( array $settings = array() ): string {
		ob_start();
		?>
		<div class="mrc-preview-checkout-elements <?php echo esc_attr( $this->wrapper_classes( $settings ) ); ?>">
			<div class="mrc-preview-left-column">
				<?php $this->the_checkout_placeholder_block( __( 'Shipping address', 'merchant' ), 3 ); ?>
			</div>
			<div class="mrc-preview-right-column">
				<?php $this->the_order_summary(); ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The wrapper classes the two switchers toggle.
	 *
	 * @param array<string, mixed> $settings The module settings.
	 *
	 * @return string
	 */
	private function wrapper_classes( array $settings ): string {
		$classes = array( 'merchant-dp-preview' );

		if ( $settings['show_slot_price'] ?? 1 ) {
			$classes[] = 'merchant-dp-show-prices';
		}

		if ( $settings['show_free_delivery_hint'] ?? 1 ) {
			$classes[] = 'merchant-dp-show-hint';
		}

		return implode( ' ', $classes );
	}

	/**
	 * A greyed-out checkout section, so the module has context around it.
	 *
	 * @param string $title The section title.
	 * @param int    $rows  How many placeholder rows to draw.
	 *
	 * @return void
	 */
	private function the_checkout_placeholder_block( string $title, int $rows ): void {
		?>
		<div class="merchant-dp-checkout-block">
			<div class="merchant-dp-checkout-block-title"><?php echo esc_html( $title ); ?></div>
			<?php for ( $row = 0; $row < $rows; $row++ ) : ?>
				<div class="mrc-preview-text-placeholder"></div>
			<?php endfor; ?>
		</div>
		<?php
	}

	/**
	 * The order summary, where the module does its asking.
	 *
	 * @return void
	 */
	private function the_order_summary(): void {
		?>
		<div class="merchant-dp-preview-summary">
			<div class="merchant-dp-preview-summary-title"><?php esc_html_e( 'Your order', 'merchant' ); ?></div>
			<table class="shop_table">
				<tbody>
					<?php
					$this->the_answered_row();
					$this->the_unanswered_row();
					?>
				</tbody>
			</table>
			<?php
			$this->the_shipments();
			$this->the_free_delivery_hint();
			$this->the_totals();
			?>
		</div>
		<?php
	}

	/**
	 * A row whose question has been answered: the panel open on its five controls.
	 *
	 * @return void
	 */
	private function the_answered_row(): void {
		?>
		<tr class="cart_item">
			<td class="product-name">
				<span class="merchant-dp-preview-item"><?php esc_html_e( 'Blue mug', 'merchant' ); ?></span>
				<span class="merchant-dp-preview-qty">&times;&nbsp;1</span>
				<div class="merchant-dp-line">
					<details class="merchant-dp-line-details" open>
						<summary class="merchant-dp-line-summary"><?php esc_html_e( 'Delivery · Sat 15 Aug · 13:00–15:00', 'merchant' ); ?></summary>
						<?php $this->the_line_panel( true ); ?>
					</details>
				</div>
			</td>
			<td class="product-total"><?php echo esc_html( $this->price( 24 ) ); ?></td>
		</tr>
		<?php
	}

	/**
	 * A row still waiting on its answer, collapsed to the prompt the module shows.
	 *
	 * @return void
	 */
	private function the_unanswered_row(): void {
		?>
		<tr class="cart_item">
			<td class="product-name">
				<span class="merchant-dp-preview-item"><?php esc_html_e( 'Tea towel', 'merchant' ); ?></span>
				<span class="merchant-dp-preview-qty">&times;&nbsp;2</span>
				<div class="merchant-dp-line">
					<details class="merchant-dp-line-details">
						<summary class="merchant-dp-line-summary">
							<span class="merchant-dp-line-prompt"><?php esc_html_e( 'Choose delivery or collection', 'merchant' ); ?></span>
						</summary>
						<?php $this->the_line_panel( false ); ?>
					</details>
				</div>
			</td>
			<td class="product-total"><?php echo esc_html( $this->price( 12 ) ); ?></td>
		</tr>
		<?php
	}

	/**
	 * The five questions a row asks, answered or unanswered with nothing chosen.
	 *
	 * @param bool $answered Whether to mark the stored choice on each control.
	 *
	 * @return void
	 */
	private function the_line_panel( bool $answered ): void {
		?>
		<div class="merchant-dp-line-panel">
			<?php
			$this->the_select(
				__( 'How', 'merchant' ),
				array(
					array( 'label' => __( 'Delivery', 'merchant' ), 'selected' => $answered ),
					array( 'label' => __( 'Collection', 'merchant' ) ),
				)
			);
			$this->the_fixed_control(
				__( 'Service', 'merchant' ),
				__( 'Standard delivery', 'merchant' ),
				'+' . $this->price( 4.95 )
			);
			$this->the_select(
				__( 'Day', 'merchant' ),
				array(
					array( 'label' => __( 'Fri 14 Aug', 'merchant' ) ),
					array( 'label' => __( 'Sat 15 Aug', 'merchant' ), 'selected' => $answered ),
					array( 'label' => __( 'Sun 16 Aug', 'merchant' ) ),
				)
			);
			$this->the_select(
				__( 'Time', 'merchant' ),
				array(
					array( 'label' => __( '09:00–11:00', 'merchant' ) ),
					array( 'label' => __( '11:00–13:00 — 2 left', 'merchant' ) ),
					array( 'label' => __( '13:00–15:00', 'merchant' ), 'selected' => $answered ),
					array( 'label' => __( '17:00–19:00', 'merchant' ), 'blocked' => true ),
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * One dropdown, as the module renders a question with more than one answer.
	 *
	 * `disabled` does not survive the preview's kses pass, so state rides in a class.
	 *
	 * @param string                            $label   The control's label.
	 * @param array<int, array<string, mixed>>  $options The choices.
	 *
	 * @return void
	 */
	private function the_select( string $label, array $options ): void {
		?>
		<label class="merchant-dp-line-field">
			<span class="merchant-dp-line-label"><?php echo esc_html( $label ); ?></span>
			<select class="merchant-dp-line-select">
				<?php foreach ( $options as $option ) : ?>
					<option
						class="<?php echo esc_attr( empty( $option['blocked'] ) ? '' : 'merchant-dp-preview-option-blocked' ); ?>"
						<?php echo empty( $option['selected'] ) ? '' : 'selected'; ?>
					><?php echo esc_html( (string) $option['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * A question with one answer, which the module prints as text rather than a dropdown.
	 *
	 * @param string $label The control's label.
	 * @param string $value The only answer.
	 * @param string $fee   Its fee, hidden with the price switcher.
	 *
	 * @return void
	 */
	private function the_fixed_control( string $label, string $value, string $fee ): void {
		?>
		<span class="merchant-dp-line-fixed">
			<span class="merchant-dp-line-label"><?php echo esc_html( $label ); ?></span>
			<span class="merchant-dp-line-value">
				<?php echo esc_html( $value ); ?>
				<span class="merchant-dp-preview-fee"><?php echo esc_html( $fee ); ?></span>
			</span>
		</span>
		<?php
	}

	/**
	 * What the cart adds up to, once its rows disagree about when they arrive.
	 *
	 * @return void
	 */
	private function the_shipments(): void {
		$shipments = array(
			array(
				'when'  => __( 'Delivery · Sat 15 Aug · 13:00–15:00', 'merchant' ),
				'items' => __( 'Blue mug', 'merchant' ),
			),
			array(
				'when'  => __( 'Collection · High Street shop · Mon 17 Aug', 'merchant' ),
				'items' => __( 'Tea towel', 'merchant' ),
			),
		);
		?>
		<div class="merchant-dp-shipments">
			<p class="merchant-dp-shipments-title"><?php esc_html_e( 'Arriving in 2 parts', 'merchant' ); ?></p>
			<ul>
				<?php foreach ( $shipments as $shipment ) : ?>
					<li class="merchant-dp-shipment">
						<span class="merchant-dp-shipment-when"><?php echo esc_html( $shipment['when'] ); ?></span>
						<span class="merchant-dp-shipment-items"><?php echo esc_html( $shipment['items'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * The nudge towards a free delivery, printed in the same spot as the controls.
	 *
	 * @return void
	 */
	private function the_free_delivery_hint(): void {
		?>
		<div class="merchant-dp-hint">
			<?php echo esc_html( sprintf( /* translators: %s: price */ __( 'Add %s more for free delivery', 'merchant' ), $this->price( 12 ) ) ); ?>
		</div>
		<?php
	}

	/**
	 * The totals, with each part's charge named the way the module names it.
	 *
	 * @return void
	 */
	private function the_totals(): void {
		$lines = array(
			array( 'label' => __( 'Subtotal', 'merchant' ), 'value' => $this->price( 36 ), 'fee' => false ),
			array( 'label' => __( 'Delivery Sat 15 Aug, 13:00–15:00', 'merchant' ), 'value' => $this->price( 4.95 ), 'fee' => true ),
		);
		?>
		<div class="merchant-dp-preview-totals">
			<?php foreach ( $lines as $line ) : ?>
				<div class="merchant-dp-preview-total-line<?php echo esc_attr( $line['fee'] ? ' merchant-dp-preview-fee-line' : '' ); ?>">
					<span><?php echo esc_html( $line['label'] ); ?></span>
					<span><?php echo esc_html( $line['value'] ); ?></span>
				</div>
			<?php endforeach; ?>
			<div class="merchant-dp-preview-total-line merchant-dp-preview-total">
				<span><?php esc_html_e( 'Total', 'merchant' ); ?></span>
				<span><?php echo esc_html( $this->price( 40.95 ) ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Format a preview amount using the store currency.
	 *
	 * @param float $amount The amount to format.
	 *
	 * @return string
	 */
	private function price( float $amount ): string {
		if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return number_format_i18n( $amount, 2 );
		}

		return get_woocommerce_currency_symbol() . number_format_i18n( $amount, 2 );
	}
}
