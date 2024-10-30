<?php

namespace BlueOdin\WordPress\Admin;

use BlueOdin\WordPress\BlueOdin_i18n;
use BlueOdin\WordPress\BlueOdinLoader;
use WC_Order;
use WC_Product;

final class CostOfGoods {

	const META_KEY = '_wc_cog_cost';
	const WC_COG_ITEM_COST = '_wc_cog_item_cost';
	const WC_COG_ITEM_TOTAL_COST = '_wc_cog_item_total_cost';
	const WC_COG_ORDER_TOTAL_COST = '_wc_cog_order_total_cost';

	public function __construct()
	{

	}

	public static function load( BlueOdinLoader $loader ): self
	{
		$cost_of_goods = new CostOfGoods();

		// Add the meta box to the product edit page
		$loader->add_action( 'woocommerce_product_options_pricing', $cost_of_goods, 'action_woocommerce_product_options_pricing' );
		$loader->add_action( 'woocommerce_process_product_meta', $cost_of_goods, 'action_woocommerce_process_product_meta', 10, 2 );

		// Update the cost of goods when an order is created
		$loader->add_action( 'woocommerce_checkout_update_order_meta', $cost_of_goods, 'action_woocommerce_checkout_update_order_meta', 10, 1 );

		// Don't display the COG cost on the order edit page.
		$loader->add_filter( 'woocommerce_hidden_order_itemmeta', $cost_of_goods, 'filter_woocommerce_hidden_order_itemmeta' );

		// Add the COG to the product table
		$loader->add_filter( 'manage_edit-product_columns', $cost_of_goods, 'filter_manage_edit_product_columns', 11 );
		$loader->add_action( 'manage_product_posts_custom_column', $cost_of_goods, 'action_manage_product_posts_custom_column', 11 );
		$loader->add_filter( 'manage_edit-product_sortable_columns', $cost_of_goods, 'filter_manage_edit_product_sortable_columns', 11 );
		$loader->add_filter( 'request', $cost_of_goods, 'filter_request', 11 );

		return $cost_of_goods;
	}

	public function action_woocommerce_product_options_pricing()
	{

		woocommerce_wp_text_input( [
			'id'          => self::META_KEY,
			'class'       => 'wc_input_price short',
			'label'       => sprintf( __( 'Cost of Good (%s)', BlueOdin_i18n::TEXTDOMAIN ), '<span>' . get_woocommerce_currency_symbol() . '</span>' ),
			'data_type'   => 'price',
			'desc_tip'    => true,
			'description' => __( 'Cost of Goods is the cost of the product, excluding any additional costs such as shipping, taxes, etc.', BlueOdin_i18n::TEXTDOMAIN ),
		] );
	}

	/**
	 * Save cost field for simple product
	 *
	 * @param int $post_id post id
	 *
	 */
	public function action_woocommerce_process_product_meta( int $post_id )
	{
		update_post_meta( $post_id, self::META_KEY, stripslashes( wc_format_decimal( $_POST[ self::META_KEY ] ) ) );
	}

	/**
	 * Sets the cost of goods for a given order.
	 *
	 * In WC 3.0+ this simply sums up all the line item total costs.
	 *
	 * @param int|\WP_Post|WC_Order $order_id the order ID, post object, or order object
	 *
	 */
	public function action_woocommerce_checkout_update_order_meta( $order_id )
	{

		$order = wc_get_order( $order_id );

		$total_cost = 0;

		foreach ( $order->get_items() as $item_id => $item ) {

			if ( ! $item_id || empty( $item ) ) {
				continue;
			}

			$product_id = ( ! empty( $item['variation_id'] ) ) ? $item['variation_id'] : $item['product_id'];
			$item_cost  = $this->get_cost_by_id( $product_id );
			$quantity   = $item->get_quantity();

			$this->set_item_cost_meta( $item_id, $item_cost, $quantity );

			// add to the item cost to the total order cost.
			$total_cost += ( $item_cost * $quantity );

		}

		$formatted_total_cost = wc_format_decimal( $total_cost, wc_get_price_decimals() );

		$order->update_meta_data( self::WC_COG_ORDER_TOTAL_COST, $formatted_total_cost );
		$order->save_meta_data();
	}

	/**
	 * Returns the product cost, if any
	 *
	 * @param int $product_id product id
	 *
	 * @return float product cost if configured, the empty string otherwise
	 */
	public function get_cost_by_id( int $product_id ): ?float
	{
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return 0.0;
		}

