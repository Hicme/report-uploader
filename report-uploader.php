<?php
/**
 * Plugin Name: Report-Uploader
 * Description: Report-Uploader.
 * Version: 0.7.0
 * Author: Hicme
 * Author URI: https://prosvit.design
 * Text Domain: report_uploader
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
if ( ! defined( 'P_VERSION' ) ) {
	define( 'P_VERSION', '0.7.0' );
}

if ( ! defined( 'P_PATH' ) ) {
	define( 'P_PATH', dirname( __FILE__ ) . '/' );
}

if ( ! defined( 'P_URL_FOLDER' ) ) {
	define( 'P_URL_FOLDER', plugin_dir_url( __FILE__ ) );
}

// Include the main class.
register_activation_hook(__FILE__, 'p_activate');

register_deactivation_hook( __FILE__, 'p_deactivate' );

include P_PATH . 'vendor/autoload.php';
include P_PATH . 'autoloader.php';
include P_PATH . 'includes/functions/functions.php';

report_uploader();

function p_activate()
{
	\system\Install::install_depencies();
}

function p_deactivate()
{
    
}
