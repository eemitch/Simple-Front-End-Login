=== Plugin Name ===
Contributors: eemitch
Donate link: http://elementengage.com/
Tags: user login
Requires at least: 5.1
Tested up to: 5.6
Requires PHP: 7.2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Adds a basic front-end login for to any page, post or widget and redirects to the page you choose.
 
== Description ==
 
Adds a basic front-end login for to any page, post or widget and redirects to the page you choose. It also blocks access to the back-end and hides the Admin Bar. This plugin is for when you want your users to be logged-in, but do not want them to have access to the Wordpress Dashboard.

To display the login form, place this shortcode on any page, post, or widget:

[eeBFEL]

After the user has logged in, they will be redirected to your home page. To define a destination after login, use the "redirect" attribute

[eeBFEL redirect="https://website.com/your-files-page/"]

Place multiple forms for different purposes using different "redirect" attribute values.
 

Basic Plugin Features
 
* Denys Admin access to Wordpress Subscribers.

 
== Installation ==
 
Just like every other plugin.
 
== Frequently Asked Questions ==
 
= What is this plugin for? =
 
I wrote this plugin because I needed a front-end login form, but didn't want the users to have any access to the back-end.
 
= Can't the users just type in the dashboard address to reach it? =
 
No. Any user with the Subscriber role will be prevent from viewing the Wordpress Dashboard.
 
== Screenshots ==
 
1. screenshot-1.jpg
2. screenshot-2.jpg
 
== Changelog ==

= 1.0.2 =
* Expanded for public distribution.
 
= 1.0.1 =
* Basic build for Simple File List, File Access Manager demo login.