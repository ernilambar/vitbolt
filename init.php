<?php
/**
 * Vitbolt loader: single entry point and "latest version wins" when used by multiple plugins.
 *
 * @package Nilambar\Vitbolt
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( defined( 'VITBOLT_LOADED' ) ) {
	return;
}

if ( ! defined( 'VITBOLT_VERSION' ) ) {
	define( 'VITBOLT_VERSION', '1.0.0' );
}

/**
 * Get the version of a copy of the library at the given package root.
 *
 * @param string $package_root Absolute path to the package root (directory containing init.php).
 * @return string Version string.
 */
function vitbolt_get_package_version( $package_root ) {
	$installed_file = $package_root . '/../composer/installed.json';
	if ( is_readable( $installed_file ) ) {
		$json = file_get_contents( $installed_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = json_decode( $json, true );
		if ( isset( $data['packages'] ) && is_array( $data['packages'] ) ) {
			foreach ( $data['packages'] as $pkg ) {
				if ( isset( $pkg['name'] ) && 'ernilambar/vitbolt' === $pkg['name'] && isset( $pkg['version'] ) ) {
					return $pkg['version'];
				}
			}
		}
		if ( isset( $data['installed'] ) && is_array( $data['installed'] ) ) {
			foreach ( $data['installed'] as $pkg ) {
				if ( isset( $pkg['name'] ) && 'ernilambar/vitbolt' === $pkg['name'] && isset( $pkg['version'] ) ) {
					return $pkg['version'];
				}
			}
		}
	}
	return VITBOLT_VERSION;
}

/**
 * Resolve the path to the copy of the library with the highest version.
 *
 * @return string Absolute path to the winning package root (no trailing slash).
 */
function vitbolt_resolve_winner_root() {
	static $root = null;

	if ( null !== $root ) {
		return $root;
	}

	$candidates = array();

	$candidates[ __DIR__ ] = vitbolt_get_package_version( __DIR__ );

	if ( defined( 'WP_PLUGIN_DIR' ) && is_dir( WP_PLUGIN_DIR ) ) {
		$pattern = WP_PLUGIN_DIR . '/*/vendor/ernilambar/vitbolt/init.php';
		$files   = glob( $pattern );
		if ( is_array( $files ) ) {
			foreach ( $files as $path ) {
				$package_root = dirname( $path );
				if ( isset( $candidates[ $package_root ] ) ) {
					continue;
				}
				$candidates[ $package_root ] = vitbolt_get_package_version( $package_root );
			}
		}
	}

	$best_root = array_key_first( $candidates );
	$best_ver  = $candidates[ $best_root ];

	foreach ( $candidates as $dir => $ver ) {
		if ( version_compare( $ver, $best_ver, '>' ) ) {
			$best_ver  = $ver;
			$best_root = $dir;
		}
	}

	$root = $best_root;
	return $root;
}

/**
 * PSR-4 autoloader for Nilambar\Vitbolt namespace.
 *
 * @param string $class_name Fully qualified class name.
 * @return void
 */
function vitbolt_autoload( $class_name ) {
	$prefix = 'Nilambar\\Vitbolt\\';
	if ( 0 !== strpos( $class_name, $prefix ) ) {
		return;
	}

	$relative = substr( $class_name, strlen( $prefix ) );
	$file     = str_replace( '\\', '/', $relative ) . '.php';
	$root     = vitbolt_resolve_winner_root();
	$path     = $root . '/src/' . $file;

	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

spl_autoload_register( 'vitbolt_autoload', true, true );

define( 'VITBOLT_LOADED', true );
