<?php

namespace BlueOdin\WordPress;

use BlueOdin\WordPress\Admin\BlueOdinAdmin;
use BlueOdin\WordPress\Admin\CostOfGoods;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://blueodin.io
 * @since      1.0.0
 *
 * @package    BlueOdin
 * @subpackage BlueOdin/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BlueOdin
 * @subpackage BlueOdin/includes
 * @author     Blue Odin <support@blueodin.io>
 */
final class BlueOdin {
	const NONCE_VALUE = 'blueodin_nonce_value';


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      BlueOdinLoader|null $loader Maintains and registers all hooks for the plugin.
	 */
	private $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	private $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	private $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if ( defined( 'BLUE_ODIN_VERSION' ) ) {
			$this->version = BLUE_ODIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'blueodin';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - BlueOdinLoader. Orchestrates the hooks of the plugin.
	 * - BlueOdin_i18n. Defines internationalization functionality.
	 * - BlueOdinAdmin. Defines all hooks for the admin area.
	 * - BlueOdinPublic. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies(): void
	{
		$this->loader = new BlueOdinLoader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the BlueOdin_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale(): void
	{

		$plugin_i18n = new BlueOdin_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks(): void
	{

		$plugin_admin = new BlueOdinAdmin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		CostOfGoods::load($this->loader);

	}

	/**
	 * Register all the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks(): void
	{

		$session = BlueOdinSession::load( $this->loader );

		VersionRestRoute::load( $this->loader, $this->get_version() );
		CaptureEmailFromParameters::load( $this->loader, $session );
		CaptureEmailFromCheckoutForm::load( $this->loader, $session, $this->get_version() );
		CaptureEmailFromLogin::load( $this->loader, $session );
		BlueOdinUTMTracking::load( $this->loader, $session );
		BlueOdinAbandonedCart::load( $this->loader, $session );
		BlueOdinCartWebhook::load( $this->loader );
	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run(): void
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name(): string
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    BlueOdinLoader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader(): BlueOdinLoader
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version(): string
	{
		return $this->version;
	}

}