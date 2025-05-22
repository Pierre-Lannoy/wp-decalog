<?php
/**
 * Autoload for DecaLog.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$paths = array(
			'Decalog\\System\\'           => DECALOG_INCLUDES_DIR . 'system/',
			'Decalog\\Plugin\\Feature\\'  => DECALOG_INCLUDES_DIR . 'features/',
			'Decalog\\Plugin\\'           => DECALOG_INCLUDES_DIR . 'plugin/',
			'Decalog\\Processor\\'        => DECALOG_INCLUDES_DIR . 'processors/',
			'Decalog\\Handler\\'          => DECALOG_INCLUDES_DIR . 'handlers/',
			'Decalog\\Storage\\'          => DECALOG_INCLUDES_DIR . 'storage/',
			'Decalog\\Formatter\\'        => DECALOG_INCLUDES_DIR . 'formatters/',
			'Decalog\\Listener\\WP_CLI\\' => DECALOG_INCLUDES_DIR . 'listeners/wp-cli/',
			'Decalog\\Listener\\'         => DECALOG_INCLUDES_DIR . 'listeners/',
			'Decalog\\Panel\\'            => DECALOG_INCLUDES_DIR . 'panels/',
			'Decalog\\Library\\'          => DECALOG_VENDOR_DIR,
			'Decalog\\Integration\\'      => DECALOG_INCLUDES_DIR . 'integrations/',
			'Decalog\\API\\'              => DECALOG_INCLUDES_DIR . 'api/',
			'Decalog\\'                   => DECALOG_INCLUDES_DIR . 'api/',
		);

		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'Decalog\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			foreach ( $paths as $prefix => $dir ) {
				if ( strpos( $class, $prefix ) === 0 ) {
					$filepath = $dir;
					break;
				}
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = DECALOG_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = DECALOG_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
