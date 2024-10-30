<?php

include_once __DIR__ . '/gateway.php';

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Lenbox_FLOA_Blocks extends AbstractPaymentMethodType
{

    /**
     * The gateway instance.
     *
     * @var WC_lenbox_base_Gateway
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = "lenbox_floa_cbnx";

    public function get_name()
    {
        return $this->name;
    }

    public function initialize()
    {
        $this->gateway = new WC_lenbox_FLOA_Gateway();
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
        return true;
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {

        $integration_registry_name = $this->name . '-blocks-integration';

        wp_register_script(
            $integration_registry_name,
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        return [$integration_registry_name];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return [
            'id'          => $this->gateway->id,
            'title'       => $this->gateway->title,
            'description' => $this->gateway->description,
        ];
    }
}
