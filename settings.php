<?php
namespace YALW;

/**
 * Settings.
 *
 * @package YALW
 * @since 0.11
 */
class Settings {

	function yalw_plugin_menu() {
		add_options_page(
				'YALW Options',
				'YALW',
				'manage_options',
				'YALW-OPTIONS',
				'Settings::yalw_plugin_options'
			);
	}

	function register_settings() {
		register_setting( 'yalwoption-group', 'code_reset_email' );
	}

	function yalw_plugin_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<h2>YALW</h2>';
		echo '<form method="post" action="options.php"> ';
		settings_fields( 'yalwoption-group' );
		do_settings_sections( 'yalwmyoption-group' );
			
		echo '<table class="form-table">';
		echo '<tr valign="top">';
		echo '<th scope="row">' . __( 'Text for the code reset mail', 'YALW' ) . '</th>';
		echo '<td>';
		echo '<p>';
		echo '<label for="code_reset_email">';
		echo __( 'Here you can enter the message that will be mailed for delivering the reset code. [user_login] will be replaced by the user\'s name and [reset_code] will be replaced by the reset code.', 'YALW' );
		echo '</label>';
		echo '</p>';
		echo '<p><textarea name="code_reset_email" rows="10" cols="50" id="code_reset_email" class="large-text code">' . esc_attr( get_option( 'code_reset_email' ) ) . '</textarea></p>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';

		submit_button();
		echo '</form>';
		echo '</div>';
	}

}
?>
