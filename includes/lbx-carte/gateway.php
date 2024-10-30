<?php

include_once __DIR__ . '/../lbxGateway.php';
class WC_lenbox_Carte_Gateway extends WC_lenbox_base_Gateway
{


	public $cbnx_config;
	public $id;
	public $icon;
	public $method_title;
	public $method_description;
	public $supports;
	public $title;
	public $description;
	public $logger;
	public $form_fields;
	public $use_test;
	public $live_client_id;
	public $live_client_authkey;



	/**
	 * Class constructor, more about it in Step 3
	 */
	public function __construct()
	{
		include_once __DIR__ . '/config.php';
		$this->id   = 'lenbox_carte'; // payment gateway plugin ID
		$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		// $this->has_fields = true; // in case you need a custom credit card form
		$this->method_title       = __('Lenbox Carte Gateway', 'lenbox-cbnx');
		$this->method_description = __('Paiement par carte', 'lenbox-cbnx');
		$this->logger             = wc_get_logger();


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
		$this->use_test            = $this->get_option('use_test');
		$this->live_client_id      = $this->get_option('live_client_id');
		$this->live_client_authkey = $this->get_option('live_client_authkey');

		// Load action hooks
		parent::__construct();
	}

	// Plugin options
	public function init_form_fields()
	{
		$this->form_fields = lenbox_carte_get_form_fields();
	}

	public function validate_fields()
	{
		// Not required at the moment as we are using only text fields as input at the moment
	}

	public function get_payment_product_options($montant)
	{
		return ["FLOA_1XC"];
	}
}
