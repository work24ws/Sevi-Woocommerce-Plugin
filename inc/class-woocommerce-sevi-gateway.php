<?php

class WooCommerceSeviGateway extends WC_Payment_Gateway
{
    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {
        // echo get_post_meta(20, '_payment_method', true);
        // die;
        global $wpdb;
        $this->id = 'sevi'; // payment gateway plugin ID
        $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = 'Sevi';
        $this->method_description = 'This is the official Woocommerce plugin for Sevi'; // will be displayed on the options page

        $this->supports = array(
            'products'
        );

        $keys = $wpdb->get_results("SELECT * from " . $wpdb->prefix . "woocommerce_api_keys WHERE description = 'Sevi API User' ");

        //If Sevi Woocommerce API key not exist then create a new one
        if (count($keys) < 1) {
            //If Sevi Woocommerce API key not found then create a new key
            $description = "Sevi API User";
            $permissions = 'read_write';
            $user_id = absint(get_current_user_id());

            $consumer_key = 'ck_' . wc_rand_hash();
            $consumer_secret = 'cs_' . wc_rand_hash();

            $this->woocommerce_api_key = $consumer_key;
            $this->woocommerce_api_secret = $consumer_secret;

            add_option('sevi_wc_key', $consumer_key, '', 'no');
            add_option('sevi_wc_secret', $consumer_secret, '', 'no');

            $data = array(
                'user_id' => $user_id,
                'description' => $description,
                'permissions' => $permissions,
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7),
            );

            $wpdb->insert(
                $wpdb->prefix . 'woocommerce_api_keys',
                $data,
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );
        }

        $results = $wpdb->get_results("select option_name, option_value from " . $wpdb->prefix . "options where option_name = 'sevi_wc_secret' or option_name = 'sevi_wc_key'");
        $key = [];
        foreach ($results as $r)
            $key[$r->option_name] = $r->option_value;

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->thankyou_url = $this->get_option('thankyou_url');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->sevi_token = $this->get_option('sevi_token');
        $this->woocommerce_api_key = $key['sevi_wc_key'];
        $this->woocommerce_api_secret = $key['sevi_wc_secret'];

