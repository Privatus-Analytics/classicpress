<?php
/**
 * Plugin Name:       Privatus Analytics
 * Plugin URI:        https://github.com/Privatus-Analytics/classicpress
 * Description:       Simple, private website analytics for ClassicPress. No cookies, no IP addresses stored, bots left out of your numbers.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires CP:       2.0
 * Premium URI:       https://privatusanalytics.com/pricing
 * Author:            Privatus Analytics
 * Author URI:        https://privatusanalytics.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       privatus-analytics
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   10.3
 *
 * @package PrivatusAnalytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRIVATUS_ANALYTICS_VERSION', '1.0.0' );
define( 'PRIVATUS_ANALYTICS_FILE', __FILE__ );
define( 'PRIVATUS_ANALYTICS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRIVATUS_ANALYTICS_URL', plugin_dir_url( __FILE__ ) );

require_once PRIVATUS_ANALYTICS_DIR . 'includes/class-privatus-analytics-settings.php';
require_once PRIVATUS_ANALYTICS_DIR . 'includes/class-privatus-analytics-tracker.php';
require_once PRIVATUS_ANALYTICS_DIR . 'includes/class-privatus-analytics-woocommerce.php';
require_once PRIVATUS_ANALYTICS_DIR . 'includes/class-privatus-analytics-dashboard-widget.php';
require_once PRIVATUS_ANALYTICS_DIR . 'includes/class-privatus-analytics-optout.php';
require_once PRIVATUS_ANALYTICS_DIR . 'includes/class-privatus-analytics-privacy.php';

/**
 * Boot the plugin.
 */
function privatus_analytics_init() {
	( new Privatus_Analytics_Settings() )->register();
	( new Privatus_Analytics_Tracker() )->register();
	( new Privatus_Analytics_WooCommerce() )->register();
	( new Privatus_Analytics_Dashboard_Widget() )->register();
	( new Privatus_Analytics_Optout() )->register();
	( new Privatus_Analytics_Privacy() )->register();
}
add_action( 'plugins_loaded', 'privatus_analytics_init' );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage.
 */
function privatus_analytics_declare_wc_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'privatus_analytics_declare_wc_compatibility' );
