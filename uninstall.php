<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package MemberPress_Forward_Only
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mepr_forward_only_settings' );
