<?php
/*
 * Just in case we think of doing some fancy CSS stuff or mindblowing
 * JavaScript shit or whatever in the future, we give every item an id.
 */

namespace YALW;

/**
 * Display functions for the widget 
 *
 * @package YALW
 * @since 0.5
 */
class Display {
	/**
	 * Display the widget title
	 *
	 * If the user is already logged in, we give him/her a warm welcome by
	 * addressing him by name.
	 *
	 * @param string $user_login user ID, slug, email address, or login name
	 * @param array $instance The settings for the particular instance of the widget	 
	 */
	static function display_widget_title( $user_login, $instance ) {
		/*
		 * TODO: Would it be better to let the user set a title? If yes, we
		 * need to change this part and add an option to the settings
		 * 
		 */
		if ( is_user_logged_in() ) {
			$user_data = get_user_by( 'login', $user_login );
			if ( ! $user_data ) {
				$widget_title = esc_attr( __( 'Welcome', 'YALW' ) ) . '!';
			} else {
				if ( ( ! empty( $user_data->first_name ) )
						&& ( ! empty( $user_data->last_name ) )
				) {
					$widget_title = esc_attr( __( 'Welcome', 'YALW' ) ) . ' ' . 
							$user_data->first_name . ' ' . $user_data->last_name . '!';
				} else {
					$widget_title =	esc_attr( __( 'Welcome', 'YALW' ) ) . ' ' .	$user_login . '!';
				}
			}
		} else {
			if ( ! empty( $instance['title'] ) ) {
				$widget_title = apply_filters( 'widget_title', $instance['title'] );
			} else {
				$widget_title = esc_attr( __( 'Login', 'YALW' ) );
			}
		}
		echo $widget_title;
	}
	
	/**
	 * Display the login form
	 */
	static function display_login_form() {
		echo '<div id="YALW_widget">';
		echo '<form name="YALW_login_form" id="YALW_login_form" method="post" action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '">';
		wp_nonce_field( 'login_form', 'yalw_nonce' );
		echo '<input type="hidden" name="YALW_option" value="YALW_user_login" />';
		echo '<input type="hidden" name="YALW_redirect" value="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" />';
		
		echo '<div class="YALW_label_container">';
		echo '<label id="YALW_user_login_label" for="YALW_user_login" class="YALW_label">' . esc_attr( __( 'Username', 'YALW' ) ) . '</label>';
		echo '</div>';
		
		echo '<div class="YALW_input_container">';
		echo '<input type="text" name="YALW_user_login" id="YALW_user_login" class="YALW_input" size="20" value="' . esc_attr( Session::get_user_login() ) . '" required="required" />';
		echo '</div>';

		echo '<div class="YALW_label_container"><label id="YALW_user_password_label" for="YALW_user_password" class="YALW_label">' . esc_attr( __( 'Password', 'YALW' ) ) . '</label></div>';
		echo '<div class="YALW_input_container"><input type="password" name="YALW_user_password" id="YALW_user_password" class="YALW_input" size="20" required="required" autocomplete="off" /></div>';
		
		do_action( 'login_form' );
		
		// show option to remember the user after login if chosen in settings
		$options = get_option( 'yalw_option' );
		if ( isset( $options['widget_rememberme'] ) && $options['widget_rememberme'] == 1 ) {
			echo '<div id="YALW_rememberme_container">';
			echo '<label id="YALW_rememberme_label" for="YALW_rememberme" class="YALW_label"><input name="YALW_rememberme" type="checkbox" id="YALW_rememberme" value="forever" /> ' . esc_attr( __( 'Remember Me', 'YALW') ) . '</label>';
			echo '</div>';
		}
		
		echo '<div class="YALW_submit_container">';
		echo '<input type="submit" name="YALW_submit" id="YALW_submit_login" class="button button-primary button-large" value="' . esc_attr( __( 'Login', 'YALW' ) ). '" />';
		echo '</div>';
		
		echo '<div id="YALW_credentials_lost_link_container">';
		// keep all GET variables Wordpress might have used so far and add our action trigger
		echo '<a href="' . esc_html( $_SERVER['PHP_SELF'] . '?' . http_build_query( array_merge( $_GET, array( 'action' => 'retrieve_code' ) ) ) ) . '">' . esc_attr( __( 'Lost your credentials?', 'YALW' ) ) . '</a>';
		echo '</div>';
		
		echo '</form>';
		echo '</div>';
		Display::display_error_messages();	
	}
	
