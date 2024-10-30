<?php

namespace BlueOdin\WordPress\Models;

final class BlueOdinCartItem {

	/**
	 * @var string
	 */
	private $key;
	/**
	 * @var int
	 */
	private $product_id;
	/**
	 * @var int
	 */
	private $quantity;
	/**
	 * @var BlueOdinCart|null
	 */
	private $cart;

	/**
	 * @var array
	 */
	private $wc_cart_item = [];

	/**
	 * @param string $key
	 * @param int $product_id
	 * @param int $quantity
	 */
	public function __construct( string $key, int $product_id, int $quantity )
	{
		$this->key        = $key;
		$this->product_id = $product_id;
		$this->quantity   = $quantity;
	}

	public static function fromWC_Cart( array $wc_item ): BlueOdinCartItem
	{
		$item               = new BlueOdinCartItem(
			$wc_item['key'],
			$wc_item['product_id'],
			$wc_item['quantity']
		);
		$item->wc_cart_item = $wc_item;

		return $item;
	}


	public function toArray(): array
	{
		return [
			'key'               => $this->key,
			'product_id'        => $this->product_id,
			'quantity'          => $this->quantity,
			"line_subtotal"     => $this->wc_cart_item['line_subtotal'] ?? 0,
			"line_subtotal_tax" => $this->wc_cart_item['line_subtotal_tax'] ?? 0,
			"line_total"        => $this->wc_cart_item['line_total'] ?? 0,
			"line_tax"          => $this->wc_cart_item['line_tax'] ?? 0,
		];
	}

	public function setCart( BlueOdinCart $cart ): void
	{
		$this->cart = $cart;
	}

	public function save_to_database(): void
	{
		global $wpdb;

		$query = $wpdb->prepare(
			"INSERT INTO {$wpdb->prefix}bo_cart_items(cart_id, item_key, product_id, quantity) VALUES (%d, %s, %d, %d ) ON DUPLICATE KEY UPDATE product_id=%d, quantity = %d",
			$this->cart->cart_id(),
			$this->key,
			$this->product_id,
			$this->quantity,
			$this->product_id,
			$this->quantity
		);
		//blueodin_write_log('save_cart_item_to_database', ['query' => $query, 'item' => $item]);
		$wpdb->query( $query );
	}

}