<?php

namespace BlueOdin\WordPress;

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
final class BlueOdinUninstaller {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function uninstall(): void {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( ! is_super_admin() ) {
				return;
			}
			$blogs = get_sites();
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog->blog_id );
				self::uninstall_site();
				restore_current_blog();
			}
		} else {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			self::uninstall_site();
		}

	}

	private static function drop_all_tables(): void
	{
		global $wpdb;
		$tables = [
			$wpdb->prefix . "bo_sessions",
			$wpdb->prefix . "bo_carts",
			$wpdb->prefix . "bo_cart_items",
			$wpdb->prefix . "bo_utm_data",
		];

		foreach ($tables as $tablename) {
			$wpdb->query("DROP TABLE IF EXISTS $tablename");
		}
	}

	private static function remove_options(): void
	{
		delete_option(BlueOdinActivator::DB_UPGRADE_FAILED_OPTION);
		delete_option(BlueOdinActivator::DB_VERSION_OPTION);
	}

	/**
	 * @return void
	 */
	private static function uninstall_site(): void
	{
		//blueodin_write_log( "uninstalling for " .  get_current_blog_id());

		if ( ! get_option( 'blueodin_do_uninstall', false ) ) {
			return;
		}
		self::drop_all_tables();
		self::remove_options();
	}

}