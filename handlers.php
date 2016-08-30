<?php
namespace YALW;

/**
 * Handlers and minor methods.
 *
 * Here's where the work is happening. The object oriented approach is not
 * really necessary because we only use static functions, but who knows what is
 * yet to come...
 *
 * TODO: This file is quite large -- could possibly need some more refactoring,
 * compare http://sourcemaking.com/refactoring/moving-features-between-objects
 *
 * @package YALW
 * @since 0.5
 */
class Handlers {
	/**
	 * handle the general login
	 *
	 * The user has entered his or her user name and password. Let's see if he or
	 * she can be logged in right away...
	 *
	 * @return null|WP_Error null if OK, else WP_Error with further information
	 */
	public static function handle_login() {
		// Prevent Cross-Site-Request-Forgery
		if ( ! Handlers::is_nonce_ok( 'login_form' ) ) {
			return new \WP_Error( 'nonce' , __( 'There seems to be a security issue. Please do not continue, but inform us!', 'YALW' ), 'error' );
		}

		$events = new \WP_Error();
		// empty username
		if ( empty( $_POST['YALW_user_login'] ) ) {
			$events->add( 'empty_username' , __( 'Please enter your username.', 'YALW' ), 'warn' );
		} else {
			Session::set_user_login( trim ( $_POST['YALW_user_login'] ) );
		}
		// empty password
		if ( empty( $_POST['YALW_user_password'] ) ) {
			$events->add( 'empty_password' , __( 'Please enter your password.', 'YALW' ), 'warn' );
		}
		// username/password mismatch
		if ( ( ! empty ( $_POST['YALW_user_login'] ) ) && ( ! empty ( $_POST['YALW_user_password'] ) )) {
			if ( empty ( $_POST['YALW_rememberme'] ) ) {
				$_POST['YALW_rememberme'] = '';
			}
			$tmp_error = Handlers::sign_on (
					$_POST['YALW_user_login'],
					$_POST['YALW_user_password'],
					( $_POST['YALW_rememberme'] == 'forever' ) ? true : false );
			$events->add(
					$tmp_error->get_error_code(),
					$tmp_error->get_error_message(),
					Handlers::get_event_type( $tmp_error ) );
		}
		return $events;
	}

	/**
	 * handle the password retrieval procedure
	 *
	 * The user has entered his user name or email address in order to
	 * receive a new password. Now, let's check if that is possible
	 * If yes, send him or her a code for resetting his or her password
	 *
	 * @return WP_Error event if code could not be sent to the user
	 */
	public static function handle_code_retrieval() {
		// Prevent Cross-Site-Request-Forgery
		if ( ! Handlers::is_nonce_ok( 'code_retrieval_form' ) ) {
			return new \WP_Error( 'nonce' , __( 'There seems to be a security issue. Please do not continue, but inform us!', 'YALW' ), 'error' );
		}

		Session::set_user_login( trim ( $_POST['YALW_user_login'] ) );

		$user_data = Handlers::get_user_data_by( Session::get_user_login() );
		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		do_action( 'retrieve_password', $user_data->user_login );

		/*
		 * check if the user may reset his or her password
		 * the range of possible return types of apply_filters makes it useless
		 * to move this stuff in a separate function, IMHO.
		 */
		$allowed = apply_filters( 'allow_password_reset', true, $user_data->ID );
		if ( ! $allowed )
			return new \WP_Error( 'no_password_reset' , __( 'Password reset is not allowed for this user', 'YALW' ), 'warn' );
		else if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$send_status = Handlers::send_reset_code( $user_data );
		if ( is_wp_error ( $send_status ) ) {
			return $send_status;
		}

		// We only save the user_login and the ID for later use, not the whole WP_User -- we don't need to
		Session::set_user_login( $user_data->user_login );
		Session::set_user_id( $user_data->ID );
		Session::set_next_widget_task( 'check_code' );
		return new \WP_Error( 'email_sent' , __( 'You should have received an email with a reset code. Please check your inbox.', 'YALW' ), 'info' );
	}

