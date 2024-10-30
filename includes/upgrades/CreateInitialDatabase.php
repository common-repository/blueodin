<?php

namespace BlueOdin\WordPress\upgrades;

use wpdb;

class CreateInitialDatabase {
	public function __invoke(): bool {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		self::create_bo_utm_data( $wpdb, $charset_collate );
		self::create_bo_carts( $wpdb, $charset_collate );
		self::create_bo_cart_items( $wpdb, $charset_collate );
		self::create_bo_sessions( $wpdb, $charset_collate );

		return true;
	}

	/**
	 * @param wpdb $wpdb
	 * @param string $charset_collate
	 *
	 * @return void
	 */
	private static function create_bo_utm_data( wpdb $wpdb, string $charset_collate ): void
	{
		$table_name = $wpdb->prefix . "bo_utm_data";

		$sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    time datetime DEFAULT now() NOT NULL,
                    session_id char(36) NOT NULL,
                    name tinytext NOT NULL,
                    value text NOT NULL,
                    PRIMARY KEY  (id),
                    UNIQUE KEY utm_data_name_session (name(50), session_id)
                ) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * @param wpdb $wpdb
	 * @param $charset_collate
	 *
	 * @return void
	 */
	private static function create_bo_carts( wpdb $wpdb, string $charset_collate ): void
	{
		$table_name            = $wpdb->prefix . "bo_carts";
		$unique_key_session_id = $wpdb->prefix . 'cart_session_id';

		$sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    time datetime DEFAULT now() NOT NULL,
                    session_id tinytext NOT NULL,
                    user_id mediumint(11),
                    ip_address tinytext NOT NULL,
                    order_id mediumint(11),
                    PRIMARY KEY  (id)
                ) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * @param wpdb $wpdb
	 * @param string $charset_collate
	 *
	 * @return void
	 */
	private static function create_bo_cart_items( wpdb $wpdb, string $charset_collate ): void
	{
		$table_name                  = $wpdb->prefix . "bo_cart_items";
		$unique_key_cart_id_item_key = $wpdb->prefix . 'cart_items_cart_id_item_key';

		$sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    cart_id mediumint(9) NOT NULL,
                    item_key varchar(32) NOT NULL,
                    product_id mediumint(9) NOT NULL,
                    quantity smallint NOT NULL,
                    PRIMARY KEY  (id),
                   UNIQUE KEY $unique_key_cart_id_item_key (cart_id, item_key)
                ) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * @param wpdb $wpdb
	 * @param string $charset_collate
	 *
	 * @return void
	 */
	private static function create_bo_sessions( wpdb $wpdb, string $charset_collate ): void
	{
		$table_name            = $wpdb->prefix . "bo_sessions";
		$unique_key_session_id = $wpdb->prefix . 'sessions_session_id';

		$sql = "CREATE TABLE $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    created_at datetime DEFAULT now() NOT NULL,
                    last_seen datetime DEFAULT now(),
                    session_id char(36) NOT NULL,
				    current_cart_id mediumint,
				    email varchar(255),
				    email_source varchar(255),
                    PRIMARY KEY  (id),
                   UNIQUE KEY $unique_key_session_id (session_id)
                ) $charset_collate;";

		dbDelta( $sql );
	}


}