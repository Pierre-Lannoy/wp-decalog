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
	 * <list|start|pause|clean|purge|remove>
	 * : The action to take.
	 * ---
	 * default: list
	 * options:
	 *  - list
	 *  - start
	 *  - pause
	 *  - clean
	 *  - purge
	 *  - remove
	 * ---
	 *
	 * [<logger_uuid>]
	 * : The uuid of the logger to perform an action on.
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
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * ## EXAMPLES
	 *
	 * Lists configured loggers:
	 * + wp decalog logger list
	 * + wp decalog logger list --detail=full
	 * + wp decalog logger list --format=json
	 *
	 * Starts a logger:
	 * + wp decalog logger start 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Pauses a logger:
	 * + wp decalog logger pause 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Deletes old records of a logger:
	 * + wp decalog logger clean 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Deletes all records of a logger:
	 * + wp decalog logger purge 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 * + wp decalog logger purge 37cf1c00-d67d-4e7d-9518-e579f01407a7 --yes
	 *
	 * Permanently deletes a logger:
	 * + wp decalog logger remove 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 * + wp decalog logger remove 37cf1c00-d67d-4e7d-9518-e579f01407a7 --yes
	 *
	 */
	public static function logger( $args, $assoc_args ) {
		$loggers_list = Option::network_get( 'loggers' );
		$uuid         = '';
		$ilog         = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$action       = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$uuid = $args[1];
			if ( ! array_key_exists( $uuid, $loggers_list ) ) {
				$uuid = '';
			}
		}
		if ( 'list' !== $action && '' === $uuid ) {
			\WP_CLI::warning( 'Invalid logger uuid supplied. Please specify a valid logger uuid:' );
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
			case 'start':
				if ( $loggers_list[$uuid]['running'] ) {
					\WP_CLI::line( sprintf( 'The logger %s is already running.', $uuid ) );
				} else {
					$loggers_list[$uuid]['running'] = true;
					Option::network_set( 'loggers', $loggers_list );
					$ilog->info( sprintf( 'Logger "%s" has started.', $loggers_list[ $uuid ]['name'] ) );
					\WP_CLI::success( sprintf( 'The logger %s is now running.', $uuid ) );
				}
				break;
			case 'pause':
				if ( ! $loggers_list[$uuid]['running'] ) {
					\WP_CLI::line( sprintf( 'The logger %s is already paused.', $uuid ) );
				} else {
					$loggers_list[$uuid]['running'] = false;
					$ilog->info( sprintf( 'Logger "%s" has been paused.', $loggers_list[ $uuid ]['name'] ) );
					Option::network_set( 'loggers', $loggers_list );
					\WP_CLI::success( sprintf( 'The logger %s is now paused.', $uuid ) );
				}
				break;
			case 'purge':
				$loggers_list[$uuid]['uuid'] = $uuid;
				if ( 'WordpressHandler' !== $loggers_list[$uuid]['handler'] ) {
					\WP_CLI::warning( sprintf( 'The logger %s can\'t be purged.', $uuid ) );
				} else {
					\WP_CLI::confirm( sprintf( 'Are you sure you want to purge logger %s?', $uuid ), $assoc_args );
					$factory = new LoggerFactory();
					$factory->purge( $loggers_list[$uuid] );
					$ilog->notice( sprintf( 'Logger "%s" has been purged.', $loggers_list[ $uuid ]['name'] ) );
					\WP_CLI::success( sprintf( 'The logger %s has been purged.', $uuid ) );
				}
				break;
			case 'clean':
				$loggers_list[$uuid]['uuid'] = $uuid;
				if ( 'WordpressHandler' !== $loggers_list[$uuid]['handler'] ) {
					\WP_CLI::warning( sprintf( 'The logger %s can\'t be cleaned.', $uuid ) );
				} else {
					$factory = new LoggerFactory();
					$count   = $factory->clean( $loggers_list[$uuid] );
					\WP_CLI::log( sprintf( '%d record(s) deleted.', $count ) );
					\WP_CLI::success( sprintf( 'The logger %s has been cleaned.', $uuid ) );
				}
				break;
			case 'remove':
				$loggers_list[$uuid]['uuid'] = $uuid;
				\WP_CLI::confirm( sprintf( 'Are you sure you want to remove logger %s?', $uuid ), $assoc_args );
				$factory = new LoggerFactory();
				$factory->destroy( $loggers_list[$uuid] );
				unset( $loggers_list[$uuid] );
				$ilog->notice( sprintf( 'Logger "%s" has been removed.', $loggers_list[ $uuid ]['name'] ) );
				Option::network_set( 'loggers', $loggers_list );
				\WP_CLI::success( sprintf( 'The logger %s has been removed.', $uuid ) );
				break;
		}

	}

	/**
	 * Manage Decalog listener.
	 *
	 * ## OPTIONS
	 *
	 * <list|enable|disable|auto-on|auto-off>
	 * : The action to take.
	 * ---
	 * default: list
	 * options:
	 *  - list
	 *  - enable
	 *  - disable
	 *  - auto-on
	 *  - auto-off
	 * ---
	 *
	 * [<listener_id>]
	 * : The id of the listener to perform an action on.
	 *
	 * [--detail=<detail>]
	 * : The details of the output when listing listeners.
	 * ---
	 * default: short
	 * options:
	 *  - short
	 *  - full
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing listeners.
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
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * ## EXAMPLES
	 *
	 * Lists configured listeners:
	 * + wp decalog listener list
	 * + wp decalog listener list --detail=full
	 * + wp decalog listener list --format=json
	 *
	 * Enables a listener:
	 * + wp decalog listener enable wpdb
	 *
	 * Disables a listener:
	 * wp decalog listener disable wpdb
	 *
	 * Activates auto-listening:
	 * + wp decalog listener auto-on
	 * + wp decalog listener auto-on --yes
	 *
	 * Deactivates auto-listening:
	 * + wp decalog listener auto-off
	 * + wp decalog listener auto-off --yes
	 *
	 */
	public static function listener( $args, $assoc_args ) {
		$activated = Option::network_get( 'listeners' );
		$listeners = [];
		foreach ( ListenerFactory::$infos as $listener ) {
			$listener['enabled']        = Option::network_get( 'autolisteners') ? 'auto' : ( in_array( $listener['id'], $activated, true ) ? 'yes' : 'no' );
			$listener['available']      = $listener['available'] ? 'yes' : 'no';
			$listeners[$listener['id']] = $listener;
		}
		uasort(
			$listeners,
			function ( $a, $b ) {
				return strcmp( strtolower( $a[ 'name' ] ), strtolower( $b[ 'name' ] ) );
			}
		);
		$uuid   = '';
		$ilog   = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$action = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$uuid = strtolower( $args[1] );
			if ( ! array_key_exists( $uuid, $listeners ) ) {
				$uuid = '';
			}
		}
		if ( 'list' !== $action && 'auto-on' !== $action && 'auto-off' !== $action && '' === $uuid ) {
			\WP_CLI::warning( 'Invalid listener id supplied. Please specify a valid listener id:' );
			$action = 'list';
		}

		switch ( $action ) {
			case 'list':
				$detail = isset( $assoc_args['detail'] ) ? $assoc_args['detail'] : 'short';
				if ( 'full' === $detail ) {
					$detail = [ 'id', 'class', 'name', 'product', 'version', 'available', 'enabled' ];
				} else {
					$detail = [ 'id', 'name', 'available', 'enabled' ];
				}
				\WP_CLI\Utils\format_items( $assoc_args['format'], $listeners, $detail );
				break;
			case 'enable':
				if ( in_array( $uuid, $activated, true ) ) {
					\WP_CLI::line( sprintf( 'The listener %s is already enabled.', $uuid ) );
				} else {
					$activated[] = $uuid;
					Option::network_set( 'listeners', $activated );
					$ilog->info( 'Listeners settings updated.' );
					\WP_CLI::success( sprintf( 'The listener %s is now enabled.', $uuid ) );
					if ( Option::network_get( 'autolisteners' ) ) {
						\WP_CLI::warning( 'Auto-listening is activated so, enabling/disabling listeners have no effect.' );
					}
				}
				break;
			case 'disable':
				if ( ! in_array( $uuid, $activated, true ) ) {
					\WP_CLI::line( sprintf( 'The listener %s is already disabled.', $uuid ) );
				} else {
					$list = [];
					foreach ( $activated as $listener ) {
						if ( $listener !== $uuid ) {
							$list[] = $listener;
						}
					}
					Option::network_set( 'listeners', $list );
					$ilog->info( 'Listeners settings updated.' );
					\WP_CLI::success( sprintf( 'The listener %s is now disabled.', $uuid ) );
					if ( Option::network_get( 'autolisteners' ) ) {
						\WP_CLI::warning( 'Auto-listening is activated so, enabling/disabling listeners have no effect.' );
					}
				}
				break;
			case 'auto-on':
				if ( Option::network_get( 'autolisteners' ) ) {
					\WP_CLI::warning( 'Auto-listening is already activated.' );
				} else {
					\WP_CLI::confirm( 'Are you sure you want to activate auto-listening?', $assoc_args );
					Option::network_set( 'autolisteners', true );
					$ilog->info( 'Listeners settings updated.' );
					\WP_CLI::success( 'Auto-listening is now activated.' );
				}
				break;
			case 'auto-off':
				if ( ! Option::network_get( 'autolisteners' ) ) {
					\WP_CLI::warning( 'Auto-listening is already deactivated.' );
				} else {
					\WP_CLI::confirm( 'Are you sure you want to deactivate auto-listening?', $assoc_args );
					Option::network_set( 'autolisteners', false );
					$ilog->info( 'Listeners settings updated.' );
					\WP_CLI::success( 'Auto-listening is now deactivated.' );
				}
				break;
		}

	}


	public static function test( $args, $assoc_args ) {
		$test = time();
		while ( true ) {
			if ( $test !== time() ) {
				$date = new \DateTime();
				$test = time();
				\WP_CLI::line( $date->format( 'Ymd-H:i:s.u' ) );
			}
			usleep(100000);
		}
		//\WP_CLI::line( ini_get( 'max_execution_time' ) );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'decalog status', [ Wpcli::class, 'status' ] );
	\WP_CLI::add_command( 'decalog logger', [ Wpcli::class, 'logger' ] );
	\WP_CLI::add_command( 'decalog listener', [ Wpcli::class, 'listener' ] );
	\WP_CLI::add_command( 'decalog test', [ Wpcli::class, 'test' ] );
}