<?php
/**
 * Plugin Name: MemberPress Forward-Only Access
 * Description: Restricts members from viewing content published before their signup date. Includes a [mepr_forward_link] shortcode and settings under MemberPress.
 * Version:     1.1.0
 * Author:      Caseproof Support
 * License:     GPL-2.0-or-later
 * Text Domain: memberpress-forward-only
 * Requires Plugins: memberpress
 *
 * @package MemberPress_Forward_Only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MEPR_FORWARD_ONLY_PLUGIN_FILE', __FILE__ );
define( 'MEPR_FORWARD_ONLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEPR_FORWARD_ONLY_VERSION', '1.1.0' );

require_once MEPR_FORWARD_ONLY_PLUGIN_DIR . 'includes/class-mepr-forward-only-bootstrap.php';

Mepr_Forward_Only_Bootstrap::init();
