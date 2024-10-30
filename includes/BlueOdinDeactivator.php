<?php

namespace BlueOdin\WordPress;

use function get_site;

/**
 * Fired during plugin deactivation
 *
 * @link       https://blueodin.io
 * @since      1.0.0
 *
 * @package    BlueOdin
 * @subpackage BlueOdin/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    BlueOdin
 * @subpackage BlueOdin/includes
 * @author     Blue Odin <support@blueodin.io>
 */
final class BlueOdinDeactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate(bool $network_wide): void {
		global $wpdb;

		$is_single_site = ! ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide );
		if ( $is_single_site ) {
			self::deactivate_site();

			return;
		}

		// Get all blog ids
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			self::deactivate_site();
			restore_current_blog();
		}
	}

	/**
	 * @return void
	 */
	private static function deactivate_site(): void
	{
		//blueodin_write_log( "deactivating for " . get_current_blog_id());
	}

}