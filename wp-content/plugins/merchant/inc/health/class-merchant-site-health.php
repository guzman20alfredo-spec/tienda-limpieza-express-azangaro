<?php
/**
 * Merchant_Site_Health Class.
 *
 * Shows failed checks and reported problems in Tools > Site Health.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Merchant_Site_Health' ) ) {
	/**
	 * Class Merchant_Site_Health
	 */
	class Merchant_Site_Health {

		/**
		 * Hook into Site Health.
		 *
		 * @return void
		 */
		public static function init() {
			add_filter( 'site_status_tests', array( __CLASS__, 'register_test' ) );
			add_filter( 'debug_information', array( __CLASS__, 'add_debug_info' ) );
		}

		/**
		 * Register the Merchant status test.
		 *
		 * @param array<string, array<string, mixed>> $tests Site Health tests.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function register_test( $tests ) {
			$tests['direct']['merchant_health'] = array(
				'label' => __( 'Merchant health', 'merchant' ),
				'test'  => array( __CLASS__, 'run_test' ),
			);

			return $tests;
		}

		/**
		 * Run every check and collect the open problems into one result.
		 *
		 * @return array<string, mixed> Site Health test result.
		 */
		public static function run_test() {
			$failures = array_merge( self::get_check_failures(), array_map( array( __CLASS__, 'describe_problem' ), Merchant_Health_Problems::get_all() ) );
			$passed   = empty( $failures );

			return array(
				'label'       => $passed
					? __( 'Merchant is working as expected', 'merchant' )
					: __( 'Merchant has problems that need your attention', 'merchant' ),
				'status'      => $passed ? 'good' : 'critical',
				'badge'       => array(
					'label' => __( 'Merchant', 'merchant' ),
					'color' => $passed ? 'blue' : 'red',
				),
				'description' => $passed
					? '<p>' . esc_html__( 'All Merchant checks passed and no problems were reported.', 'merchant' ) . '</p>'
					: self::render_list( $failures ),
				'actions'     => '',
				'test'        => 'merchant_health',
			);
		}

		/**
		 * Run the registered checks and return their failure messages.
		 *
		 * @return string[]
		 */
		public static function get_check_failures() {
			/**
			 * Filter the Merchant health checks.
			 *
			 * Each check is a callable that returns an array of failure messages, empty when it passes.
			 *
			 * @param array<string, mixed> $checks Checks keyed by check ID.
			 *
			 * @since 2.3.3
			 */
			$checks   = apply_filters( 'merchant_health_checks', array( 'db_tables' => array( 'Merchant_DB_Tables', 'health_check' ) ) );
			$failures = array();

			foreach ( $checks as $check ) {
				if ( is_callable( $check ) ) {
					$failures = array_merge( $failures, (array) call_user_func( $check ) );
				}
			}

			return $failures;
		}

		/**
		 * Add a Merchant section to the Site Health Info tab.
		 *
		 * @param array<string, mixed> $info Site Health debug information.
		 *
		 * @return array<string, mixed>
		 */
		public static function add_debug_info( $info ) {
			$fields = array(
				'db_version' => array(
					'label' => __( 'Database version', 'merchant' ),
					'value' => get_option( 'merchant_db_version', __( 'Not set', 'merchant' ) ),
				),
			);

			$engines = Merchant_DB_Tables::get_table_engines();
			foreach ( Merchant_DB_Tables::get_table_names() as $table_name ) {
				$fields[ 'table_' . $table_name ] = array(
					'label' => $table_name,
					'value' => isset( $engines[ $table_name ] ) ? $engines[ $table_name ] : __( 'Missing', 'merchant' ),
				);
			}

			foreach ( Merchant_Health_Problems::get_all() as $key => $problem ) {
				$fields[ 'problem_' . $key ] = array(
					/* translators: %s: problem key */
					'label' => sprintf( __( 'Problem: %s', 'merchant' ), $key ),
					/* translators: 1: problem message, 2: times seen, 3: last seen date */
					'value' => sprintf( __( '%1$s (seen %2$d times, last %3$s)', 'merchant' ), $problem['message'], $problem['count'], wp_date( 'Y-m-d H:i', $problem['last_seen'] ) ),
				);
			}

			$info['merchant'] = array(
				'label'  => __( 'Merchant', 'merchant' ),
				'fields' => $fields,
			);

			return $info;
		}

		/**
		 * Describe a problem with how often and how long ago it was seen.
		 *
		 * @param array{message: string, count: int, first_seen: int, last_seen: int} $problem Stored problem.
		 *
		 * @return string
		 */
		private static function describe_problem( $problem ) {
			$ago = human_time_diff( $problem['last_seen'] );

			return 1 === $problem['count']
				/* translators: 1: problem message, 2: time since, e.g. "2 hours" */
				? sprintf( __( '%1$s (%2$s ago)', 'merchant' ), $problem['message'], $ago )
				/* translators: 1: problem message, 2: times seen, 3: time since last seen, e.g. "2 hours" */
				: sprintf( __( '%1$s (%2$d times, last %3$s ago)', 'merchant' ), $problem['message'], $problem['count'], $ago );
		}

		/**
		 * Render failure messages as an HTML list.
		 *
		 * @param string[] $messages Failure messages.
		 *
		 * @return string
		 */
		private static function render_list( $messages ) {
			$items = array_map(
				function ( $message ) {
					return '<li>' . esc_html( $message ) . '</li>';
				},
				$messages
			);

			return '<ul>' . implode( '', $items ) . '</ul>';
		}
	}
}
