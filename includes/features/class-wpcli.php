<?php
/**
 * Watchdog for DecaLog.
 *
 * This listener is used in case of 'PhpListener' deactivation to
 * allow class banning.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Listener\ListenerFactory;
use Decalog\Plugin\Feature\Log;
use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\System\GeoIP;

/**
 * Watchdog for DecaLog.
 *
 * Defines methods and properties for watchdog class.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Wpcli {

	/**
	 * Get DecaLog details and operation modes
	 *
	 * ## EXAMPLES
	 *
	 * wp decalog status
	 */
	public static function status( $args, $assoc_args ) {
		$run  = 0;
		$list = 0;
		foreach ( Option::network_get( 'loggers' ) as $key => $logger ) {
			if ( $logger['running'] ) {
				$run++;
			}
		}
		if ( Option::network_get( 'autolisteners' ) ) {
			$list = 'on all available listeners';
		} else {
			$listeners = ListenerFactory::$infos;
			foreach ( $listeners as $listener ) {
				if ( $listener['available'] && in_array( $listener['id'], Option::network_get( 'listeners' ), true ) ) {
					$list++;
				}
			}
			if ( 0 === $list) {
				$list = 'on no listener';
			} elseif ( 1 === $list) {
				$list = 'on 1 listener';
			} else {
				$list = sprintf( 'on %d listeners', $list );
			}
		}
		if ( 0 === $run) {
			$run  = '';
			$list = '';
		} elseif ( 1 === $run) {
			$run = '1 logger';
		} else {
			$run = sprintf( '%d loggers', $run );
		}
		\WP_CLI::line( sprintf( '%s running %s %s.', Environment::plugin_version_text(), $run, $list ) );
		if ( Option::network_get( 'earlyloading' ) ) {
			\WP_CLI::line( 'Early-Loading: enabled.' );
		} else {
			\WP_CLI::line( 'Early-Loading: disabled.' );
		}
		$geo = new GeoIP();
		if ( $geo->is_installed() ) {
			\WP_CLI::line( 'IP information support: yes (' . $geo->get_full_name() . ').');
		} else {
			\WP_CLI::line( 'IP information support: no.' );
		}
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			\WP_CLI::line( 'Device detection support: yes (Device Detector v' . PODD_VERSION . ').');
		} else {
			\WP_CLI::line( 'Device detection support: no.' );
		}
	}

	/**
	 * Manage Decalog loggers.
	 *
	 * ## OPTIONS
	 *
	 * <list|start|pause|purge|remove>
	 * : The action to take.
	 * ---
	 * default: list
	 * options:
	 *  - list
	 *  - start
	 *  - pause
	 *  - purge
	 *  - remove
	 * ---
	 *
	 * [<logger_uuid>]
	 * : The id of the logger to perform an action on.
	 *
	 * [--detail=<detail>]
	 * : The details of the output when listing loggers.
	 * ---
	 * default: short
	 * options:
	 *  - short
	 *  - full
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing loggers.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - ids
	 *  - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * wp decalog logger list
	 * wp decalog logger list --detail=full
	 * wp decalog logger list --format=json
	 * wp jetpack module activate stats
	 * wp jetpack module deactivate stats
	 * wp jetpack module toggle stats
	 * wp jetpack module activate all
	 * wp jetpack module deactivate all
	 */
	public static function logger( $args, $assoc_args ) {
		$loggers_list = Option::network_get( 'loggers' );
		$action       = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$logger_id = $args[1];






		} elseif ( 'list' !== $action ) {
			\WP_CLI::line( 'Please specify a valid logger.' );
			$action = 'list';
		}

		switch ( $action ) {
			case 'list':
				$detail          = isset( $assoc_args['detail'] ) ? $assoc_args['detail'] : 'short';
				$handler_types   = new HandlerTypes();
				$processor_types = new ProcessorTypes();
				$loggers         = [];
				foreach ( $loggers_list as $key => $logger ) {
					$handler           = $handler_types->get( $logger['handler'] );
					$logger['type']    = $handler['name'];
					$logger['uuid']    = $key;
					$logger['level']   = strtolower( Log::level_name( $logger['level'] ) );
					$logger['running'] = $logger['running'] ? 'yes' : 'no';
					$list              = [ 'Standard' ];
					foreach ( $logger['processors'] as $processor ) {
						$list[] = $processor_types->get( $processor )['name'];
					}
					$logger['processors'] =  implode( ', ', $list );
					$loggers[]            = $logger;
				}
				usort(
					$loggers,
					function ( $a, $b ) {
						return strcmp( strtolower( $a[ 'name' ] ), strtolower( $b[ 'name' ] ) );
					}
				);
				if ( 'full' === $detail ) {
					$detail = [ 'uuid', 'type', 'name', 'running', 'level', 'processors' ];
				} else {
					$detail = [ 'uuid', 'type', 'name', 'running' ];
				}
				\WP_CLI\Utils\format_items( $assoc_args['format'], $loggers, $detail );
				break;
		}

	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'decalog status', [ Wpcli::class, 'status' ] );
	\WP_CLI::add_command( 'decalog logger', [ Wpcli::class, 'logger' ] );
}