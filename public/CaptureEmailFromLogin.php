<?php

namespace BlueOdin\WordPress;

use WP_User;

final class CaptureEmailFromLogin {


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
		$capture_email_from_login = new CaptureEmailFromLogin( $session );
		$loader->add_action( 'wp_login', $capture_email_from_login, 'action_wp_login', 10, 2 );
		$loader->add_action( 'wp_loaded', $capture_email_from_login, 'action_wp_loaded' );

		return $capture_email_from_login;
	}

	/**
	 * @param string $user_login
	 * @param WP_User $user
	 *
	 * @return void
	 */
	public function action_wp_login( string $user_login, WP_User $user ): void
	{
		$this->session->set_email( $user->user_email, 'logged_in_user' );
	}

	public function action_wp_loaded(): void
	{
		$user = wp_get_current_user();

		if ( $user->exists() ) {
			$this->session->set_email( $user->user_email, 'logged_in_user' );
		}
	}

}