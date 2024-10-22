<?php // Mitchell Bennis | Element Engage, LLC | mitch@elementengage.com

defined( 'ABSPATH' ) or die( 'No direct access is allowed' );
if ( ! wp_verify_nonce( $eeNonce, 'eeInclude' ) ) exit('Noncence!'); // Exit if nonce fails



// The Admin Menu
function eeBFEL_AdminMenu() {

	add_users_page(
		__('Basic Front-End Login Form', 'basic-front-end-login'), // Page Title
		__('Login Form', 'basic-front-end-login'), // Menu Title
		'manage_options', // User status required to see the menu
		eeBFEL_PluginSlug, // Slug
		'eeBFEL_AdminPage' // Function that displays the menu page
	);
}


// Admin <head> Additions
function eeBFEL_AdminHead($eeHook) {
	
	// wp_die($eeHook);
	
	wp_enqueue_script('jquery');
	
	$eeHooks = array(
		'users_page_basic-front-end-login'
	);
	
	if(in_array($eeHook, $eeHooks)) {
		
		wp_enqueue_style( 'basic-front-end-login-back-css', plugins_url() . '/' . eeBFEL_PluginSlug . '/css/style-back.css', '', eeBFEL_Version );
		wp_enqueue_script('basic-front-end-login-back-js', plugins_url() . '/' . eeBFEL_PluginSlug . '/js/scripts.js', array('jquery'), null, TRUE);
	}
}



// Function to Display the Login Form
function eeBFEL_Shortcode( $eeBFEL_Attributes ) {
	
	wp_enqueue_style('basic-front-end-login-front', plugin_dir_url(__FILE__) . 'css/style-front.css', '', eeBFEL_Version);
	
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
		$eeOutput = '<a href="' . wp_logout_url() . '">' . __('Logout', 'basic-front-end-login') . '</a>';
	
	} else {
		
		// Get the login form
		$eeOutput = wp_login_form($eeFormArgs);
	}
	
	return $eeOutput;
}


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


// Admin Page
function eeBFEL_AdminPage() {
	
	global $wp_roles;
	
	$eeOutput = '';
	
	// Default values
	$eeBFEL_Redirect = get_option('eeBFEL_Redirect');
	$eeBFEL_DenyRoles = get_option('eeBFEL_DenyRoles');

	// Check if POST data has been sent
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		
		// Check nonce for security
		if (check_admin_referer('basic-front-end-login', 'basic-front-end-login-nonce')) {
			
			$eeBFEL_DenyRoles = '';
			
			if (isset($_POST['eeBFEL_Redirect'])) {
				
				$eeBFEL_Redirect = esc_url_raw($_POST['eeBFEL_Redirect']); // Use esc_url_raw for saving URLs to the database
				
				if (wp_http_validate_url($eeBFEL_Redirect)) { // WordPress URL validation function
					
					update_option('eeBFEL_Redirect', $eeBFEL_Redirect);
				
				} elseif(!$eeBFEL_Redirect) {
				
					delete_option('eeBFEL_Redirect');
					
				} else {
					
					$eeOutput .= '<div class="error"><p>Invalid redirect URL provided.</p></div>';
				}
			} else {
					
				delete_option('eeBFEL_Redirect');
					
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
			$eeOutput .= '<div class="error"><p>Nonce verification failed. Please try again.</p></div>';
		}
	}
	
	// Display the form...
	
	$eeOutput .= '<div class="wrap">
	
	<form id="eeBFEL_Settings" action="' . admin_url() . 'users.php?page=basic-front-end-login" method="POST">';
		
		// Add Nonce for Security
		$eeOutput .= wp_nonce_field( 'basic-front-end-login', 'basic-front-end-login-nonce', TRUE, FALSE);	
		
		$eeBFEL_Redirect = get_option('eeBFEL_Redirect');
		$eeBFEL_DenyRoles = get_option('eeBFEL_DenyRoles');
			
		// Form HTML
		$eeOutput .= '
		
		<fieldset>
	
		<h1>' . __('Basic Front-End Login Form', 'basic-front-end-login') . '</h1>
		
		<p>' . 
		__('This plugin provides you with a basic front-end login form for any page, post or widget.', 'basic-front-end-login') . ' ' . __('It will also redirect to the page you choose.') . ' ' .
		__('It also blocks access to the back-end and hides the Admin Bar.', 'basic-front-end-login') . ' </p><p>' .
		__('To display the login form, place this shortcode on any page, post, or widget:', 'basic-front-end-login') . ' <strong>[eeBFEL]</strong>
		</p>
		
		<p><input type="text" id="eeBFEL_Shortcode" value="[eeBFEL]" readonly>
			<button class="button" id="eeBFEL_CopyShortcode">Copy Shortcode</button>
		</p>

		
		</fieldset>
		<fieldset>
		
		<h2>' . __('Redirect URL', 'basic-front-end-login') . '</h2>
		
		<label for="eeBFEL_Redirect">' . __('Default Login Redirect', 'basic-front-end-login') . '</label>
		<input type="url" name="eeBFEL_Redirect" value="' . $eeBFEL_Redirect . '" id="eeBFEL_Redirect" size="64" />
		<div class="eeNote">' . __('After login, go to this page.', 'basic-front-end-login') . '<br />' .
		__('You can over-ride this to create multiple login forms by using this shortcode attribute:', 'basic-front-end-login') . '<br/>
		[eeBFEL redirect="https://website.com/your-files-page/"]</div>
		
		</fieldset>
		<fieldset>
		
		<h2>' . __('Restrict Dashboard Access', 'basic-front-end-login') . '</h2>
				
		<p>' . __('This setting is for when you want your users to be logged-in, but do not want them to have access to the Wordpress Dashboard.', 'basic-front-end-login') . ' </p>
		<p><button type="button" id="eeBFEL_checkAll">' . __('Check All', 'basic-front-end-login') . '</button> 
		<button type="button" id="eeBFEL_uncheckAll">' . __('Uncheck All', 'basic-front-end-login') . '</button><p>';
		
		foreach ($wp_roles->roles as $role_slug => $role) {
			if(esc_attr($role_slug != 'administrator')) {
				$eeOutput .= '<label class="eeBFEL_DenyRoleCheck"> ' . esc_html($role['name']) . 
				'<input type="checkbox" name="eeBFEL_DenyRoles[]" value="' . esc_attr($role_slug) . '" ' . (in_array($role_slug, explode(',', $eeBFEL_DenyRoles)) ? 'checked="checked"' : '') . ' />
				</label>';
			}
		}
			
		$eeOutput .= '
		<div class="eeNote">' . __('Checked roles will not see the Admin Bar or be allowed to access the Dashboard.', 'basic-front-end-login') . '</div>
		
		</fieldset>
		
		<fieldset>
			<input type="submit"name="eeBFEL_Save" id="eeBFEL_Save" value="' . __('SAVE', 'basic-front-end-login') . '" />
		</fieldset>
		
		<fieldset id="eeBFEL_Footer">
			<p><a href="https://simplefilelist.com/basic-front-end-login/">' . __('Basic Front End Login', 'basic-front-end-login') . '</a> (' . __('Version', 'basic-front-end-login') . ': ' . eeBFEL_Version . ') | ' . __('Plugin by', 'basic-front-end-login') . ' <a href="https://elementengage.com" target="_blank">Element Engage, LLC</a><br />
				<a href="https://elementengage.com/shop/plugin-donation/">' . __('Please donate if you find this plugin useful.', 'basic-front-end-login') . '</a></p>
		</fieldset>
	</form>
	
	</div>';
	
	echo $eeOutput;

}

?>