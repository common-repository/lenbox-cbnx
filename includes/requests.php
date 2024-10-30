<?php

/**
 * Handles integration with Lenbox Bubble API
 */
class Lenbox_API_Handler
{

	public $logger;
	public $authkey;
	public $client_id;
	public $base_api;
	public $is_test;
	public $gateway_config;
	public $gateway_settings;

	/**
	 * Returns boolean for checkbox values
	 */
	private function get_checkbox_bool($inputval)
	{
		return (isset($inputval) && 'yes' === $inputval);
	}

	/**
	 * Constructor builds base_url and pulls auth details from Payment Gateway
	 */
	public function __construct($gateway_settings)
	{
		include_once __DIR__ . '/gatewayConfig.php';
		$this->gateway_config   = lenbox_get_gateway_config($gateway_settings->id);
		$this->logger           = wc_get_logger();
		$this->gateway_settings = $gateway_settings;
		// Env settings
		$this->authkey   = $gateway_settings->live_client_authkey;
		$this->client_id = $gateway_settings->live_client_id;
		$this->base_api  = 'https://app.finnocar.com';
		$this->is_test   = $this->get_checkbox_bool($gateway_settings->use_test);
	}

	public function get_applicable_cbnx_products($montant)
	{
		$active_modes = array();
		$montant = (int)$montant;

		foreach ($this->gateway_settings->cbnx_config as $key => $item) {
			if ($this->get_checkbox_bool($item['is_active'])) {
				$is_range_unconfigured = ($item["max"] == 0 && $item["min"] == 0);
				$is_valid_price_range = ($montant >= $item["min"] && ($montant <= $item["max"] || $item["max"] == 0));
				if ($is_range_unconfigured || $is_valid_price_range) {
					array_push($active_modes, $item['payment_mode_id']);
				}
			}
		}
		return $active_modes;
	}

	function start_new_order($order)
	{
		// Order Metadata
		$order_id   = $order->get_id();
		$product_id = md5(wp_rand()) . '_' . $order_id;
		$order->update_meta_data('lenbox_ref', $product_id);
		$lenbox_api = $this->base_api . $this->gateway_config["demande_api"];

		$payment_options = $order->get_meta('lenbox_payment_options');
		if (empty($payment_options)) {
			$payment_options = $this->gateway_settings->get_payment_product_options($order->get_total());
		}

		$order_pm = $order->get_payment_method();

		// URL Configs.
		$return_url  = $this->gateway_settings->get_return_url($order);
		$cancel_url  = wc_get_checkout_url();
		$notif_url = str_replace(
			'https:',
			'http:',
			add_query_arg(
				array(
					'wc-api'    => 'WC_' . $order_pm . '_update_order',
					'productid' => $product_id,
				),
				home_url('/')
			)
		);
		$failure_url = str_replace(
			'https:',
			'http:',
			add_query_arg('wc-api', 'WC_' . $order_pm . '_Gateway_Failed', home_url('/'))
		);



		$json_basique = array(
			// Champs Obligatoire.
			'authkey'        => $this->authkey,
			'vd'             => $this->client_id,
			'test'           => $this->is_test,
			'montant'        => $order->get_total(), // Min 100.
			'typeprojet'     => '8', // can be deprecated ?
			'productid'      => $product_id,
			'paymentoptions' => $payment_options,
			// URLs for redirection.
			'notification'   => $notif_url,
			'retour'         => $return_url,
			'cancellink'     => $cancel_url,
			'failurelink'    => $failure_url,
			'integration'    => 'woocommerce',
		);

		$additional_params = get_post_meta($order_id, 'product_details');
		$additional_params = (count($additional_params) == 1) ? $additional_params[0] : array();

		/** Champs non-obligatoire from additional_params **
		 *
		 * "modele"=> "text modele",
		 * "marque"=> "Peugeot",
		 * "image"=> "lien d'image",
		 * "kilometrage"=> 2345.42,
		 * "pfisc"=> 2345.42,
		 */

		$json_body = array_merge($json_basique, $additional_params);
		$this->logger->debug('[lenbox] Starting new order API Call at : ' . $lenbox_api);
		$this->logger->debug('[lenbox] Starting new order API Call with body : ' . wp_json_encode($json_body));

		$args     = array(
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'body'        => wp_json_encode($json_body),
			'method'      => 'POST',
			'data_format' => 'body',
		);
		$response = wp_remote_post($lenbox_api, $args);

		return $response;
	}

	/**
	 * Get Payment status
	 */
	public function get_payment_status($product_id)
	{

		$this->logger->debug('[lenbox] Starting status API Call for : ' . $product_id);
		$lenbox_api = $this->base_api . $this->gateway_config["status_api"];

		$json_body = array(
			'authkey'   => $this->authkey,
			'vd'        => $this->client_id,
			'productId' => $product_id,
			'type'      => $this->gateway_config["type_demande"],
		);

		$args     = array(
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'body'        => wp_json_encode($json_body),
			'method'      => 'POST',
			'data_format' => 'body',
		);
		$response = wp_remote_post($lenbox_api, $args);
		return $response;
	}

	/**
	 * Request refunds (partial and full)
	 */
	public function request_remboursement($order, $amount)
	{
		$lenbox_api = $this->base_api . '/api/1.1/wf/updateorder';

		$this->logger->debug('Order total :' . $order->get_total());
		$this->logger->debug('Refund total :' . $order->get_total_refunded());

		$new_amount = round($order->get_total() - $order->get_total_refunded(), PHP_ROUND_HALF_DOWN);
		$product_id = $order->get_meta('lenbox_ref');

		$this->logger->debug(
			'[lenbox] Requesting refund for order ' . $order->get_id() .
				' with updated amount : ' . $new_amount
		);

		$json_body = array(
			'authkey'        => $this->authkey,
			'vd'             => $this->client_id,
			'productId'      => $product_id,
			'updated_amount' => $new_amount,
		);

		$args     = array(
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'body'        => wp_json_encode($json_body),
			'method'      => 'POST',
			'data_format' => 'body',
		);
		$response = wp_remote_post($lenbox_api, $args);
		return $response;
	}

	/**
	 * Get Payment status
	 */
	public function get_product_options($montant)
	{

		$lenbox_api = $this->base_api . '/api/1.1/wf/get_nx_eligibility';
		$applicable_products = $this->get_applicable_cbnx_products($montant);
		$this->logger->debug(
			'[lenbox] Starting paymentoptions call for : ' . $montant
				. ' with applicable products : ' . wp_json_encode($applicable_products)
		);
		if (!is_array($applicable_products) || count($applicable_products) == 0) {
			return array();
		}
		$options    = array();

		$json_body = array(
			'authkey'        => $this->authkey,
			'vd'             => $this->client_id,
			'montant'        => $montant,
			'paymentoptions' => $applicable_products,
		);

		$args = array(
			'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			'body'        => wp_json_encode($json_body),
			'method'      => 'POST',
			'data_format' => 'body',
		);


		try {
			$wp_response = wp_remote_post($lenbox_api, $args);
			if (!is_wp_error($wp_response)) {
				$data =   preg_replace('/\xc2\xa0/', '', $wp_response['body']); // Due to bubble shenanigans, we need to clean the json before decode
				$data = json_decode($data, true);
				$options = $data['body']['results'];
			}
		} catch (Exception $th) {
			$this->logger->debug('[lenbox] Error loading eligibilties from lenbox', 1);
		}

		return $options;
	}
}
