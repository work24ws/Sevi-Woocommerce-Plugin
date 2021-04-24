<?php
/**
 * @package WooCommerceSevi
 */
/**
 * Plugin Name: WooCommerce Sevi Gateway
 * Plugin URI: https://sevi.io
 * Description: This is a WooCommerce plugin for Sevi Payment Gateway.
 * Version: 1.0.0
 * Author: Waqar M. Irfan
 * License: GPLv3 or later
 * Text Domain: woocommerce-sevi
 */

defined('ABSPATH') || exit;

// check if woocommerce is installed and activated, if not - then do not proceed with this plugin
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

// include the class for wordpress core tables
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

// constants
define('WOOCOMMERCE_SEVI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define( 'WC_SEVI_MAIN_FILE', __FILE__ );
define( 'WC_SEVI_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

// require the main file for the plugin logics
require_once WOOCOMMERCE_SEVI_PLUGIN_PATH . 'inc/class-woocommerce-sevi.php';

// activation
register_activation_hook(__FILE__, array('WooCommerceSevi', 'activate'));

// deactivation
register_deactivation_hook(__FILE__, array('WooCommerceSevi', 'deactivate'));