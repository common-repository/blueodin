<?php

namespace BlueOdin\WordPress;

use BlueOdin\WordPress\upgrades\CreateInitialDatabase;
use WP_Site;

/**
 * Fired during plugin activation
 *
 * @link       https://blueodin.io
 * @since      1.0.0
 *
 * @package    BlueOdin
 * @subpackage BlueOdin/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    BlueOdin
 * @subpackage BlueOdin/includes
 * @author     Blue Odin <support@blueodin.io>
 */
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

final class BlueOdinActivator {

	const DB_VERSION_OPTION = 'blueodin_db_version';
	const DB_UPGRADE_FAILED_OPTION = 'blueodin_upgrade_failed';

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate( bool $network_wide ): void
	{

		global $wpdb;

		$is_single_site = ! ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide );
		if ( $is_single_site ) {
			self::activate_site();

			return;
		}

		// Get all blog ids
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			self::activate_site();
			restore_current_blog();
		}

	}

	/**
	 * Handle activating for new sites
	 *
	 * @param WP_Site $new_site
	 * @param array $args
	 *
	 * @return void
	 */
	public function action_wp_initialize_site( WP_Site $new_site, array $args ): void
	{

		if ( ! is_plugin_active_for_network( 'blueodin-plugin/blueodin-plugin.php' ) ) {
			return;
		}
		switch_to_blog( $new_site->id );
		self::activate_site();
		restore_current_blog();
	}


	/**
	 * @return void
	 */
	private static function activate_site(): void
	{
		self::update_database();
	}

	private static function update_database(): bool
	{
		$success = true;

		$success &= self::do_upgrade( 1, new CreateInitialDatabase() );

		update_option( self::DB_UPGRADE_FAILED_OPTION, ! $success );

		return (bool) $success;
	}


	/**
	 *
	 * @param int $version
	 * @param callable|string $function
	 *
	 * @return bool
	 */
	private static function do_upgrade( int $version, $function ): bool
	{
		$current_version = (int) get_option( self::DB_VERSION_OPTION, 0 );

		if ( ( $version - 1 ) > $current_version ) {
			return false;
		}

		if ( $version <= $current_version ) {
			return true;
		}

		blueodin_write_log( "Upgrading DB from '$current_version' to '$version'" );

		$update_option = call_user_func( $function );

		if ( $update_option ) {
			update_option( self::DB_VERSION_OPTION, $version );
		}

		return $update_option;
	}
}