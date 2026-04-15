<?php
/**
 * Backward-compatible global helpers (same names as the original snippet).
 *
 * @package MemberPress_Forward_Only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mepr_forward_only_get_signup_ts' ) ) {
	/**
	 * Member's earliest active transaction timestamp.
	 *
	 * @param int        $user_id     User ID.
	 * @param array|null $product_ids Optional membership IDs.
	 * @return int|null
	 */
	function mepr_forward_only_get_signup_ts( int $user_id, ?array $product_ids = null ): ?int {
		return Mepr_Forward_Only_Core::get_signup_ts( $user_id, $product_ids );
	}
}

if ( ! function_exists( 'mepr_forward_only_rule_applies' ) ) {
	/**
	 * Whether a rule ID is subject to forward-only enforcement.
	 *
	 * @param int $rule_id Rule ID.
	 * @return bool
	 */
	function mepr_forward_only_rule_applies( int $rule_id ): bool {
		return Mepr_Forward_Only_Core::rule_applies( $rule_id );
	}
}

if ( ! function_exists( 'mepr_forward_only_is_excluded' ) ) {
	/**
	 * Whether the post is excluded from forward-only checks.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	function mepr_forward_only_is_excluded( WP_Post $post ): bool {
		return Mepr_Forward_Only_Core::is_excluded( $post );
	}
}
