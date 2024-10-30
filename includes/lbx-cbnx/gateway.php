<?php

include_once __DIR__ . '/../lbxGateway.php';
class WC_lenbox_FLOA_Gateway extends WC_lenbox_base_Gateway
{

	public $cbnx_config;
	public $id;
	public $icon;
	public $method_title;
	public $method_description;
	public $supports;
	public $title;
	public $description;
	public $form_fields;
	public $use_test;
	public $show_eligibility;
	public $live_client_id;
	public $live_client_authkey;


	/**
	 * Class constructor, more about it in Step 3
	 */
	public function __construct()
	{
		include_once __DIR__ . '/config.php';
		$this->id   = 'lenbox_floa_cbnx'; // payment gateway plugin ID
		$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		// $this->has_fields = true; // in case you need a custom credit card form
		$this->method_title       = __('Lenbox CBNX Gateway', 'lenbox-cbnx');
		$this->method_description = __('Paiement plusieurs fois', 'lenbox-cbnx');


		// gateways can support subscriptions, refunds, saved payment methods,
		$this->supports = array(
			'products',
			// 'refunds',
		);

		// Method with all the options fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->title       = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->cbnx_config = array(
			'cbnx3_payant'   => array(
				'is_active'       => $this->get_option('cbnx3_payant'),
				'payment_mode_id' => 'FLOA_3XP',
				'client_label'    => __('Pay in 3', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx3_payant_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx3_payant_max') ?? null,
			),
			'cbnx3_gratuit'  => array(
				'is_active'       => $this->get_option('cbnx3_gratuit'),
				'payment_mode_id' => 'FLOA_3XG',
				'client_label'    => __('Pay in 3', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx3_gratuit_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx3_gratuit_max') ?? null,
			),
			'cbnx4_payant'   => array(
				'is_active'       => $this->get_option('cbnx4_payant'),
				'payment_mode_id' => 'FLOA_4XP',
				'client_label'    => __('Pay in 4', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx4_payant_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx4_payant_max') ?? null,
			),
			'cbnx4_gratuit'  => array(
				'is_active'       => $this->get_option('cbnx4_gratuit'),
				'payment_mode_id' => 'FLOA_4XG',
				'client_label'    => __('Pay in 4', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx4_gratuit_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx4_gratuit_max') ?? null,
			),
			'cbnx10_payant'  => array(
				'is_active'       => $this->get_option('cbnx10_payant'),
				'payment_mode_id' => 'FLOA_10XP',
				'client_label'    => __('Pay in 10', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx10_payant_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx10_payant_max') ?? null,
			),
			'cbnx10_gratuit' => array(
				'is_active'       => $this->get_option('cbnx10_gratuit'),
				'payment_mode_id' => 'FLOA_10XG',
				'client_label'    => __('Pay in 10', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx10_gratuit_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx10_gratuit_max') ?? null,
			),
			'cbnx12_payant'  => array(
				'is_active'       => $this->get_option('cbnx12_payant'),
				'payment_mode_id' => 'FLOA_12XP',
				'client_label'    => __('Pay in 12', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx12_payant_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx12_payant_max') ?? null,
			),
			'cbnx12_gratuit' => array(
				'is_active'       => $this->get_option('cbnx12_gratuit'),
				'payment_mode_id' => 'FLOA_12XG',
				'client_label'    => __('Pay in 12', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx12_gratuit_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx12_gratuit_max') ?? null,
			),
			'cbnx24_payant'  => array(
				'is_active'       => $this->get_option('cbnx24_payant'),
				'payment_mode_id' => 'FLOA_24XP',
				'client_label'    => __('Pay in 24', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx24_payant_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx24_payant_max') ?? null,
			),
			'cbnx24_gratuit' => array(
				'is_active'       => $this->get_option('cbnx24_gratuit'),
				'payment_mode_id' => 'FLOA_24XG',
				'client_label'    => __('Pay in 24', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx24_gratuit_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx24_gratuit_max') ?? null,
			),
			'cbnx36_payant'  => array(
				'is_active'       => $this->get_option('cbnx36_payant'),
				'payment_mode_id' => 'FLOA_36XP',
				'client_label'    => __('Pay in 36', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx36_payant_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx36_payant_max') ?? null,
			),
			'cbnx36_gratuit' => array(
				'is_active'       => $this->get_option('cbnx36_gratuit'),
				'payment_mode_id' => 'FLOA_36XG',
				'client_label'    => __('Pay in 36', 'lenbox-cbnx'),
				'min'             => (int) $this->get_option('cbnx36_gratuit_min') ?? 0,
				'max'             => (int) $this->get_option('cbnx36_gratuit_max') ?? null,
			),

		);

		$this->use_test            = $this->get_option('use_test');
		$this->show_eligibility    = $this->get_option('show_eligibility');
		$this->live_client_id      = $this->get_option('live_client_id');
		$this->live_client_authkey = $this->get_option('live_client_authkey');

		// Load action hooks
		parent::__construct();
		add_action('woocommerce_checkout_create_order', array($this, 'save_order_payment_type_meta_data'), 10, 2);
	}

	/**
	 * Save the chosen payment type as order meta data.
	 *
	 * @param object $order
	 * @param array $data
	 */
	public function save_order_payment_type_meta_data($order, $data)
	{
		if ($data['payment_method'] === $this->id && isset($_POST['transaction_type']))
			$order->update_meta_data('lenbox_payment_options', esc_attr($_POST['transaction_type']));
	}

	// Plugin options
	public function init_form_fields()
	{
		$this->form_fields = lenbox_cbnx_get_form_fields();
	}

	public function validate_fields()
	{
		// Not required at the moment as we are using only text fields as input at the moment
	}


	public function payment_fields()
	{
		if ($description = $this->get_description()) {
			echo wpautop(wptexturize($description));
		}

		$show_client_label = $this->get_option('client_label');
		if ($show_client_label === 'no') {
			return;
		}

		// Get eligibile options for the current cart total
		$cart_total = WC()->cart->total;
		$lenbox_handler = new Lenbox_API_Handler($this);
		$eligibile_options = $lenbox_handler->get_product_options($cart_total);
		$payment_modes = array();
		$valid_options = array();

		// Hackery to get the payment modes as server does not return the key
		foreach ($eligibile_options as $option) {
			$product_key = 'FLOA_' . $option['nombre_mois'] . "X" . ($option["sans frais"] ? "G" : "P");
			$label = $option['nombre_mois'] . ' fois x ' . $option['mensualite'] . ' € ';
			$valid_options[$product_key] =  $label;
			array_push($payment_modes, $product_key);
		}
		woocommerce_form_field(
			'transaction_type',
			array(
				'type'        => 'radio',
				'label'         => __('Type de demande', 'lenbox-cbnx'),
				'options'       => $valid_options,
			),
			reset($payment_modes)
		);
	}

	public function get_payment_product_options($montant)
	{

		$active_modes = array();
		$montant = (int)$montant;

		foreach ($this->cbnx_config as $key => $item) {
			if (isset($item['is_active']) && 'yes' === $item['is_active']) {
				$is_range_unconfigured = ($item["max"] == 0 && $item["min"] == 0);
				$is_valid_price_range = ($montant >= $item["min"] && ($montant <= $item["max"] || $item["max"] == 0));
				if ($is_range_unconfigured || $is_valid_price_range) {
					array_push($active_modes, $item['payment_mode_id']);
				}
			}
		}
		return $active_modes;
	}
}
