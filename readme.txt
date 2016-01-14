=== YALW ===
Contributors: otacke
Donate link: https://donate.childsplaycharity.org/572f2e6c3a9e561ea1f51f573ca68c0c/
Tags: login, widget
Requires at least: 4.0
Tested up to: 4.4.1
Stable tag: 4.4
License: WTFPL – Do What the Fuck You Want to Public License
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

Thanks to [edik](https://profiles.wordpress.org/plocha/ "edik") for his support!

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

1. On the left you can see the YALW widget for login.
2. You can configure an individual email message that is sent to reset a lost password.

== Changelog ==

= 0.11 =
* Initial public version (English + locale files for German).


== Upgrade Notice ==

= 0.11 =
Initial public version.