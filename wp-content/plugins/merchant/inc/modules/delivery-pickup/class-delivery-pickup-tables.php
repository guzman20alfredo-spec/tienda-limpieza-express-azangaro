<?php
/**
 * Delivery & Pickup — the capacity tables.
 *
 * @package Merchant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The capacity tables. They live here because pro cannot bump `MERCHANT_DB_VERSION`, only this plugin's updater can.
 */
class Merchant_Delivery_Pickup_Tables {

	/**
	 * The scope-lock table, without the prefix.
	 */
	const LOCKS_TABLE = 'merchant_delivery_pickup_locks';

	/**
	 * The bookings ledger, without the prefix.
	 */
	const BOOKINGS_TABLE = 'merchant_delivery_pickup_bookings';

	/**
	 * Register both tables with the free plugin's own installer.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'merchant_db_tables', array( $this, 'register_tables' ), 10, 1 );
	}

	/**
	 * Add both tables to the registry.
	 *
	 * @param array<string, array<string, mixed>> $tables Registered table definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function register_tables( $tables ) {
		$tables['delivery_pickup_locks_table']    = $this->locks_table();
		$tables['delivery_pickup_bookings_table'] = $this->bookings_table();

		return $tables;
	}

	/**
	 * The lock table: scope keys and nothing else.
	 *
	 * No `used` column: capacity comes from the ledger; rows are seeded, never deleted.
	 *
	 * @return array<string, mixed>
	 */
	private function locks_table() {
		global $wpdb;

		$table   = $wpdb->prefix . self::LOCKS_TABLE;
		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		return array(
			'name'           => $table,
			'query'          => "
				CREATE TABLE $table (
					lock_key VARCHAR(191) NOT NULL,
					PRIMARY KEY (lock_key)
				) $collate;
			",
			'version'        => 1,
			'schema_updater' => '',
		);
	}

	/**
	 * The bookings ledger: one row per shipment, and the single source of capacity truth.
	 *
	 * - Both `units_orders` and `units_items` are stored, so flipping the counting unit
	 *   re-derives instead of invalidating every booked window.
	 * - `slot_start`/`slot_end` are DATETIMEs counted by half-open overlap, not by
	 *   matching a key string, so editing slot geometry leaves old bookings counted.
	 * - `group_hash` is 191 because it holds the full shipment signature
	 *   (`fulfilment|schedule|location|date|slot_start|slot_end`, ~103 chars); at 64 MySQL
	 *   truncated it and two shipments of one order collided on `order_group`.
	 * - `booked_date`, `slot_start` and `slot_end` are civil store time; `created_at` and
	 *   `updated_at` are UTC instants written by PHP. No `TIMESTAMP` and no
	 *   `CURRENT_TIMESTAMP`, which resolve against the MySQL session timezone rather than
	 *   WordPress's and would skew every TTL window.
	 *
	 * @return array<string, mixed>
	 */
	private function bookings_table() {
		global $wpdb;

		$table   = $wpdb->prefix . self::BOOKINGS_TABLE;
		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		return array(
			'name'           => $table,
			'query'          => "
				CREATE TABLE $table (
					ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					order_id BIGINT UNSIGNED NOT NULL,
					fulfilment VARCHAR(10) NOT NULL,
					schedule_id VARCHAR(36) NOT NULL,
					location_id VARCHAR(36) NULL,
					booked_date DATE NOT NULL,
					slot_start DATETIME NULL,
					slot_end DATETIME NULL,
					group_hash VARCHAR(191) NOT NULL DEFAULT '',
					booked_items TEXT NULL,
					units_orders SMALLINT UNSIGNED NOT NULL DEFAULT 1,
					units_items SMALLINT UNSIGNED NOT NULL DEFAULT 1,
					status VARCHAR(20) NOT NULL DEFAULT 'active',
					created_at DATETIME NOT NULL,
					updated_at DATETIME NOT NULL,
					PRIMARY KEY (ID),
					UNIQUE KEY order_group (order_id, group_hash),
					KEY schedule_day_status (schedule_id, booked_date, status),
					KEY location_day_status (location_id, booked_date, status),
					KEY schedule_slot_status (schedule_id, status, slot_start),
					KEY day_status (booked_date, status),
					KEY status_created (status, created_at)
				) $collate;
			",
			'version'        => 2,
			'schema_updater' => array( $this, 'migrate_bookings' ),
		);
	}

