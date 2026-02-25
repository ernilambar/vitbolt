<?php
/**
 * PHPUnit bootstrap: WordPress function stubs.
 *
 * @package Nilambar\Vitbolt
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// ---------------------------------------------------------------------------
// WordPress stub: WP_Error class.
// ---------------------------------------------------------------------------

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stub for unit tests.
	 */
	class WP_Error {} // phpcs:ignore
}

// ---------------------------------------------------------------------------
// WordPress function stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * Stub for trailingslashit().
	 *
	 * @param string $string Path or URL.
	 * @return string String with trailing slash.
	 */
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Stub for wp_parse_args().
	 *
	 * @param array|object $args     Values to merge.
	 * @param array        $defaults Default values.
	 * @return array Merged array.
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * Stub for wp_remote_get().
	 *
	 * Returns whatever is stored in $GLOBALS['wp_remote_get_response'].
	 *
	 * @param string $url  Request URL.
	 * @param array  $args Request arguments.
	 * @return mixed
	 */
	function wp_remote_get( $url, $args = array() ) {
		return $GLOBALS['wp_remote_get_response'] ?? null;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Stub for is_wp_error().
	 *
	 * @param mixed $thing Value to check.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * Stub for wp_remote_retrieve_response_code().
	 *
	 * @param array $response HTTP response array.
	 * @return int HTTP status code.
	 */
	function wp_remote_retrieve_response_code( $response ) {
		return isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 0;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stub for add_filter() — no-op in tests.
	 *
	 * @param string   $tag           Filter tag.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted args.
	 */
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	/**
	 * Stub for wp_enqueue_script() — no-op, records call in global for tests.
	 *
	 * @param string $handle    Script handle.
	 * @param string $src      Script URL.
	 * @param array  $deps     Dependencies.
	 * @param mixed  $version  Version.
	 * @param bool   $in_footer In footer.
	 */
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $version = null, $in_footer = false ) {
		$GLOBALS['wp_enqueue_script_calls']   = $GLOBALS['wp_enqueue_script_calls'] ?? array();
		$GLOBALS['wp_enqueue_script_calls'][] = array(
			'handle'    => $handle,
			'src'       => $src,
			'deps'      => $deps,
			'version'   => $version,
			'in_footer' => $in_footer,
		);
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	/**
	 * Stub for wp_enqueue_style() — no-op, records call in global for tests.
	 *
	 * @param string $handle Style handle.
	 * @param string $src    Style URL.
	 * @param array  $deps   Dependencies.
	 * @param mixed  $version Version.
	 */
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $version = null ) {
		$GLOBALS['wp_enqueue_style_calls']   = $GLOBALS['wp_enqueue_style_calls'] ?? array();
		$GLOBALS['wp_enqueue_style_calls'][] = array(
			'handle'  => $handle,
			'src'     => $src,
			'deps'    => $deps,
			'version' => $version,
		);
	}
}
