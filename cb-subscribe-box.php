<?php
/**
 * Plugin Name:       CB Subscribe Box
 * Plugin URI:        https://captainbaltimore.com
 * Description:       Styled newsletter subscribe box via the [cb_subscribe] shortcode. Stores signups locally, hands off to TNP if present, and fires a hook for the Resend Connector / n8n.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Captain Baltimore
 * License:           GPL-2.0-or-later
 * Text Domain:       cb-subscribe-box
 */

defined( 'ABSPATH' ) || exit;

define( 'CBSUB_VERSION', '1.2.0' );
define( 'CBSUB_PLUGIN_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'class-cb-subscribe-icons.php';
require_once plugin_dir_path( __FILE__ ) . 'class-cb-subscribe-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'class-cb-subscribe-shortcode.php';

/**
 * Create the subscribers table on activation instead of checking on every init.
 */
register_activation_hook( __FILE__, [ 'CB_Subscribe_Shortcode', 'maybe_create_table' ] );

/**
 * Clean up the version flag on uninstall is handled in uninstall.php.
 */
