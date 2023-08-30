<?php

/**
 * @package Simple Front-End Login
 */
/*
Plugin Name: Simple Front-End Login
Plugin URI: https://simplefilelist.com/basic-front-end-login/
Description: A very simple front-end login form which can also disable access to the back-end.
Author: Mitchell Bennis
Version: 1.2.1
Author URI: https://elementengage.com
License: GPLv2 or later
Text Domain: ee-basic-front-end-login
Domain Path: /languages
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// Version
define('eeBFEL_Version', '1.2.1'); // Going from "just for me" to Public


// Function to Display the Login Form
function eeBFEL_Shortcode( $eeBFEL_Attributes ) {
	
	// Shortcode Attributes
	$eeAtts = shortcode_atts( array( 'redirect' => site_url() ), $eeBFEL_Attributes );
	extract($eeAtts); // Convert into variables
	
	// Make sure it's a good URL format
	if( !filter_var($redirect, FILTER_VALIDATE_URL) ) { // Get the passed url
		
		$redirect = get_option('eeBFEL_Redirect'); // Get the saved URL
		
		if(!filter_var($redirect, FILTER_VALIDATE_URL)) {
			$redirect = FALSE;
		}
	}
	
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
    
    if (get_current_user_id()) {
		
		// Show a Logout Link
		$eeOutput = '<a href="' . wp_logout_url() . '">' . __('Logout', 'ee-basic-front-end-login') . '</a>';
	
	} else {
		
		// Get the login form
		$eeOutput = wp_login_form($eeFormArgs);
	}
	
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
    	'users_page_ee-basic-front-end-login'
    );
    
    if(in_array($eeHook, $eeHooks)) {
        
        // Admin CSS
        wp_enqueue_style( 'ee-basic-front-end-login-back', plugins_url('style-back.css', __FILE__), '', eeBFEL_Version );
	}
}
add_action('admin_enqueue_scripts', 'eeBFEL_AdminHead');


// The Admin Menu
function eeBFEL_AdminMenu() {

	add_users_page(
		__('Basic Front-End Login Form', 'ee-basic-front-end-login'), // Page Title
		__('Login Form', 'ee-basic-front-end-login'), // Menu Title
		'manage_options', // User status required to see the menu
		'ee-basic-front-end-login', // Slug
		'eeBFEL_AdminPage' // Function that displays the menu page
	);
}

add_action( 'admin_menu', 'eeBFEL_AdminMenu' );



// Admin Page
function eeBFEL_AdminPage() {
	
	global $wp_roles;
	
	// Default values
	$eeBFEL_Redirect = get_option('eeBFEL_Redirect');
	$eeBFEL_DenyRoles = get_option('eeBFEL_DenyRoles');

	// Check if POST data has been sent
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		
		// Check nonce for security
		if (check_admin_referer('ee-basic-front-end-login', 'ee-basic-front-end-login-nonce')) {
			
			$eeBFEL_DenyRoles = '';
			
			if (isset($_POST['eeBFEL_Redirect'])) {
				$eeBFEL_Redirect = esc_url_raw($_POST['eeBFEL_Redirect']); // Use esc_url_raw for saving URLs to the database
				
				if (wp_http_validate_url($eeBFEL_Redirect)) { // WordPress URL validation function
					update_option('eeBFEL_Redirect', $eeBFEL_Redirect);
				} else {
					// Display error message if URL is not valid
					echo '<div class="error"><p>Invalid redirect URL provided.</p></div>';
				}
			}
			
			if (isset($_POST['eeBFEL_DenyRoles']) && is_array($_POST['eeBFEL_DenyRoles'])) {
				foreach ($_POST['eeBFEL_DenyRoles'] as $key => $role) {
					$eeBFEL_DenyRoles .= sanitize_text_field($role) . ',';
				}
				$eeBFEL_DenyRoles = trim($eeBFEL_DenyRoles, ','); // Strip last comma
				update_option('eeBFEL_DenyRoles', $eeBFEL_DenyRoles); // Store the string
			} else {
				update_option('eeBFEL_DenyRoles', 'NO'); // Don't hide the back-end
			}
			
		} else {
			// Display error message if nonce verification fails
			echo '<div class="error"><p>Nonce verification failed. Please try again.</p></div>';
		}
	}
	
	// Display the form
	?>
	<div class="wrap">
		<h1>Basic Front-End Login Settings</h1>
		<form method="post">
			<?php wp_nonce_field('ee-basic-front-end-login', 'ee-basic-front-end-login-nonce'); ?>

			<table class="form-table">
				<tr valign="top">
					<th scope="row">Redirect URL</th>
					<td>
						<input type="text" name="eeBFEL_Redirect" value="<?php 
						
						if($eeBFEL_Redirect) {
							echo esc_attr($eeBFEL_Redirect);
						} else {
							echo site_url();
						}
						
						?>" />
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Deny Access to Roles</th>
					<td>
						<?php 
						foreach ($wp_roles->roles as $role_slug => $role) {
							if(esc_attr($role_slug != 'administrator')) {
								echo '<label><input type="checkbox" name="eeBFEL_DenyRoles[]" value="' . esc_attr($role_slug) . '" ' . (in_array($role_slug, explode(',', $eeBFEL_DenyRoles)) ? 'checked="checked"' : '') . ' /> ' . esc_html($role['name']) . '</label><br />';
							}
						}
						?>
					</td>
				</tr>
			</table>
			
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}



?>