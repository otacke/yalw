# yalw
This widget for Wordpress (see [Wordpress Plugin directory](https://wordpress.org/plugins/yalw/)) is plain and simple and allows you to handle logins and password retrieval without a separate login screen. Install, add widget, 
done. Well, maybe not quite. You may want to modify the stylesheet a little bit to match your theme's needs...

YALW rebuilds quite a bunch of features offered by wp-login.php instead of using the features therein because it is hardly possible. I had the option to
refactor the file wp-login.php, making its functionality better accessible from the outside, and thus contributing to the Wordpress Core. I thought about that
possibility for approximately 0.0897 seconds (which is a long time for an android) but decided against. I am merely a casual programmer and I don't have the
time or ambition to deal with the project management processes of the Wordpress development community in order to promote my changes, hoping they might be
integrated some day. I need to get shit done. And, more importantly, I don't consider myself a good programmer. It's probably not advisable for me to tinker
with such a crucial part of Wordpress.

Thanks to [edik](https://profiles.wordpress.org/plocha/ "edik") for his support and to [akoww](https://github.com/akoww) for fixing bugs!

## Details
* Allows to configure the email message that is sent to reset a lost password.
* Can use fail2ban to detect failed login attempts and act accordingly. A sample filter and a sample jail description are included.
* Is localizable.

## Screenshots
On the right you can see the YALW widget for login on a plain Twentysixteen theme.
![login widget](https://github.com/otacke/yalw/blob/master/assets/screenshot-1.png "login widget")

You can configure an individual email message that is sent to reset a lost password.
![settings](https://github.com/otacke/yalw/blob/master/assets/screenshot-2.png "settings")