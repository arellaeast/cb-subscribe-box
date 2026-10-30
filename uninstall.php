<?php
/**
 * Uninstall cleanup for CB Subscribe Box.
 *
 * Note: this DROPS the wp_cb_subscribers table. Export your list first
 * if you're removing the plugin permanently.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cb_subscribers" );
delete_option( 'cbsub_table_ver' );
delete_option( 'cbsub_settings' );

// Clear any lingering rate-limit transients.
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '\_transient\_cbsub\_rl\_%'
	    OR option_name LIKE '\_transient\_timeout\_cbsub\_rl\_%'"
);