        // Method with all the options fields
        $this->init_form_fields();

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // We need custom JavaScript to obtain a token
        //add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        //add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        add_action('woocommerce_thankyou', array($this, 'woocommerce_thankyou_change_order_status'));
    }

    /**
     * Plugin options
     */

    public function woocommerce_thankyou_change_order_status($order_id)
    {
        if (!$order_id) return;

        $order = wc_get_order($order_id);
        $pmt = get_post_meta($order_id, '_payment_method', true);
        if ($pmt == 'sevi') {
            if ($order->get_status() == 'processing')
                $order->update_status('pending');
        }
        $redirect_url = 'https://app.sevi.io/checkout/payment/?order_id=' . $order_id . '&thanks_url=' . $this->thankyou_url;
        wp_redirect($redirect_url);

        exit;
    }
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('woocommerce_sevi_fields', array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Sevi Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Sevi',
                'desc_tip'    => true,
            ),
            'thankyou_url' => array(
                'title'       => 'Thankyou Page URL',
                'type'        => 'text',
                'description' => 'This will be the URL on which page will redirect after successfull payment..',
                'default'     => site_url('thankyou'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Buy now, Pay later',
            ),
            'testmode' => array(
                'title'       => 'Test mode',
                'label'       => 'Enable Test Mode',
                'type'        => 'checkbox',
                'description' => 'Place the payment gateway in test mode',
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'sevi_token' => array(
                'title'       => 'Sevi Token',
                'type'        => 'text'
            )
        ));
    }

    /**
     * Save plugin option and call Sevi API
     */
    public function process_admin_options()
    {
        parent::process_admin_options();

        global $wpdb;

        $wpdb->update($wpdb->prefix . "options", array('option_value' => $this->woocommerce_api_key), array('option_name' => 'sevi_wc_key'));
        $wpdb->update($wpdb->prefix . "options", array('option_value' => $this->woocommerce_api_secret), array('option_name' => 'sevi_wc_secret'));

        add_option('sevi_wc_key', $this->woocommerce_api_key, '');
        add_option('sevi_wc_secret', $this->woocommerce_api_secret, '');
        //Call Sevi API here.

        $data = [
            'active' => $this->get_option('enabled') == 'yes' ? true : false,
            'jwtToken' => $this->get_option('sevi_token'),
            'url' => get_site_url(),
            'consumerKey' => $this->woocommerce_api_key,
            'consumerSecret' => $this->woocommerce_api_secret
        ];
        $this->curl_call('https://curvy-pig-7.loca.lt/ecommerce/woocommerce/connect', $data);
    }

    public function payment_fields()
    {
        if ($this->description) {
            // display the description with <p> tags etc.
            echo wpautop(wp_kses_post($this->description));
        }
    }

    /**
     * Get_icon function.
     */
    public function get_icon()
    {
        $icons = array(
            'visa'       => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/visa.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Visa" />',
            'amex'       => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/amex.svg" style="max-width:40px;padding-left:3px" class="icon" alt="American Express" />',
            'mastercard' => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/mastercard.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Mastercard" />',
            'discover'   => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/discover.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Discover" />',
            'diners'     => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/diners.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Diners" />',
            'jcb'        => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/jcb.svg" style="max-width:40px;padding-left:3px" class="icon" alt="JCB" />',
            'alipay'     => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/alipay.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Alipay" />',
            'wechat'     => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/wechat.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Wechat Pay" />',
            'bancontact' => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/bancontact.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Bancontact" />',
            'ideal'      => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/ideal.svg" style="max-width:40px;padding-left:3px" class="icon" alt="iDeal" />',
            'p24'        => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/p24.svg" style="max-width:40px;padding-left:3px" class="icon" alt="P24" />',
            'giropay'    => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/giropay.svg" style="max-width:40px;padding-left:3px" class="icon stripe-icon" alt="Giropay" />',
            'eps'        => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/eps.svg" style="max-width:40px;padding-left:3px" class="icon" alt="EPS" />',
            'multibanco' => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/multibanco.svg" style="max-width:40px;padding-left:3px" class="icon" alt="Multibanco" />',
            'sofort'     => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/sofort.svg" style="max-width:40px;padding-left:3px" class="icon" alt="SOFORT" />',
            'sepa'       => '<img src="' . WC_SEVI_PLUGIN_URL . '/assets/images/sepa.svg" style="max-width:40px;padding-left:3px" class="icon" alt="SEPA" />',
        );

        $icons_str = '';

        $icons_str .= isset($icons['visa']) ? $icons['visa'] : '';
        $icons_str .= isset($icons['amex']) ? $icons['amex'] : '';
        $icons_str .= isset($icons['mastercard']) ? $icons['mastercard'] : '';

        if ('USD' === get_woocommerce_currency()) {
            $icons_str .= isset($icons['discover']) ? $icons['discover'] : '';
            $icons_str .= isset($icons['jcb']) ? $icons['jcb'] : '';
            $icons_str .= isset($icons['diners']) ? $icons['diners'] : '';
        }

        return apply_filters('woocommerce_gateway_icon', $icons_str, $this->id);
    }

    /*
     * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
     */
    public function payment_scripts()
    {
        // we need to load our css only on checkout page
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue css too
        if ('no' === $this->enabled) {
            return;
        }

        wp_register_style('sevi_styles', plugins_url('assets/css/style.css', WC_SEVI_MAIN_FILE), array(), '1.0');
        wp_enqueue_style('sevi_styles');
    }

    /*
      * Fields validation, more in Step 5
     */
    public function validate_fields()
    {
        return true;
    }

    /*
     * We're processing the payments here, everything about it is in Step 5
     */
    public function process_payment($order_id)
    {
        global $woocommerce;

        // we need it to get any order detailes
        $order = wc_get_order($order_id);
        $order->payment_complete();
        $order->reduce_order_stock();

        // Empty cart
        $woocommerce->cart->empty_cart();

        // Redirect to the thank you page
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    /*
     * In case you need a webhook, like PayPal IPN etc
     */
    public function webhook()
    {
        $order = wc_get_order($_GET['id']);
        $order->payment_complete();
        $order->reduce_order_stock();

        update_option('webhook_debug', $_GET);
    }

    private function curl_call($url = '', $args = [])
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        return $server_output;
    }
}
