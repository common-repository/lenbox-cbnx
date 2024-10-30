<?php

class WC_lenbox_base_Gateway extends WC_Payment_Gateway
{
    public $logger;
    public $id;

    public function __construct()
    {
        include_once __DIR__ . '/requests.php';
        $this->logger = wc_get_logger();

        // This action hook saves the settings based on gateway ID
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_' . $this->id . '_update_order', array($this, 'lenbox_payment_completed_wh'));
        add_action('woocommerce_api_wc_' . $this->id . '_gateway_failed', array($this, 'lenbox_payment_failed_wh'));
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {

        $this->logger->debug('[lenbox] Starting refund for : ' . $order_id);
        if (!isset($amount) || 0 === $amount) {
            return false;
        }

        $order    = wc_get_order($order_id);
        $lenbox_handler = new Lenbox_API_Handler($this);
        $response = $lenbox_handler->request_remboursement($order, $amount);

        if (!is_wp_error($response)) {
            $body   = json_decode($response['body'], true);
            $msg    = $body['response']['message'];
            $status = $body['status'];

            $this->logger->debug('[lenbox] Refund status :' . $status);
            $this->logger->debug('[lenbox] Refund API response msg : ' . $msg);

            if ('success' === $body['status']) {
                return true;
            } else {
                wc_add_notice($msg, 'error');
            }
        } else {
            $this->logger->debug('[lenbox] Refund API call failed ');
            wc_add_notice(__('Connection error.', 'lenbox-cbnx'), 'error');
        }
        $this->logger->debug('[lenbox] Processed refund : false');
        return false;
    }

    public function get_payment_product_options($montant)
    {
        throw new Exception("Not implemented for this gateway");
    }

    /**
     *  API Call for new order
     */
    public function process_payment($order_id)
    {
        $order    = wc_get_order($order_id);
        $lenbox_handler = new Lenbox_API_Handler($this);
        $response = $lenbox_handler->start_new_order($order);

        // Redirects to Failure by default.
        $redirection_array = array(
            'result' => 'failure',
        );

        if (!is_wp_error($response)) {
            $body = json_decode($response['body'], true);
            if ('success' === $body['status']) {
                $order->save();
                $redirection_array = array(
                    'result'   => 'success',
                    'redirect' => esc_url($body['response']['url']),
                );
            } else {
                wc_add_notice($body['message'], 'error');
            }
        } else {
            wc_add_notice(__('Connection error.', 'lenbox-cbnx'), 'error');
        }

        return $redirection_array;
    }

    public function lenbox_payment_failed_wh()
    {
        $cbnx_failed_msg = __('Payment Failed', 'lenbox-cbnx');
        wc_add_notice($cbnx_failed_msg, 'error');
        wp_safe_redirect(wc_get_page_permalink('cart'));
    }

    /**
     * Validate and fetch the order that uniquely matches the product_id
     */
    public function fetch_order($product_id)
    {

        $err_msg = null;
        $this->logger->debug('[lenbox] Searching orders with lenbox_ref : ' . $product_id);
        $orders = wc_get_orders(
            array(
                'meta_key'      => 'lenbox_ref',
                'meta_value'    =>  $product_id,
                'meta_compare'  => 'LIKE',
            )
        );

        if (empty($orders)) {

            $err_msg = 'Invalid lenbox_ref recieved : ' . $product_id;
            throw new Exception($err_msg);
        } elseif (count($orders) > 1) {

            $conflicting_orders = array_reduce(
                $orders,
                function ($carry, $order) {
                    return is_null($carry) ? $order->get_id() : $carry . ', ' . $order->get_id();
                }
            );

            $err_msg = 'Multiple orders associated with product ID ' . $product_id . ' : ' .
                $conflicting_orders . '. Verify the lenbox_ref and contact the lenbox team for support.';

            throw new Exception($err_msg);
        }

        $order = $orders[0];

        if (!$this->is_lenbox_order($order, $product_id)) {
            throw new Exception('The order no.' . $order->get_id() . 'is not a lenbox order.');
        }

        return $order;
    }

    /**
     * Check if order was paid using lenbox_gateway
     */
    public function is_lenbox_order($order, $product_id)
    {
        $is_lenbox_order = true;
        $order_pm        = $order->get_payment_method();

        $this->logger->debug(
            '[lenbox] Order associated with product ID ' . $product_id .
                ' was paid using : ' . $order_pm
        );

        if ($order_pm !== $this->id) {
            $is_lenbox_order = false;
        }

        return $is_lenbox_order;
    }

    /**
     * Payment Status API
     */
    public function lenbox_payment_completed_wh()
    {
        $product_id       = null;
        $operation_status = array(
            'product_id'     => null,
            'has_error'      => null,
            'err_msg'        => null,
            'status'         => null,
            'action_details' => null,
        );

        if (isset($_GET['productid'])) {
            $product_id                     = sanitize_text_field($_GET['productid']);
            $operation_status['product_id'] = $product_id;
        } else {
            $this->logger->debug('[lenbox] Payment status update API called without product_id');
            $operation_status['has_error'] = true;
            $operation_status['err_msg']   = 'update API called without product_id';
            wp_send_json($operation_status);
        }

        // Fetch the order; throw an error if it doesn't exist
        try {
            $order    = $this->fetch_order($product_id);
            $order_id = $order->get_id();
        } catch (Exception $err_msg) {
            $this->logger->debug('[lenbox] ' . $err_msg);
            $this->logger->debug('[lenbox] Stopping update for lenbox_ref ' . $product_id);
            $operation_status['has_error'] = true;
            $operation_status['err_msg']   = 'Error fetching Order : ' . $err_msg;
            wp_send_json($operation_status);
        }

        // If payment has been marked as completed, abort the update
        $order_paid_dt = $order->get_date_paid();
        $order_status = $order->get_status();
        // $this->logger->debug('[lenbox] Order status' . $order_status);
        if ($order_paid_dt && 'pending' != $order_status) {
            $this->logger->debug('[lenbox] Order already paid on ' . $order_paid_dt);
            $operation_status['has_error'] = true;
            $operation_status['err_msg']   = 'Aborting state update wbhk : Order already paid on ' . $order_paid_dt;
            wp_send_json($operation_status);
        }

        // Get status from lenbox
        $lenbox_handler = new Lenbox_API_Handler($this);
        $response = $lenbox_handler->get_payment_status($product_id);

        $this->logger->debug('[lenbox] Response for ' . $product_id . ' is ' . wp_json_encode($response));

        // Check if api call was successful
        if (!is_wp_error($response)) {
            $data   = json_decode($response['body'], true);
            $status = $data['status'];
            if ('success' === $status) {
                $accepted = $data['response']['accepted'];

                // Set the status based on the lenbox error
                if ($accepted) {
                    $order->payment_complete();
                    $this->logger->debug('[lenbox] Payment successful for Order_ID : ' . $order_id);
                    $operation_status['status'] = 'SUCCESS';
                } else {
                    $order->update_status('wc-failed');
                    $this->logger->debug('[lenbox] Payment failed for Order_ID :' . $order_id);
                    $operation_status['status'] = 'FAILED';
                }
            } else {
                $this->logger->debug(
                    '[lenbox] Error from the API for lenbox order : ' . $order_id .
                        ' : ' . $data['body']['message']
                );
                $operation_status['has_error'] = true;
                $operation_status['err_msg']   = 'Error from Lenbox API : ' . $data['body']['message'];
                wp_send_json($operation_status);
            }
        } else {
            $this->logger->debug('[lenbox] Error invoking the API for lenbox order' . $product_id);
            $operation_status['has_error'] = true;
            $operation_status['err_msg']   = 'Error invoking the API for lenbox order';
            wp_send_json($operation_status);
        }
        $this->logger->debug('[lenbox] Successfully invoked the API and updated lenbox order' . $product_id);
        $this->logger->debug('[lenbox] returning response ' . wp_json_encode($operation_status));
        $operation_status['has_error'] = false;
        wp_send_json($operation_status);
    }
}
