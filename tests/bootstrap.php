<?php
/**
 * PHPUnit bootstrap (minimal WordPress stubs; no full WP load).
 *
 * @package MemberPress_Forward_Only
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$GLOBALS['wp_test_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $option
	 * @param mixed  $default
	 * @return mixed
	 */
	function get_option( $option, $default = false ) {
		if ( array_key_exists( $option, $GLOBALS['wp_test_options'] ) ) {
			return $GLOBALS['wp_test_options'][ $option ];
		}
		return $default;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * @param string|array $value
	 * @return string|array
	 */
	function wp_unslash( $value ) {
		return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * @param string $str
	 * @return string
	 */
	function sanitize_textarea_field( $str ) {
		return trim( (string) $str );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * @param string $data
	 * @return string
	 */
	function wp_kses_post( $data ) {
		return (string) $data;
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * @param mixed $maybeint
	 * @return int
	 */
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * @param string $title
	 * @return string
	 */
	function sanitize_title( $title ) {
		$title = trim( (string) $title );
		return strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $title ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data
	 * @return string|false
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * @param array|string $args
	 * @param array        $defaults
	 * @return array
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args =& $args;
		} else {
			wp_parse_str( (string) $args, $parsed_args );
		}

		if ( is_array( $defaults ) && ! empty( $defaults ) ) {
			return array_merge( $defaults, $parsed_args );
		}

		return $parsed_args;
	}
}

if ( ! function_exists( 'wp_parse_str' ) ) {
	/**
	 * @param string $input
	 * @param array  $result
	 * @return void
	 */
	function wp_parse_str( $input, &$result ) {
		parse_str( $input, $result );
	}
}

require_once ABSPATH . 'includes/class-mepr-forward-only-bootstrap.php';
require_once ABSPATH . 'includes/class-mepr-forward-only-settings.php';
require_once ABSPATH . 'includes/class-mepr-forward-only-admin.php';
require_once ABSPATH . 'includes/class-mepr-forward-only-core.php';
