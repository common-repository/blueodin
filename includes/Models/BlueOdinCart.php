<?php

namespace BlueOdin\WordPress\Models;

use BlueOdin\WordPress\BlueOdinSession;
use WC_Cart;
use WC_Geolocation;

final class BlueOdinCart {

	/**
	 * @var int|null
	 */
	private $id;
	/**
	 * @var array<BlueOdinCartItem>
	 */
	private $items = [];
	/**
	 * @var string
	 */
	private $status = 'in-process';
	/**
	 * @var WC_Cart|null
	 */
	private $wc_cart;
	/**
	 * @var BlueOdinSession
	 */
	private $session;


	/**
	 * @var \WC_Order|null
	 */
	private $order;

	/**
	 * @param BlueOdinSession $session
	 */
	public function __construct( BlueOdinSession $session )
	{
		$this->session = $session;
	}

	/**
	 * @param WC_Cart $wc_cart
	 * @param BlueOdinSession $session
	 *
	 * @return BlueOdinCart
	 */
	public static function fromWC_Cart( WC_Cart $wc_cart, BlueOdinSession $session ): BlueOdinCart
	{
		$cart          = new BlueOdinCart( $session );
		$cart->wc_cart = $wc_cart;
		foreach ( $wc_cart->cart_contents as $wc_item ) {
			$cart->addItem( BlueOdinCartItem::fromWC_Cart( $wc_item ) );
		}

		return $cart;
	}

	public static function fromAddedItem( BlueOdinSession $session, string $key, int $product_id, int $quantity ): BlueOdinCart
	{
		$cart = new BlueOdinCart( $session );
		$cart->addItem( new BlueOdinCartItem( $key, $product_id, $quantity ) );

		return $cart;
	}


	public function update(): void
	{
		global $wpdb;
		$user_id    = get_current_user_id();
		$ip_address = WC_Geolocation::get_ip_address();
		$order_id   = $this->order ? $this->order->get_id() : null;
		$wpdb->update( $wpdb->prefix . "bo_carts",
			[
				'time'       => wp_date( DATE_ATOM ),
				'user_id'    => $user_id,
				'ip_address' => $ip_address,
				'order_id'   => $order_id
			],
			[
				'id' => $this->cart_id(),
			]

		);

		$wpdb->delete( $wpdb->prefix . 'bo_cart_items', [ 'cart_id' => $this->cart_id() ] );
		foreach ( $this->items as $item ) {
			$item->save_to_database();
		}
	}

	/**
	 * @return int
	 */
	public function cart_id(): int
	{
		if ( $this->id ) {
			return $this->id;
		}

		$this->id = $this->session->get_current_cart_id();
		if ( ! is_null( $this->id ) ) {
			return $this->id;
		}

		$this->id = $this->insert();
		$this->session->set_current_cart_id( $this->id );

		return $this->id;

	}

	/**
	 * @return WC_Cart
	 */
	public function wc_cart(): WC_Cart
	{
		return $this->wc_cart;
	}

	/**
	 * @return void
	 */
	public function push_to_blueodin(): void
	{
		do_action( 'blueodin_cart_updated', [
			'id'     => $this->cart_id(),
			'data'   => $this->toArray(),
			'action' => 'updated'
		] );
	}

	private function toArray(): array
	{
		$this->wc_cart->calculate_totals();

		$items = [];
		foreach ( $this->items as $item ) {
			$items[] = $item->toArray();
		}

		return [
			'wc_cart'          => $this->wc_cart,
			'id'               => $this->id,
			'session_id'       => $this->session->get_session_id(),
			'customer_details' => $this->getCustomerDetails(),
			'order_total'      => $this->wc_cart->get_total( 'notview' ),
			'coupons'          => [],
			'captured_by'      => 'unknown',
			'cart_status'      => $this->status,
			'items'            => $items,
			'order_id'         => $this->order ? $this->order->get_id() : null,
		];
	}

	public function addItem( BlueOdinCartItem $item ): void
	{
		$item->setCart( $this );
		$this->items[] = $item;
	}

	private function getCustomerDetails(): array
	{
		if ( $this->order && $this->order->get_user() ) {
			return [
				'email_address' => $this->order->get_user()->user_email,
				'source'        => 'order',
				'customer_id'   => $this->order->get_user()->ID,
			];
		}

		if ( $this->order ) {
			return [
				'email_address' => $this->order->get_billing_email(),
				'source'        => 'order',
			];
		}

		$session_email = $this->session->get_email();
		if ( $session_email && $session_email->email ) {
			return [
				'email_address' => $session_email->email,
				'source'        => $session_email->source,
			];
		}

		return [
			'email_address' => 'unknown',
			'source'        => 'unknown',
		];
	}

	public function setOrder( \WC_Order $order ): void
	{
		$this->order  = $order;
		$this->status = 'ordered';
	}

	private function insert(): int
	{
		global $wpdb;
		$user_id    = get_current_user_id();
		$ip_address = WC_Geolocation::get_ip_address();
		$session_id = $this->session->get_session_id();
		$wpdb->insert( $wpdb->prefix . 'bo_carts',
			[
				'time'       => wp_date( DATE_ATOM ),
				'session_id' => $session_id,
				'user_id'    => $user_id,
				'ip_address' => $ip_address,
			]

		);

		return $this->id = $wpdb->insert_id;
	}
}