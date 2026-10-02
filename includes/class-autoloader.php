<?php
/**
 * PSR-4 Autoloader for SEO City Pages.
 *
 * @package SeoCityPages
 */

namespace SeoCityPages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader Class.
 */
class Autoloader {

	/**
	 * Namespace prefix to match.
	 *
	 * @var string
	 */
	private static $prefix = 'SeoCityPages\\';

	/**
	 * Base directory path for namespace.
	 *
	 * @var string
	 */
	private static $base_dir = '';

	/**
	 * Register the autoloader.
	 *
	 * @param string $base_dir Base directory path.
	 * @return void
	 */
	public static function register( string $base_dir ): void {
		self::$base_dir = rtrim( $base_dir, '/\\' ) . '/';
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class ): void {
		if ( strncmp( self::$prefix, $class, strlen( self::$prefix ) ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::$prefix ) );
		$parts          = explode( '\\', $relative_class );
		$class_name     = array_pop( $parts );

		$slug = strtolower( str_replace( '_', '-', $class_name ) );
		$sub_dir = ! empty( $parts ) ? implode( '/', $parts ) . '/' : '';

		// Candidates to check: class, interface, trait.
		$candidates = [
			'class-' . $slug . '.php',
			'interface-' . $slug . '.php',
			'interface-' . preg_replace( '/-interface$/', '', $slug ) . '.php',
			'trait-' . $slug . '.php',
		];

		foreach ( $candidates as $candidate ) {
			$file = self::$base_dir . $sub_dir . $candidate;
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
}
