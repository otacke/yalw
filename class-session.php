<?php
namespace YALW;

/**
 * Session handling
 *
 * This class is used for handling the state of the login or password retrieval
 * process.
 * It is not totally necessary, because it's not much more than very simple
 * get- and set-methods. Then again, I think that some function names make
 * the codes logic more clear than assigning values to session variables.
 *
 * Keys used during session handling:
 * - action           = (string) task to be performed by Display
 * - events           = (WP_Error) notifications for the user
 * - user_login       = (string) login given, later login retrieved from WP database 
 * - user_id          = (string) user_id retrieved from WP database
 * - code_error_count = (integer) amount of unsuccessful code entries
 *
 * @package YALW
 * @since 0.5
 */
class Session {
	/**
	 * start the session
	 */
	public static function start_session() {
		if ( ! session_id() ) {
			// set HttpOnly flag to prevent scripts from accessing the cookie
			$cookie_params = session_get_cookie_params();
			session_set_cookie_params  (
					$cookie_params['lifetime'],
					$cookie_params['path'],
					$cookie_params['domain'],
					$cookie_params['secure'],
					true ); // httponly
		
			session_start();
		}
	}
	
	/**
	 * end the session
	 */
	public static function end_session() {
		session_destroy();
	}
	
	/**
	 * set next task to be shown in the widget
	 *
	 * possible tasks (so far):
	 * - NULL (default for login)
	 * - retrieve_code
	 * - check_code
	 * - enter_new_password
	 *
	 * @param string $task the next task
	 */
	public static function set_next_widget_task( $task ) {
		if ( ! is_string( $task ) ) {
		} else {
			$_SESSION['action'] = $task;
		}
	}
	
	/**
	 * get the next task for the widget
	 *
	 * @return string next task for the widget
	 */
	public static function get_next_widget_task() {
		if ( empty ( $_SESSION['action'] ) ) {
			return '';
		}
		return $_SESSION['action'];
	}

	/**
	 * unset the action
	 */	
	public static function clean_next_widget_task() {
		unset( $_SESSION['action'] );
	}
	
	/**
	 * set the events for YALW
	 *
	 * @param WP_Error $events The events
	 */
	public static function set_events( $events ) {
		if ( ! $events instanceof \WP_Error ) {
		} else {
			$_SESSION['events'] = $events;
		}
	}

	/**
	 * get YALW's events
	 *
	 * @return WP_Error|null YALW's events or null, if no events present
	 */
	public static function get_events() {
		if ( empty ( $_SESSION['events'] ) ) {
			return null;
		}
		return $_SESSION['events'];
	}
	
	/**
	 * unset the events
	 */
	public static function clean_events() {
		unset( $_SESSION['events'] );
	}

	/**
	 * set the WP user login
	 *
	 * @param string $user_login The WP user login
	 */	
	public static function set_user_login( $user_login ) {
		/*
		 * TODO: Investigate if more stuff should be done here, since
		 * this variable is handed to WP-functions and should be checked
		 * for attack vectors -- so far I guess, WP does this itself
		 */
		if ( ! is_string ( $user_login ) ) {
		} else {
			$_SESSION['user_login'] = trim( $user_login );
		}
	}

	/**
	 * get the WP user login
	 *
	 * @return string|null the WP user login or null if not set
	 */	
	public static function get_user_login() {
		if ( empty ( $_SESSION['user_login'] ) ) {
			return null;
		}
		return $_SESSION['user_login'];
	}

	/**
	 * set the WP user ID
	 *
	 * @param int $id The WP user ID
	 */
	public static function set_user_id( $id ) {
		if ( ! is_int( $id ) ) {
		} else {
			$_SESSION['user_id'] = $id;
		}
	}

	/**
	 * get the WP user ID
	 *
	 * @return int|null the WP user ID od null if not set
	 */
	public static function get_user_id() {
		if ( empty( $_SESSION['user_id'] ) ) {
			return null;
		}
		return $_SESSION['user_id'];
	}	

	/**
	 * increment the number of wrong code entries by one
	 */	
	public static function increment_code_error_count() {
		if ( empty( $_SESSION['code_error_count'] ) ) {
			$_SESSION['code_error_count'] = 1;
		} else {
			$_SESSION['code_error_count']++;
		}		
	}

	/**
	 * get the number of wrong code entries
	 *
	 * @return int number of wrong code entries
	 */
	public static function get_code_error_count() {
		if ( empty( $_SESSION['code_error_count'] ) ) {
			return 0;
		}	
		return $_SESSION['code_error_count'];
	}

	/**
	 * unset the error count
	 */	
	public static function clean_code_error_count() {
		unset( $_SESSION['code_error_count'] );
	}
}