	/**
	 * get WP_User by email or username
	 *
	 * @param string $login_given data entered by user
	 * @return WP_User|WP_Error WP_User if found, else WP_Error
	 */
	private static function get_user_data_by( $login_given ) {
		// check whether a user can be found for the login data given by the user
		$events = new \WP_Error();
		if ( empty( $login_given ) ) {
			do_action( 'lostpassword_post' );
			return new \WP_Error( 'empty_username', __( 'Please enter your username or e-mail address.', 'YALW' ), 'warn' );
		}
		if ( is_email( $login_given ) ) {
			$user_data = get_user_by( 'email', $login_given );
			if ( empty( $user_data ) ) {
				do_action( 'lostpassword_post' );
				return new \WP_Error( 'invalid_email ', __( 'There is no user registered with that email address.', 'YALW' ), 'warn' );
			}
		} elseif ( is_string( $login_given ) ) {
			$user_data = get_user_by( 'login', $login_given );
			if ( empty( $user_data ) ) {
				do_action( 'lostpassword_post' );
				return new \WP_Error( 'invalidcombo', __( 'The username is not registered.', 'YALW' ), 'warn' );
			}
		} else {
			return new \WP_Error( 'internal_error', __( 'An internal program error has occured. Your request cannot be complied with. Please inform us.', 'YALW' ), 'error' );
		}
		return $user_data;
	}

