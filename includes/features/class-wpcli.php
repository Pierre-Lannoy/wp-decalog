<?php
/**
 * WP-CLI for DecaLog.
 *
 * Adds WP-CLI commands to DecaLog
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\Handler\SharedMemoryHandler;
use Decalog\Listener\ListenerFactory;
use Decalog\Plugin\Feature\Log;
use Decalog\System\Cache;
use Decalog\System\Environment;
use Decalog\System\Option;
use Decalog\System\GeoIP;
use Decalog\System\UUID;

/**
 * WP-CLI for DecaLog.
 *
 * Defines methods and properties for WP-CLI commands.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Wpcli {

	/**
	 * Get params from command line.
	 *
	 * @param   array   $args   The command line parameters.
	 * @return  array The true parameters.
	 * @since   2.0.0
	 */
	private static function get_params ( $args ) {
		$result = '';
		if ( array_key_exists( 'settings', $args ) ) {
			$result = \json_decode( $args['settings'], true );
		}
		if ( ! $result || ! is_array( $result ) ) {
			$result = [];
		}
		return $result;
	}

	/**
	 * Update processors.
	 *
	 * @param   array   $processors     The current processors.
	 * @param   string  $proc           The processor to set.
	 * @param   boolean $value          The value to set.
	 * @return  array The updated processors.
	 * @since   2.0.0
	 */
	private static function updated_proc ( $processors, $proc, $value ) {
		$key = '';
		switch ( $proc ) {
			case 'proc_wp':
				$key = 'WordpressProcessor';
				break;
			case 'proc_http':
				$key = 'WWWProcessor';
				break;
			case 'proc_php':
				$key = 'IntrospectionProcessor';
				break;
			case 'proc_trace':
				$key = 'BacktraceProcessor';
				break;
		}
		if ( '' !== $key ) {
			if ( $value && ! in_array( $key, $processors, true ) ) {
				$processors[] = $key;
			}
			if ( ! $value && in_array( $key, $processors, true ) ) {
				$processors = array_diff( $processors, [$key] );
			}
		}
		return $processors;
	}

	/**
	 * Modify a logger.
	 *
	 * @param   string  $uuid   The logger uuid.
	 * @param   array   $args   The command line parameters.
	 * @param   boolean $start  Optional. Force running mode.
	 * @return  string The logger uuid.
	 * @since   2.0.0
	 */
	private static function logger_modify ( $uuid, $args, $start = false ) {
		$params        = self::get_params( $args );
		$loggers       = Option::network_get( 'loggers' );
		$logger        = $loggers[$uuid];
		$handler_types = new HandlerTypes();
		$handler       = $handler_types->get( $logger['handler'] );
		unset ( $loggers[$uuid] );
		foreach ( $params as $param => $value ) {
			switch ( $param ) {
				case 'obfuscation':
				case 'pseudonymization':
					$logger['privacy'][$param] = (bool) $value;
					break;
				case 'proc_wp':
				case 'proc_http':
				case 'proc_php':
				case 'proc_trace':
					$logger['processors'] = self::updated_proc( $logger['processors'], $param, (bool) $value );
					break;
				case 'level':
					if ( array_key_exists( strtolower( $value ), EventTypes::$levels ) ) {
						$logger['level'] = EventTypes::$levels[ strtolower( $value ) ];
					} else {
						$logger['level'] = $handler['minimal'];
					}
					break;
				case 'name':
					$logger['name'] = esc_html( (string) $value) ;
					break;
				default:
					if ( array_key_exists( $param, $handler['configuration'] ) ) {
						switch ( $handler['configuration'][$param]['control']['cast'] ) {
							case 'boolean':
								$logger['configuration'][$param] = (bool) $value;
								break;
							case 'integer':
								$logger['configuration'][$param] = (integer) $value;
								break;
							case 'string':
								$logger['configuration'][$param] = (string) $value;
								break;
						}
					}
					break;
			}
		}
		if ( $start ) {
			$logger['running'] = true;
		}
		$loggers[$uuid] = $logger;
		Option::network_set( 'loggers', $loggers );
		return $uuid;
	}

	/**
	 * Add a logger.
	 *
	 * @param   string  $uuid   The logger uuid.
	 * @param   array   $args   The command line parameters.
	 * @return  string The logger uuid.
	 * @since   2.0.0
	 */
	private static function logger_add ( $handler, $args ) {
		$uuid             = UUID::generate_v4();
		$logger           = [
			'uuid'    => $uuid,
			'name'    => esc_html__( 'New logger', 'decalog' ),
			'handler' => $handler,
			'running' => false,
		];
		$loggers          = Option::network_get( 'loggers' );
		$factory          = new LoggerFactory();
		$loggers[ $uuid ] = $factory->check( $logger, true );
		Option::network_set( 'loggers', $loggers );
		if ( self::logger_modify( $uuid, $args, Option::network_get( 'logger_autostart' ) ) === $uuid ) {
			return $uuid;
		}
		return '';
	}

	/**
	 * Get DecaLog details and operation modes
	 *
	 * ## EXAMPLES
	 *
	 * wp decalog status
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
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
	 * Get informations on logger types.
	 *
	 * ## OPTIONS
	 *
	 * <list|describe>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 *  - describe
	 * ---
	 *
	 * [<logger_type>]
	 * : The type of the logger to describe.
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
	 * Lists available types:
	 * + wp decalog type list
	 * + wp decalog type list --format=json
	 *
	 * Starts a logger:
	 * + wp decalog type describe WordpressHandler
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public static function handler( $args, $assoc_args ) {
		$handler_types = new HandlerTypes();
		$handlers      = [];
		foreach ( $handler_types->get_all() as $key => $handler ) {
			if ( 'system' !== $handler['class'] ) {
				$handler['type']                        = $handler['id'];
				$handlers[strtolower( $handler['id'] )] = $handler;
			}
		}
		uasort(
			$handlers,
			function ( $a, $b ) {
				return strcmp( strtolower( $a[ 'name' ] ), strtolower( $b[ 'name' ] ) );
			}
		);
		$uuid   = '';
		$action = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$uuid = strtolower( $args[1] );
			if ( ! array_key_exists( $uuid, $handlers ) ) {
				$uuid = '';
			}
		}
		if ( 'list' !== $action && '' === $uuid ) {
			\WP_CLI::warning( 'Invalid logger type supplied. Please specify a valid logger type:' );
			$action = 'list';
		}
		switch ( $action ) {
			case 'list':
				\WP_CLI\Utils\format_items( $assoc_args['format'], $handlers, [ 'type', 'class', 'name', 'version' ] );
				break;
			case 'describe':
				$example = [];
				$handler = $handlers[$uuid];
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%8' . $handler['name'] . ' - ' . $handler['id'] . '%n' ) );
				\WP_CLI::line( $handler['help'] );
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%UMinimal Level%n' ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( '  ' .  strtolower( Log::level_name( $handler['minimal'] ) ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%UParameters%n' ) );
				\WP_CLI::line( '' );
				$param = '  * ';
				$elem  = '    - ';
				$list  = '       ';
				\WP_CLI::line( $param . 'Name - Used only in admin dashboard.' );
				\WP_CLI::line( $elem . 'field name: name' );
				\WP_CLI::line( $elem . 'field type: string' );
				\WP_CLI::line( $elem . 'default value: "New Logger"' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Minimal level - Minimal reported level.' );
				\WP_CLI::line( $elem . 'field name: level' );
				\WP_CLI::line( $elem . 'field type: string' );
				\WP_CLI::line( $elem . 'default value: "' . strtolower( Log::level_name( $handler['minimal'] ) ) . '"' );
				\WP_CLI::line( $elem . 'available values:' );
				foreach ( Log::get_levels( EventTypes::$levels[ strtolower( Log::level_name( $handler['minimal'] ) ) ] ) as $level ) {
					\WP_CLI::line( $list . '"' . strtolower( $level[1] ) . '": ' . $level[2]);
				}
				\WP_CLI::line( '' );
				foreach ( $handler['configuration'] as $key => $conf ) {
					if ( ! $conf['show'] || ! $conf['control']['enabled'] ) {
						continue;
					}
					\WP_CLI::line( $param . $conf['name'] . ' - ' . $conf['help'] );
					\WP_CLI::line( $elem . 'field name: ' . $key );
					\WP_CLI::line( $elem . 'field type: ' . $conf['type'] );
					switch ( $conf['control']['type'] ) {
						case 'field_input_integer':
							\WP_CLI::line( $elem . 'default value: ' . $conf['default'] );
							\WP_CLI::line( $elem . 'range: [' . $conf['control']['min'] . '-' . $conf['control']['max'] . ']' );
							$example[] = '"' . $key . '": ' . $conf['default'];
							break;
						case 'field_checkbox':
							\WP_CLI::line( $elem . 'default value: ' . ( $conf['default'] ? 'true' : 'false' ) );
							$example[] = '"' . $key . '": ' . ( $conf['default'] ? 'true' : 'false' );
							break;
						case 'field_input_text':
							\WP_CLI::line( $elem . 'default value: "' . $conf['default'] . '"' );
							$example[] = '"' . $key . '": "' . $conf['default'] . '"';
							break;
						case 'field_select':
							switch ( $conf['control']['cast'] ) {
								case 'integer':
									\WP_CLI::line( $elem . 'default value: ' . $conf['default'] );
									$example[] = '"' . $key . '": ' . $conf['default'];
									break;
								case 'string':
									\WP_CLI::line( $elem . 'default value: "' . $conf['default'] . '"' );
									$example[] = '"' . $key . '": "' . $conf['default'] . '"';
									break;
							}
							\WP_CLI::line( $elem . 'available values:' );
							foreach ( $conf['control']['list'] as $point ) {
								switch ( $conf['control']['cast'] ) {
									case 'integer':
										\WP_CLI::line( $list . $point[0] . ': ' . $point[1]);
										break;
									case 'string':
										\WP_CLI::line( $list . '"' . $point[0] . '": ' . $point[1]);
										break;
								}
							}
							break;
					}
					\WP_CLI::line( '' );
				}
				\WP_CLI::line( $param . 'IP obfuscation - Log fields will contain hashes instead of real IPs.' );
				\WP_CLI::line( $elem . 'field name: obfuscation' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: false' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'User pseudonymization - Log fields will contain hashes instead of user IDs & names.' );
				\WP_CLI::line( $elem . 'field name: pseudonymization' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: false' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: WordPress - Allows to log site, user and remote IP of the current request.' );
				\WP_CLI::line( $elem . 'field name: proc_wp' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: true' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: HTTP request - Allows to log url, method, referrer and remote IP of the current web request.' );
				\WP_CLI::line( $elem . 'field name: proc_http' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: true' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: PHP introspection - Allows to log line, file, class and function generating the event.' );
				\WP_CLI::line( $elem . 'field name: proc_php' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: true' );
				\WP_CLI::line( '' );
				\WP_CLI::line( $param . 'Reported details: Backtrace - Allows to log the full PHP and WordPress call stack.' );
				\WP_CLI::line( $elem . 'field name: proc_trace' );
				\WP_CLI::line( $elem . 'field type: boolean' );
				\WP_CLI::line( $elem . 'default value: false' );
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%UExample%n' ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( '  {' . implode( ', ', $example ) . '}' );
				\WP_CLI::line( '' );
				break;
		}

	}

	/**
	 * Manage Decalog loggers.
	 *
	 * ## OPTIONS
	 *
	 * <list|start|pause|clean|purge|remove|add|set>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 *  - start
	 *  - pause
	 *  - clean
	 *  - purge
	 *  - remove
	 *  - add
	 *  - set
	 * ---
	 *
	 * [<uuid_or_type>]
	 * : The uuid of the logger to perform an action on or the type of the logger to add.
	 *
	 * [--settings=<settings>]
	 * : The settings needed by "add" and "modify" actions.
	 * MUST be a string containing a json configuration.
	 * ---
	 * default: '{}'
	 * example: '{"host": "syslog.collection.eu.sumologic.com", "timeout": 800, "ident": "DecaLog", "format": 1}'
	 * ---
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
	 * Adds a new logger:
	 * + wp decalog logger add WordpressHandler {"rotate": 8000, "purge": 5, "level":"warning", "proc_wp": true}
	 *
	 * Change the settings of a logger
	 * + wp decalog logger set 37cf1c00-d67d-4e7d-9518-e579f01407a7 --settings='{"proc_trace": false, "level":"warning"}'
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public static function logger( $args, $assoc_args ) {
		$loggers_list = Option::network_get( 'loggers' );
		$uuid         = '';
		$ilog         = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$action       = isset( $args[0] ) ? $args[0] : 'list';
		if ( isset( $args[1] ) ) {
			$uuid = $args[1];
			if ( 'add' === $action ) {
				$handler_types = new HandlerTypes();
				$t             = '';
				foreach ( $handler_types->get_all() as $handler ) {
					if ( 'system' !== $handler['class'] && strtolower( $uuid ) === strtolower( $handler['id'] ) ) {
						$t = $uuid;
					}
				}
				$uuid = $t;
			} else {
				if ( ! array_key_exists( $uuid, $loggers_list ) ) {
					$uuid = '';
				}
			}
		}
		if ( 'add' === $action && '' === $uuid ) {
			\WP_CLI::warning( 'Invalid logger type supplied. Please specify a valid logger type:' );
			self::handler( [], $assoc_args);
			$action = '';
		} elseif ( 'list' !== $action && '' === $uuid ) {
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
				$ilog->notice( sprintf( 'Logger "%s" has been removed.', $loggers_list[ $uuid ]['name'] ) );
				unset( $loggers_list[$uuid] );
				Option::network_set( 'loggers', $loggers_list );
				\WP_CLI::success( sprintf( 'The logger %s has been removed.', $uuid ) );
				break;
			case 'add':
				$result = self::logger_add( $uuid, $assoc_args );
				if ( '' === $result ) {
					$ilog->error( 'Unable to add a logger.', 1 );
					\WP_CLI::error( 'Unable to add logger.' );
				} else {
					$loggers_list = Option::network_get( 'loggers' );
					$ilog->notice( sprintf( 'Logger "%s" has been saved.', $loggers_list[ $uuid ]['name'] ) );
					\WP_CLI::line( $result );
					\WP_CLI::success( 'Logger successfully added.' );
				}
				break;
			case 'set':
				$result = self::logger_modify( $uuid, $assoc_args );
				if ( '' === $result ) {
					$ilog->error( 'Unable to modify a logger.', 1 );
					\WP_CLI::error( 'Unable to set logger.' );
				} else {
					$loggers_list = Option::network_get( 'loggers' );
					$ilog->notice( sprintf( 'Logger "%s" has been saved.', $loggers_list[ $uuid ]['name'] ) );
					\WP_CLI::line( $result );
					\WP_CLI::success( 'Logger successfully set.' );
				}
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
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
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

	/**
	 * Launch a live logging session in this console. Use CTRL-C to end the session.
	 *
	 * [--level=<level>]
	 * : The minimal level to log.
	 * ---
	 * default: info
	 * options:
	 *  - debug
	 *  - info
	 *  - notice
	 *  - warning
	 *  - error
	 *  - critical
	 *  - alert
	 *  - emergency
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * wp decalog live --level=debug
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public static function live( $args, $assoc_args ) {
		if ( ! function_exists( 'shmop_open' ) || ! function_exists( 'shmop_read' ) || ! function_exists( 'shmop_write' ) || ! function_exists( 'shmop_delete' ) || ! function_exists( 'shmop_close' )) {
			\WP_CLI::error( 'Unable to launch live logging, no shared memory manager found.' );
		}

		//$levelstr = isset( $assoc_args['level'] ) ? $assoc_args['level'] : 'notice';





		$uuid = self::logger_add( 'SharedMemoryHandler', [ 'level' => 100, 'ftok' => 'z', 'source' => '' ] );
		self::logger( [ 0 => 'start', 1 => $uuid ], [ 'quiet' => 'yes' ] );
		while ( true ) {
			$records = [];//SharedMemoryHandler::read( $uuid );
			foreach ( $records as $record ) {
				\WP_CLI::line( $record['timestamp'] . '  ' . $record['channel'] . '  ' . $record['level'] );
			}
			//usleep(100000);
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
	\WP_CLI::add_command( 'decalog type', [ Wpcli::class, 'handler' ] );
	\WP_CLI::add_command( 'decalog listener', [ Wpcli::class, 'listener' ] );
	\WP_CLI::add_command( 'decalog live', [ Wpcli::class, 'live' ] );
	//\WP_CLI::add_command( 'decalog test', [ Wpcli::class, 'test' ] );
}