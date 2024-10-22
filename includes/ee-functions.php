<?php // Mitchell Bennis | Element Engage, LLC | mitch@elementengage.com

defined( 'ABSPATH' ) or die( 'No direct access is allowed' );
if ( ! wp_verify_nonce( $eeNonce, 'eeInclude' ) ) exit('Noncence!'); // Exit if nonce fails


// Logout Button
function eeBFEL_AddLogoutButton() {
	
	if(get_option('eeBFEL_ShowLogout') == 'YES') {
		
		// Only show the logout button if the user is logged in
		if ( is_user_logged_in() ) {
			
			wp_enqueue_style('basic-front-end-login-front', plugins_url() . '/' . eeBFEL_PluginSlug . '/css/style-front.css', '', eeBFEL_Version);

			$logout_url = esc_url( wp_logout_url( home_url() ) ); // Logout URL
			
			echo '
			<div id="eeBFEL_LogoutButton">
				<a href="' . esc_url($logout_url) . '" class="button" aria-label="' . __('Log out of this website', 'basic-front-end-login') . '">
					' . __('Log Out', 'basic-front-end-login') . '
				</a>
			</div>';

				  
			return TRUE;
		}
	}
	
	return FALSE;
}


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
		wp_enqueue_script('basic-front-end-login-back-js', plugins_url() . '/' . eeBFEL_PluginSlug . '/js/scripts.js', array('jquery'), eeBFEL_Version, TRUE);
	}
}