	/**
	 * Generate a random key and insert its hash value into the database in
	 * order to allow a passwort reset.
	 *
	 * @global WordPress Database Access Abstraction Object $wpdb Wordpress database
	 * @global PasswordHash $wp_hasher Password Hasher
	 *
	 * @param WP_User $user_data object containing user data
	 * @return string|false the key to be sent, or false on error.
	 */
	private static function get_retrieval_key( $user_data ) {
		global $wpdb, $wp_hasher;

		if ( ! $user_data instanceof \WP_User ) {
			return false;
		}

		$user_login = $user_data->user_login;
		$key        = wp_generate_password( 20, false );

		do_action( 'retrieve_password_key', $user_login, $key );

		if ( empty( $wp_hasher ) ) {
			require_once ( ABSPATH . WPINC . '/class-phpass.php' );
			$wp_hasher = new \PasswordHash( 8, true );
		}
		$hashed = $wp_hasher->HashPassword( $key );

		if ( $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) ) == true ) {
			return $key;
		} else {
			return false;
		}
	}

	/**
	 * Send a password reset code via email
	 *
	 * @param WP_User $user_data
	 * @return true|WP_Error True if mail was sent, or WP_Error on error
	 */
	private static function send_reset_code( $user_data ) {
		if ( ! $user_data instanceof \WP_User ) {
			return new \WP_Error( 'internal_error', __( 'An internal program error has occured. Your request cannot be complied with. Please inform us.', 'YALW' ), 'error' );
		}
		if ( ! Handlers::get_retrieval_key ( $user_data ) ) {
			return new \WP_Error( 'internal_error', __( 'An internal program error has occured. Your request cannot be complied with. Please inform us.', 'YALW' ), 'error' );
		}

		$blogname = Handlers::get_blogname();
		$title    = Handlers::create_mail_title( $blogname );
		$db_code  = Handlers::get_retrieval_code( $user_data->user_login );
		if ( empty( $db_code ) ) {
			// could not get the code from the database
			return new \WP_error( 'unknown_error' , __( 'There seems to be a problem with our database. Sorry. Please try again later.', 'YALW' ), 'error' );
		}
		$message  = Handlers::get_password_retrieval_message( $user_data->user_login, $db_code );

		if ( ! wp_mail( $user_data->user_email, wp_specialchars_decode( $title ), $message ) ) {
			return new \WP_error(
					'email_not_sent' ,
					__( 'The e-mail could not be sent.', 'YALW' ) . '<br />' . __( 'Possible reason: your host may have disabled the mail() function.', 'YALW' ),
					'error' );
		} else {
			Session::clean_code_error_count();
			return true;
		}
	}

	/**
	 * get the blog's name
	 *
	 * @return string the blog's name
	 */
	private static function get_blogname() {
		if ( is_multisite() ) {
			$blogname = $GLOBALS['current_site']->site_name;
		} else {
			/*
			 * The blogname option is escaped with esc_html on the way into the database
			 * in sanitize_option we want to reverse this for the plain text area of emails.
			 */
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}
		return $blogname;
	}

	/**
	 * create mail title from blog's name
	 *
	 * @return string the mail title
	 */
	private static function create_mail_title( $blogname ) {
		if ( ! is_string( $blogname ) ) {
			$blogname = '';
		}
		$title = sprintf( __( '[%s] Password Reset', 'YALW' ), $blogname );
		$title = apply_filters( 'retrieve_password_title', $title );
		return $title;
	}

	/**
	 * get the code from the user activation key stored in the database
	 *
	 * @global WordPress Database Access Abstraction Object $wpdb Wordpress database
	 *
	 * @param string $user_login user's login name
	 */
	private static function get_retrieval_code( $user_login ){
		global $wpdb;
		if ( ! is_string ( $user_login ) ) {
			return null;
		}
		$code_query = $wpdb->prepare(
				"SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s" ,
				$user_login );
		return Handlers::generate_pin_code( $wpdb->get_var( $code_query ) );
	}

	/**
	 * Generate a PIN-like code with a specific length
	 *
	 * You can use a seed if you want the code to be generated based on a
	 * certain value. If you don't specify a seed, the code will be randomly
	 * generated.
	 *
	 * The default settings will give you a code from a pool of 16^16
	 * possible combinations (that's 18,446,744,073,709,551,616)
	 *
	 * @param string $seed string as seed or default value is random
	 * @param int $length length between 0 and 41 or default value is 16
	 * @param string $delimiter optional delimiter inserted after 4 code chars, default is '-'
	 * @return string pin like string
	 */
	private static function generate_pin_code( $seed = '', $length = 16, $delimiter = '-' ) {
		if ( ! is_string( $seed ) ) {
			$seed = '';
		} elseif ( $seed == '') {
			$seed = mt_rand();
		}

		if ( ! is_int( $length ) ) {
			$length = 16;
		} else {
			$length = min( max( 1, $length ), 40 );
		}

		$code_raw = substr( sha1( $seed ), 0, $length );

		if ( ! is_string( $delimiter ) ) {
			$delimiter = '-';
		}

		// add a delimiter inside the pin code if it's set
		if ( $delimiter != '' ) {
			$code = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$code .= substr( $code_raw, $i, 1 );
				if ( ( ($i+1)%4 == 0 ) && ( $i != $length-1 ) ) {
					$code .= $delimiter;
				}
			}
			return $code;
		} else {
			return $code_raw;
		}
	}

	/**
	 * generate the code reset message
	 *
	 * @param string $user_login user login
	 * @param string $reset_code reset code
	 * @return string The message to be sent via email
	 */
	private static function get_password_retrieval_message( $user_login, $reset_code ) {
		if ( ! is_string( $user_login ) ) {
			$user_login = '';
		}
		if ( ! is_string( $reset_code ) ) {
			$reset_code = '';
		}

		// use individual message from options if set, else use a default message
		$message = __( 'Dear [user_login], please enter [reset_code] in the input field. You can set a new password afterwards.', 'YALW' );
		$options = get_option( 'yalw_option' );
		if ( ! empty ( $options ) ) {
			$tmp =  trim( $options['code_reset_email_text'] );
			if ( ! empty ( $tmp ) ) {
				$message = $options['code_reset_email_text'];
			}
		}

		// replace the markers within the message text by appropriate values
		$message = str_replace( '[user_login]', $user_login, $message );
		$message = str_replace( '[reset_code]', $reset_code, $message );
		$message = str_replace( '[admin_email]', get_option( 'admin_email' ), $message );

		return $message;
	}

	/**
	 * handle the password reset code entry after sending an email with the code to the user
	 *
	 * The user has entered the code that was sent to him in order to reset
	 * his or her password up to MAX_CODE_RETRIES-1 times. The code is
	 * generated from the "user_activation_key" within the database table
	 * "users" that is also used by wp_login.php. This way, we do not have
	 * to alter the databese structure at all.
	 *
	 * If the code entered is valid, ths ueser will be taken to the password
	 * reset mask. If the code is wrong more than MAX_CODE_RETRIES times,
	 * the user_activation_key is set randomly to prevent brute force
	 * attacks. Of course, the user can receive a new code via email, but
	 * he or she has to request one manually again.
	 *
	 * @return WP_Error error message if code was wrong (too often)
	 */
	public static function handle_reset_code() {
		// Prevent Cross-Site-Request-Forgery
		if ( ! Handlers::is_nonce_ok( 'code_entry_form' ) ) {
			return new \WP_Error( 'nonce' , __( 'There seems to be a security issue. Please do not continue, but inform us!', 'YALW' ), 'error' );
		}
		$MAX_CODE_RETRIES = 2;

		$user_login = Session::get_user_login();
		$events = new \WP_Error();

		// get the code from the user activation key stored in the database
		$db_code = Handlers::get_retrieval_code( $user_login );
		if ( empty( $db_code ) ) {
			// could not get the code from the database
			Session::set_next_widget_task( 'check_code' );
			$events->add( 'unknown_error' , __( 'There seems to be a problem with our database. Sorry. Please try again later.', 'YALW' ), 'error' );
		} elseif ( $_POST['YALW_code'] != $db_code ) {
			Session::increment_code_error_count();

			if ( Session::get_code_error_count() > $MAX_CODE_RETRIES ) {
				// maximum retries exceeded, set new code
				Handlers::set_random_reset_code( $user_login );

				/*
				 * We log the fact that the code was entered wrong too often. Too many of these
				 * entries in the logfile, e. g. three within a certain period of time, can be used
				 * via fail2ban to block the user's IP address. It is likely someone is trying to
				 * "brute force" the plugin.
				 */
				 \openlog( 'yalw(' . $_SERVER['HTTP_HOST'] . ')', LOG_NDELAY|LOG_PID, LOG_AUTH );
				 \syslog( LOG_NOTICE, "Code reset failure for $user_login from " . Handlers::get_remote_address() );

				/*
				 * From a security driven point of view, we could erase the
				 * username in the session so the user must reenter it -- we
				 * don't for the sake of usability.
				 */
				Session::clean_code_error_count();
				Session::set_next_widget_task( 'retrieve_code' );
				$events->add( 'code_reset' , __( 'The code was wrong too often. Please get a new one.', 'YALW' ), 'warn' );
			} else {
				/*
				 * We log the fact that the code was entered wrong. Too many of these
				 * entries in the logfile, e. g. three within a certain period of time, can be used
				 * via fail2ban to block the user's IP address. It is likely someone is trying to
				 * "brute force" the plugin.
				 */
				\openlog( 'yalw(' . $_SERVER['HTTP_HOST'] . ')', LOG_NDELAY|LOG_PID, LOG_AUTHPRIV );
				\syslog( LOG_NOTICE, "Code entry failure for $user_login from " . Handlers::get_remote_address() );
				// code wrong
				Session::set_next_widget_task( 'check_code' );
				$events->add( 'code_mismatch' , __( 'The code is wrong.', 'YALW' ), 'warn' );
			}
		} else {
			// code is OK, remove dispensable stuff from session, and go to password entry
			Session::clean_code_error_count();
			Session::set_next_widget_task( 'enter_new_password' );
			return null;
		}
		return $events;
	}

	/**
	 * set a new random reset code after too many attempts to enter the code
	 *
	 * @global WordPress Database Access Abstraction Object $wpdb Wordpress database
	 *
	 * @param string $user_login login of user whose code to be reset
	 */
	private static function set_random_reset_code( $user_login ) {
		global $wpdb;

		if ( ! is_string( $user_login ) ) {
		} else {
			$wpdb->update(
					$wpdb->users,
					array( 'user_activation_key' => mt_rand() ),
					array( 'user_login' => $user_login ) );
		}
	}

	/**
	 * reset the password and sign the user on
	 *
	 * The user has entered his or her new password. It should be entered
	 * twice -- just in case...
	 * If both entries match, the new password is stored in the database and
	 * the user is logged in.
	 *
	 * @return WP_Error event if password could not be reset or user could not be signed on
	 */
	public static function handle_reset_password() {
		// Prevent Cross-Site-Request-Forgery
		if ( ! Handlers::is_nonce_ok( 'new_password_form' ) ) {
			return new \WP_Error( 'nonce' , __( 'There seems to be a security issue. Please do not continue, but inform us!', 'YALW' ), 'error' );
		}
		// Prevent user's from obtaining rights of other users
		if ( Handlers::get_retrieval_code( Session::get_user_login() ) != $_POST['YALW_code'] ) {
			return new \WP_Error( 'security' , __( 'I\'m sorry, Dave. I\'m afraid I can\'t do that.', 'YALW' ), 'error' );
		}
		$events = new \WP_Error();

		if ( empty( $_POST['YALW_new_password'] ) ) {
			// password empty?
			Session::set_next_widget_task( 'enter_new_password' );
			$events->add( 'password_empty' , __( 'The password cannot be empty.', 'YALW' ), 'warn' );
		} elseif ( $_POST['YALW_new_password'] != $_POST['YALW_control_password'] ) {
			// password mismatch?
			Session::set_next_widget_task( 'enter_new_password' );
			$events->add( 'password_mismatch' , __( 'The passwords are not the same. Please re-enter.', 'YALW' ), 'warn' );
		} else {
			// set new password and login
			wp_set_password( $_POST['YALW_new_password'], Session::get_user_id() );
			$tmp_error = Handlers::sign_on(
					Session::get_user_login(),
					$_POST['YALW_new_password'] );
			$events->add( $tmp_error->get_error_code(), $tmp_error->get_error_message(), Handlers::get_event_type( $tmp_error ) );
		}
		return $events;
	}

	/**
	 * Sign on the user from YALW
	 *
	 * @param string $login user login
	 * @param string $password user password
	 * @param bool $rememberme indicates, if Wordpress should remember the user
	 * @return WP_Error WP_Error on error
	 */
	static function sign_on( $login, $password, $rememberme = true ) {
		if ( ! is_string( $login ) ) {
			return new \WP_Error( 'internal_error', __( 'An internal program error has occured. Your request cannot be complied with. Please inform us.', 'YALW' ), 'error' );
		}
		if ( ! is_string( $password ) ) {
			return new \WP_Error( 'internal_error', __( 'An internal program error has occured. Your request cannot be complied with. Please inform us.', 'YALW' ), 'error' );
		}
		if ( ! is_bool( $rememberme ) ) {
			$rememberme = false;
		}

		// These lines are needed for working with BuddyPress
		if ( ! username_exists( $login ) ) {
			return new \WP_Error( 'login_failure', __( 'There seems to be something wrong with the username or password.', 'YALW' ), 'warn' );
		}

		$YALW_credentials = array( 'user_login' => $login, 'user_password' => $password, 'remember' => $rememberme );
		$user = wp_signon( $YALW_credentials, true );

		if ($user instanceof \WP_Error ) {
			return new \WP_Error( 'login_failure', __( 'There seems to be something wrong with the username or password.', 'YALW' ), 'warn' );
		} else{
			wp_set_auth_cookie( $user->ID );
			// remove our GET-Variable if user logs in after password retrieval
			wp_redirect( Handlers::remove_get_from_uri ( $_POST['YALW_redirect'] ) ) ;
			exit();
		}
	}

	/**
	 * Remove YALW's GET variables from URI
	 *
	 * @param string $uri URI to be sanitized
	 * @returns string URI without YALW's GET variables
	 */
	private static function remove_get_from_uri( $uri ) {
		if ( ! is_string( $uri ) ) {
			return '';
		} else {
			return str_replace( array( '?action=retrieve_code', '&action=retrieve_code' ), '', $uri );
		}
	}

	/**
	 * Get YALW event type from WP_Error severity
	 *
	 * YALW uses WP_Error to interact with the user -- a new class wouldn't make
	 * sense, I think. Anyway, YALW distinguishes between event types in regard
	 * of their significance according to ITIL V3 (not because it's necessary
	 * to refer to ITIL, but because I think it's useful to have three types):
	 * - information (info) for informational need - no exception
	 * - warning (warn): something went wrong, and acion is needed, but the service is still fine
	 * - exception (error): the service seems not to be able to work within normal parameters
	 *
	 * Since Wordpress does only distinguish between 'message' and 'error',
	 * this function maps both to YALW's needs.
	 *
	 * @param WP_Error $error error to be checked
	 * @returns string info|warn 'info' if WP_Error's message, else 'warn'
	 */
	private static function get_event_type( $error ) {
		if ( ! $error instanceof \WP_Error ) {
			return 'info';
		}
		if ( $error->get_error_data() == 'message' ) {
			return 'info';
		} else {
			return 'warn';
		}
	}

	/**
	 * check if a nonce is OK
	 *
	 * can be used to prevent Cross-Site-Request-Forgery
	 *
	 * @param string $action Action name that should have given the context to what is taking place
	 * @param string $name Nonce name
	 * @return boolean true, if everything was OK, else false
	 */
	private static function is_nonce_ok( $action = '-1', $name = 'yalw_nonce' ) {
		if ( ! is_string( $action ) ) {
			$action = '-1';
		}
		if ( ! is_string( $name ) ) {
			$name = 'yalw_nonce';
		}
		if ( empty( $_POST[$name] ) ) {
			return false;
		}
		if ( ! wp_verify_nonce( $_POST[$name], $action ) ) {
			return false;
		}
		return true;
	}

	/**
	 * get remote IP address from user
	 *
	 * The function tries to recognize proxies that were set in connection with the plugin
	 * wp_fail2ban in order to get the "true" IP address. Otherwise, a proxy might be blocked
	 * accidentally.
	 *
	 * @return String IP address the user uses
	 */
	private static function get_remote_address() {
		if ( defined( 'WP_FAIL2BAN_PROXIES' ) ) {
			if ( array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER ) ) {
				$ip = ip2long( $_SERVER['REMOTE_ADDR'] );
				foreach( explode( ',', WP_FAIL2BAN_PROXIES ) as $proxy ) {
					$cidr = explode( '/', $proxy );
					if ( count( $cidr ) == 2 ) {
						$net = ip2long( $cidr[0] );
						$mask = ~ ( pow( 2, ( 32 - $cidr[1] ) ) - 1 );
					} else {
						$net = ip2long( $proxy );
						$mask = -1;
					}
					if ( ( $ip & $mask ) == $net ) {
						if ( ( $len = strpos( $_SERVER['HTTP_X_FORWARDED_FOR'], ',' ) ) === false ) {
							return $_SERVER['HTTP_X_FORWARDED_FOR'];
						} else {
							return substr( $_SERVER['HTTP_X_FORWARDED_FOR'], 0, $len );
						}
					}
				}
			}
		}
		return $_SERVER['REMOTE_ADDR'];
	}
}
