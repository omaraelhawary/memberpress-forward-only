<?php
/**
 * Forward-only access logic (filters, shortcode, frontend styles).
 *
 * @package MemberPress_Forward_Only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core hooks mirroring the original MU-plugin behavior.
 */
final class Mepr_Forward_Only_Core {

	/**
	 * Request globals (single page load).
	 *
	 * @var bool
	 */
	private static $locked = false;

	/**
	 * @var string
	 */
	private static $signup_date = '';

	/**
	 * @var int
	 */
	private static $active_rule_id = 0;

	/**
	 * Whether notice inline CSS was already prepended to output this request.
	 *
	 * @var bool
	 */
	private static $notice_styles_printed = false;

	/**
	 * Per-request cache for get_signup_ts() keyed by user + product scope.
	 *
	 * @var array<string, int|null>
	 */
	private static $signup_ts_cache = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'mepr_content_locked_for_user', array( __CLASS__, 'restrict_full_page' ), 20, 3 );
		add_filter( 'mepr_pre_run_partial_rule', array( __CLASS__, 'restrict_partial' ), 20, 3 );
		add_filter( 'do_shortcode_tag', array( __CLASS__, 'filter_shortcode_output' ), 20, 4 );
		add_filter( 'mepr_unauthorized_message', array( __CLASS__, 'custom_unauthorized_message' ), 20, 3 );
		add_shortcode( 'mepr_forward_link', array( __CLASS__, 'shortcode_forward_link' ) );
	}

	/**
	 * Earliest active transaction timestamp for the member.
	 *
	 * Comparison with post publish times uses `strtotime()` on MemberPress `created_at` and WordPress
	 * `post_date` strings. Both should resolve in the site timezone; if you see edge cases near
	 * midnight, verify MemberPress transaction storage vs. WordPress post date settings.
	 *
	 * @param int         $user_id     User ID.
	 * @param array|null  $product_ids Optional membership IDs to scope lookup.
	 * @return int|null Unix timestamp or null.
	 */
	public static function get_signup_ts( int $user_id, ?array $product_ids = null ): ?int {
		if ( ! class_exists( 'MeprUser' ) ) {
			return null;
		}

		$cache_key = self::signup_cache_key( $user_id, $product_ids );
		if ( array_key_exists( $cache_key, self::$signup_ts_cache ) ) {
			return self::$signup_ts_cache[ $cache_key ];
		}

		$mepr_user   = new MeprUser( $user_id );
		$active_txns = (array) $mepr_user->active_product_subscriptions( 'transactions' );

		$earliest = null;

		foreach ( $active_txns as $txn ) {
			if ( null !== $product_ids && ! in_array( (int) $txn->product_id, $product_ids, true ) ) {
				continue;
			}

			$ts = strtotime( $txn->created_at );

			if ( false === $ts ) {
				continue;
			}

			if ( null === $earliest || $ts < $earliest ) {
				$earliest = $ts;
			}
		}

		self::$signup_ts_cache[ $cache_key ] = $earliest;

		return $earliest;
	}

	/**
	 * Cache key for signup timestamp lookups.
	 *
	 * @param int         $user_id     User ID.
	 * @param array|null  $product_ids Product scope.
	 * @return string
	 */
	private static function signup_cache_key( int $user_id, ?array $product_ids ): string {
		if ( null === $product_ids ) {
			return (string) $user_id . "\0all";
		}

		$sorted = array_values( array_map( 'intval', $product_ids ) );
		sort( $sorted );

		return (string) $user_id . "\0" . wp_json_encode( $sorted );
	}

	/**
	 * Whether this rule ID is subject to forward-only enforcement.
	 *
	 * @param int $rule_id Rule ID.
	 * @return bool
	 */
	public static function rule_applies( int $rule_id ): bool {
		$ids = Mepr_Forward_Only_Settings::get_rule_ids();

		if ( empty( $ids ) ) {
			return true;
		}

		return in_array( $rule_id, $ids, true );
	}

	/**
	 * Whether post is excluded via filters / settings.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	public static function is_excluded( WP_Post $post ): bool {
		$excluded_types = apply_filters( 'mepr_forward_only_exclude_post_types', array() );
		if ( ! empty( $excluded_types ) && in_array( $post->post_type, (array) $excluded_types, true ) ) {
			return true;
		}

		$excluded_cats = apply_filters( 'mepr_forward_only_exclude_categories', array() );
		if ( ! empty( $excluded_cats ) && 'post' === $post->post_type ) {
			$post_cats = wp_get_post_categories( $post->ID, array( 'fields' => 'slugs' ) );
			if ( array_intersect( (array) $excluded_cats, $post_cats ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Full-page lock filter.
	 *
	 * @param mixed $locked        Whether locked.
	 * @param mixed $current_post  Current post.
	 * @param mixed $uri           Request URI.
	 * @return bool|mixed
	 */
	public static function restrict_full_page( $locked, $current_post = null, $uri = null ) {
		if ( $locked ) {
			return $locked;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return $locked;
		}

		if ( ! $current_post instanceof WP_Post ) {
			return $locked;
		}

		if ( self::is_excluded( $current_post ) ) {
			return $locked;
		}

		if ( ! is_user_logged_in() ) {
			return $locked;
		}

		if ( ! class_exists( 'MeprRule' ) ) {
			return $locked;
		}

		$post_rules = MeprRule::get_rules( $current_post );

		foreach ( $post_rules as $rule ) {
			$rule_id = (int) $rule->ID;

			if ( ! self::rule_applies( $rule_id ) ) {
				continue;
			}

			// post_date is the local publish time string stored by WordPress (same basis as the editor).
			$publish_ts = strtotime( $current_post->post_date );
			$signup_ts  = self::get_signup_ts( get_current_user_id() );

			if ( null === $signup_ts ) {
				continue;
			}

			if ( $signup_ts > $publish_ts ) {
				self::$locked          = true;
				self::$signup_date     = wp_date( get_option( 'date_format' ), $signup_ts );
				self::$active_rule_id  = $rule_id;
				return true;
			}
		}

		return $locked;
	}

	/**
	 * Partial content (shortcode) rule filter.
	 *
	 * @param mixed $is_allowed   Whether allowed.
	 * @param mixed $current_post Post.
	 * @param mixed $rule         Rule object.
	 * @return bool|mixed
	 */
	public static function restrict_partial( $is_allowed, $current_post = null, $rule = null ) {
		if ( ! $is_allowed ) {
			return $is_allowed;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return $is_allowed;
		}

		if ( ! $current_post instanceof WP_Post ) {
			return $is_allowed;
		}

		if ( self::is_excluded( $current_post ) ) {
			return $is_allowed;
		}

		if ( ! is_user_logged_in() ) {
			return $is_allowed;
		}

		$rule_id = 0;
		if ( is_object( $rule ) && isset( $rule->ID ) ) {
			$rule_id = (int) $rule->ID;
		}

		$configured_rules = Mepr_Forward_Only_Settings::get_rule_ids();
		if ( ! empty( $configured_rules ) && 0 === $rule_id ) {
			return $is_allowed;
		}

		if ( $rule_id > 0 && ! self::rule_applies( $rule_id ) ) {
			return $is_allowed;
		}

		$publish_ts = strtotime( $current_post->post_date );
		$signup_ts  = self::get_signup_ts( get_current_user_id() );

		if ( null === $signup_ts ) {
			return $is_allowed;
		}

		if ( $signup_ts > $publish_ts ) {
			self::$locked      = true;
			self::$signup_date = wp_date( get_option( 'date_format' ), $signup_ts );
			if ( $rule_id > 0 ) {
				self::$active_rule_id = $rule_id;
			}
			return false;
		}

		return $is_allowed;
	}

	/**
	 * Replace mepr-active unauthorized output with forward-only message when locked.
	 *
	 * @param string $output Shortcode output.
	 * @param string $tag    Tag name.
	 * @param array  $attr   Attributes.
	 * @param array  $m      Regex matches.
	 * @return string
	 */
	public static function filter_shortcode_output( $output, $tag, $attr = array(), $m = array() ) {
		if ( 'mepr-active' !== $tag && 'mepr_active' !== $tag ) {
			return $output;
		}

		if ( ! self::$locked ) {
			return $output;
		}

		if ( empty( $output ) || false !== strpos( $output, 'mepr-unauthorized' ) || false !== strpos( $output, 'mepr_unauthorized' ) ) {
			$message = Mepr_Forward_Only_Settings::get_message();
			$message = str_replace( '%signup_date%', esc_html( self::$signup_date ), $message );
			return self::prepend_notice_styles( $message );
		}

		return $output;
	}

	/**
	 * Full-page unauthorized message override.
	 *
	 * @param string $message Default message.
	 * @param mixed  $post    Post.
	 * @param mixed  $unauth  Unauthorized context.
	 * @return string
	 */
	public static function custom_unauthorized_message( $message, $post = null, $unauth = null ) {
		if ( ! self::$locked ) {
			return $message;
		}

		$custom = Mepr_Forward_Only_Settings::get_message();
		$custom = str_replace( '%signup_date%', esc_html( self::$signup_date ), $custom );

		self::$locked = false;

		return self::prepend_notice_styles( $custom );
	}

	/**
	 * Prepend notice CSS once per request when custom HTML is output.
	 *
	 * @param string $html Message HTML.
	 * @return string
	 */
	private static function prepend_notice_styles( string $html ): string {
		if ( self::$notice_styles_printed ) {
			return $html;
		}

		self::$notice_styles_printed = true;

		$css = '<style id="mepr-forward-only-inline-css">'
			. '.mepr-forward-only-notice {'
			. 'background-color:#fff8e1;border-left:4px solid #ffc107;padding:16px 20px;'
			. 'margin:20px 0;border-radius:4px;font-size:14px;line-height:1.6;'
			. '}'
			. '.mepr-forward-only-notice p{margin:0 0 8px;}'
			. '.mepr-forward-only-notice p:last-child{margin-bottom:0;}'
			. '.mepr-forward-only-notice strong{color:#333;}'
			. '</style>';

		return $css . $html;
	}

	/**
	 * [mepr_forward_link] shortcode.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Enclosed content.
	 * @return string
	 */
	public static function shortcode_forward_link( $atts, $content = null ) {
		if ( is_null( $content ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'page_id'        => '',
				'membership_ids' => '',
			),
			$atts,
			'mepr_forward_link'
		);

		$page_id = (int) $atts['page_id'];

		if ( $page_id <= 0 ) {
			return '';
		}

		if ( ! is_user_logged_in() ) {
			return '';
		}

		if ( ! class_exists( 'MeprUser' ) || ! class_exists( 'MeprTransaction' ) ) {
			return '';
		}

		if ( current_user_can( 'manage_options' ) ) {
			return do_shortcode( $content );
		}

		$archive_page = get_post( $page_id );

		if ( ! $archive_page || 'publish' !== $archive_page->post_status ) {
			return '';
		}

		$archive_publish_ts = strtotime( $archive_page->post_date );

		$product_ids = null;
		if ( ! empty( $atts['membership_ids'] ) ) {
			$product_ids = array_map( 'intval', array_map( 'trim', explode( ',', $atts['membership_ids'] ) ) );
		}

		$signup_ts = self::get_signup_ts( get_current_user_id(), $product_ids );

		if ( null === $signup_ts ) {
			return '';
		}

		if ( $signup_ts <= $archive_publish_ts ) {
			return do_shortcode( $content );
		}

		return '';
	}
}