// Function to Display the Login Form
function eeBFEL_Shortcode( $eeBFEL_Attributes ) {
	
	// Enqueue the login form styles
	wp_enqueue_style('basic-front-end-login-front', plugins_url() . '/' . eeBFEL_PluginSlug . '/css/style-front.css', '', eeBFEL_Version);
	
	// Shortcode Attributes: Use 'redirect' from shortcode or fallback to option or site URL
	$eeAtts = shortcode_atts( array( 'redirect' => '' ), $eeBFEL_Attributes );
	$redirect = esc_url_raw($eeAtts['redirect']); // Sanitize the URL
	
	// Debugging: Check what's being received as the redirect value
	error_log('Shortcode redirect value: ' . $redirect);
	
	// If the redirect from shortcode is empty or invalid, get the saved option value
	if( empty($redirect) || !filter_var($redirect, FILTER_VALIDATE_URL) ) {
		$redirect = esc_url_raw(get_option('eeBFEL_Redirect'));
		
		// Debugging: Check the saved redirect value from settings
		error_log('Saved option redirect value: ' . $redirect);
	}
	
	// If the saved option is also invalid or empty, use the site URL
	if( empty($redirect) || !filter_var($redirect, FILTER_VALIDATE_URL) ) {
		$redirect = site_url();
	}
	
	// Final debug check to see what the final redirect URL is
	error_log('Final redirect value: ' . $redirect);

	// WordPress Login Form Settings
	$eeFormArgs = array(
		'echo'           => FALSE, // Return the login form as a string
		'redirect'       => $redirect, // Set the redirect URL
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
	
	// Check if the user is already logged in
	if (get_current_user_id()) {
		// Show a Logout Link
		$eeOutput = '<a href="' . esc_url(wp_logout_url()) . '">' . __('Logout', 'basic-front-end-login') . '</a>';
	} else {
		// Show the login form
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
	$eeBFEL_ShowLogout = get_option('eeBFEL_ShowLogout');

	// Check if POST data has been sent
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		
		// Check nonce for security
		if (check_admin_referer('basic-front-end-login', 'basic-front-end-login-nonce')) {
			
			// Redirection
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
			
			// Deny Roles
			$eeBFEL_DenyRoles = '';
			if (isset($_POST['eeBFEL_DenyRoles']) && is_array($_POST['eeBFEL_DenyRoles'])) {
				foreach ($_POST['eeBFEL_DenyRoles'] as $role) {
					// Validate that the role exists before saving
					if (wp_roles()->is_role(sanitize_text_field($role))) {
						$eeBFEL_DenyRoles .= sanitize_text_field($role) . ',';
					}
				}
				$eeBFEL_DenyRoles = trim($eeBFEL_DenyRoles, ','); // Strip last comma
				update_option('eeBFEL_DenyRoles', $eeBFEL_DenyRoles); // Store the string
			} else {
				update_option('eeBFEL_DenyRoles', 'NO'); // Don't hide the back-end
			}

			
			
			// Handle show logout button option
			if (isset($_POST['eeBFEL_ShowLogout']) && $_POST['eeBFEL_ShowLogout'] == 'YES') {
				update_option('eeBFEL_ShowLogout', 'YES');
			} else {
				update_option('eeBFEL_ShowLogout', 'NO');
			}
			
		} else {
			// Display error message if nonce verification fails
			$eeOutput .= '<div class="error"><p>Nonce verification failed. Please try again.</p></div>';
		}
	}
	
	// Display the form...
	
	$eeOutput .= '<div class="wrap">
		
		<form id="eeBFEL_Settings" action="' . esc_url(admin_url('users.php?page=basic-front-end-login')) . '" method="POST" aria-labelledby="eeBFEL_SettingsTitle">';
			
			// Add Nonce for Security
			$eeOutput .= wp_nonce_field('basic-front-end-login', 'basic-front-end-login-nonce', true, false);    
			
			$eeBFEL_Redirect = esc_url(get_option('eeBFEL_Redirect'));
			$eeBFEL_DenyRoles = get_option('eeBFEL_DenyRoles');
			$eeBFEL_ShowLogout = get_option('eeBFEL_ShowLogout');
				
			// Form HTML
			$eeOutput .= '
			
			<fieldset>
				<h1 id="eeBFEL_SettingsTitle" aria-live="polite">' . __('Basic Front-End Login Form', 'basic-front-end-login') . '</h1>
			
				<p aria-describedby="eeBFEL_ShortcodeDesc">' . 
				__('This plugin provides you with a basic front-end login form for any page, post or widget.', 'basic-front-end-login') . ' ' . __('It will also redirect to the page you choose.') . ' ' .
				__('It also blocks access to the back-end and hides the Admin Bar.', 'basic-front-end-login') . ' </p><p>' .
				__('To display the login form, place this shortcode on any page, post, or widget:', 'basic-front-end-login') . '
				</p>
			
				<p>
					<label for="eeBFEL_Shortcode">' . __('Shortcode', 'basic-front-end-login') . ':</label>
					<input type="text" id="eeBFEL_Shortcode" value="[eeBFEL]" readonly aria-label="' . __('Shortcode for the login form', 'basic-front-end-login') . '">
					<button class="button" id="eeBFEL_CopyShortcode" aria-label="' . __('Copy shortcode to clipboard', 'basic-front-end-login') . '">' . __('Copy Shortcode', 'basic-front-end-login') . '</button>
				</p>
	
			</fieldset>
			
			<fieldset>
			
			<h1>' . __('Options', 'basic-front-end-login') . '</h1>
			
			<h2 id="eeBFEL_RedirectHeading">' . __('Redirect URL', 'basic-front-end-login') . '</h2>
			
			<label for="eeBFEL_Redirect" aria-describedby="eeBFEL_RedirectDesc">' . __('Redirect URL', 'basic-front-end-login') . '</label>
			<input type="url" name="eeBFEL_Redirect" value="' . esc_attr($eeBFEL_Redirect) . '" id="eeBFEL_Redirect" size="64" aria-labelledby="eeBFEL_RedirectHeading" />
			<div id="eeBFEL_RedirectDesc" class="eeNote">' . __('After login, go to this page. You can override this by using the "redirect" attribute in the shortcode.', 'basic-front-end-login'). '<br />' .
			__('You can over-ride this to create multiple login forms by using this shortcode attribute:', 'basic-front-end-login') . '<br/>
			[eeBFEL redirect="https://website.com/your-files-page/"]' . '</div>
			
			<hr />
			
			<h2 id="eeBFEL_DenyRolesHeading">' . __('Restrict Dashboard Access', 'basic-front-end-login') . '</h2>
			
			<p aria-labelledby="eeBFEL_DenyRolesHeading">' . __('Select roles that should be denied access to the Dashboard.', 'basic-front-end-login') . ' ' . __('This setting is for when you want your users to be logged-in, but do not want them to have access to the Wordpress Dashboard.', 'basic-front-end-login') . '</p>
			';
			
			foreach ($wp_roles->roles as $role_slug => $role) {
				if ($role_slug !== 'administrator') {
					$eeOutput .= '
					<label class="eeBFEL_DenyRoleCheck" aria-label="' . esc_attr($role['name']) . '">
					<input type="checkbox" name="eeBFEL_DenyRoles[]" value="' . esc_attr($role_slug) . '" ' . checked(in_array($role_slug, explode(',', $eeBFEL_DenyRoles)), true, false) . ' aria-describedby="eeBFEL_DenyRolesDesc" />
					' . esc_html($role['name']) . '</label>';
				}
			}
			
			$eeOutput .= '
			<p>
				<button type="button" id="eeBFEL_checkAll" aria-label="' . __('Check all roles', 'basic-front-end-login') . '">' . __('Check All', 'basic-front-end-login') . '</button>
				<button type="button" id="eeBFEL_uncheckAll" aria-label="' . __('Uncheck all roles', 'basic-front-end-login') . '">' . __('Uncheck All', 'basic-front-end-login') . '</button>
			</p>
			
			<div id="eeBFEL_DenyRolesDesc" class="eeNote">' . __('Selected roles will not see the Admin Bar or access the Dashboard.', 'basic-front-end-login') . '</div>
			
			<hr />
			
			<h2 id="eeBFEL_ShowLogoutHeading">' . __('Logout Button', 'basic-front-end-login') . '</h2>
						
			<label for="eeBFEL_ShowLogout" aria-labelledby="eeBFEL_ShowLogoutHeading eeBFEL_ShowLogoutDesc">
				<input type="checkbox" name="eeBFEL_ShowLogout" id="eeBFEL_ShowLogout" value="YES" ' . checked($eeBFEL_ShowLogout, 'YES', false) . ' />
				' . __('Show Logout Button', 'basic-front-end-login') . '
			</label>
			
			<div id="eeBFEL_ShowLogoutDesc" class="eeNote">
				' . __('Show a "Log Out" button at the bottom-right corner of each page for logged-in users.', 'basic-front-end-login') . '<br/>
				ID: eeBFEL_LogoutButton
			</div>
			
			<hr />

			
			<input type="submit" name="eeBFEL_Save" id="eeBFEL_Save" class="button button-primary" value="' . __('Save Settings', 'basic-front-end-login') . '" aria-label="' . __('Save form settings', 'basic-front-end-login') . '" />
			</fieldset>
			
			<fieldset id="eeBFEL_Footer">
				<p><strong>' . __('Basic Front-End Login', 'basic-front-end-login') . '</strong></p>
				<p><a href="https://wordpress.org/plugins/basic-front-end-login/">' . __('Version', 'basic-front-end-login') . '</a><a href="https://wordpress.org/plugins/basic-front-end-login/"> : ' . eeBFEL_Version . '</a> | ' . __('Plugin by', 'basic-front-end-login') . ' <a href="https://elementengage.com" target="_blank">Element Engage, LLC</a>
				</p>
			</fieldset>
		</form>
		
	</div>';
	
	echo $eeOutput;

}

?>