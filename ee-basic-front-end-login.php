<?php

/**
 * @package Basic Front-End Login
 */
/*
Plugin Name: Basic Front-End Login
Plugin URI: https://simplefilelist.com/basic-front-end-login/
Description: A very simple front-end login form which can also disable access to the back-end.
Author: Mitchell Bennis
Version: 2.1
Author URI: https://elementengage.com
License: GPLv2 or later
Text Domain: basic-front-end-login
Domain Path: /languages
*/

if( ! defined( 'ABSPATH' ) ) exit;

define('eeBFEL_PluginSlug', 'basic-front-end-login');
define('eeBFEL_Version', '2.1');

add_action('init', 'eeBFEL_Setup');
add_action('init', 'eeBFEL_DenyDashbord');
add_action( 'init', 'eeBFEL_Textdomain' );



// Setup
function eeBFEL_Setup() {
	
	$eeNonce = wp_create_nonce('eeInclude');
	include(plugin_dir_path(__FILE__) . '/includes/ee-functions.php');
	
	add_action('admin_enqueue_scripts', 'eeBFEL_AdminHead');
	add_action( 'admin_menu', 'eeBFEL_AdminMenu' );
	
	// Logout Button
	if(get_option('eeBFEL_ShowLogout') == 'YES') {
		add_action( 'wp_footer', 'eeBFEL_AddLogoutButton' );
	}
	
	add_shortcode( 'eeBFEL', 'eeBFEL_Shortcode' ); // Shotcode: [eeBFEL]
}

// Language Enabler
function eeBFEL_Textdomain() {
	load_plugin_textdomain( 'basic-front-end-login', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}


// Activation
function eeBFEL_Activate() { return TRUE; }
register_activation_hook( __FILE__, 'eeBFEL_Activate' );







?>