	/**
	 * Display password retrieval form
	 */
	static function display_code_retrieval_form() {
		echo '<div id="YALW_widget">';
		echo '<form name="YALW_password_form" id="YALW_password_form" method="post" action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '">';
		wp_nonce_field( 'code_retrieval_form', 'yalw_nonce' );
		echo '<input type="hidden" name="YALW_option" value="retrieve_code" />';
		
		echo '<div class="YALW_label_container">';
		echo '<label id="YALW_user_login_label" for="YALW_user_login" class="YALW_label">' . esc_attr( __( 'Username or E-mail:', 'YALW' ) ) . '</label>';
		echo '</div>';
		
		echo '<div class="YALW_input_container">';
		echo '<input type="text" name="YALW_user_login" id="YALW_user_retrieval_login" class="YALW_input" size="20" value="' . esc_attr( Session::get_user_login() ) . '" required="required" />';
		echo '</div>';
		
		echo '<div class="YALW_submit_container">';
		echo '<input type="submit" name="YALW_submit" id="YALW_submit_retrieval" class="button button-primary button-large" value="' . esc_attr( __( 'Get New Password', 'YALW' ) ) . '" />';
		echo '</div>';
		
		echo '</form>';
		echo '</div>';
		Display::display_error_messages();
	}
	
	/**
	 * Display code entry form
	 */
	static function display_code_entry_form() {
		echo '<div id="YALW_widget">';
		echo '<form name="YALW_code_entry_form" id="YALW_code_entry_form" method="post" action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '">';
		wp_nonce_field( 'code_entry_form', 'yalw_nonce' );
		echo '<input type="hidden" name="YALW_option" value="YALW_enter_code" />';
		
		echo '<div class="YALW_label_container">';
		echo '<label id="YALW_code_label" for="YALW_code" class="YALW_label">' . esc_attr( __( 'Reset-Code', 'YALW' ) ) . '</label>';
		echo '</div>';
		
		echo '<div class="YALW_input_container">';
		echo '<input type="text" name="YALW_code" id="YALW_code" class="YALW_input" size="20" required="required" />';
		echo '</div>';
		
		echo '<div class="YALW_submit_container">';
		echo '<input type="submit" name="YALW_submit" id="YALW_submit_code" class="button button-primary button-large" value="' . esc_attr( __( 'Check code', 'YALW' ) ) . '" />';
		echo '</div>';

		echo '</form>';
		echo '</div>';
		Display::display_error_messages();
	}	

