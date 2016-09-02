<?php
/**
 * Plugin Name: Yet Another Login Widget (YALW)
 * Description: This widget is plain and simple and allows you to handle logins and password retrieval without a separate login screen. Install, add widget, done. Well, maybe not quite. You may want to modify the stylesheet a little bit to match your theme's needs...
 * Version: 0.16
 * Author: Oliver Tacke
 * Author URI: http://www.olivertacke.de
 * Text Domain: YALW
 * Domain Path: /languages/
 * License: WTFPL
 * License URI: http://www.wtfpl.net/about/
 *
 * TODO: implement sending all messages with error status to the admin
 *       (maybe in a later version)
 * TODO: add password strength detector (maybe in a later version),
 *       see display.php/display_new_password_form() 
 */

/*
 * This widget rebuilds quite a bunch of features offered by wp-login.php instead
 * of using the features therein because it is hardly possible.
 * I had the option to refactor the file wp-login.php, making its functionality
 * better accessible from the outside, and thus contributing to the Wordpress Core.
 * I thought about that possibility for approximately 0.0897 seconds (which is a
 * long time for an android) but decided against. I am merely a casual programmer
 * and I don't have the time or ambition to deal with the project management
 * processes of the Wordpress development community in order to promote my
 * changes, hoping they might be integrated some day. I need to get shit done.
 * And, more importantly, I don't consider myself a good programmer. It's
 * probably not advisable for me to tinker with such a crucial part of Wordpress.
 *
 * Thanks to edik [https://profiles.wordpress.org/plocha/] for his support and
 * to akoww [https://github.com/akoww] for fixing bugs!
 */

namespace YALW;

// As suggested by the Wordpress Community
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// display.php contains all functions for sending widget output the the browser
require_once( __DIR__ . '/display.php' );

// handlers.php contains all general handler functions and their sub fuctions
require_once( __DIR__ . '/handlers.php' );

// session.php contains all functions to control the session
require_once( __DIR__ . '/session.php' );

// settings.php contains all functions for the YALW settings
require_once( __DIR__ . '/settings.php' );

/**
 * Main class.
 *
 * Initializes the widget, sends interactions to control handlers, and displays
 * the results.
 *
 * @package YALW
 * @since 0.1
 */
class YALW extends \WP_Widget {
	/**
     * Sets up the widgets name etc
	 */
    public function __construct() {
        wp_enqueue_style( 'YALW', plugins_url( 'css/yalw.css', __FILE__ ) );
		parent::__construct(
                'YALW',
                __( 'Yet Another Login Widget', 'YALW' ),
                array( 'description' => __( 'A simple login widget', 'YALW' ), ) );
    }
	
	/**
	 * Output the content of the widget
	 *
	 * @global int|string $user_login user ID, slug, email address, or login name
	 *
	 * @param array $args display arguments
	 * @param array $instance The settings for the particular instance of the widget
	 */
	public function widget( $args, $instance ) {
		global $user_login;

		echo $args['before_widget'];

		// display the widget title
		echo $args['before_title'];
		Display::display_widget_title( $user_login, $instance );
		echo $args['after_title'];

		/*
		 * Depending on the state of the 'YALW_action' value stored in the
		 * session cookie we present different options
		 */
		if ( ! is_user_logged_in() ) {
			$events = new \WP_Error();
			switch ( Session::get_next_widget_task() ) {
				case 'retrieve_code':
					Display::display_code_retrieval_form();
					break;
				case 'check_code':
					Display::display_code_entry_form();
					break;
				case 'enter_new_password':
					Display::display_new_password_form();
					break;
				default:
					Display::display_login_form();
			}
			Session::clean_next_widget_task();
		} else {
			/*
			 * The user is logged in and we give him/her the options to either
			 * go to the Dashboard or to logout.
			 * The wp_logout hook takes care of deleting the session cookie
			 */
			Display::display_logged_in_options();
		}
	
		echo $args['after_widget'];
	}
}

/**
 * Control the flow of the login process
 *
 * Depending on the state of the widget, this function uses session variables
 * to store values
 */
function control_login() {
	/*
	 * Mixing GET and POST variables feels a little awkward. I'd prefer POST
	 * variables only, but you cannot set them via HTML links and a button for
	 * the link would look plain ugly, I think :-/
	 */
	
	// set session action to show password retrieval form
	if ( ! empty( $_GET['action'] ) ) {
		if ( $_GET['action'] == 'retrieve_code' ) {
			Session::set_next_widget_task( 'retrieve_code' );
		}
	}

	// Oh, master, what is thy desire?
	if ( ! empty( $_POST['YALW_option'] ) ) {
		switch ( $_POST['YALW_option'] ) {
			case 'YALW_user_login':
				$events = Handlers::handle_login();
				break;
			case 'retrieve_code':
				$events = Handlers::handle_code_retrieval();
				break;
			case 'YALW_enter_code':
				$events = Handlers::handle_reset_code();
				break;
			case 'YALW_reset_password':
				$events = Handlers::handle_reset_password();
				break;
			default:
				$events = null; // should not be necessary, but who knows...
		}
	}
	// store any error that may have occured for Display
	if ( ! empty( $events ) ) {
		Session::set_events( $events );
		
		/*
		 * TODO: Implement an option that will allow admins to receive
		 * a notification if an exception occured such as an unexpected
		 * internal error, problems with the database or a likely attack
		 */
	}
}

/**
 * add the widget
 */
function register_YALW_widget() {
	register_widget( 'YALW\YALW' );
}

/**
 * initialize the widget
 */
function init_widget() {
	// Yes, we're actually supporting multiple languages
	load_plugin_textdomain( 'YALW', false, basename( dirname( __FILE__ ) ) . '/languages' );
	control_login();
}

// Let's add some action :-)
add_action( 'widgets_init', 'YALW\register_YALW_widget' );
add_action( 'init', 'YALW\Session::start_session', 1 );
add_action( 'wp_logout', 'YALW\Session::end_session' );
add_action( 'wp_login', 'YALW\Session::end_session' );
add_action( 'init', 'YALW\init_widget' );

if ( is_admin() ) {
	$settings = new Settings;
}
