<?php

namespace BlueOdin\WordPress;

use WP;

final class CaptureEmailFromParameters {

	/**
	 * @var BlueOdinSession
	 */
	private $session;

	/**
	 * @param BlueOdinSession $session
	 */
	public function __construct( BlueOdinSession $session )
	{
		$this->session = $session;
	}

	public static function load( BlueOdinLoader $loader, BlueOdinSession $session ): self
	{
		$detect_email_addresses = new CaptureEmailFromParameters( $session );
		$loader->add_action( 'wp', $detect_email_addresses, 'action_wp' );

		return $detect_email_addresses;
	}

	/**
	 * @param WP $wp
	 *
	 * @return void
	 */
	public function action_wp( WP $wp ): void
	{
		if ( is_404() ) {
			return;
		}

		$email = $this->session->get_email();
		if ( ! is_null( $email ) && ! is_null( $email->email ) ) {
			return;
		}

		//blueodin_write_log("DetectEmailAddresses", ['GET' => $_GET, 'POST' => $_POST]);
		$data = array_merge( $_GET, $_POST );
		foreach ( $data as $key => $value ) {
			if ( str_contains( $key, 'email' ) && is_email( $value ) ) {
				//blueodin_write_log('found email key', ['key' => $key, 'value' => $value]);
				$this->session->set_email( $value, 'form_submit' );
			}
		}
	}

}