<?php
/**
 * Libraries autoload for WordPress plugin boilerplate.
 *
 * @package Libraries
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$file = '';
		foreach ( WPPluginBoilerplate\Libraries\Libraries::get_psr4() as $library ) {
			$len = strlen( $library['prefix'] );
			if ( strncmp( $library['prefix'], $class, $len ) === 0 ) {
				$file = $library['base'] . str_replace( '\\', '/', substr( $class, $len ) ) . '.php';
			}
		}
		if ( '' === $file ) {
			foreach ( WPPluginBoilerplate\Libraries\Libraries::get_mono() as $library ) {
				if ( $library['detect'] === $class ) {
					$file = $library['base'] . $library['detect'] . '.php';
				}
			}
		}
		if ( '' !== $file ) {
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
