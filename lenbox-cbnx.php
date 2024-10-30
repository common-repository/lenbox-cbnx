<?php
/*
Plugin Name: Lenbox Paiement plusieurs fois
Description: Allows users to demand EMI from Lenbox and it's partner FLOA
TextDomain:  lenbox-cbnx
Version:     3.3.1
Author:      Lenbox
Domain Path: /languages
Author URI:  https://www.lenbox.io/
License:     GPL2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html


Copyright 2021 Lenbox (email : tech@lenbox.io)
*/

/************* Order UI *************/


add_action('admin_menu', 'lenbox_add_admin_menu');


function lenbox_add_admin_menu()
{
	$hook = add_menu_page('Lenbox', 'Lenbox', 'manage_options', 'lenbox', 'lenbox_order_list_page');
	add_action("load-$hook", 'lenbox_add_order_options');
}


function lenbox_add_order_options()
{
	include 'views/order_table.php';
	global $myListTable;
	$option = 'per_page';
	$args   = array(
		'label'   => __('Orders', 'lenbox-cbnx'),
		'default' => 20,
		'option'  => 'orders_per_page',
	);
	add_screen_option($option, $args);
	$myListTable = new Lenbox_Order_Table();
}


function lenbox_order_list_page()
{
	global $myListTable;
	echo '</pre><div class="wrap"><h2>' . __('Transactions', 'lenbox-cbnx') . '</h2>';
	$myListTable->prepare_items();
	echo '<form method="post">
		<input type="hidden" name="page" value="order_list_table">';
	$myListTable->search_box('search', 'search_id');
	$myListTable->display();
	echo '</form></div>';
}

/************* Woocommerce Integration */

