<?php
/**
 * Plugin bootstrap.
 *
 * @package MemberPress_Forward_Only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads dependencies and hooks when MemberPress is available.
 */
final class Mepr_Forward_Only_Bootstrap {

	/**
	 * Option name for stored settings.
	 */
	public const OPTION_NAME = 'mepr_forward_only_settings';

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'plugins_loaded', array( __CLASS__, 'load' ), 20 );
		register_activation_hook( MEPR_FORWARD_ONLY_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
	}

	/**
	 * Load plugin after MemberPress.
	 *
	 * @return void
	 */
	public static function load(): void {
		load_plugin_textdomain( 'memberpress-forward-only', false, dirname( plugin_basename( MEPR_FORWARD_ONLY_PLUGIN_FILE ) ) . '/languages' );

		if ( ! class_exists( 'MeprUser' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_missing_memberpress' ) );
			return;
		}

		require_once MEPR_FORWARD_ONLY_PLUGIN_DIR . 'includes/class-mepr-forward-only-settings.php';
		require_once MEPR_FORWARD_ONLY_PLUGIN_DIR . 'includes/class-mepr-forward-only-core.php';
		require_once MEPR_FORWARD_ONLY_PLUGIN_DIR . 'includes/compat-functions.php';
		require_once MEPR_FORWARD_ONLY_PLUGIN_DIR . 'includes/class-mepr-forward-only-admin.php';

		Mepr_Forward_Only_Settings::init();
		Mepr_Forward_Only_Core::init();
		Mepr_Forward_Only_Admin::init();
	}

	/**
	 * Default settings on first activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( get_option( self::OPTION_NAME, null ) !== null ) {
			return;
		}

		$defaults = array(
			'rule_ids'             => array(),
			'message'              => self::default_message_html(),
			'excluded_post_types'  => '',
			'excluded_categories'  => '',
		);

		add_option( self::OPTION_NAME, $defaults );
	}

	/**
	 * Default notice HTML (matches original snippet).
	 *
	 * @return string
	 */
	public static function default_message_html(): string {
		return '<div class="mepr-forward-only-notice">'
			. '<p><strong>Access Restricted</strong></p>'
			. '<p>This content was published before your membership began on '
			. '<strong>%signup_date%</strong>. As a member, you have access to all '
			. 'content published from your signup date onward.</p>'
			. '</div>';
	}

	/**
	 * Admin notice when MemberPress is inactive.
	 *
	 * @return void
	 */
	public static function notice_missing_memberpress(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'MemberPress Forward-Only Access requires MemberPress to be installed and active.', 'memberpress-forward-only' );
		echo '</p></div>';
	}
}
