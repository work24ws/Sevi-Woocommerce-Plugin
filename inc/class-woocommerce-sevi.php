<?php
class WooCommerceSevi
{
    public static function activate()
    {
        flush_rewrite_rules();

        global $wpdb;

        $keys = $wpdb->get_results("SELECT * from ".$wpdb->prefix . "woocommerce_api_keys WHERE description = 'Sevi API User' ");

        //If Sevi Woocommerce API key exists then return
        if($keys->num_rows > 0)return;

        //If Sevi Woocommerce API key not found then create a new key
        $description = "Sevi API User";
        $permissions = 'read_write';
        $user_id     = absint( get_current_user_id() );

        $consumer_key    = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();

        add_option('sevi_wc_key',$consumer_key,'','no');
        add_option('sevi_wc_secret',$consumer_secret,'','no');

        $data = array(
                'user_id'         => $user_id,
                'description'     => $description,
                'permissions'     => $permissions,
                'consumer_key'    => wc_api_hash( $consumer_key ),
                'consumer_secret' => $consumer_secret,
                'truncated_key'   => substr( $consumer_key, -7 ),
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

        $wpdb->insert($wpdb->prefix.'options',['option_name'=>'woocommerce_sevi_settings','option_value'=>'a:5:{s:5:"title";s:4:"Sevi";s:11:"description";s:18:"Buy now, pay later";s:7:"enabled";s:3:"yes";s:8:"testmode";s:3:"yes";s:10:"sevi_token";s:0:"";}','autoload'=>'yes'],array('%s','%s','%s'));
    }

    public static function deactivate()
    {
        global $wpdb;

        $wpdb->delete( $wpdb->prefix . "options" , array( 'option_name' => 'sevi_wc_key') );
        $wpdb->delete( $wpdb->prefix . "options" , array( 'option_name' => 'sevi_wc_secret' ) );
        $wpdb->delete( $wpdb->prefix . "options" , array( 'option_name' => 'woocommerce_sevi_settings' ) );
        $wpdb->delete( $wpdb->prefix . "woocommerce_api_keys" , array( 'description' => 'Sevi API User' ) );
    }

    public static function register()
    {
        // actions
        add_action('admin_menu', array(self::class, 'add_admin_pages'));
        add_action('plugins_loaded', array(self::class, 'init_payment_gateway_class'));

        //filters
        add_filter('woocommerce_payment_gateways', array(self::class, 'add_payment_gateway_class'));
    }

    #region - admin pages
    public static function add_admin_pages()
    {
        add_menu_page('Sevi Transactions', 'Sevi Transactions', 'manage_options', 'sevi', array(self::class, 'admin_index'), 'dashicons-text-page', 100);
    }

    public static function admin_index()
    {
        require_once WOOCOMMERCE_SEVI_PLUGIN_PATH . 'templates/admin-index.php';
    }

    #endregion

    public static function add_payment_gateway_class($gateways)
    {
        $gateways[] = 'WooCommerceSeviGateway';
        return $gateways;
    }

    public static function init_payment_gateway_class()
    {
        require_once WOOCOMMERCE_SEVI_PLUGIN_PATH . 'inc/class-woocommerce-sevi-gateway.php';
    }

}

WooCommerceSevi::register();