if (!function_exists('is_plugin_active')) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
	add_action(
		'admin_notices',
		function () {
			/* translators: 1. URL link. */
			echo '<div class="error"><p><strong>' . sprintf(esc_html__('Lenbox Paiement plusieurs fois requires WooCommerce to be installed and active. You can download %s here.', 'lenbox-cbnx'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
		}
	);
	return;
} else {


	/**
	 * Custom function to declare compatibility with cart_checkout_blocks feature 
	 */
	function lenbox_declare_cart_checkout_blocks_compatibility()
	{
		// Check if the required class exists
		if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
			// Declare compatibility for 'cart_checkout_blocks'
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
		}
	}
	// Hook the custom function to the 'before_woocommerce_init' action
	add_action('before_woocommerce_init', 'lenbox_declare_cart_checkout_blocks_compatibility');


	function get_price($product)
	{
		if ($product->is_type('simple')) {
			return  $product->get_price();
		} elseif ($product->is_type('variable')) {
			return  $product->get_variation_regular_price('min', true);
		}
		return 0;
	}

	include_once(plugin_dir_path(__FILE__) . 'includes/requests.php');

	add_action('woocommerce_before_add_to_cart_button', 'lenbox_paymentoptions', 9);
	function lenbox_paymentoptions($args)
	{
		global $product;
		$lenbox_gateway = new WC_lenbox_FLOA_Gateway();
		$lenbox_api = new Lenbox_API_Handler($lenbox_gateway);
		$show_eligibility = $lenbox_gateway->show_eligibility;
		if (!isset($show_eligibility) || $show_eligibility != 'yes') {
			return;
		}

		$price = get_price($product);
		$options = $lenbox_api->get_product_options($price);

		if (!$options) {
			return;
		}

		$before_start   = '<div class="row mt-1">
		   <p class="lenbox-options col-xs-12">  </p>';
		$before_options = '';

		foreach ($options as $option) {
			$before_options = $before_options . "<div class='col-xs-12 relative'>
				   <p class='col-xs-12' 
					   style=' display: flex; align-items: center; gap:0.35em;
					   margin: 0.1em 0; width: fit-content; padding : 0.25em 0.5em;
					   font-size:0.9em; font-weight: 400; color: white;
					   color: #1f2443; border-radius:0.5rem;
					   '>" . $option['nombre_mois'] . ' paiements* de ' . round($option['mensualite'], 2) . ' € avec ' .
				"<img src='" . $option['icon'] . "' style='max-height:15px; width:auto;'" .
				" alt='" . $option['nom'] . "'/>" .
				' </p> </div>';
		}
		$before = $before_start . $before_options . '</div>';

		echo $before;
	}


	function lenbox_add_gateways($gateways)
	{
		$gateways[] = 'WC_lenbox_FLOA_Gateway';
		$gateways[] = 'WC_lenbox_Carte_Gateway';
		return $gateways;
	}

	// Add suboptions to the payment gateway
	add_filter('woocommerce_available_payment_gateways', 'conditionally_allow_gateway');
	function conditionally_allow_gateway($available_gateways)
	{
		global $woocommerce;
		$cart = $woocommerce->cart;
		if (!is_admin() && !empty($cart) && is_checkout()) {
			// Remove lenbox if no valid products are available
			$lenbox_gateway = new WC_lenbox_FLOA_Gateway();
			$lenbox_api = new Lenbox_API_Handler($lenbox_gateway);
			$available_products = $lenbox_api->get_applicable_cbnx_products($cart->total);
			if (!$available_products) {
				unset($available_gateways['lenbox_floa_cbnx']);
			}
		}
		return $available_gateways;
	}

	add_action('plugins_loaded', 'lenbox_init_plugin');
	function lenbox_init_plugin()
	{
		load_plugin_textdomain('lenbox-cbnx', false, dirname(plugin_basename(__FILE__)) . '/languages');
		if (class_exists('WC_Payment_Gateway')) {
			include_once(plugin_dir_path(__FILE__) . 'includes/lbx-cbnx/gateway.php');
			include_once(plugin_dir_path(__FILE__) . 'includes/lbx-carte/gateway.php');
		} else {
			exit;
		}
		add_filter('woocommerce_payment_gateways', 'lenbox_add_gateways');
	}

	// Hook the custom function to the 'woocommerce_blocks_loaded' action
	add_action('woocommerce_blocks_loaded', 'lenbox_register_blocks_payment');

	/**
	 * Custom function to register a payment method type

	 */
	function lenbox_register_blocks_payment()
	{
		// Check if the required class exists
		if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
			return;
		}

		// Include the custom Blocks Checkout class
		require_once plugin_dir_path(__FILE__) . 'includes/lbx-cbnx/blocks.php';
		require_once plugin_dir_path(__FILE__) . 'includes/lbx-carte/blocks.php';

		// Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
				$payment_method_registry->register(new Lenbox_Carte_Blocks());
				$payment_method_registry->register(new Lenbox_FLOA_Blocks());
			}
		);
	}

	function handle_lenbox_ref_query($query, $query_vars)
	{
		if (!empty($query_vars['lenbox_ref'])) {
			$query['meta_query'][] = array(
				'key'   => 'lenbox_ref',
				'value' => esc_attr($query_vars['lenbox_ref']),
			);
		}

		return $query;
	}
	add_filter('woocommerce_order_data_store_cpt_get_orders_query', 'handle_lenbox_ref_query', 10, 2);

	// edit-shop_order is the screen ID of the orders page
	add_filter('bulk_actions-edit-shop_order', 'lenbox_bulk_update_status');

	/**
	 * Add new bulk action status
	 */
	function lenbox_bulk_update_status($bulk_actions)
	{

		$bulk_actions['lenbox_status_update'] = __('Refresh Status for Lenbox Orders', 'lenbox-cbnx');
		return $bulk_actions;
	}

	/**
	 * The "hook name" in the admin_action_{hook} is same as the above function name
	 */
	add_action('admin_action_lenbox_status_update', 'lenbox_bulk_update_action');

	/**
	 * Bulk action handler
	 */
	function lenbox_bulk_update_action()
	{

		// if an array with order IDs is not presented, exit the function
		if (!isset($_REQUEST['post']) && !is_array($_REQUEST['post'])) {
			return;
		}

		$lenbox_gateway = new WC_lenbox_FLOA_Gateway();
		$lenbox_api = new Lenbox_API_Handler($lenbox_gateway);
		$logger         = wc_get_logger();

		foreach ($_REQUEST['post'] as $order_id) {

			$order    = new WC_Order($order_id);
			$order_pm = $order->get_payment_method();

			$product_ids = $order->get_meta('lenbox_ref');
			if (empty($product_ids)) {
				// For backwards compatibility,
				// existing orders without lenbox_ref will use Order ID
				$product_ids = $order_id;
			}
			$order_note = 'Lenbox ref : ' . $product_ids . '.';

			if ($order_pm !== $lenbox_gateway->id) {
				$logger->debug(
					'[lenbox] Order ID ' . $order_id .
						' belongs to ' . $order_pm .
						'. Skipping for ' . $lenbox_gateway->id . ' refresh.'
				);
				continue;
			}

			$separated = explode(',', $product_ids);
			$updated = false;

			foreach ($separated as $product_id) {
				$response = $lenbox_api->get_payment_status($product_id, $lenbox_gateway);

				if (!is_wp_error($response)) {
					$data   = json_decode($response['body'], true);
					$status = $data['status'];
					if ('success' === $status) {
						$accepted = $data['response']['accepted'];
						$updated = true;

						if ($accepted) {
							$order->payment_complete();
							wc_reduce_stock_levels($order_id);
							error_log('order successful ' . $order_id);
							break;
						} else {
							$order->update_status('wc-failed');
							error_log('Payment failed Client order ID :' . $product_id);
						}
					} else {
						error_log($data['body']['message'] . ' for lenbox order ID ' . $product_id);
					}
				} else {
					$logger->debug('Error Invoking the lenbox API for lenbox order' . $product_id);
				}
			}
			if (!$updated) {
				return new WP_REST_Response(null, 500);
			}
		}

		$location = add_query_arg(
			array(
				'post_type'            => 'shop_order',
				'lenbox_status_update' => 1, // Custom $_GET variable for notices
				'post_status'          => 'all',
			),
			'edit.php'
		);

		wp_redirect(admin_url($location));
		exit;
	}

	/*
	* Notices
	*/
	add_action('admin_notices', 'lenbox_status_bulk_update_notices');

	function lenbox_status_bulk_update_notices()
	{

		global $pagenow, $typenow;

		if (
			$typenow == 'shop_order'
			&& $pagenow == 'edit.php'
			&& isset($_REQUEST['lenbox_status_update'])
			&& $_REQUEST['lenbox_status_update'] == 1
			&& isset($_REQUEST['changed'])
		) {
			$message = sprintf(_n('Order status changed.', '%s order statuses changed.', $_REQUEST['changed']), number_format_i18n($_REQUEST['changed']));
			echo "<div class=\"updated\"><p>{$message}</p></div>";
		}
	}
}
