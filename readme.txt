=== YALW ===
Contributors: otacke
Donate link: https://donate.childsplaycharity.org/572f2e6c3a9e561ea1f51f573ca68c0c/
Tags: login, widget, authentification
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 0.16.2
License: WTFPL - Do What the Fuck You Want to Public License
License URI: http://www.wtfpl.net/

This widget is plain and simple and allows you to handle logins and password retrieval without a separate login screen. Install, add widget, done.

== Description ==

This widget is plain and simple and allows you to handle logins and password retrieval without a separate login screen. Install, add widget, done. Well, maybe
not quite. You may want to modify the stylesheet a little bit to match your theme's needs...

YALW rebuilds quite a bunch of features offered by wp-login.php instead of using the features therein because it is hardly possible. I had the option to
refactor the file wp-login.php, making its functionality better accessible from the outside, and thus contributing to the Wordpress Core. I thought about that
possibility for approximately 0.0897 seconds (which is a long time for an android) but decided against. I am merely a casual programmer and I don't have the
time or ambition to deal with the project management processes of the Wordpress development community in order to promote my changes, hoping they might be
integrated some day. I need to get shit done. And, more importantly, I don't consider myself a good programmer. It's probably not advisable for me to tinker
with such a crucial part of Wordpress.

Thanks to [edik](https://profiles.wordpress.org/plocha/ "edik") for his support and to [akoww](https://github.com/akoww) for fixing bugs!

= Details =
* Allows to configure the email message that is sent to reset a lost password.
* Can use fail2ban to detect failed login attempts and act accordingly. A sample filter and a sample jail description are included.
* Is localizable.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Use the Settings->YALW screen to configure the plugin.
1. Go to the Appearance->Widgets screen and assign the Widget "Yet Another Login Widget" to your preferred location.


== Frequently Asked Questions ==

None so far.


== Screenshots ==

1. On the right you can see the YALW widget for login on a plain Twentysixteen theme.
2. You can configure an individual email message that is sent to reset a lost password.

== Changelog ==

= 0.16 =
* fixed a bug that can occur with PHP versions prior to 5.5

= 0.15 =
* checked if YALW runs on Wordpress 4.6
* revamped the German localization
* fixed a bug with setting an individual email text

= 0.14 =
* added uninstall.php in order to clean the database options after uninstall

= 0.13 =
* added header information neccessary for proper Wordpress localization
* improved settings page

= 0.12 =
* now logs whenever a code was entered wrong instead of only logging too many wrong attempts.
* improved security: autocomplete in password field not allowed anymore
* improved security: sets HttpOnly flag to mitigate the risk of client side script accessing the protected cookie
* fixed a bug that prevented YALW to run with BuddyPress.
* fixed a bug with fail2ban.

= 0.11 =
* Initial public version (English + locale files for German).


== Upgrade Notice ==

= 0.16 =
Upgrade if you use PHP prior to version 5.5.

= 0.15 =
Upgrade if you need an individual message for emails sent to users for resetting a password.

= 0.14 =
Upgrade if you like your database clean.

= 0.13 =
Upgrade if you plan to create locale files for YALW.

= 0.12 =
Upgrade if you need improvements for working with fail2ban or if you're runnung BuddyPress.

= 0.11 =
Initial public version.
