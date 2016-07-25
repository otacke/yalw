<?

namespace YALW;

/**
 * Clean up after uninstall
 *
 * @package YALW
 * @since 0.14
 */

// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
 
$option_name = 'yalw_option';

delete_option( $option_name );
 
// For site options in Multisite
delete_site_option( $option_name );