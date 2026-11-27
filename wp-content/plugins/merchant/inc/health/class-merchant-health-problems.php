<?php
/**
 * Merchant_Health_Problems Class.
 *
 * Stores runtime failures reported by modules so they can be shown in Site Health.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Merchant_Health_Problems' ) ) {
	/**
	 * Class Merchant_Health_Problems
	 *
	 * @phpstan-type Problem array{message: string, count: int, first_seen: int, last_seen: int}
	 */
	class Merchant_Health_Problems {

		/**
		 * Option that holds the open problems.
		 */
		const OPTION = 'merchant_health_problems';

		/**
		 * Most problems kept at once, so dynamic keys can't bloat the option.
		 */
		const MAX_PROBLEMS = 50;

		/**
		 * Problems not seen for this long are dropped.
		 */
		const EXPIRY = 30 * DAY_IN_SECONDS;

		/**
		 * Record a problem, or bump its count if it is already open.
		 *
		 * @param string $key     Unique problem key, e.g. `dp_tables_missing`.
		 * @param string $message Plain-language description shown to the admin.
		 * @param array<string, mixed> $context Extra data written to the log only.
		 *
		 * @return void
		 */
		public static function report( $key, $message, $context = array() ) {
			$key      = sanitize_key( $key );
			$problems = self::get_all();
			$now      = time();

			$problems[ $key ] = array(
				'message'    => (string) $message,
				'count'      => isset( $problems[ $key ] ) ? $problems[ $key ]['count'] + 1 : 1,
				'first_seen' => isset( $problems[ $key ] ) ? $problems[ $key ]['first_seen'] : $now,
				'last_seen'  => $now,
			);

			self::save( self::trim( $problems ) );
			self::log( $key, $message, $context );
		}

		/**
		 * Remove a problem once the failing action works again.
		 *
		 * @param string $key Problem key used in report().
		 *
		 * @return void
		 */
		public static function clear( $key ) {
			$key      = sanitize_key( $key );
			$problems = self::get_all();

			if ( ! isset( $problems[ $key ] ) ) {
				return;
			}

			unset( $problems[ $key ] );
			self::save( $problems );
		}

		/**
		 * Get the open problems, without expired ones.
		 *
		 * @return array<string, Problem>
		 */
		public static function get_all() {
			$problems = get_option( self::OPTION, array() );

			if ( ! is_array( $problems ) ) {
				return array();
			}

			$cutoff = time() - self::EXPIRY;

			return array_filter(
				$problems,
				function ( $problem ) use ( $cutoff ) {
					return isset( $problem['last_seen'] ) && $problem['last_seen'] >= $cutoff;
				}
			);
		}

		/**
		 * Keep only the most recently seen problems.
		 *
		 * @param array<string, Problem> $problems Problems keyed by problem key.
		 *
		 * @return array<string, Problem>
		 */
		private static function trim( $problems ) {
			if ( count( $problems ) <= self::MAX_PROBLEMS ) {
				return $problems;
			}

			uasort(
				$problems,
				function ( $a, $b ) {
					return $b['last_seen'] - $a['last_seen'];
				}
			);

			return array_slice( $problems, 0, self::MAX_PROBLEMS, true );
		}

		/**
		 * Save the problems without autoloading them.
		 *
		 * @param array<string, Problem> $problems Problems keyed by problem key.
		 *
		 * @return void
		 */
		private static function save( $problems ) {
			if ( empty( $problems ) ) {
				delete_option( self::OPTION );

				return;
			}

			update_option( self::OPTION, $problems, false );
		}

		/**
		 * Write the problem to the WooCommerce logs.
		 *
		 * @param string $key     Problem key.
		 * @param string $message Problem description.
		 * @param array<string, mixed> $context Extra data for the log entry.
		 *
		 * @return void
		 */
		private static function log( $key, $message, $context ) {
			if ( ! function_exists( 'wc_get_logger' ) ) {
				return;
			}

			wc_get_logger()->error(
				sprintf( '[%s] %s', $key, $message ),
				array_merge( $context, array( 'source' => 'merchant' ) )
			);
		}
	}
}
