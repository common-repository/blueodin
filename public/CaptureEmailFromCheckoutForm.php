<?php

namespace BlueOdin\WordPress;

use BlueOdin\WordPress\Models\BlueOdinCart;
use WC_Checkout;

final class CaptureEmailFromCheckoutForm {
	const BLUEODIN_CHECKOUT_SCRIPT = 'blueodin_checkout';

	/**
	 * @var BlueOdinSession
	 */
	private $session;
	private $version;

	/**
	 * @param BlueOdinSession $session
	 * @param string $version
	 */
	public function __construct( BlueOdinSession $session, string $version )
	{
		$this->session = $session;
		$this->version = $version;
	}

	public static function load( BlueOdinLoader $loader, BlueOdinSession $session, string $version ): self
	{
		$detect_email_addresses = new CaptureEmailFromCheckoutForm( $session, $version );
		$loader->add_action( 'wp_ajax_blueodin_capture_email', $detect_email_addresses, 'action_wp_ajax_blueodin_capture_email' );
		$loader->add_action( 'woocommerce_checkout_init', $detect_email_addresses, 'action_woocommerce_checkout_init' );

		return $detect_email_addresses;
	}

	/**
	 * @return void
	 */
	public function action_wp_ajax_blueodin_capture_email(): void
	{
		check_ajax_referer( BlueOdin::NONCE_VALUE );

		$email = sanitize_email( $_POST['email'] );

		$this->session->set_email( $email, 'checkout' );

		$wc_cart = WC()->cart;
		if ( ! is_null( $wc_cart ) ) {
			$cart = BlueOdinCart::fromWC_Cart( $wc_cart, $this->session );
			$cart->push_to_blueodin();
		};

		wp_die();
	}

	public function action_woocommerce_checkout_init( WC_Checkout $checkout ): void
	{
		wp_enqueue_script( self::BLUEODIN_CHECKOUT_SCRIPT, plugins_url( '../assets/js/', __FILE__ ) . 'blueodin-checkout.js', array( 'jquery' ), $this->version, false );
		$data = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( BlueOdin::NONCE_VALUE ),
		];
		wp_localize_script( self::BLUEODIN_CHECKOUT_SCRIPT, 'blueodin_properties', $data );
	}

}