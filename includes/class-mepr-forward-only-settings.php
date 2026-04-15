<?php
/**
 * Settings helpers (options + wp-config overrides).
 *
 * @package MemberPress_Forward_Only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and sanitizes plugin settings.
 */
final class Mepr_Forward_Only_Settings {

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'mepr_forward_only_exclude_post_types', array( __CLASS__, 'filter_exclude_post_types' ), 5 );
		add_filter( 'mepr_forward_only_exclude_categories', array( __CLASS__, 'filter_exclude_categories' ), 5 );
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array{rule_ids: int[], message: string, excluded_post_types: string, excluded_categories: string}
	 */
	public static function get(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$defaults = array(
			'rule_ids'            => array(),
			'message'             => Mepr_Forward_Only_Bootstrap::default_message_html(),
			'excluded_post_types' => '',
			'excluded_categories' => '',
		);

		$stored = get_option( Mepr_Forward_Only_Bootstrap::OPTION_NAME, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		self::$cache = wp_parse_args( $stored, $defaults );
		return self::$cache;
	}

	/**
	 * Clear cache after save.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		self::$cache = null;
	}

	/**
	 * Rule IDs to enforce (empty = all rules). Constants override options.
	 *
	 * @return int[]
	 */
	public static function get_rule_ids(): array {
		if ( defined( 'MEPR_FORWARD_ONLY_RULE_IDS' ) && is_array( MEPR_FORWARD_ONLY_RULE_IDS ) ) {
			return array_map( 'intval', (array) MEPR_FORWARD_ONLY_RULE_IDS );
		}

		$ids = self::get()['rule_ids'];
		if ( ! is_array( $ids ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'intval', $ids ) ) );
	}

	/**
	 * Message HTML with %signup_date% placeholder.
	 *
	 * @return string
	 */
	public static function get_message(): string {
		if ( defined( 'MEPR_FORWARD_ONLY_MESSAGE' ) && is_string( MEPR_FORWARD_ONLY_MESSAGE ) ) {
			return MEPR_FORWARD_ONLY_MESSAGE;
		}

		$msg = self::get()['message'];
		return is_string( $msg ) ? $msg : Mepr_Forward_Only_Bootstrap::default_message_html();
	}

	/**
	 * Parse comma/newline list into array of non-empty strings.
	 *
	 * @param string $raw Raw field value.
	 * @return string[]
	 */
	public static function parse_list( string $raw ): array {
		$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );
		$parts = preg_split( '/[\n,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $item ) {
							return sanitize_title( trim( $item ) );
						},
						$parts
					)
				)
			)
		);
	}

	/**
	 * Excluded post types from settings (slugs).
	 *
	 * @return string[]
	 */
	public static function get_excluded_post_types_parsed(): array {
		return self::parse_list( (string) self::get()['excluded_post_types'] );
	}

	/**
	 * Excluded category slugs from settings.
	 *
	 * @return string[]
	 */
	public static function get_excluded_categories_parsed(): array {
		return self::parse_list( (string) self::get()['excluded_categories'] );
	}

	/**
	 * Merge stored exclusions with filter (for backward compatibility).
	 *
	 * @param array $existing Existing slugs from filter.
	 * @return array
	 */
	public static function filter_exclude_post_types( $existing ): array {
		$existing = is_array( $existing ) ? $existing : array();
		$from_opts = self::get_excluded_post_types_parsed();
		if ( empty( $from_opts ) ) {
			return $existing;
		}

		return array_values( array_unique( array_merge( $existing, $from_opts ) ) );
	}

	/**
	 * Merge stored category exclusions with filter.
	 *
	 * @param array $existing Existing slugs from filter.
	 * @return array
	 */
	public static function filter_exclude_categories( $existing ): array {
		$existing = is_array( $existing ) ? $existing : array();
		$from_opts = self::get_excluded_categories_parsed();
		if ( empty( $from_opts ) ) {
			return $existing;
		}

		return array_values( array_unique( array_merge( $existing, $from_opts ) ) );
	}
}
