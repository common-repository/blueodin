<?php

namespace BlueOdin\WordPress;
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://blueodin.io
 * @since      1.0.0
 *
 * @package    BlueOdin
 * @subpackage BlueOdin/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    BlueOdin
 * @subpackage BlueOdin/public
 * @author     Blue Odin <support@blueodin.io>
 */
final class VersionRestRoute {

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( string $version )
	{

		$this->version = $version;

	}

	public static function load( BlueOdinLoader $loader, string $version ): self
	{
		$version_rest_route = new VersionRestRoute( $version );
		$loader->add_action( 'rest_api_init', $version_rest_route, 'action_rest_api_init' );

		return $version_rest_route;
	}

	public function action_rest_api_init(): void
	{
		register_rest_route( 'blueodin/v1', '/version', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_get_version' ],
			'permission_callback' => '__return_true',
		] );

	}

	public function handle_get_version(): void
	{
		$data = [
			'version' => $this->version,
		];
		wp_send_json( $data, 200 );
	}
}