	/**
	 * Display new password form
	 */
	static function display_new_password_form() {
		echo '<div id="YALW_widget">';
		echo '<form name="YALW_new_password_form" id="YALW_new_password_form" method="post" action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '">';
		wp_nonce_field( 'new_password_form', 'yalw_nonce' );
		// Prevent user's from obtaining rights of other users
		echo '<input type="hidden" name="YALW_code" value="' . esc_attr ( $_POST['YALW_code'] ) . '" />';
		echo '<input type="hidden" name="YALW_option" value="YALW_reset_password" />';
		echo '<input type="hidden" name="YALW_redirect" value="' . esc_attr( $_SERVER['REQUEST_URI'] ) . '" />';
		
		echo '<div class="YALW_label_container">';
		echo '<label id="YALW_new_password_label" for="YALW_new_password" class="YALW_label">' . esc_attr( __( 'New password', 'YALW' ) ) . '</label>';
		echo '</div>';
		
		echo '<div class="YALW_input_container">';
		echo '<input type="password" name="YALW_new_password" id="YALW_new_password" class="YALW_input" size="20" required="required" autocomplete="off" />';
		echo '</div>';

		echo '<div class="YALW_label_container">';
		echo '<label id="YALW_control_password_label" for="YALW_control_password" class="YALW_label">' . esc_attr( __( 'Retype password', 'YALW' ) ) . '</label>';
		echo '</div>';
		
		echo '<div class="YALW_input_container">';
		echo '<input type="password" name="YALW_control_password" id="YALW_control_password" class="YALW_input" size="20" required="required" autocomplete="off" />';
		echo '</div>';
		
		echo '<div class="YALW_submit_container">';
		echo '<input type="submit" name="YALW_submit" id="YALW_submit_new_password" class="button button-primary button-large" value="' . esc_attr( __( 'Reset password and login', 'YALW' ) ) . '" />';
		
		/*
		 * TODO: implement a password strength indicator (maybe)
		 */
		echo '</div>';
		
		echo '</form>';
		echo '</div>';
		Display::display_error_messages();
	}
	
	/**
	 * Display options when logged in
	 */
	static function display_logged_in_options() {
		echo '<div id="YALW_widget">';
		echo '<ul id="YALW_login_options">';
		echo '<li id="YALW_dashboard_link">';
		echo '<a href="' . esc_url( admin_url() ) . '" title="' . esc_attr( __( 'Dashboard' , 'YALW' ) ) . '">' . esc_attr( __( 'Dashboard' , 'YALW' ) ) .'</a>';
		echo '</li>';
		echo '<li id="YALW_logout_link">';
		echo '<a href="' . esc_url( wp_logout_url( $_SERVER['REQUEST_URI'] ) ) . '" title="' . esc_attr( __( 'Logout' , 'YALW' ) ) . '">' . esc_attr( __( 'Logout' , 'YALW' ) ) . '</a>';
		echo '</li>';
		echo '</ul>';
		echo '</div>';
	}
	
	/**
	 * Display events that have occured grouped by type
	 */
	private static function display_error_messages() {
		$events = Session::get_events();
		if ( ( ! empty( $events ) ) && ( is_wp_error( $events ) ) ) {
			$notifications = Display::prepare_events_for_display( $events );
			foreach ( $notifications as $type => $message ) {
				if ( ! empty( $message ) ) {
					echo '<div id="YALW_' . $type . '" class="YALW_' . $type . '_container">' . apply_filters( 'login_errors', $message ) . "</div>\n";
				}
			}
			Session::clean_events();
		}
	}

	/**
	 * sorts the events by type
	 *
	 * @param WP_Error $events the events
	 * @return array array sorted by types
	 */
	private static function prepare_events_for_display( $events ) {
		/*
		 * in future versions, this might possibly become more elegant as the
		 * event types could also be made flexible, not hard coded
		 */
		$information = '';
		$warnings    = '';
		$exceptions  = '';
		 
		if ( $events instanceof \WP_Error ) {
			foreach ( $events->get_error_codes() as $code ) {
				$event_type = $events->get_error_data( $code );
				foreach ( $events->get_error_messages( $code ) as $event_message ) {
					/*
					 * use event types as defined in ITIL V3 event management for
					 * color coding via CSS
					 */
					switch ( $event_type ) {
						case 'info':
							$information .= ' ' . $event_message . "<br />\n";
							break;
						case 'warn':
							$warnings    .= ' ' . $event_message . "<br />\n";
							break;
						case 'error':
							$exceptions  .= ' ' . $event_message . "<br />\n";
							break;						
						default:
							// should not be reached, but just in case...
							$warnings    .= ' ' . $event_message . "<br />\n";
							break;
					}
				}
			}
		}
		return array( 'exceptions' => $exceptions, 'warnings' => $warnings, 'information' => $information );
	}
}