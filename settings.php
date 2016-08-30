<?php
/*
 * Just in case we think of doing some fancy CSS stuff or mindblowing
 * JavaScript shit or whatever in the future, we give every item an id.
 */

namespace YALW;

/**
 * Display and handle the settings page
 *
 * @package YALW
 * @since 0.13
 */
class Settings {

	/**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'YALW', 
            'manage_options', 
            'yalw-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Set class property
        $this->options = get_option( 'yalw_option' );
        ?>
        <div class="wrap">
            <h2>YALW</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'yalw_option_group' );   
                do_settings_sections( 'yalw-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {        
        register_setting(
            'yalw_option_group',
            'yalw_option',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'general_settings',
            '',
            array( $this, 'print_general_section_info' ),
            'yalw-admin'
        );		

        add_settings_field(
            'widget_rememberme', 
            __( 'Widget', 'YALW' ),
            array( $this, 'widget_rememberme_callback' ), 
            'yalw-admin', 
            'general_settings'
        );
		
         add_settings_field(
            'code_reset_email_text', 
            __( 'Email', 'YALW' ),
            array( $this, 'code_reset_email_text_callback' ), 
            'yalw-admin', 
            'general_settings'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
		if( isset( $input['widget_rememberme'] ) )
            $new_input['widget_rememberme'] = absint( $input['widget_rememberme'] );
		if( isset( $input['code_reset_email_text'] ) )
            $filtered = wp_check_invalid_utf8( $input['code_reset_email_text'] );
			$new_input['code_reset_email_text'] = wp_strip_all_tags( $filtered );
        return $new_input;
    }

    /** 
     * Print Widget Section text
     */
    public function print_general_section_info() {
    }
	
    /** 
     * Get the settings option array and print one of its values
     */
    public function code_reset_email_text_callback() {
		echo '<p><textarea name="yalw_option[code_reset_email_text]" rows="10" cols="50" id="code_reset_email_text" class="large-text code">' . ( isset( $this->options['code_reset_email_text'] ) ? esc_attr( $this->options['code_reset_email_text']) : '' ) . '</textarea></p>';
		echo '<p>';
		echo '<label for="code_reset_email_text">';
		echo __( 'Here you can enter the message that will be mailed for delivering the reset code. [user_login] will be replaced by the user\'s name, [reset_code] will be replaced by the reset code and [admin_email] will be replaced by the admin\'s email address.', 'YALW' );
		echo '</label>';
		echo '</p>';		
    }
	
    /** 
     * Get the settings option array and print one of its values
     */
    public function widget_rememberme_callback() {
		echo'<label for="widget_rememberme">';
		echo '<input type="checkbox" name="yalw_option[widget_rememberme]" id="widget_rememberme" value="1" ' . ( isset( $this->options['widget_rememberme']) ? checked( '1', $this->options['widget_rememberme'], false ) : '') . ' />';
		echo __('Enable option to remember the user when logging in', 'YALW');
		echo '</label>';
    }
}