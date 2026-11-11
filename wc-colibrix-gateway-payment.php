<?php
/**
 * Plugin Name: WooCommerce Colibrix Gateway Payment
 * Description: WooCommerce payment gateways for Colibrix Gateway (Card + APM).
 * Version: 1.1.0
 * Author: Colibrix Gateway
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 * Text Domain: wc-colibrix-gateway-payment
 */

defined('ABSPATH') || exit;

define('WC_COLIBRIX_GATEWAY_PLUGIN_FILE', __FILE__);
define('WC_COLIBRIX_GATEWAY_PLUGIN_DIR', __DIR__);
define('WC_COLIBRIX_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WC_COLIBRIX_GATEWAY_PLUGIN_DIR . '/includes/class-wc-colibrix-gateway-plugin.php';

WC_Colibrix_Gateway_Plugin::init();
