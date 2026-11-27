<?php
/**
 * Delivery & Pickup — Option group definitions.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Choices used by more than one field, built once.
$weekday_choices  = Merchant_Admin_Options::get_weekday_choices();
$zone_choices     = Merchant_Admin_Options::get_shipping_zone_select2_choices();
$category_choices = Merchant_Admin_Options::get_category_select2_choices();
$brand_choices    = Merchant_Admin_Options::get_brand_select2_choices();

// Time-only preset; stored value is always H:i.
$time_picker = array(
	'onlyTimepicker' => true,
	'timeFormat'     => 'HH:mm',
	'minDate'        => '',
);

// Subject: short single-value tags only; rest goes in the body.
$subject_merge_tags = array(
	'{customer_name}'   => esc_html__( 'the customer’s first name', 'merchant' ),
	'{order_number}'    => esc_html__( 'the order number', 'merchant' ),
	'{fulfilment_type}' => esc_html__( 'your Delivery or Pickup label, whichever the order chose', 'merchant' ),
	'{delivery_date}'   => esc_html__( 'the booking date, in the format you set for the checkout', 'merchant' ),
	'{delivery_slot}'   => esc_html__( 'the booking slot, as 09:00–11:00', 'merchant' ),
	'{location_name}'   => esc_html__( 'the pickup location’s name, empty on a delivery', 'merchant' ),
);

// Out of subject: long text and links read as spam.
$body_only_tags = array(
	'{location_address}' => esc_html__( 'the pickup location’s address, empty on a delivery', 'merchant' ),
	'{collection_notes}' => esc_html__( 'the collection notes you set on the pickup location', 'merchant' ),
	'{reschedule_link}'  => esc_html__( 'the web address the customer changes the booking at, empty while self-service rescheduling is off', 'merchant' ),
	'{ics_link}'         => esc_html__( 'the web address the calendar file downloads from, empty while the calendar download is off', 'merchant' ),
);

$body_merge_tags = array_merge(
	$subject_merge_tags,
	$body_only_tags,
	array(
		'{booking_details}' => esc_html__( 'a table of every part of the order, in the columns you ticked under Order Emails. Reach for it when orders can arrive in more than one delivery, since the tags above only describe whichever part arrives first', 'merchant' ),
		'{order_details}'   => esc_html__( 'the items, quantities and totals, the way WooCommerce prints them in its own order emails', 'merchant' ),
	)
);

// Subject may show where it moved; details stay in message.
$subject_moved_tags = array(
	'{previous_delivery}' => esc_html__( 'the date and slot the booking moved from, empty when the order had no booking until now', 'merchant' ),
	'{new_delivery}'      => esc_html__( 'the date and slot it moved to', 'merchant' ),
);

$moved_merge_tags = array_merge(
	$subject_moved_tags,
	array(
		'{moved_booking}' => esc_html__( 'what moved (the items in that part, or the whole order when it arrives in one piece)', 'merchant' ),
		'{moved_part}'    => esc_html__( 'the number of the part that moved, empty unless the order arrives in several', 'merchant' ),
	)
);

/**
 * One "Show more" panel listing the tags a single field reads.
 *
 * @param array<string, string> $tags Tag to description.
 *
 * @return string
 */
$merge_tag_panel = static function ( array $tags ): string {
	$lines = array();

	foreach ( $tags as $tag => $description ) {
		$lines[] = '<strong>' . $tag . ':</strong> ' . $description;
	}

	return implode( '<br>', $lines );
};