		return $this->get_cost( $product );
	}

	/**
	 * Returns the product cost, if any
	 *
	 * @param WC_Product $product
	 *
	 * @return float product cost if configured, the empty string otherwise
	 */
	public function get_cost( WC_Product $product ): ?float
	{
		$cost = $product->get_meta( self::META_KEY, true, 'edit' );

		return is_numeric( $cost ) ? $cost : null;
	}

	/**
	 * Returns the product cost html, if any
	 *
	 * @param WC_Product $product the product or product id
	 *
	 * @return string product cost markup
	 * @since 1.1
	 */
	public function get_cost_html( WC_Product $product ): ?string
	{
		$cost = $this->get_cost( $product );

		if (! $cost ) {
			return null;
		}

		return wc_price( $cost );
	}


	/**
	 * Sets an order item's cost meta.
	 *
	 * @param int $item_id item ID
	 * @param float|string $item_cost item cost
	 * @param int $quantity item quantity
	 *
	 */
	private function set_item_cost_meta( int $item_id, float $item_cost, int $quantity ): void
	{

		if ( empty( $item_cost ) || ! is_numeric( $item_cost ) ) {
			$item_cost = '0';
		}

		$formatted_cost  = wc_format_decimal( $item_cost );
		$formatted_total = wc_format_decimal( $item_cost * $quantity );

		try {
			wc_update_order_item_meta( $item_id, self::WC_COG_ITEM_COST, $formatted_cost );
			wc_update_order_item_meta( $item_id, self::WC_COG_ITEM_TOTAL_COST, $formatted_total );
		} catch ( \Exception $e ) {
		}
	}

	public function filter_woocommerce_hidden_order_itemmeta( array $hidden_fields ): array
	{
		return array_merge( $hidden_fields, [ self::WC_COG_ITEM_COST, self::WC_COG_ITEM_TOTAL_COST ] );
	}

	/**
	 * Adds a "Cost" column header after the core "Price" one, on the Products
	 * list table
	 *
	 * @param array $existing_columns associative array of column key to name
	 *
	 * @return array associative array of column key to name
	 */
	public function filter_manage_edit_product_columns( array $existing_columns ): array
	{

		$columns = [];

		foreach ( $existing_columns as $key => $value ) {

			$columns[ $key ] = $value;

			if ( 'price' === $key ) {
				$columns['cost'] = __( 'Cost', BlueOdin_i18n::TEXTDOMAIN );
			}
		}

		return $columns;
	}

	/**
	 * Renders the product cost value in the products list table
	 *
	 * @param string $column column id
	 *
	 * @since 1.1
	 */
	public function action_manage_product_posts_custom_column( string $column ): void
	{
		if ( 'cost' !== $column ) {
			return;
		}

		/* @type \WC_Product $the_product */
		global $post, $the_product;

		if ( ! $the_product instanceof \WC_Product || $the_product->get_id() !== $post->ID ) {
			$the_product = wc_get_product( $post );
		}

		if ( $this->get_cost_html( $the_product ) ) {
			echo $this->get_cost_html( $the_product );
		} else {
			echo '<span class="na">&ndash;</span>';
		}
	}

	/**
	 * Add the "Cost" column to the list of sortable columns
	 *
	 * @param array $columns associative array of sortable columns, id to id
	 *
	 * @return array sortable columns
	 *
	 */
	public function filter_manage_edit_product_sortable_columns( array $columns ): array
	{

		$columns['cost'] = 'cost';

		return $columns;
	}

	/**
	 * Add the "Cost" column to the orderby clause if sorting by cost
	 *
	 * @param array $vars query vars
	 *
	 * @return array query vars
	 */
	public function filter_request( array $vars ): array
	{

		if ( ! isset( $vars['order_by'] ) || 'cost' !== $vars['order_by'] ) {
			return $vars;
		}

		$order = strtoupper( $vars['order'] ?? 'DESC' );

		// place the products with no cost at the top or the bottom of the list, depending on chosen sort order
		if ( 'ASC' === $order ) {
			$order_by = [
				'cost_of_goods_not_set' => 'ASC',
				'cost_of_goods'         => 'ASC',
			];
		} else {
			$order_by = [
				'cost_of_goods_not_set' => 'DESC',
				'cost_of_goods'         => 'DESC',
			];
		}

		return array_merge( $vars, [
			'order_by'   => $order_by,
			'meta_query' => [
				'relation'              => 'OR',
				'cost_of_goods'         => [
					'key'     => '_wc_cog_cost',
					'compare' => 'EXISTS',
					'type'    => 'NUMERIC',
				],
				'cost_of_goods_not_set' => [
					'key'     => '_wc_cog_cost',
					'compare' => 'NOT EXISTS',
					'type'    => 'NUMERIC',
				],
			]
		] );
	}


}