<?php

namespace BlueOdin\WordPress;

use WP;

final class BlueOdinUTMTracking {

	const BO_THANKYOU_ACTION_DONE = '_bo_thankyou_action_done';
	private $parameter_names = [
		'utm_campaign',
		'utm_source',
		'utm_medium',
		'utm_content',
		'utm_id',
		'utm_term',
		'address',
	];

	/**
	 * @var array<string,string> $parameters
	 */
	private $parameters = [];

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
		$utm_tracking = new BlueOdinUTMTracking( $session );
		$loader->add_action( 'wp', $utm_tracking, 'action_wp' );
		$loader->add_action( 'woocommerce_thankyou', $utm_tracking, 'action_woocommerce_thankyou' );
		$loader->add_filter( 'query_vars', $utm_tracking, 'filter_query_vars' );

		return $utm_tracking;
	}


	/**
	 * @param array $vars
	 *
	 * @return array
	 */
	public function filter_query_vars( array $vars ): array
	{
		//error_log("in query_vars function");
		foreach ( $this->parameter_names as $parameter ) {
			$vars[] = $parameter;
		}

		return $vars;
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

		//error_log("url is: " . home_url(add_query_arg($wp->query_vars,  $wp->request )));

		global $wp_query;
		foreach ( $this->parameter_names as $name ) {
			$value = $wp_query->get( $name, null );
			if ( $value ) {
				$this->parameters[ $name ] = $value;
			}
		}

		if ( array_key_exists( 'bo_address', $this->parameters ) ) {
			$email = base64_decode( $this->parameters['bo_address'] );
			if ( is_email( $email ) ) {
				$this->parameters['bo_address'] = $email;
				$this->session->set_email( $email, 'blueodin_email' );
			}
		}

		$this->save_parameters();
	}

	function action_woocommerce_thankyou( int $order_id ): void
	{
		//error_log("in action_woocommerce_thankyou");
		if ( ! $order_id ) {
			return;
		}

		// Allow code execution only once
		if ( get_post_meta( $order_id, self::BO_THANKYOU_ACTION_DONE, true ) ) {
			return;
		}

		$this->load_parameters();

		// Get an instance of the WC_Order object
		$order = wc_get_order( $order_id );

		foreach ( $this->parameters as $name => $value ) {
			$order->update_meta_data( "_bo_" . $name, $value );
		}

		// Flag the action as done (to avoid repetitions on reload for example)
		$order->update_meta_data( self::BO_THANKYOU_ACTION_DONE, true );
		$order->save();
	}


	private function save_parameters(): void
	{
		//error_log("save_parameters " . print_r($this->parameters, true));

		global $wpdb;

		foreach ( $this->parameters as $name => $value ) {

			$query = $wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}bo_utm_data(time, session_id, name, value ) VALUES (now(), %s, %s, %s ) ON DUPLICATE KEY UPDATE value=%s",
				$this->session->get_session_id(),
				$name,
				$value,
				$value
			);
			//error_log($query);
			$wpdb->query( $query );
		}
	}

	private function load_parameters(): void
	{
		//error_log("load_parameters " . print_r($this->parameters, true));

		global $wpdb;

		$query   = $wpdb->prepare(
			"SELECT name, value FROM {$wpdb->prefix}bo_utm_data WHERE session_id=%s ",
			$this->session->get_session_id()
		);
		$results = $wpdb->get_results( $query );
		foreach ( $results as $result ) {
			$this->parameters[ $result->name ] = $result->value;
		}
	}
}