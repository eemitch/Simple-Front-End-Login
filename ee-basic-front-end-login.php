<?php

/**
 * @package Basic Front-End Login
 */
/*
Plugin Name: Basic Front-End Login
Plugin URI: https://simplefilelist.com/basic-front-end-login/
Description: A very simple front-end login form which can also disable access to the back-end.
Author: Mitchell Bennis
Version: 1.1.1
Author URI: https://elementengage.com
License: GPLv2 or later
Text Domain: ee-basic-front-end-login
Domain Path: /languages
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// Version
define('eeBFEL_Version', '1.1.1'); // Going from "just for me" to Public


// Function to Display the Login Form
function eeBFEL_Shortcode( $eeBFEL_Attributes ) {
	
	// Shortcode Attributes
	$eeAtts = shortcode_atts( array('redirect' 	=> site_url() ), $eeBFEL_Attributes );
	extract($eeAtts); // Convert into variables
    
    // Get Default Redirect
   $redirect = get_option('eeBFEL_Redirect');
   if(!$redirect) { $redirect = site_url(); }
	
	// Wordpress Login Form Settings
	$eeFormArgs = array(
        'echo'           => FALSE, // Return it
        'redirect'       => $redirect,
        'form_id'        => 'eeBFEL',
        'label_username' => __( 'Username' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in'   => __( 'Log In' ),
        'id_username'    => 'user_login',
        'id_password'    => 'user_pass',
        'id_remember'    => 'rememberme',
        'id_submit'      => 'wp-submit',
        'remember'       => FALSE,
        'value_username' => '',
        'value_remember' => false
    );
    
    if( get_current_user_id() ) {
	    
	    // Show a Logout Link
	    $eeOutput = '<a href="' . wp_logout_url() . '">' . __('Logout', 'ee-basic-front-end-login') . '</a>';
	
	} else {
	    
	    // Show the form
		$eeOutput = wp_login_form($eeFormArgs);
	}
	
	// Return the form
	return $eeOutput;
}
add_shortcode( 'eeBFEL', 'eeBFEL_Shortcode' ); // Shotcode: [eeBFEL]




// Deny Access to the Back-End to Subscribers
function eeBFEL_DenyDashbord() {
  
	$eeBFEL_DenyRoles = get_option('eeBFEL_DenyRoles');
	
	if(!$eeBFEL_DenyRoles OR $eeBFEL_DenyRoles == 'NO') { return; }
	
	// Else...
	
	$eeBFEL_DenyRoles = explode(',', $eeBFEL_DenyRoles);
	
	// Get current user's roles
	$user = wp_get_current_user();
	
	foreach( $eeBFEL_DenyRoles as $key => $role) {
		
		if ($role != 'administrator' AND in_array( $role, (array) $user->roles ) ) {
			
			show_admin_bar(FALSE); // Hide the Admin Bar
			
			if( is_admin() && !defined('DOING_AJAX')) { // Deny Back-End Access
		
				wp_redirect( home_url() ); // Redirect to Home
				exit;
			}
		}
	}
}
add_action('init', 'eeBFEL_DenyDashbord');



// Language Enabler
function eeBFEL_Textdomain() {
    load_plugin_textdomain( 'ee-basic-front-end-login', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'eeBFEL_Textdomain' );




// Front-side <head> Additions
function eeBFEL_Enqueue() {

	// Login Form CSS
    wp_register_style( 'ee-basic-front-end-login-front', plugin_dir_url(__FILE__) . 'style-front.css', '', eeBFEL_Version);
	wp_enqueue_style('ee-basic-front-end-login-front');

}
add_action( 'wp_enqueue_scripts', 'eeBFEL_Enqueue' );




// Admin <head> Additions
function eeBFEL_AdminHead($eeHook) {
	
	// wp_die($eeHook);
    
    $eeHooks = array(
    	'settings_page_ee-basic-front-end-login'
    );
    
    if(in_array($eeHook, $eeHooks)) {
        
        // Admin CSS
        wp_enqueue_style( 'ee-basic-front-end-login-back', plugins_url('style-back.css', __FILE__), '', eeBFEL_Version );
	}
}
add_action('admin_enqueue_scripts', 'eeBFEL_AdminHead');



// Admin Menu
function eeBFEL_AdminMenu() {

	// The Admin Menu
	add_options_page(
		__('Basic Front-End Login Form', 'ee-basic-front-end-login'), // Page Title
		__('Login Form', 'ee-basic-front-end-login'), // Menu Title
		'activate_plugins', // User status reguired to see the menu
		'ee-basic-front-end-login', // Slug
		'eeBFEL_AdminPage', // Function that displays the menu page
		99 // Position
	);
}
add_action( 'admin_menu', 'eeBFEL_AdminMenu' );



// Admin Page
function eeBFEL_AdminPage() {
	
	global $wp_roles;
	
	// Makes sure our options are in place
	if(!get_option('eeBFEL_Redirect')) {
		update_option('eeBFEL_Redirect', '');
	}
	if(!get_option('eeBFEL_DenyRoles')) {
		update_option('eeBFEL_DenyRoles', 'NO');
	}
	
	if( @$_POST ) {
		
		// Security
		if(check_admin_referer( 'ee-basic-front-end-login', 'ee-basic-front-end-login-nonce')) {
			
			$eeBFEL_DenyRoles ='';
			$eeBFEL_Redirect = filter_var(sanitize_text_field($_POST['eeBFEL_Redirect']), FILTER_VALIDATE_URL);
			
			if( $eeBFEL_Redirect ) {
				
				update_option('eeBFEL_Redirect', $eeBFEL_Redirect);
			}
			
			if(@is_array(@$_POST['eeBFEL_DenyRoles'])) {
				
				foreach( $_POST['eeBFEL_DenyRoles'] as $key => $role) {
					
					$eeBFEL_DenyRoles .= filter_var( sanitize_text_field($role) ) . ',';
				}
				
				$eeBFEL_DenyRoles = substr($eeBFEL_DenyRoles, 0, -1); // Strip last comma
				
				update_option('eeBFEL_DenyRoles', $eeBFEL_DenyRoles); // Store the string
				
			} else {
				
				update_option('eeBFEL_DenyRoles', 'NO'); // Don't hide the back-end
			}
		}
	}
	
	$eeOutput = '
	
	<div class="wrap">
	
	<form id="eeBFEL_Settings" action="' . admin_url() . 'options-general.php?page=ee-basic-front-end-login" method="POST">';
		
		// Ad Nonce for Security
		$eeOutput .= wp_nonce_field( 'ee-basic-front-end-login', 'ee-basic-front-end-login-nonce', TRUE, FALSE);	
		
		$eeBFEL_Redirect = get_option('eeBFEL_Redirect');
		
		$eeBFEL_DenyRoles = get_option('eeBFEL_DenyRoles');
		$eeBFEL_DenyRoles = explode(',', $eeBFEL_DenyRoles);
			
		// Form HTML
		$eeOutput .= '
		
		<fieldset>
	
		<h1>Basic Front-End Login Form</h1>
		
		<p>' . 
		__('This plugin provides you with a basic front-end login form for any page, post or widget.') . ' ' . __('It will also redirect to the page you choose.') . ' ' .
		__('It also blocks access to the back-end and hides the Admin Bar.', 'ee-basic-front-end-login') . ' </p><p>' .
		__('To display the login form, place this shortcode on any page, post, or widget:', 'ee-basic-front-end-login') . ' <strong>[eeBFEL]</strong>
		</p>
		
		</fieldset>
		<fieldset>
		
		<h2>Redirect URL</h2>
		
		<label for="eeBFEL_Redirect">' . __('Default Login Redirect', 'ee-basic-front-end-login') . '</label>
		<input placeholder="https://website.com/your-files-page/" type="url" name="eeBFEL_Redirect" value="' . $eeBFEL_Redirect . '" id="eeBFEL_Redirect" size="64" />
		<div class="eeNote">' . __('After login, go to this page.', 'ee-basic-front-end-login') . '<br />' .
		__('You can over-ride this to create multiple login forms by using this shortcode attribute:', 'ee-basic-front-end-login') . '<br/>
		[eeBFEL redirect="https://website.com/your-files-page/"]</div>
		
		</fieldset>
		<fieldset>
		
		<h2>Restrict Dashboard Access</h2>
		
		<p>' . __('This setting is for when you want your users to be logged-in, but do not want them to have access to the Wordpress Dashboard.', 'ee-basic-front-end-login') . ' </p>';
		
		foreach( $wp_roles->roles as $eeRole => $eeRoleObject ) {
			if($eeRole != 'administrator') {
				$eeOutput .= '<label class="eeBFEL_DenyRoleCheck">'  . ucwords($eeRole) . ': <input type="checkbox" name="eeBFEL_DenyRoles[]" value="' . $eeRole . '"';
				if(in_array($eeRole, $eeBFEL_DenyRoles)) { $eeOutput .= ' checked="checked"'; }
				$eeOutput .= '/></label>';
			}
		}
			
		$eeOutput .= '
		<div class="eeNote">' . __('Checked roles will not see the Admin Bar or be allowed to access the Dashboard.', 'ee-basic-front-end-login') . '</div>
		
		</fieldset>
		
		<fieldset>
			<input type="submit"name="eeBFEL_Save" id="eeBFEL_Save" value="SAVE" />
		</fieldset>
		
		<fieldset id="eeBFEL_Footer">
			<p>Basic Front End Login (Version: ' . eeBFEL_Version . ') | Plugin by <a href="https://elementengage.com" target="_blank">Element Engage, LLC</a><br />
				Please <a href="https://elementengage.com/shop/plugin-donation/">donate</a> if you find this plugin useful.</p>
		</fieldset>
	</form>
	
	</div>';
	
	
	echo $eeOutput;
	
}

?>