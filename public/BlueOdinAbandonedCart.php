<?php

namespace BlueOdin\WordPress;

use BlueOdin\WordPress\Models\BlueOdinCart;
use WC_Cart;
use WC_Order;

final class BlueOdinAbandonedCart {

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

	static public function load( BlueOdinLoader $loader, BlueOdinSession $session ): self
	{
		$module = new BlueOdinAbandonedCart( $session );
		$loader->add_action( 'woocommerce_add_to_cart', $module, 'action_woocommerce_add_to_cart', 10, 6 );
		$loader->add_action( 'woocommerce_cart_item_removed', $module, 'action_woocommerce_cart_item_removed', 10, 2 );
		$loader->add_action( 'woocommerce_cart_item_restored', $module, 'action_woocommerce_cart_item_restored', 10, 2 );
		$loader->add_action( 'woocommerce_cart_emptied', $module, 'action_woocommerce_cart_emptied' );
		$loader->add_action( 'woocommerce_cart_item_set_quantity', $module, 'action_woocommerce_cart_item_set_quantity', 10, 3 );
		$loader->add_action( 'woocommerce_new_order', $module, 'action_woocommerce_new_order', 10, 2 );

		return $module;
	}


	/**
	 * @param string $cart_item_key
	 * @param int $product_id
	 * @param string $quantity
	 * @param int $variation_id
	 * @param array $variation
	 * @param array $cart_item_data
	 *
	 * @return void
	 */
	public function action_woocommerce_add_to_cart(
		string $cart_item_key,
		int $product_id,
		string $quantity,
		int $variation_id,
		array $variation,
		array $cart_item_data
	): void {
		$wc_session_cookie = WC()->session->get_session_cookie();

		$wc_cart = WC()->cart;

		//blueodin_write_log( "in action_woocommerce_add_to_cart function", [
		//	'wc_session_cookie' => $wc_session_cookie,
		//	'cart_item_key'  => $cart_item_key,
		//	'product_id'     => $product_id,
		//	'quantity'       => $quantity,
		//	'variation_id'   => $variation_id,
		//	'variation'      => $variation,
		//	'cart_item_data' => $cart_item_data,
		//	'cart'           => $wc_cart,
		//] );

		$cart = is_null( $wc_cart )
			? BlueOdinCart::fromAddedItem( $this->session, $cart_item_key, $product_id, $quantity )
			: BlueOdinCart::fromWC_Cart( $wc_cart, $this->session );;

		$cart->update();
		$cart->push_to_blueodin();
	}

	/**
	 * @param string $cart_item_key
	 * @param WC_Cart $wc_cart
	 *
	 * @return void
	 */
	public function action_woocommerce_cart_item_removed( string $cart_item_key, WC_Cart $wc_cart ): void
	{
		//blueodin_write_log( "in action_woocommerce_cart_item_removed function", [
		//	'cart_item_key' => $cart_item_key,
		//	'cart'          => $wc_cart,
		//] );
		$cart = BlueOdinCart::fromWC_Cart( $wc_cart, $this->session );

		$cart->update();
		$cart->push_to_blueodin();

	}

	/**
	 * @param string $cart_item_key
	 * @param WC_Cart $wc_cart
	 *
	 * @return void
	 */
	public function action_woocommerce_cart_item_restored( string $cart_item_key, WC_Cart $wc_cart ): void
	{
		//blueodin_write_log( "in action_woocommerce_cart_item_restored function", [
		//	'cart_item_key' => $cart_item_key,
		//	'cart'          => $wc_cart,
		//] );
		$cart = BlueOdinCart::fromWC_Cart( $wc_cart, $this->session );

		$cart->update();
		$cart->push_to_blueodin();
	}

	/**
	 * @param bool $clear_persistent_cart
	 *
	 * @return void
	 */
	public function action_woocommerce_cart_emptied( bool $clear_persistent_cart ): void
	{
		//blueodin_write_log( "in action_woocommerce_cart_emptied function", [
		//	'clear_persistent_cart' => $clear_persistent_cart,
		//] );

		$this->session->set_current_cart_id(null);
	}

	/**
	 * @param string $cart_item_key
	 * @param int $quantity
	 * @param WC_Cart $wc_cart
	 *
	 * @return void
	 */
	public function action_woocommerce_cart_item_set_quantity( string $cart_item_key, int $quantity, WC_Cart $wc_cart ): void
	{
		//blueodin_write_log( "in action_woocommerce_cart_item_set_quantity function", [
		//	'cart_item_key' => $cart_item_key,
		//	'quantity'      => $quantity,
		//	'cart'          => $wc_cart,
		//] );
		$cart = BlueOdinCart::fromWC_Cart( $wc_cart, $this->session );

		$cart->update();
		$cart->push_to_blueodin();

	}

	public function action_woocommerce_new_order( int $order_id, WC_Order $order )
	{
		$wc_cart = WC()->cart;

		//blueodin_write_log( "in action_woocommerce_new_order function", [
		//	'cart'     => $wc_cart,
		//	'order_id' => $order_id,
		//	'order'    => $order,
		//] );

		if (is_null( $wc_cart )) {
			return;
		}

		$cart = BlueOdinCart::fromWC_Cart( $wc_cart, $this->session );;
		$cart->setOrder($order);

		$this->session->set_email($order->get_billing_email(), 'order');

		$cart->update();
		$cart->push_to_blueodin();

	}


}