return array(

	array(
		'title'  => esc_html__( 'Delivery Schedules', 'merchant' ),
		'module' => Merchant_Delivery_Pickup::MODULE_ID,
		'fields' => array(
			array(
				'id'           => 'schedules',
				'type'         => 'flexible_content',
				'button_label' => esc_html__( 'Add New Schedule', 'merchant' ),
				'style'        => Merchant_Delivery_Pickup::MODULE_ID . '-style default',
				'sorting'      => true,
				'duplicate'    => true,
				'accordion'    => true,
				'layouts'      => array(
					'schedule' => array(
						'title'        => esc_html__( 'Schedule', 'merchant' ),
						'title-field'  => 'schedule_title',
						'status-field' => 'schedule_status',
						'fields'       => array(
							array(
								'id'      => 'schedule_status',
								'type'    => 'select',
								'title'   => esc_html__( 'Status', 'merchant' ),
								'options' => array(
									'active'   => esc_html__( 'Active', 'merchant' ),
									'inactive' => esc_html__( 'Inactive', 'merchant' ),
								),
								'default' => 'active',
								'ai_meta' => array(
									'usage_hint' => 'Controls whether this schedule is active or inactive. Set to active to enable, inactive to disable.',
								),
							),
							array(
								'id'      => 'schedule_title',
								'type'    => 'text',
								'title'   => esc_html__( 'Schedule name', 'merchant' ),
								'default' => esc_html__( 'Standard delivery', 'merchant' ),
							),
							array(
								'id'      => 'fulfilment_type',
								'type'    => 'radio',
								'title'   => esc_html__( 'Fulfilment type', 'merchant' ),
								'options' => array(
									'delivery' => esc_html__( 'Delivery', 'merchant' ),
									'pickup'   => esc_html__( 'Pickup', 'merchant' ),
								),
								'default' => 'delivery',
								'ai_meta' => array(
									'usage_hint' => 'Whether this schedule offers delivery to the customer address or collection from a pickup location.',
								),
							),

							array(
								'id'    => 'availability_divider',
								'type'  => 'divider',
								'title' => esc_html__( 'Availability', 'merchant' ),
							),
							array(
								'id'      => 'available_days',
								'type'    => 'checkbox_multiple',
								'title'   => esc_html__( 'Available days', 'merchant' ),
								'options' => $weekday_choices,
								'default' => array( 'mon', 'tue', 'wed', 'thu', 'fri' ),
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'      => 'lead_time',
								'type'    => 'number',
								'title'   => esc_html__( 'Lead time', 'merchant' ),
								'desc'    => esc_html__( 'Delay bookings by this much so you have time to prepare. 1 day hides today, 2 days hides today and tomorrow.', 'merchant' ),
								'default' => 1,
								'min'     => 0,
								'step'    => 1,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'      => 'lead_time_unit',
								'type'    => 'select',
								'title'   => esc_html__( 'Lead time unit', 'merchant' ),
								'options' => array(
									'hours' => esc_html__( 'Hours', 'merchant' ),
									'days'  => esc_html__( 'Days', 'merchant' ),
								),
								'default' => 'days',
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'      => 'max_days_ahead',
								'type'    => 'number',
								'title'   => esc_html__( 'Bookable window (days)', 'merchant' ),
								'desc'    => esc_html__( 'How far into the future customers can book.', 'merchant' ),
								'default' => 14,
								'min'     => 1,
								'step'    => 1,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'          => 'cutoff_time',
								'type'        => 'date_time',
								'title'       => esc_html__( 'Daily cut-off time', 'merchant' ),
								'desc'        => esc_html__( 'Orders placed after this time move to the next available day.', 'merchant' ),
								'placeholder' => '14:00',
								'default'     => '14:00',
								'options'     => $time_picker,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'      => 'allow_same_day',
								'type'    => 'switcher',
								'title'   => esc_html__( 'Allow same-day', 'merchant' ),
								'default' => 0,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'      => 'blackout_dates',
								'type'    => 'sortable_repeater',
								'title'   => esc_html__( 'Blackout dates', 'merchant' ),
								'desc'    => esc_html__( 'Dates you never deliver or open, one per row, in YYYY-MM-DD format.', 'merchant' ),
								'button_label' => esc_html__( 'Add Date', 'merchant' ),
								'default' => array(),
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'      => 'repeat_blackouts_yearly',
								'type'    => 'switcher',
								'title'   => esc_html__( 'Repeat blackout dates every year', 'merchant' ),
								'default' => 0,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),

							array(
								'id'    => 'slots_divider',
								'type'  => 'divider',
								'title' => esc_html__( 'Time slots & capacity', 'merchant' ),
							),
							array(
								'id'      => 'slot_mode',
								'type'    => 'select',
								'title'   => esc_html__( 'What customers pick', 'merchant' ),
								'options' => array(
									'time_slots' => esc_html__( 'Date and time slot', 'merchant' ),
									'date_only'  => esc_html__( 'Date only', 'merchant' ),
									'fulfilment' => esc_html__( 'Neither (no date to choose)', 'merchant' ),
								),
								'default' => 'time_slots',
								'desc'    => esc_html__( 'Pick the last option when you cannot commit to a day yet. Customers choose delivery or collection and you arrange the timing with them. Capacity limits, reminders and rescheduling all need a date, so they do not apply.', 'merchant' ),
							),
							array(
								'id'          => 'slot_window_start',
								'type'        => 'date_time',
								'title'       => esc_html__( 'Slots start at', 'merchant' ),
								'placeholder' => '09:00',
								'default'     => '09:00',
								'options'     => $time_picker,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => '===',
											'value'    => 'time_slots',
										),
									),
								),
							),
							array(
								'id'          => 'slot_window_end',
								'type'        => 'date_time',
								'title'       => esc_html__( 'Slots end at', 'merchant' ),
								'placeholder' => '18:00',
								'default'     => '18:00',
								'options'     => $time_picker,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => '===',
											'value'    => 'time_slots',
										),
									),
								),
							),
							array(
								'id'        => 'slot_duration',
								'type'      => 'select',
								'title'     => esc_html__( 'Slot length', 'merchant' ),
								'options'   => Merchant_Delivery_Pickup_Choices::slot_durations(),
								'default'   => '120',
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => '===',
											'value'    => 'time_slots',
										),
									),
								),
							),
							array(
								'id'      => 'capacity_per_day',
								'type'    => 'number',
								'title'   => esc_html__( 'Orders per day', 'merchant' ),
								'desc'    => esc_html__( 'Leave at 0 for unlimited.', 'merchant' ),
								'default' => 0,
								'min'     => 0,
								'step'    => 1,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'        => 'capacity_per_slot',
								'type'      => 'number',
								'title'     => esc_html__( 'Orders per slot', 'merchant' ),
								'desc'      => esc_html__( 'Leave at 0 for unlimited.', 'merchant' ),
								'default'   => 0,
								'min'       => 0,
								'step'      => 1,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => '===',
											'value'    => 'time_slots',
										),
									),
								),
							),
							array(
								'id'      => 'show_remaining',
								'type'    => 'switcher',
								'title'   => esc_html__( 'Show how many places are left', 'merchant' ),
								'desc'    => esc_html__( 'Adds a "2 left" badge to slots that are nearly full.', 'merchant' ),
								'default' => 1,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'slot_mode',
											'operator' => 'in',
											'value'    => array( 'time_slots', 'date_only' ),
										),
									),
								),
							),
							array(
								'id'        => 'remaining_threshold',
								'type'      => 'number',
								'title'     => esc_html__( 'Show the badge when places left are this many or fewer', 'merchant' ),
								'default'   => 3,
								'min'       => 1,
								'step'      => 1,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'show_remaining',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),

							array(
								'id'    => 'pricing_divider',
								'type'  => 'divider',
								'title' => esc_html__( 'Pricing', 'merchant' ),
							),
							array(
								'id'      => 'charge_fee',
								'type'    => 'switcher',
								'title'   => esc_html__( 'Charge for this schedule', 'merchant' ),
								'default' => 0,
							),
							array(
								'id'        => 'fee_type',
								'type'      => 'select',
								'title'     => esc_html__( 'Charge type', 'merchant' ),
								'options'   => array(
									'fixed'      => esc_html__( 'Fixed amount', 'merchant' ),
									'percentage' => esc_html__( 'Percentage of the cart subtotal, after discounts', 'merchant' ),
									'per_item'   => esc_html__( 'Amount per item', 'merchant' ),
								),
								'default'   => 'fixed',
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'charge_fee',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),
							array(
								'id'        => 'fee_amount',
								'type'      => 'number',
								'title'     => esc_html__( 'Amount', 'merchant' ),
								'default'   => 0,
								'min'       => 0,
								'step'      => 0.01,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'charge_fee',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),
							array(
								'id'         => 'same_day_fee',
								'type'       => 'number',
								'title'      => esc_html__( 'Same-day surcharge', 'merchant' ),
								'desc'       => esc_html__( 'Added on top when the customer picks today.', 'merchant' ),
								'default'    => 0,
								'min'        => 0,
								'step'       => 0.01,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'charge_fee',
											'operator' => '===',
											'value'    => true,
										),
										array(
											'field'    => 'allow_same_day',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),
							array(
								'id'        => 'weekend_fee',
								'type'      => 'number',
								'title'     => esc_html__( 'Weekend surcharge', 'merchant' ),
								'default'   => 0,
								'min'       => 0,
								'step'      => 0.01,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'charge_fee',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),
							array(
								'id'        => 'free_over_cart_total',
								'type'      => 'number',
								'title'     => esc_html__( 'Free when the cart reaches', 'merchant' ),
								'desc'      => esc_html__( 'Checked against the subtotal of the items going the same way, after discounts and excluding tax, so items being collected do not buy free delivery. Leave at 0 to always charge.', 'merchant' ),
								'default'   => 0,
								'min'       => 0,
								'step'      => 0.01,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'charge_fee',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),
							array(
								'id'        => 'fee_taxable',
								'type'      => 'switcher',
								'title'     => esc_html__( 'Charge tax on the fee', 'merchant' ),
								'default'   => 1,
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'charge_fee',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),
							array(
								'id'         => 'fee_tax_class',
								'type'       => 'select',
								'title'      => esc_html__( 'Tax class', 'merchant' ),
								'options'    => Merchant_Delivery_Pickup_Choices::tax_classes(),
								'default'    => 'inherit',
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'charge_fee',
											'operator' => '===',
											'value'    => true,
										),
										array(
											'field'    => 'fee_taxable',
											'operator' => '===',
											'value'    => true,
										),
									),
								),
							),

							array(
								'id'    => 'eligibility_divider',
								'type'  => 'divider',
								'title' => esc_html__( 'Who gets this schedule', 'merchant' ),
							),
							array(
								'id'      => 'eligibility_info',
								'type'    => 'info',
								'content' => esc_html__( 'Leave everything untouched to offer it to every cart.', 'merchant' ),
							),
							array(
								'id'      => 'rules_to_display',
								'type'    => 'select',
								'title'   => esc_html__( 'Applies to', 'merchant' ),
								'options' => array(
									'all'        => esc_html__( 'All Products', 'merchant' ),
									'products'   => esc_html__( 'Specific Products', 'merchant' ),
									'categories' => esc_html__( 'Specific Categories', 'merchant' ),
									'tags'       => esc_html__( 'Specific Tags', 'merchant' ),
									'brands'     => esc_html__( 'Specific Brands', 'merchant' ),
								),
								'default' => 'all',
								'ai_meta' => array(
									'semantic_group' => 'product_targeting',
									'usage_hint'     => 'Controls which products this schedule applies to. Set this BEFORE setting product_ids, category_slugs, tag_slugs or brand_slugs — those fields are conditional on this value.',
								),
							),
							array(
								'id'            => 'product_ids',
								'type'          => 'products_selector',
								'title'         => esc_html__( 'Products', 'merchant' ),
								'multiple'      => true,
								'desc'          => esc_html__( 'Only carts containing these products can use the schedule.', 'merchant' ),
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'rules_to_display',
											'operator' => '===',
											'value'    => 'products',
										),
									),
								),
								'allowed_types' => array( 'simple', 'variable' ),
								'ai_meta'       => array(
									'entity'         => 'product',
									'value_format'   => 'product_id',
									'semantic_group' => 'product_targeting',
									'usage_hint'     => 'Use when targeting specific products. Requires rules_to_display=products.',
								),
							),
							array(
								'id'          => 'category_slugs',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Categories', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => $category_choices,
								'placeholder' => esc_html__( 'Select categories', 'merchant' ),
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'rules_to_display',
											'operator' => '===',
											'value'    => 'categories',
										),
									),
								),
								'ai_meta'     => array(
									'entity'         => 'category',
									'taxonomy'       => 'product_cat',
									'value_format'   => 'slug',
									'semantic_group' => 'product_targeting',
									'usage_hint'     => 'Use when targeting products by category. Requires rules_to_display=categories.',
								),
							),
							array(
								'id'          => 'tag_slugs',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Tags', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => Merchant_Admin_Options::get_tag_select2_choices(),
								'placeholder' => esc_html__( 'Select tags', 'merchant' ),
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'rules_to_display',
											'operator' => '===',
											'value'    => 'tags',
										),
									),
								),
							),
							array(
								'id'          => 'brand_slugs',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Brands', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => $brand_choices,
								'placeholder' => esc_html__( 'Select brands', 'merchant' ),
								'conditions' => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'rules_to_display',
											'operator' => '===',
											'value'    => 'brands',
										),
									),
								),
							),
							array(
								'id'      => 'enable_exclusions',
								'type'    => 'switcher',
								'title'   => esc_html__( 'Exclude some products', 'merchant' ),
								'desc'    => esc_html__( 'Carts containing an excluded product cannot use this schedule.', 'merchant' ),
								'default' => 0,
							),
							// Excluded axis hides when "Applies to" already targets it.
							array(
								'id'            => 'excluded_product_ids',
								'type'          => 'products_selector',
								'title'         => esc_html__( 'Excluded products', 'merchant' ),
								'multiple'      => true,
								'conditions'    => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'enable_exclusions',
											'operator' => '===',
											'value'    => true,
										),
										array(
											'field'    => 'rules_to_display',
											'operator' => '!==',
											'value'    => 'products',
										),
									),
								),
								'allowed_types' => array( 'simple', 'variable' ),
							),
							array(
								'id'          => 'excluded_category_slugs',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Excluded categories', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => $category_choices,
								'placeholder' => esc_html__( 'Select categories', 'merchant' ),
								'conditions'  => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'enable_exclusions',
											'operator' => '===',
											'value'    => true,
										),
										array(
											'field'    => 'rules_to_display',
											'operator' => '!==',
											'value'    => 'categories',
										),
									),
								),
							),
							array(
								'id'          => 'excluded_brand_slugs',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Excluded brands', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => $brand_choices,
								'placeholder' => esc_html__( 'Select brands', 'merchant' ),
								'conditions'  => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'enable_exclusions',
											'operator' => '===',
											'value'    => true,
										),
										array(
											'field'    => 'rules_to_display',
											'operator' => '!==',
											'value'    => 'brands',
										),
									),
								),
							),
							array(
								'id'          => 'shipping_zone_ids',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Shipping zones', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => $zone_choices,
								'placeholder' => esc_html__( 'Any zone', 'merchant' ),
								'desc'        => esc_html__( 'Restrict the schedule to customers in these zones.', 'merchant' ),
							),
							array(
								'id'          => 'shipping_method_ids',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Shipping methods', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => Merchant_Admin_Options::get_shipping_method_select2_choices(),
								'placeholder' => esc_html__( 'Any shipping method', 'merchant' ),
								// A collected order has no shipping rate to restrict.
								'conditions'  => array(
									'relation' => 'AND',
									'terms'    => array(
										array(
											'field'    => 'fulfilment_type',
											'operator' => '===',
											'value'    => 'delivery',
										),
									),
								),
							),
							array(
								'id'          => 'payment_gateway_ids',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Payment methods', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => Merchant_Admin_Options::get_payment_gateway_select2_choices(),
								'placeholder' => esc_html__( 'Any payment method', 'merchant' ),
								'desc'        => esc_html__( 'Useful when a courier only accepts prepaid orders.', 'merchant' ),
							),
							array(
								'id'          => 'user_role_slugs',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'User roles', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => Merchant_Admin_Options::get_user_roles_select2_choices(),
								'placeholder' => esc_html__( 'Any customer', 'merchant' ),
								'desc'        => esc_html__( 'Keep wholesale route days for wholesale accounts only.', 'merchant' ),
							),
							array(
								'id'      => 'min_cart_total',
								'type'    => 'number',
								'title'   => esc_html__( 'Minimum cart total', 'merchant' ),
								'desc'    => esc_html__( 'Checked against the cart subtotal after discounts. Leave at 0 to accept any order value.', 'merchant' ),
								'default' => 0,
								'min'     => 0,
								'step'    => 0.01,
							),
						),
					),
				),
			),
		),
	),

	array(
		'title'  => esc_html__( 'Pickup Locations', 'merchant' ),
		'module' => Merchant_Delivery_Pickup::MODULE_ID,
		'fields' => array(
			array(
				'id'      => 'suppress_core_pickup',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Hide WooCommerce Local Pickup', 'merchant' ),
				'desc'    => esc_html__( 'These locations replace it, so customers are not offered pickup twice at checkout.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'           => 'pickup_locations',
				'type'         => 'flexible_content',
				'button_label' => esc_html__( 'Add New Location', 'merchant' ),
				'style'        => Merchant_Delivery_Pickup::MODULE_ID . '-style default',
				'sorting'      => true,
				'duplicate'    => true,
				'accordion'    => true,
				'layouts'      => array(
					'location' => array(
						'title'        => esc_html__( 'Location', 'merchant' ),
						'title-field'  => 'location_name',
						'status-field' => 'location_status',
						'fields'       => array(
							array(
								'id'      => 'location_status',
								'type'    => 'select',
								'title'   => esc_html__( 'Status', 'merchant' ),
								'options' => array(
									'active'   => esc_html__( 'Active', 'merchant' ),
									'inactive' => esc_html__( 'Inactive', 'merchant' ),
								),
								'default' => 'active',
							),
							array(
								'id'      => 'location_name',
								'type'    => 'text',
								'title'   => esc_html__( 'Location name', 'merchant' ),
								'default' => esc_html__( 'Main store', 'merchant' ),
							),
							array(
								'id'          => 'address',
								'type'        => 'text',
								'title'       => esc_html__( 'Address', 'merchant' ),
								'placeholder' => esc_html__( '12 High Street', 'merchant' ),
							),
							array(
								'id'          => 'city',
								'type'        => 'text',
								'title'       => esc_html__( 'City', 'merchant' ),
							),
							array(
								'id'          => 'postcode',
								'type'        => 'text',
								'title'       => esc_html__( 'Postcode', 'merchant' ),
							),
							array(
								'id'    => 'collection_notes',
								'type'  => 'textarea',
								'title' => esc_html__( 'Collection notes', 'merchant' ),
								'desc'  => esc_html__( 'Shown next to the location at checkout and repeated in the order email.', 'merchant' ),
							),
							array(
								'id'    => 'location_hours_divider',
								'type'  => 'divider',
								'title' => esc_html__( 'Opening hours & capacity', 'merchant' ),
							),
							array(
								'id'      => 'open_days',
								'type'    => 'checkbox_multiple',
								'title'   => esc_html__( 'Open days', 'merchant' ),
								'options' => $weekday_choices,
								'default' => array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ),
							),
							array(
								'id'          => 'open_from',
								'type'        => 'date_time',
								'title'       => esc_html__( 'Open from', 'merchant' ),
								'placeholder' => '09:00',
								'default'     => '09:00',
								'options'     => $time_picker,
							),
							array(
								'id'          => 'open_until',
								'type'        => 'date_time',
								'title'       => esc_html__( 'Open until', 'merchant' ),
								'placeholder' => '17:30',
								'default'     => '17:30',
								'options'     => $time_picker,
							),
							array(
								'id'      => 'preparation_time',
								'type'    => 'number',
								'title'   => esc_html__( 'Preparation time (hours)', 'merchant' ),
								'desc'    => esc_html__( 'The earliest collection time is pushed out by this much.', 'merchant' ),
								'default' => 2,
								'min'     => 0,
								'step'    => 1,
							),
							array(
								'id'      => 'collections_per_day',
								'type'    => 'number',
								'title'   => esc_html__( 'Collections per day', 'merchant' ),
								'desc'    => esc_html__( 'Leave at 0 for unlimited.', 'merchant' ),
								'default' => 0,
								'min'     => 0,
								'step'    => 1,
							),
							array(
								'id'      => 'location_blackout_dates',
								'type'    => 'sortable_repeater',
								'title'   => esc_html__( 'Closed dates', 'merchant' ),
								'desc'    => esc_html__( 'One date per row, in YYYY-MM-DD format.', 'merchant' ),
								'button_label' => esc_html__( 'Add Date', 'merchant' ),
								'default' => array(),
							),
							array(
								'id'    => 'location_pricing_divider',
								'type'  => 'divider',
								'title' => esc_html__( 'Pricing & eligibility', 'merchant' ),
							),
							array(
								'id'      => 'location_fee',
								'type'    => 'number',
								'title'   => esc_html__( 'Collection fee', 'merchant' ),
								'desc'    => esc_html__( 'Use a negative amount to discount collection instead.', 'merchant' ),
								'default' => 0,
								'step'    => 0.01,
							),
							array(
								'id'          => 'location_zone_ids',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Offer to these shipping zones', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => $zone_choices,
								'placeholder' => esc_html__( 'Any zone', 'merchant' ),
								'desc'        => esc_html__( 'WooCommerce shows every pickup location to everyone. This keeps distant customers from picking a shop they cannot reach.', 'merchant' ),
							),
							array(
								'id'          => 'location_category_slugs',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Only stocks these categories', 'merchant' ),
								'source'      => 'options',
								'multiple'    => true,
								'options'     => $category_choices,
								'placeholder' => esc_html__( 'Everything', 'merchant' ),
							),
							array(
								'id'          => 'staff_user_ids',
								'type'        => 'select_ajax',
								'title'       => esc_html__( 'Notify these users', 'merchant' ),
								'source'      => 'user',
								'multiple'    => true,
								'placeholder' => esc_html__( 'Search users', 'merchant' ),
								'desc'        => esc_html__( 'Everyone listed here gets a copy of every collection order for this location. Users are notified at the address on their account.', 'merchant' ),
							),
							array(
								'id'           => 'staff_emails',
								'type'         => 'sortable_repeater',
								'title'        => esc_html__( 'Notify these addresses', 'merchant' ),
								'desc'         => esc_html__( 'For anyone without an account on the store. One address per row, for example shop@example.com.', 'merchant' ),
								'button_label' => esc_html__( 'Add Email', 'merchant' ),
								'default'      => array(),
							),
						),
					),
				),
			),
		),
	),

	array(
		'title'  => esc_html__( 'Checkout Display', 'merchant' ),
		'module' => Merchant_Delivery_Pickup::MODULE_ID,
		'fields' => array(
			array(
				'id'      => 'date_format',
				'type'    => 'select',
				'title'   => esc_html__( 'Date format', 'merchant' ),
				'options' => array(
					'site_default'           => esc_html__( 'Site default', 'merchant' ),
					'weekday_day_month'      => esc_html__( 'Thu 20 Aug', 'merchant' ),
					'day_month'              => esc_html__( '20 Aug', 'merchant' ),
					'weekday_day_month_year' => esc_html__( 'Thu 20 Aug 2026', 'merchant' ),
					'day_month_year'         => esc_html__( '20 Aug 2026', 'merchant' ),
					'weekday_full'           => esc_html__( 'Thursday 20 August', 'merchant' ),
					'day_month_name_year'    => esc_html__( '20 August 2026', 'merchant' ),
					'day_first_numeric'      => esc_html__( '20/08/2026', 'merchant' ),
					'month_first_numeric'    => esc_html__( '08/20/2026', 'merchant' ),
					'iso'                    => esc_html__( '2026-08-20', 'merchant' ),
				),
				'default' => 'weekday_day_month',
			),
			array(
				'id'      => 'time_format',
				'type'    => 'select',
				'title'   => esc_html__( 'Time format', 'merchant' ),
				'options' => array(
					'site_default'        => esc_html__( 'Site default', 'merchant' ),
					'twenty_four'         => esc_html__( '09:00', 'merchant' ),
					'twenty_four_compact' => esc_html__( '9:00', 'merchant' ),
					'twenty_four_dot'     => esc_html__( '09.00', 'merchant' ),
					'twelve'              => esc_html__( '2:00 pm', 'merchant' ),
					'twelve_caps'         => esc_html__( '2:00 PM', 'merchant' ),
					'twelve_hour'         => esc_html__( '2 pm', 'merchant' ),
					'twelve_hour_caps'    => esc_html__( '2 PM', 'merchant' ),
				),
				'default' => 'site_default',
			),
			array(
				'id'      => 'show_slot_price',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Show the price on each slot', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'      => 'show_free_delivery_hint',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Show how much more buys free delivery', 'merchant' ),
				'desc'    => esc_html__( 'Counts up to the nearest "Free when the cart reaches" amount among the schedules on offer. Steps aside while the Free Shipping Progress Bar module is active, so customers see one message.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'      => 'is_required',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Require a choice before checkout', 'merchant' ),
				'desc'    => esc_html__( 'Only enforced when the section actually renders, so a layout without it never blocks checkout.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'        => 'required_message',
				'type'      => 'text',
				'title'     => esc_html__( 'Message when nothing is chosen', 'merchant' ),
				'desc'      => esc_html__( 'Use {items} for the products still waiting on a choice, and {count} for how many there are.', 'merchant' ),
				'default'   => esc_html__( 'Please choose when you would like your order.', 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'is_required',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),
		),
	),

	array(
		'title'  => esc_html__( 'Order Emails', 'merchant' ),
		'module' => Merchant_Delivery_Pickup::MODULE_ID,
		'fields' => array(
			array(
				'id'      => 'email_to_customer',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Add the block to customer emails', 'merchant' ),
				'desc'    => esc_html__( 'Order confirmation, processing, completed and the customer invoice.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'      => 'email_to_admin',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Add the block to admin emails', 'merchant' ),
				'desc'    => esc_html__( 'The new order email your team receives.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'      => 'email_block_heading',
				'type'    => 'text',
				'title'   => esc_html__( 'Block heading', 'merchant' ),
				'default' => esc_html__( 'Delivery details', 'merchant' ),
			),
			array(
				'id'      => 'email_details',
				'type'    => 'checkbox_multiple',
				'title'   => esc_html__( 'Show in the block', 'merchant' ),
				'options' => array(
					'fulfilment' => esc_html__( 'Delivery or pickup', 'merchant' ),
					'date'       => esc_html__( 'Chosen date', 'merchant' ),
					'slot'       => esc_html__( 'Chosen time slot', 'merchant' ),
					'location'   => esc_html__( 'Pickup location and address', 'merchant' ),
					'notes'      => esc_html__( 'Collection notes', 'merchant' ),
					'charge'     => esc_html__( 'What the slot cost', 'merchant' ),
				),
				'default' => array( 'fulfilment', 'date', 'slot', 'location', 'notes' ),
			),
			array(
				'id'      => 'email_position',
				'type'    => 'select',
				'title'   => esc_html__( 'Where in the email', 'merchant' ),
				'options' => array(
					'before_order_table' => esc_html__( 'Before the order table', 'merchant' ),
					'after_order_table'  => esc_html__( 'After the order table', 'merchant' ),
					'after_customer'     => esc_html__( 'After the customer details', 'merchant' ),
				),
				'default' => 'after_order_table',
			),
		),
	),

	array(
		'title'  => esc_html__( 'Customer Notifications', 'merchant' ),
		'module' => Merchant_Delivery_Pickup::MODULE_ID,
		'fields' => array(
			array(
				'type'  => 'divider',
				'title' => esc_html__( 'Reminder before the slot', 'merchant' ),
			),
			array(
				'id'      => 'reminder_email',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Send a reminder', 'merchant' ),
				'desc'    => esc_html__( 'Fewer missed deliveries, fewer collections left on the shelf.', 'merchant' ),
				'default' => 0,
			),
			array(
				'id'        => 'reminder_hours_before',
				'type'      => 'number',
				'title'     => esc_html__( 'Hours before the slot', 'merchant' ),
				'default'   => 24,
				'min'       => 1,
				'step'      => 1,
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'reminder_email',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),
			array(
				'id'          => 'reminder_email_subject',
				'type'        => 'text',
				'title'       => esc_html__( 'Subject', 'merchant' ),
				'desc'        => esc_html__( 'You can use these codes in the subject. The two that print a table only work in the message.', 'merchant' ),
				'hidden_desc' => $merge_tag_panel( $subject_merge_tags ),
				'default'     => esc_html__( 'Your order arrives {delivery_date}', 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'reminder_email',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),
			array(
				'id'          => 'reminder_email_body',
				'type'        => 'textarea_multiline',
				'title'       => esc_html__( 'Message', 'merchant' ),
				'desc'        => esc_html__( 'You can use these codes in the content.', 'merchant' ),
				'hidden_desc' => $merge_tag_panel( $body_merge_tags ),
				'default'     => __( 'Hello {customer_name}, a quick reminder that order {order_number} is on its way {delivery_date} between {delivery_slot}.', 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'reminder_email',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),

			array(
				'type'  => 'divider',
				'title' => esc_html__( 'Ready to collect', 'merchant' ),
			),
			array(
				'id'      => 'ready_email',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Tell customers when their order is ready', 'merchant' ),
				'desc'    => esc_html__( 'Sent when you mark a collection order ready.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'          => 'ready_email_subject',
				'type'        => 'text',
				'title'       => esc_html__( 'Subject', 'merchant' ),
				'desc'        => esc_html__( 'You can use these codes in the subject. The two that print a table only work in the message.', 'merchant' ),
				'hidden_desc' => $merge_tag_panel( $subject_merge_tags ),
				'default'     => esc_html__( 'Your order is ready at {location_name}', 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'ready_email',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),
			array(
				'id'          => 'ready_email_body',
				'type'        => 'textarea_multiline',
				'title'       => esc_html__( 'Message', 'merchant' ),
				'desc'        => esc_html__( 'You can use these codes in the content.', 'merchant' ),
				'hidden_desc' => $merge_tag_panel( $body_merge_tags ),
				'default'     => __( 'Hello {customer_name}, order {order_number} is packed and waiting for you at {location_name}, {location_address}. {collection_notes}', 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'ready_email',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),

			array(
				'type'  => 'divider',
				'title' => esc_html__( 'Changed slot', 'merchant' ),
			),
			array(
				'id'      => 'reschedule_email',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Confirm a changed date or slot', 'merchant' ),
				'desc'    => esc_html__( 'Sent whether you moved the order or the customer did.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'          => 'reschedule_email_subject',
				'type'        => 'text',
				'title'       => esc_html__( 'Subject', 'merchant' ),
				'desc'        => esc_html__( 'You can use these codes in the subject. The two that print a table only work in the message.', 'merchant' ),
				'hidden_desc' => $merge_tag_panel( array_merge( $subject_merge_tags, $subject_moved_tags ) ),
				'default'     => esc_html__( 'A new time for order {order_number}', 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'reschedule_email',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),
			array(
				'id'          => 'reschedule_email_body',
				'type'        => 'textarea_multiline',
				'title'       => esc_html__( 'Message', 'merchant' ),
				'desc'        => esc_html__( 'You can use these codes in the content.', 'merchant' ),
				'hidden_desc' => $merge_tag_panel( array_merge( $body_merge_tags, $moved_merge_tags ) ),
				'default'     => __( "Hello {customer_name}, {moved_booking} has been rescheduled to {new_delivery}.\n\n{booking_details}\n\n{order_details}", 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'reschedule_email',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),

			array(
				'type'  => 'divider',
				'title' => esc_html__( 'Opting out', 'merchant' ),
			),
			array(
				'id'      => 'unsubscribe_link',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Let customers unsubscribe', 'merchant' ),
				'desc'    => esc_html__( 'Adds an unsubscribe link to the reminder, the ready-to-collect and the changed-slot emails, and a matching checkbox on the account details page. It stops all three.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'        => 'unsubscribe_message',
				'type'      => 'textarea',
				'title'     => esc_html__( 'Confirmation message', 'merchant' ),
				'desc'      => esc_html__( 'Shown after the customer follows the unsubscribe link.', 'merchant' ),
				'default'   => __( 'You will no longer receive delivery and collection emails from us.', 'merchant' ),
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'unsubscribe_link',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),

			array(
				'type'  => 'divider',
				'title' => esc_html__( 'Telling the shop', 'merchant' ),
			),
			array(
				'id'      => 'reschedule_staff_email',
				'type'    => 'switcher',
				'title'   => esc_html__( 'A slot was changed', 'merchant' ),
				'desc'    => esc_html__( 'Goes to the pickup location\'s staff, and to the store admin when the customer moved it.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'      => 'cancellation_staff_email',
				'type'    => 'switcher',
				'title'   => esc_html__( 'A booking was cancelled or refunded', 'merchant' ),
				'desc'    => esc_html__( 'The slot goes back to the pool quietly. This says so, to the pickup location\'s staff and the store admin.', 'merchant' ),
				'default' => 1,
			),

			array(
				'type'  => 'divider',
				'title' => esc_html__( 'Self-service', 'merchant' ),
			),
			array(
				'id'      => 'allow_reschedule',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Let customers reschedule from My Account', 'merchant' ),
				'default' => 0,
			),
			array(
				'id'        => 'reschedule_cutoff_hours',
				'type'      => 'number',
				'title'     => esc_html__( 'Stop rescheduling this many hours before the slot', 'merchant' ),
				'default'   => 24,
				'min'       => 0,
				'step'      => 1,
				'conditions' => array(
					'relation' => 'AND',
					'terms'    => array(
						array(
							'field'    => 'allow_reschedule',
							'operator' => '===',
							'value'    => true,
						),
					),
				),
			),
			array(
				'id'      => 'customer_ics',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Let customers add the slot to their calendar', 'merchant' ),
				'desc'    => esc_html__( 'Adds an "Add to calendar" link to the customer order emails and to the order page, and turns on the {ics_link} merge tag.', 'merchant' ),
				'default' => 0,
			),
		),
	),

	array(
		'title'  => esc_html__( 'Orders & Operations', 'merchant' ),
		'module' => Merchant_Delivery_Pickup::MODULE_ID,
		'fields' => array(
			array(
				'id'      => 'orders_list_column',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Add a delivery column to the orders list', 'merchant' ),
				'desc'    => esc_html__( 'Sortable and filterable, so you can work a single day at a time.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'      => 'delivery_calendar',
				'type'    => 'switcher',
				'title'   => esc_html__( 'Show the delivery calendar', 'merchant' ),
				'desc'    => esc_html__( 'A month, week and day view of everything booked, with CSV export and print.', 'merchant' ),
				'default' => 1,
			),
			array(
				'id'      => 'capacity_unit',
				'type'    => 'select',
				'title'   => esc_html__( 'Count capacity in', 'merchant' ),
				'desc'    => esc_html__( 'What fills up a day or a slot. Count orders when every delivery takes the same effort, one drop per address. Count items when the load is what limits you. A van, a bench or an oven fills up on cakes, not on receipts, so a 20-item order uses twenty places rather than one.', 'merchant' ),
				'options' => array(
					'orders' => esc_html__( 'Orders', 'merchant' ),
					'items'  => esc_html__( 'Items', 'merchant' ),
				),
				'default' => 'orders',
			),
			array(
				'type'  => 'divider',
				'title' => esc_html__( 'A note on your timezone', 'merchant' ),
			),
			array(
				'id'      => 'timezone_info',
				'type'    => 'info',
				'content' => esc_html__( 'Dates, cut-offs and capacity all follow the timezone in Settings > General. Pick a city rather than a UTC offset, otherwise daylight saving shifts every cut-off by an hour twice a year.', 'merchant' ),
			),
		),
	),
);
