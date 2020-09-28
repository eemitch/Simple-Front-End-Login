=== Plugin Name ===
Contributors: eemitch
Donate link: https://elementengage.com/shop/plugin-donation/
Tags: user login
Requires at least: 5.1
Tested up to: 5.6
Requires PHP: 7.2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Adds a basic front-end login for to any page, post or widget and redirects to the page you choose.


== Description ==
 
Adds a basic front-end login for to any page, post or widget and redirects to the page you choose. It also can block access to the back-end and hides the Admin Bar. This plugin is for when you want your users to be logged-in, but do not want them to have access to the Wordpress Dashboard.

To display the login form, place this shortcode on any page, post, or widget:

[eeBFEL]

After the user has logged in, they will be redirected to your home page or the URL you define in the plugin settings.

To define destinations in additional login forms, use the "redirect" attribute to over-ride the default. There is no limit to the number of forms you can use.

[eeBFEL redirect="https://website.com/your-files-page/"]

Deny Dashboard Access

In the plugin settings you can optionally select roles that you want to deny back-end access to. The Admin Bar will not appear and direct back-end access attempts will redirect to your home page. 


== Installation ==
 
Just like every other plugin.
 
== Frequently Asked Questions ==
 
= What is this plugin for? =
 
I wrote this plugin because I needed a front-end login form, but didn't want the users to have any access to the back-end.
 
= Can't the users just type in the dashboard address to reach it? =
 
No. Any user with the chosen roles will be prevented from viewing the Wordpress Dashboard.
 
== Screenshots ==
 
1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
 
== Changelog ==

= 1.1.1 =
* Initial Public Release

= 1.0.2 =
* Expanded for public distribution.
 
= 1.0.1 =
* Basic build for Simple File List, File Access Manager demo login.