	/**
	 * Bring an existing ledger up to the definition above, columns included.
	 *
	 * `maybe_create_table()` skips `dbDelta()` once the table exists, so this updater is the
	 * only thing left that can change the schema.
	 *
	 * @param string $table The prefixed table name.
	 * @param wpdb   $wpdb  The database handle.
	 *
	 * @return void
	 */
	public function migrate_bookings( $table, $wpdb ) {
		if ( ! $this->column_exists( $wpdb, $table, 'group_hash' ) ) {
			$this->alter( $wpdb, $table, "ADD COLUMN `group_hash` VARCHAR(191) NOT NULL DEFAULT '' AFTER `slot_end`" );
		} elseif ( 191 !== $this->column_length( $wpdb, $table, 'group_hash' ) ) {
			$this->alter( $wpdb, $table, "MODIFY COLUMN `group_hash` VARCHAR(191) NOT NULL DEFAULT ''" );
		}

		if ( ! $this->column_exists( $wpdb, $table, 'booked_items' ) ) {
			$this->alter( $wpdb, $table, 'ADD COLUMN `booked_items` TEXT NULL AFTER `group_hash`' );
		}

		// Old key overlaps the new one; dbDelta() never drops indexes.
		if ( $this->index_exists( $wpdb, $table, 'order_id' ) ) {
			$this->alter( $wpdb, $table, 'DROP INDEX `order_id`' );
		}

		if ( ! $this->index_exists( $wpdb, $table, 'order_group' ) ) {
			$this->alter( $wpdb, $table, 'ADD UNIQUE KEY `order_group` (`order_id`, `group_hash`)' );
		}
	}

	/**
	 * Whether one column is already there.
	 *
	 * @param wpdb   $wpdb   The database handle.
	 * @param string $table  The prefixed table name.
	 * @param string $column The column.
	 *
	 * @return bool
	 */
	private function column_exists( $wpdb, $table, $column ) {
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(1) FROM information_schema.columns'
				. ' WHERE table_schema = DATABASE() AND table_name = %s AND column_name = %s',
				$table,
				$column
			)
		) > 0;
	}

	/**
	 * How many characters one column holds, or 0 when it is absent.
	 *
	 * @param wpdb   $wpdb   The database handle.
	 * @param string $table  The prefixed table name.
	 * @param string $column The column.
	 *
	 * @return int
	 */
	private function column_length( $wpdb, $table, $column ) {
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT character_maximum_length FROM information_schema.columns'
				. ' WHERE table_schema = DATABASE() AND table_name = %s AND column_name = %s',
				$table,
				$column
			)
		);
	}

	/**
	 * Whether one index is already there.
	 *
	 * @param wpdb   $wpdb  The database handle.
	 * @param string $table The prefixed table name.
	 * @param string $index The index name.
	 *
	 * @return bool
	 */
	private function index_exists( $wpdb, $table, $index ) {
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(1) FROM information_schema.statistics'
				. ' WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s',
				$table,
				$index
			)
		) > 0;
	}

	/**
	 * Run one ALTER against the ledger.
	 *
	 * @param wpdb   $wpdb   The database handle.
	 * @param string $table  The prefixed table name.
	 * @param string $change The clause after `ALTER TABLE <table>`.
	 *
	 * @return void
	 */
	private function alter( $wpdb, $table, $change ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The table name comes from the registry and the clause is a literal from this class; neither is input.
		$wpdb->query( 'ALTER TABLE `' . esc_sql( $table ) . '` ' . $change );
	}
}
