<?php

namespace BlueOdin\WordPress;

final class BlueOdinSession {

	const SESSION_ID_COOKIE_NAME = 'wordpress_bo_session';
	/**
	 * @var mixed|string|null
	 */
	private $session_id;

	private $email;

	private $email_source;

	private $loaded = false;

	public static function load( BlueOdinLoader $loader ): self
	{
		$session = new BlueOdinSession();
		$loader->add_action( 'init', $session, 'action_init' );

		return $session;
	}

	function action_init(): void
	{
		if ( is_404() || wp_doing_cron() ) {
			return;
		}

		$this->get_session_id();
	}


	/**
	 * @return string
	 */
	public function get_session_id(): string
	{
		//blueodin_write_log("BlueOdinSession::get_session_id", [
		//	'session_id' => $this->session_id,
		//	'cookie' => $_COOKIE[ self::SESSION_ID_COOKIE_NAME ],
		//]);

		if ( ! is_null( $this->session_id ) ) {
			return $this->session_id;
		}


		if ( isset( $_COOKIE[ self::SESSION_ID_COOKIE_NAME ] ) ) {
			return $this->load_session_from_cookie();
		}

		return $this->create_new_session();
	}

	/**
	 * @return mixed|string|null
	 */
	private function create_new_session(): string
	{
		//blueodin_write_log("BlueOdinSession::create_new_session", ['session_id' => $this->session_id]);

		$domain = parse_url( home_url() )['host'];

		$this->session_id = wp_generate_uuid4();
		if ( ! headers_sent() ) {
			setcookie( self::SESSION_ID_COOKIE_NAME, $this->session_id, time() + 31556926, '/', $domain );
		}

		$this->insert();

		return $this->session_id;
	}

	/**
	 * @return mixed|string|null
	 */
	// TODO: validate is a uuid
	private function load_session_from_cookie(): string
	{
		//blueodin_write_log("BlueOdinSession::load_session_from_cookie", [
		//	'session_id' => $this->session_id,
		//	'cookie' =>  $_COOKIE[ self::SESSION_ID_COOKIE_NAME ]
		//]);

		$this->session_id = $_COOKIE[ self::SESSION_ID_COOKIE_NAME ];
		$this->touch();

		return $this->session_id;
	}

	private function insert(): void
	{
		//blueodin_write_log("BlueOdinSession::insert", ['session_id' => $this->session_id]);
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'bo_sessions',
			[
				'session_id' => $this->session_id,
			] );
	}

	public function set_current_cart_id( ?int $cart_id ): void
	{
		//blueodin_write_log( "BlueOdinSession::set_current_cart_id", [
		//	'session_id' => $this->session_id,
		//	'cart_id'    => $cart_id
		//] );
		global $wpdb;

		$rows = $wpdb->update(
			$wpdb->prefix . 'bo_sessions',
			[
				'current_cart_id' => $cart_id,
				'last_seen'       => wp_date( DATE_ATOM ),
			], [
			'session_id' => $this->session_id,
		] );

		//blueodin_write_log( "BlueOdinSession::set_current_cart_id", [ 'rows_updated' => $rows ] );
	}

	public function set_email( string $email, string $source ): void
	{
		//blueodin_write_log( "BlueOdinSession::set_email", [
		//	'session_id' => $this->session_id,
		//	'email'      => $email,
		//	'source'     => $source,
		//] );
		global $wpdb;

		$this->get_email();

		if ( ! $this->should_update_email( $email, $source ) ) {
			return;
		}

		$rows = $wpdb->update(
			$wpdb->prefix . 'bo_sessions',
			[
				'email'        => $email,
				'email_source' => $source,
				'last_seen'    => wp_date( DATE_ATOM ),
			], [
			'session_id' => $this->session_id,
		] );

		$this->email        = $email;
		$this->email_source = $source;
		$this->loaded       = true;

		//blueodin_write_log( "BlueOdinSession::set_email", [ 'rows_updated' => $rows ] );
	}

	public function get_current_cart_id(): ?int
	{
		//blueodin_write_log( "BlueOdinSession::get_current_cart_id", [ 'session_id' => $this->session_id ] );
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT current_cart_id FROM {$wpdb->prefix}bo_sessions WHERE session_id = %s", $this->session_id );
		$id  = $wpdb->get_var( $sql );

		//blueodin_write_log( "BlueOdinSession::get_current_cart_id", [ 'return' => $id ] );

		return $id;
	}

	public function get_email(): ?object
	{
		if ( $this->loaded ) {
			return (object) [
				'email'  => $this->email,
				'source' => $this->email_source,
			];
		}

		//blueodin_write_log( "BlueOdinSession::get_email", [ 'session_id' => $this->session_id ] );
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT email, email_source as source FROM {$wpdb->prefix}bo_sessions WHERE session_id = %s", $this->session_id );
		$data = $wpdb->get_row( $sql );
		//blueodin_write_log( "BlueOdinSession::get_email", [ 'return' => $data ] );

		if (!$data) {
			$data = (object) [
				'email'  => null,
				'source' => null,
			];
		}

		$this->email        = $data->email;
		$this->email_source = $data->source;
		$this->loaded       = true;

		return $data;
	}

	private function touch(): void
	{
		//blueodin_write_log("BlueOdinSession::touch", ['session_id' => $this->session_id]);
		global $wpdb;

		$rows = $wpdb->update(
			$wpdb->prefix . 'bo_sessions',
			[
				'last_seen' => wp_date( DATE_ATOM ),
			], [
				'session_id' => $this->session_id,
			]
		);
		//blueodin_write_log( "BlueOdinSession::touch", [ 'rows_updated' => $rows ] );
	}

	private function should_update_email( string $new_email, string $new_source ): bool
	{
		if ( is_null( $this->email ) ) {
			return true;
		}

		if ( $new_email === $this->email && $new_source === $this->email_source ) {
			return false;
		}

		// Key is the current source. Values is a list of sources that cannot override it
		$cannot_override = [
			'order'          => [ 'logged_in_user', 'form_submit', 'blueodin_email' ],
			'blueodin_email' => [],
			'checkout'       => [ 'logged_in_user', 'form_submit', 'blueodin_email' ],
			'form_submit'    => [],
			'logged_in_user' => [ 'form_submit', 'blueodin_email' ],
		];

		if ( ! array_key_exists( $this->email_source, $cannot_override ) ) {
			_doing_it_wrong( 'BlueOdinSession::should_update_email', 'unknown email source', '1.0.0' );
		}

		return ! ( in_array( $new_source, $cannot_override[ $this->email_source ] ) );

	}


}