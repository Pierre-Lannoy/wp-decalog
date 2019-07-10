<?php
/**
 * Autoload for WordPress plugin boilerplate.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'WPPluginBoilerplate\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'WPPluginBoilerplate\System\\' ) === 0 ) {
				$filepath = WPPB_INCLUDES_DIR . 'system/';
			}
			if ( strpos( $class, 'WPPluginBoilerplate\Plugin\\' ) === 0 ) {
				$filepath = WPPB_INCLUDES_DIR . 'plugin/';
			}
			if ( strpos( $class, 'WPPluginBoilerplate\Libraries\\' ) === 0 ) {
				$filepath = WPPB_VENDOR_DIR;
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = WPPB_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = WPPB_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
