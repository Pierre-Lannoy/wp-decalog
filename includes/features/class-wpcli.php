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
use Decalog\System\Date;
use Decalog\System\EmojiFlag;
use Decalog\System\Environment;
use Decalog\System\Markdown;
use Decalog\System\Option;
use Decalog\System\GeoIP;
use Decalog\System\PHP;
use Decalog\System\SharedMemory;
use Decalog\System\Timezone;
use Decalog\System\UUID;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\Autolog;
use Prometheus\RenderTextFormat;
use Spyc;

/**
 * Manages DecaLog, view events logs and send messages to loggers.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Wpcli {

	/**
	 * List of color format per level.
	 *
	 * @since    2.0.0
	 * @var array $level_color Level colors.
	 */
	private $level_color = [
		'standard' =>
			[
				'debug'     => '',
				'info'      => '%4%c',
				'notice'    => '%4%C',
				'warning'   => '%3%r',
				'error'     => '%1%y',
				'critical'  => '%1%Y',
				'alert'     => '%F%1%Y',
				'emergency' => '',
			],
		'soft'     =>
			[
				'debug'     => '',
				'info'      => '%0%c',
				'notice'    => '%0%C',
				'warning'   => '%0%Y',
				'error'     => '%0%r',
				'critical'  => '%0%R',
				'alert'     => '%0%F%R',
				'emergency' => '',
			],
	];

	/**
	 * List of exit codes.
	 *
	 * @since    2.0.0
	 * @var array $exit_codes Exit codes.
	 */
	private $exit_codes = [
		0   => 'operation successful.',
		1   => 'invalid logger type supplied.',
		2   => 'invalid logger uuid supplied.',
		3   => 'system loggers can\'t be managed.',
		4   => 'unable to create a new logger.',
		5   => 'unable to modify this logger.',
		6   => 'invalid listener id supplied.',
		7   => 'unrecognized setting.',
		8   => 'unrecognized action.',
		9   => 'invalid metric id supplied.',
		10  => 'forbidden or unknown level.',
		11  => 'unable to launch tail command, no shared memory manager found.',
		12  => 'histograms can\'t be displayed in command-line mode.',
		255 => 'unknown error.',
	];

	/**
	 * Flush output without warnings.
	 *
	 * @since    2.0.2
	 */
	private function flush() {
		// phpcs:ignore
		set_error_handler( null );
		// phpcs:ignore
		@ob_flush();
		// phpcs:ignore
		restore_error_handler();
	}

	/**
	 * Write ids as clean stdout.
	 *
	 * @param   array   $ids   The ids.
	 * @param   string  $field  Optional. The field to output.
	 * @since   2.0.0
	 */
	private function write_ids( $ids, $field = '' ) {
		$result = '';
		$last   = end( $ids );
		foreach ( $ids as $key => $id ) {
			if ( '' === $field ) {
				$result .= $key;
			} else {
				$result .= $id[ $field ];
			}
			if ( $id !== $last ) {
				$result .= ' ';
			}
		}
		// phpcs:ignore
		fwrite( STDOUT, $result );
	}

	/**
	 * Write an error.
	 *
	 * @param   integer  $code      Optional. The error code.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function error( $code = 255, $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() ) {
			// phpcs:ignore
			fwrite( STDOUT, '' );
			// phpcs:ignore
			exit( $code );
		} elseif ( $stdout ) {
			// phpcs:ignore
			fwrite( STDERR, ucfirst( $this->exit_codes[ $code ] ) );
			// phpcs:ignore
			exit( $code );
		} else {
			\WP_CLI::error( $this->exit_codes[ $code ] );
		}
	}

	/**
	 * Write a warning.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function warning( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::warning( $msg );
		}
	}

	/**
	 * Write a success.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function success( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::success( $msg );
		}
	}

	/**
	 * Write a wimple line.
	 *
	 * @param   string   $msg       The message.
	 * @param   string   $result    Optional. The result.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function line( $msg, $result = '', $stdout = false ) {
		if ( \WP_CLI\Utils\isPiped() || $stdout ) {
			// phpcs:ignore
			fwrite( STDOUT, $result );
		} else {
			\WP_CLI::line( $msg );
		}
	}

	/**
	 * Write a wimple log line.
	 *
	 * @param   string   $msg       The message.
	 * @param   boolean  $stdout    Optional. Clean stdout output.
	 * @since   2.0.0
	 */
	private function log( $msg, $stdout = false ) {
		if ( ! \WP_CLI\Utils\isPiped() && ! $stdout ) {
			\WP_CLI::log( $msg );
		}
	}

	/**
	 * Get params from command line.
	 *
	 * @param   array   $args   The command line parameters.
	 * @return  array The true parameters.
	 * @since   2.0.0
	 */
	private function get_params( $args ) {
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
	private function updated_proc( $processors, $proc, $value ) {
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
				$processors = array_diff( $processors, [ $key ] );
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
	private function logger_modify( $uuid, $args, $start = false ) {
		$params        = $this->get_params( $args );
		$loggers       = Option::network_get( 'loggers' );
		$logger        = $loggers[ $uuid ];
		$handler_types = new HandlerTypes();
		$handler       = $handler_types->get( $logger['handler'] );
		unset( $loggers[ $uuid ] );
		foreach ( $params as $param => $value ) {
			switch ( $param ) {
				case 'obfuscation':
				case 'pseudonymization':
					$logger['privacy'][ $param ] = (bool) $value;
					break;
				case 'proc_wp':
				case 'proc_http':
				case 'proc_php':
				case 'proc_trace':
					$logger['processors'] = $this->updated_proc( $logger['processors'], $param, (bool) $value );
					break;
				case 'level':
					if ( array_key_exists( strtolower( $value ), EventTypes::$levels ) ) {
						$logger['level'] = EventTypes::$levels[ strtolower( $value ) ];
					} else {
						$logger['level'] = $handler['minimal'];
					}
					break;
				case 'name':
					$logger['name'] = esc_html( (string) $value );
					break;
				default:
					if ( array_key_exists( $param, $handler['configuration'] ) ) {
						switch ( $handler['configuration'][ $param ]['control']['cast'] ) {
							case 'boolean':
								$logger['configuration'][ $param ] = (bool) $value;
								break;
							case 'integer':
								$logger['configuration'][ $param ] = (int) $value;
								break;
							case 'string':
								$logger['configuration'][ $param ] = (string) $value;
								break;
						}
					}
					break;
			}
		}
		if ( $start ) {
			$logger['running'] = true;
		}
		$loggers[ $uuid ] = $logger;
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
	private function logger_add( $handler, $args ) {
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
		if ( $this->logger_modify( $uuid, $args, Option::network_get( 'logger_autostart' ) ) === $uuid ) {
			return $uuid;
		}
		return '';
	}

	/**
	 * Filters records.
	 *
	 * @param array $records The records to filter.
	 * @param array $filters Optional. The filter to apply.
	 * @param string $index Optional. The starting index.
	 *
	 * @return  array   The filtered records.
	 * @since   2.0.0
	 */
	public static function records_filter( $records, $filters = [], $index = '' ) {
		$result = [];
		foreach ( $records as $idx => $record ) {
			foreach ( $filters as $key => $filter ) {
				switch ( $key ) {
					case 'level':
						if ( EventTypes::$levels[ $record['level'] ] < EventTypes::$levels[ $filter ] ) {
							continue 3;
						}
						break;
					default:
						if ( ! preg_match( $filter, $record[ $key ] ) ) {
							continue 3;
						}
				}
			}
			$result[ $idx ] = $record;
		}
		if ( '' !== $index ) {
			$tmp = [];
			foreach ( $result as $key => $record ) {
				if ( 0 < strcmp( $key, $index ) ) {
					$tmp[ $key ] = $record;
				}
			}
			$result = $tmp;
		}
		uksort( $result, 'strcmp' );
		return $result;
	}

	/**
	 * Format records.
	 *
	 * @param array     $records    The records to display.
	 * @param string    $mode       Optional. The displaying mode.
	 * @param integer   $pad        Optional. Line padding.
	 *
	 * @return  array   The ready to print records.
	 * @since   2.0.0
	 */
	public static function records_format( $records, $mode = '', $pad = 160 ) {
		$result = [];
		$geoip  = new GeoIP();
		foreach ( $records as $idx => $record ) {
			$timestamp     = '[' . Date::get_date_from_mysql_utc( $record['timestamp'], Timezone::network_get()->getName(), 'Y-m-d H:i:s' ) . ']';
			$channel_level = strtoupper( str_pad( $record['channel'], 6 ) ) . ' ' . strtoupper( str_pad( $record['level'], 9 ) );
			$component     = $record['component'];
			$message       = trim( $record['message'] );
			if ( 'unknown' !== $record['verb'] ) {
				$verb = str_pad( '[' . strtoupper( $record['verb'] ) . ']', 9 );
			} else {
				$verb = str_pad( '[-]', 9 );
			}

			if ( $geoip->is_installed() ) {
				$ip = EmojiFlag::get( $geoip->get_iso3166_alpha2( $record['remote_ip'] ) ) . ' ' . $record['remote_ip'];
			} else {
				$ip = $record['remote_ip'];
			}
			$url = $record['url'];
			if ( 'unknown' === $record['classname'] ) {
				$func = $record['function'] . '()';
			} else {
				$func = $record['classname'] . '::' . $record['function'] . '()';
			}
			$file = PHP::normalized_file_line( $record['file'], $record['line'] );
			if ( Environment::is_wordpress_multisite() ) {
				$sid = ' SID:' . str_pad( (string) $record['site_id'], 4, '0', STR_PAD_LEFT ) . ' ';
			} else {
				$sid = ' ';
			}
			$uid  = ' UID:' . str_pad( (string) $record['user_id'], 6, '0', STR_PAD_LEFT ) . ' ';
			$line = "$timestamp $channel_level$sid";
			switch ( $mode ) {
				case 'http':
					if ( 'unknown' !== $record['verb'] ) {
						$line = $line . "$verb $ip → $url";
					} else {
						$line = $line . "$verb $ip <No HTTP request>";
					}
					break;
				case 'php':
					$line = $line . "$func in $file";
					break;
				default:
					$line = $line . "$uid$component: $message";
			}
			$line = preg_replace( '/[\x00-\x1F\x7F\xA0]/u', '', $line );
			if ( $pad - 1 < strlen( $line ) ) {
				$line = substr( $line, 0, $pad - 1 ) . '…';
			}
			$result[ $idx ] = [
				'level' => strtolower( $record['level'] ),
				'line'  => decalog_mb_str_pad( $line, $pad ),
			];
		}
		return $result;
	}

	/**
	 * Displays records.
	 *
	 * @param   array   $records    The records to display.
	 * @param   string  $mode       Optional. The displaying mode.
	 * @param   string  $theme      Optional. Colors scheme.
	 * @param   integer $pad        Optional. Line padding.
	 * @since   2.0.0
	 */
	private function records_display( $records, $mode = '', $theme = 'standard', $pad = 160 ) {
		if ( ! array_key_exists( $theme, $this->level_color ) ) {
			$theme = 'standard';
		}
		foreach ( self::records_format( $records, $mode, $pad ) as $record ) {
			\WP_CLI::line( \WP_CLI::colorize( $this->level_color[ $theme ][ strtolower( $record['level'] ) ] ) . $record['line'] . \WP_CLI::colorize( '%n' ) );
		}
	}

	/**
	 * Get DecaLog details and operation modes.
	 *
	 * ## EXAMPLES
	 *
	 * wp log status
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 */
	public function status( $args, $assoc_args ) {
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
			if ( 0 === $list ) {
				$list = 'on no listener';
			} elseif ( 1 === $list ) {
				$list = 'on 1 listener';
			} else {
				$list = sprintf( 'on %d listeners', $list );
			}
		}
		if ( 0 === $run ) {
			$run  = '';
			$list = '';
		} elseif ( 1 === $run ) {
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
		if ( Option::network_get( 'logger_autostart' ) ) {
			\WP_CLI::line( 'Auto-Start: enabled.' );
		} else {
			\WP_CLI::line( 'Auto-Start: disabled.' );
		}
		if ( Autolog::is_enabled() ) {
			\WP_CLI::line( 'Auto-Logging: enabled.' );
		} else {
			\WP_CLI::line( 'Auto-Logging: disabled.' );
		}
		$geo = new GeoIP();
		if ( $geo->is_installed() ) {
			\WP_CLI::line( 'IP information support: yes (' . $geo->get_full_name() . ').' );
		} else {
			\WP_CLI::line( 'IP information support: no.' );
		}
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			\WP_CLI::line( 'Device detection support: yes (Device Detector v' . PODD_VERSION . ').' );
		} else {
			\WP_CLI::line( 'Device detection support: no.' );
		}
		if ( SharedMemory::$available ) {
			\WP_CLI::line( 'Shared memory support: yes (shmop v' . phpversion( 'shmop' ) . ').' );
		} else {
			\WP_CLI::line( 'Shared memory support: no.' );
		}
	}

	/**
	 * Get information on logger types.
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
	 * : The type of the logger to describe. Can be used to filter the list output too.
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing types.
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
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * Lists available types:
	 * + wp log type list
	 * + wp log type list --format=json
	 *
	 * Details the WordpressHandler logger type:
	 * + wp log type describe WordpressHandler
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function type( $args, $assoc_args ) {
		$stdout        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$action        = $args[0] ?? 'list';
		$uuid          = $args[1] ?? '';
		$handler_types = new HandlerTypes();
		$handlers      = [];
		foreach ( $handler_types->get_all() as $key => $handler ) {
			if ( 'system' !== $handler['class'] && ( '' === $uuid || $handler['id'] === $uuid ) ) {
				$handler['type']                          = $handler['id'];
				$handlers[ strtolower( $handler['id'] ) ] = $handler;
			}
		}
		uasort(
			$handlers,
			function ( $a, $b ) {
				return strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
			}
		);
		$uuid = '';
		if ( isset( $args[1] ) ) {
			$uuid = strtolower( $args[1] );
			if ( ! array_key_exists( $uuid, $handlers ) && 'list' !== $action ) {
				$uuid = '';
			}
		}
		if ( 'list' !== $action && '' === $uuid ) {
			$this->error( 1, $stdout );
		}
		switch ( $action ) {
			case 'list':
				$details = [];
				foreach ( $handlers as $key => $handler ) {
					$item = [];
					foreach ( $handler as $i => $h ) {
						if ( in_array( $i, [ 'type', 'class', 'name', 'version' ], true ) ) {
							$item[ $i ] = $h;
						}
					}
					$details[ $handler['type'] ] = $item;
				}
				if ( 'ids' === $format ) {
					$this->write_ids( $handlers, 'type' );
				} elseif ( 'yaml' === $format ) {
					$details = Spyc::YAMLDump( $details, true, true, true );
					$this->line( $details, $details, $stdout );
				} elseif ( 'json' === $format ) {
					$details = wp_json_encode( $details );
					$this->line( $details, $details, $stdout );
				} else {
					\WP_CLI\Utils\format_items( $format, $details, [ 'type', 'class', 'name', 'version' ] );
				}
				break;
			case 'describe':
				$example = [];
				$handler = $handlers[ $uuid ];
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%8' . $handler['name'] . ' - ' . $handler['id'] . '%n' ) );
				\WP_CLI::line( $handler['help'] );
				\WP_CLI::line( '' );
				\WP_CLI::line( \WP_CLI::colorize( '%UMinimal Level%n' ) );
				\WP_CLI::line( '' );
				\WP_CLI::line( '  ' . strtolower( Log::level_name( $handler['minimal'] ) ) );
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
					\WP_CLI::line( $list . '"' . strtolower( $level[1] ) . '": ' . $level[2] );
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
										\WP_CLI::line( $list . $point[0] . ': ' . $point[1] );
										break;
									case 'string':
										\WP_CLI::line( $list . '"' . $point[0] . '": ' . $point[1] );
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
	 * : The uuid of the logger to perform an action on or the type of the logger to add. Can be used to filter the list output too.
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
	 * : Allows overriding the output of the command when listing loggers. Note if json or yaml is chosen: full metadata is outputted too.
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
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * Lists configured loggers:
	 * + wp log logger list
	 * + wp log logger list --detail=full
	 * + wp log logger list --format=json
	 *
	 * Starts a logger:
	 * + wp log logger start 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Pauses a logger:
	 * + wp log logger pause 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Deletes old records of a logger:
	 * + wp log logger clean 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 *
	 * Deletes all records of a logger:
	 * + wp log logger purge 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 * + wp log logger purge 37cf1c00-d67d-4e7d-9518-e579f01407a7 --yes
	 *
	 * Permanently deletes a logger:
	 * + wp log logger remove 37cf1c00-d67d-4e7d-9518-e579f01407a7
	 * + wp log logger remove 37cf1c00-d67d-4e7d-9518-e579f01407a7 --yes
	 *
	 * Adds a new logger:
	 * + wp log logger add WordpressHandler --settings='{"rotate": 8000, "purge": 5, "level":"warning", "proc_wp": true}'
	 *
	 * Change the settings of a logger
	 * + wp log logger set 37cf1c00-d67d-4e7d-9518-e579f01407a7 --settings='{"proc_trace": false, "level":"warning"}'
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function logger( $args, $assoc_args ) {
		$stdout       = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format       = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$detail       = \WP_CLI\Utils\get_flag_value( $assoc_args, 'detail', 'short' );
		$uuid         = '';
		$type         = '';
		$ilog         = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$action       = $args[0] ?? 'list';
		$loggers_list = Option::network_get( 'loggers' );
		if ( isset( $args[1] ) ) {
			$uuid = $args[1];
			if ( 'add' === $action || 'list' === $action ) {
				$handler_types = new HandlerTypes();
				$t             = '';
				foreach ( $handler_types->get_all() as $handler ) {
					if ( 'system' !== $handler['class'] && strtolower( $uuid ) === strtolower( $handler['id'] ) ) {
						$t = $uuid;
					}
					if ( 'system' === $handler['class'] && strtolower( $uuid ) === strtolower( $handler['id'] ) ) {
						$t = 'system';
					}
				}
				$type = $t;
			}
			if ( 'add' !== $action ) {
				if ( ! array_key_exists( $uuid, $loggers_list ) ) {
					$uuid = '';
				} else {
					$handler_types = new HandlerTypes();
					foreach ( $handler_types->get_all() as $handler ) {
						if ( 'system' === $handler['class'] && $loggers_list[ $uuid ]['handler'] === $handler['id'] ) {
							$uuid = 'system';
						}
					}
				}
			}
		}
		if ( 'add' === $action && '' === $type ) {
			$this->error( 1, $stdout );
		} elseif ( 'system' === $uuid ) {
			$this->error( 3, $stdout );
		} elseif ( 'list' !== $action && '' === $uuid ) {
			$this->error( 2, $stdout );
		}
		switch ( $action ) {
			case 'list':
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
					$logger['processors'] = implode( ', ', $list );
					if ( ( '' === $uuid && '' === $type ) || $key === $uuid || strtolower( $logger['handler'] ) === strtolower( $type ) ) {
						$loggers[ $key ] = $logger;
					}
				}
				usort(
					$loggers,
					function ( $a, $b ) {
						return strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
					}
				);
				if ( 'full' === $detail ) {
					$detail = [ 'uuid', 'type', 'name', 'running', 'level', 'processors' ];
				} else {
					$detail = [ 'uuid', 'type', 'name', 'running' ];
				}
				if ( 'ids' === $format ) {
					$this->write_ids( $loggers, 'uuid' );
				} elseif ( 'yaml' === $format ) {
					$details = Spyc::YAMLDump( $loggers_list, true, true, true );
					$this->line( $details, $details, $stdout );
				} elseif ( 'json' === $format ) {
					$details = wp_json_encode( $loggers_list );
					$this->line( $details, $details, $stdout );
				} else {
					\WP_CLI\Utils\format_items( $format, $loggers, $detail );
				}
				break;
			case 'start':
				if ( $loggers_list[ $uuid ]['running'] ) {
					$this->line( sprintf( 'The logger %s is already running.', $uuid ), $uuid, $stdout );
				} else {
					$loggers_list[ $uuid ]['running'] = true;
					Option::network_set( 'loggers', $loggers_list );
					$ilog->info( sprintf( 'Logger "%s" has started.', $loggers_list[ $uuid ]['name'] ) );
					$this->success( sprintf( 'logger %s is now running.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'pause':
				if ( ! $loggers_list[ $uuid ]['running'] ) {
					$this->line( sprintf( 'The logger %s is already paused.', $uuid ), $uuid, $stdout );
				} else {
					$loggers_list[ $uuid ]['running'] = false;
					$ilog->info( sprintf( 'Logger "%s" has been paused.', $loggers_list[ $uuid ]['name'] ) );
					Option::network_set( 'loggers', $loggers_list );
					$this->success( sprintf( 'logger %s is now paused.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'purge':
				$loggers_list[ $uuid ]['uuid'] = $uuid;
				if ( 'WordpressHandler' !== $loggers_list[ $uuid ]['handler'] ) {
					$this->warning( sprintf( 'logger %s can\'t be purged.', $uuid ), $uuid, $stdout );
				} else {
					\WP_CLI::confirm( sprintf( 'Are you sure you want to purge logger %s?', $uuid ), $assoc_args );
					$factory = new LoggerFactory();
					$factory->purge( $loggers_list[ $uuid ] );
					$ilog->notice( sprintf( 'Logger "%s" has been purged.', $loggers_list[ $uuid ]['name'] ) );
					$this->success( sprintf( 'logger %s successfully purged.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'clean':
				$loggers_list[ $uuid ]['uuid'] = $uuid;
				if ( 'WordpressHandler' !== $loggers_list[ $uuid ]['handler'] ) {
					$this->warning( sprintf( 'logger %s can\'t be cleaned.', $uuid ), $uuid, $stdout );
				} else {
					$factory = new LoggerFactory();
					$count   = $factory->clean( $loggers_list[ $uuid ] );
					$this->log( sprintf( '%d record(s) deleted.', $count ), $stdout );
					$this->success( sprintf( 'logger %s successfully cleaned.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'remove':
				$loggers_list[ $uuid ]['uuid'] = $uuid;
				\WP_CLI::confirm( sprintf( 'Are you sure you want to remove logger %s?', $uuid ), $assoc_args );
				$factory = new LoggerFactory();
				$factory->destroy( $loggers_list[ $uuid ] );
				$ilog->notice( sprintf( 'Logger "%s" has been removed.', $loggers_list[ $uuid ]['name'] ) );
				unset( $loggers_list[ $uuid ] );
				Option::network_set( 'loggers', $loggers_list );
				$this->success( sprintf( 'logger %s successfully removed.', $uuid ), $uuid, $stdout );
				break;
			case 'add':
				$result = $this->logger_add( $type, $assoc_args );
				if ( '' === $result ) {
					$ilog->error( 'Unable to add a logger.', 1 );
					$this->error( 4, $stdout );
				} else {
					$loggers_list = Option::network_get( 'loggers' );
					$ilog->notice( sprintf( 'Logger "%s" has been saved.', $loggers_list[ $result ]['name'] ) );
					$this->success( sprintf( 'logger %s successfully created.', $result ), $result, $stdout );
				}
				break;
			case 'set':
				$result = $this->logger_modify( $uuid, $assoc_args );
				if ( '' === $result ) {
					$ilog->error( 'Unable to modify a logger.', 1 );
					$this->error( 5, $stdout );
				} else {
					$loggers_list = Option::network_get( 'loggers' );
					$ilog->notice( sprintf( 'Logger "%s" has been saved.', $loggers_list[ $result ]['name'] ) );
					$this->success( sprintf( 'logger %s successfully saved.', $result ), $result, $stdout );
				}
				break;
		}
	}

	/**
	 * Manage Decalog listeners.
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
	 * : The id of the listener to perform an action on. Can be used to filter the list output too.
	 *
	 * [--detail=<detail>]
	 * : The details of the output when listing listeners. Note if json or yaml is chosen: full metadata is outputted too.
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
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * Lists configured listeners:
	 * + wp log listener list
	 * + wp log listener list --detail=full
	 * + wp log listener list --format=json
	 *
	 * Enables a listener:
	 * + wp log listener enable wpdb
	 *
	 * Disables a listener:
	 * wp log listener disable wpdb
	 *
	 * Activates auto-listening:
	 * + wp log listener auto-on
	 * + wp log listener auto-on --yes
	 *
	 * Deactivates auto-listening:
	 * + wp log listener auto-off
	 * + wp log listener auto-off --yes
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function listener( $args, $assoc_args ) {
		$stdout    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$detail    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'detail', 'short' );
		$activated = Option::network_get( 'listeners' );
		$listeners = [];
		$uuid      = '';
		$ilog      = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
		$action    = $args[0] ?? 'list';
		if ( isset( $args[1] ) ) {
			$uuid = strtolower( $args[1] );
			if ( ! array_key_exists( $uuid, $listeners ) && 'list' !== $action ) {
				$uuid = '';
			}
		}
		foreach ( ListenerFactory::$infos as $listener ) {
			if ( '' === $uuid || $listener['id'] === $uuid ) {
				$listener['enabled']          = Option::network_get( 'autolisteners' ) ? 'auto' : ( in_array( $listener['id'], $activated, true ) ? 'yes' : 'no' );
				$listener['available']        = $listener['available'] ? 'yes' : 'no';
				$listeners[ $listener['id'] ] = $listener;
				if ( 'yaml' === $format || 'json' === $format ) {
					unset( $listeners[ $listener['id'] ]['id'] );
				}
			}
		}
		uasort(
			$listeners,
			function ( $a, $b ) {
				return strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
			}
		);

		if ( 'list' !== $action && 'auto-on' !== $action && 'auto-off' !== $action && '' === $uuid ) {
			$this->error( 6, $stdout );
		}
		switch ( $action ) {
			case 'list':
				if ( 'full' === $detail ) {
					$detail = [ 'id', 'class', 'name', 'product', 'version', 'available', 'enabled' ];
				} else {
					$detail = [ 'id', 'name', 'available', 'enabled' ];
				}
				if ( 'ids' === $format ) {
					$this->write_ids( $listeners, 'id' );
				} elseif ( 'yaml' === $format ) {
					$details = Spyc::YAMLDump( $listeners, true, true, true );
					$this->line( $details, $details, $stdout );
				} elseif ( 'json' === $format ) {
					$details = wp_json_encode( $listeners );
					$this->line( $details, $details, $stdout );
				} else {
					\WP_CLI\Utils\format_items( $format, $listeners, $detail );
				}
				break;
			case 'enable':
				if ( in_array( $uuid, $activated, true ) ) {
					$this->line( sprintf( 'the listener %s is already enabled.', $uuid ), $uuid, $stdout );
				} else {
					$activated[] = $uuid;
					Option::network_set( 'listeners', $activated );
					$ilog->info( 'Listeners settings updated.' );
					$this->success( sprintf( 'the listener %s is now enabled.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'disable':
				if ( ! in_array( $uuid, $activated, true ) ) {
					$this->line( sprintf( 'the listener %s is already disabled.', $uuid ), $uuid, $stdout );
				} else {
					$list = [];
					foreach ( $activated as $listener ) {
						if ( $listener !== $uuid ) {
							$list[] = $listener;
						}
					}
					Option::network_set( 'listeners', $list );
					$ilog->info( 'Listeners settings updated.' );
					$this->success( sprintf( 'the listener %s is now disabled.', $uuid ), $uuid, $stdout );
				}
				break;
			case 'auto-on':
				if ( Option::network_get( 'autolisteners' ) ) {
					$this->line( 'auto-listening is already activated.', '', $stdout );
				} else {
					\WP_CLI::confirm( 'Are you sure you want to activate auto-listening?', $assoc_args );
					Option::network_set( 'autolisteners', true );
					$ilog->info( 'Listeners settings updated.' );
					$this->success( 'auto-listening is now activated.', '', $stdout );
				}
				break;
			case 'auto-off':
				if ( ! Option::network_get( 'autolisteners' ) ) {
					$this->line( 'auto-listening is already deactivated.', '', $stdout );
				} else {
					\WP_CLI::confirm( 'Are you sure you want to deactivate auto-listening?', $assoc_args );
					Option::network_set( 'autolisteners', false );
					$ilog->info( 'Listeners settings updated.' );
					$this->success( 'auto-listening is now deactivated.', '', $stdout );
				}
				break;
		}

	}

	/**
	 * Modify DecaLog main settings.
	 *
	 * ## OPTIONS
	 *
	 * <enable|disable>
	 * : The action to take.
	 *
	 * <early-loading|auto-logging|auto-start>
	 * : The setting to change.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * wp log settings enable auto-logging
	 * wp log settings disable early-loading --yes
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function settings( $args, $assoc_args ) {
		$stdout  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$action  = isset( $args[0] ) ? (string) $args[0] : '';
		$setting = isset( $args[1] ) ? (string) $args[1] : '';
		switch ( $action ) {
			case 'enable':
				switch ( $setting ) {
					case 'early-loading':
						Option::network_set( 'earlyloading', true );
						$this->success( 'early-loading is now activated.', '', $stdout );
						break;
					case 'auto-start':
						Option::network_set( 'logger_autostart', true );
						$this->success( 'auto-start is now activated.', '', $stdout );
						break;
					case 'auto-logging':
						Autolog::activate();
						$this->success( 'auto-logging is now activated.', '', $stdout );
						break;
					default:
						$this->error( 7, $stdout );
				}
				break;
			case 'disable':
				switch ( $setting ) {
					case 'early-loading':
						\WP_CLI::confirm( 'Are you sure you want to deactivate early-loading?', $assoc_args );
						Option::network_set( 'earlyloading', false );
						$this->success( 'early-loading is now deactivated.', '', $stdout );
						break;
					case 'auto-start':
						\WP_CLI::confirm( 'Are you sure you want to deactivate auto-start?', $assoc_args );
						Option::network_set( 'logger_autostart', false );
						$this->success( 'auto-start is now deactivated.', '', $stdout );
						break;
					case 'auto-logging':
						\WP_CLI::confirm( 'Are you sure you want to deactivate auto-logging?', $assoc_args );
						Autolog::deactivate();
						$this->success( 'auto-logging is now deactivated.', '', $stdout );
						break;
					default:
						$this->error( 7, $stdout );
				}
				break;
			default:
				$this->error( 8, $stdout );
		}
	}

	/**
	 * Send a message to all running loggers.
	 *
	 * ## OPTIONS
	 *
	 * <info|notice|warning|error|critical|alert>
	 * : The level of the event.
	 *
	 * <message>
	 * : The message.
	 *
	 * [--code=<code>]
	 * : The code of the event. Must be a positive integer. Default is 0.
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * wp log send info 'This is an informational message'
	 * wp log send warning 'Page not found' --code=404
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function send( $args, $assoc_args ) {
		$stdout  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$level   = isset( $args[0] ) ? strtolower( $args[0] ) : '';
		$message = isset( $args[1] ) ? (string) $args[1] : '';
		$code    = isset( $assoc_args['code'] ) ? (int) $assoc_args['code'] : 0;
		if ( 0 > $code ) {
			$code = 0;
		}
		if ( ! in_array( $level, [ 'info', 'notice', 'warning', 'error', 'critical', 'alert' ], true ) ) {
			$this->error( 10, $stdout );
		}
		$logger = Log::bootstrap( 'core', 'WP-CLI', defined( 'WP_CLI_VERSION' ) ? WP_CLI_VERSION : 'x' );
		$logger->log( $level, $message, $code );
		$this->success( 'message sent.', 'OK', $stdout );
	}

	/**
	 * Get information on exit codes.
	 *
	 * ## OPTIONS
	 *
	 * <list>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 * ---
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing exit codes.
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
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * Lists available exit codes:
	 * + wp log exitcode list
	 * + wp log exitcode list --format=json
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function exitcode( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$action = $args[0] ?? 'list';
		$codes  = [];
		foreach ( $this->exit_codes as $key => $msg ) {
			$codes[ $key ] = [
				'code'    => $key,
				'meaning' => ucfirst( $msg ),
			];
		}
		switch ( $action ) {
			case 'list':
				if ( 'ids' === $format ) {
					$this->write_ids( $codes );
				} else {
					\WP_CLI\Utils\format_items( $format, $codes, [ 'code', 'meaning' ] );
				}
				break;
		}
	}

	/**
	 * Get information on collected metrics.
	 *
	 * ## OPTIONS
	 *
	 * <list|dump|get>
	 * : The action to take.
	 * ---
	 * options:
	 *  - list
	 *  - dump
	 *  - get
	 * ---
	 *
	 * [<metrics_id>]
	 * : The id of the metric to perform an action on. Can be used to filter the list or dump output too.
	 *
	 * [--format=<format>]
	 * : Allows overriding the output of the command when listing or dumping metrics.
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
	 * [--detail=<detail>]
	 * : The details of the output when listing metrics. Note if json or yaml is chosen: full metadata is outputted too.
	 * ---
	 * default: short
	 * options:
	 *  - short
	 *  - full
	 * ---
	 *
	 * [--stdout]
	 * : Use clean STDOUT output to use results in scripts. Unnecessary when piping commands because piping is detected by DecaLog.
	 *
	 * ## EXAMPLES
	 *
	 * Lists currently collected metrics:
	 * + wp log metrics list
	 * + wp log metrics list --format=json
	 *
	 * Dumps current metrics value:
	 * + wp log metrics dump
	 * + wp log metrics dump --format=yaml
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function metrics( $args, $assoc_args ) {
		$stdout = \WP_CLI\Utils\get_flag_value( $assoc_args, 'stdout', false );
		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$detail = \WP_CLI\Utils\get_flag_value( $assoc_args, 'detail', 'short' );
		$action = $args[0] ?? 'list';
		$uuid   = $args[1] ?? '';
		$list   = [];
		foreach ( DMonitor::get_metrics_definition() as $key => $metrics ) {
			if ( '' === $uuid || $key === $uuid ) {
				unset( $metrics['name'] );
				if ( 'yaml' !== $format && 'json' !== $format ) {
					$metrics['id'] = $key;
				}
				$list[ $key ] = $metrics;
			}
		}
		switch ( $action ) {
			case 'list':
				if ( 'full' === $detail ) {
					$detail = [ 'id', 'class', 'profile', 'type', 'source', 'version', 'description' ];
				} else {
					$detail = [ 'id', 'description' ];
				}
				if ( 'ids' === $format ) {
					$this->write_ids( $list );
				} elseif ( 'yaml' === $format ) {
					$details = Spyc::YAMLDump( $list, true, true, true );
					$this->line( $details, $details, $stdout );
				} elseif ( 'json' === $format ) {
					$details = wp_json_encode( $list );
					$this->line( $details, $details, $stdout );
				} else {
					\WP_CLI\Utils\format_items( $format, $list, $detail );
				}
				break;
			case 'dump':
				ListenerFactory::force_monitoring_close();
				$monitor     = new DMonitor( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
				$production  = $monitor->prod_registry()->getMetricFamilySamples();
				$development = $monitor->dev_registry()->getMetricFamilySamples();
				$result      = [];
				if ( 'full' === $detail ) {
					$detail = [ 'id', 'type', 'key', 'value' ];
				} else {
					$detail = [ 'id', 'type', 'key', 'value' ];
				}
				foreach ( array_merge( $production, $development ) as $metrics ) {
					if ( '' === $uuid || $metrics->getName() === $uuid ) {
						switch ( $metrics->getType() ) {
							case 'gauge':
							case 'counter':
								$s         = [];
								$s['id']   = $metrics->getName();
								$s['type'] = $metrics->getType();
								$s['key']  = 'current';
								$samples   = $metrics->getSamples();
								if ( 1 === count( $samples ) ) {
									$s['value']                    = (float) $samples[0]->getValue();
									$result[ $metrics->getName() ] = $s;
								}
								break;
							case 'histogram':
								foreach ( $metrics->getSamples() as $sample ) {
									$s         = [];
									$s['id']   = $metrics->getName();
									$s['type'] = $metrics->getType();
									$name      = $sample->getName();
									if ( strlen( $name ) - 4 === strpos( $name, '_sum' ) ) {
										$s['key']   = 'sum';
										$s['value'] = (float) $sample->getValue();
									}
									if ( strlen( $name ) - 6 === strpos( $name, '_count' ) ) {
										$s['key']   = 'count';
										$s['value'] = (float) $sample->getValue();
									}
									if ( strlen( $name ) - 7 === strpos( $name, '_bucket' ) ) {
										$labels     = $sample->getLabelValues();
										$s['key']   = 'bucket - ' . end( $labels );
										$s['value'] = (float) $sample->getValue();
										$name      .= '_' . end( $labels );
									}
									$result[ $name ] = $s;
								}
								break;
						}
					}
				}
				if ( 'ids' === $format ) {
					$this->write_ids( $result );
				} elseif ( 'yaml' === $format ) {
					$details = Spyc::YAMLDump( $result, true, true, true );
					$this->line( $details, $details, $stdout );
				} elseif ( 'json' === $format ) {
					$details = wp_json_encode( $result );
					$this->line( $details, $details, $stdout );
				} else {
					\WP_CLI\Utils\format_items( $format, $result, $detail );
				}
				break;
			case 'get':
				ListenerFactory::force_monitoring_close();
				$monitor     = new DMonitor( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
				$production  = $monitor->prod_registry()->getMetricFamilySamples();
				$development = $monitor->dev_registry()->getMetricFamilySamples();
				foreach ( array_merge( $production, $development ) as $metrics ) {
					if ( $metrics->getName() === $uuid ) {
						switch ( $metrics->getType() ) {
							case 'gauge':
							case 'counter':
								$samples = $metrics->getSamples();
								if ( 1 === count( $samples ) ) {
									$this->success( $metrics->getName() . ' current value is ' . (float) $samples[0]->getValue(), (float) $samples[0]->getValue(), $stdout );
									exit( 0 );
								}
								break;
							case 'histogram':
								$this->error( 12, $stdout );
								break;
						}
					}
				}
				$this->error( 9, $stdout );
				break;
		}
	}

	/**
	 * Display past or current events.
	 *
	 * ## OPTIONS
	 *
	 * [<count>]
	 * : An integer value [1-60] indicating how many most recent events to display. If 0 or nothing is supplied as value, a live session is launched, displaying events as soon as they occur.
	 *
	 * [--level=<level>]
	 * : The minimal level to display.
	 * ---
	 * default: info
	 * options:
	 *  - info
	 *  - notice
	 *  - warning
	 *  - error
	 *  - critical
	 *  - alert
	 *  - emergency
	 * ---
	 *
	 *[--filter=<filter>]
	 * : The misc. filters to apply. Show only records matching the specified pattern.
	 * MUST be a json string containing pairs "field":"regexp".
	 * ---
	 * default: '{}'
	 * available fields: 'channel', 'message', 'class', 'source', 'code', 'site_id', 'user_id', 'remote_ip', 'url', 'verb', 'server','referrer', 'file', 'line', 'classname', 'function'
	 * example: '{"source":"/Jetpack/", "remote_ip":"/(135.|164.)/"}'
	 * ---
	 *
	 * [--format=<format>]
	 * : Specifies the outputted event format.
	 * ---
	 * default: wp
	 * options:
	 *  - wp
	 *  - http
	 *  - php
	 * ---
	 *
	 * [--col=<columns>]
	 * : The Number of columns (char in a row) to display. Default is 160. Min is 80 and max is 400.
	 *
	 * [--theme=<theme>]
	 * : Modifies the colors scheme.
	 * ---
	 * default: standard
	 * options:
	 *  - standard
	 *  - soft
	 * ---
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * ## NOTES
	 *
	 * + This command needs shared memory support for PHP: the PHP module "shmop" must be activated in your PHP web configuration AND in your PHP command-line configuration.
	 * + This command relies on an internal logger. If this logger is not started at launch time, you will be prompted to starting it - this logger may be left in the "running" state without impact on your website.
	 * + This internal logger records events from info to emergency levels. It doesn't record debug-level events.
	 * + If the logger has just been started there will not be much to display if <count> is different from 0...
	 * + In a live session, just use CTRL-C to terminate it.
	 *
	 * ## EXAMPLES
	 *
	 * wp log tail
	 * wp log tail 20
	 * wp log tail 20 --level=warning
	 * wp log tail --filter='{"source":"/Jetpack/", "remote_ip":"/(135.|164.)/"}'
	 * wp log tail --filter='{"source":"/WordPress/"} --theme=soft --format=wp'
	 *
	 *
	 *   === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-decalog/blob/master/WP-CLI.md ===
	 *
	 */
	public function tail( $args, $assoc_args ) {
		if ( ! function_exists( 'shmop_open' ) || ! function_exists( 'shmop_read' ) || ! function_exists( 'shmop_write' ) || ! function_exists( 'shmop_delete' ) || ! function_exists( 'shmop_close' ) ) {
			$this->error( 11 );
		}
		if ( ! Autolog::is_enabled() ) {
			\WP_CLI::warning( 'auto-logging is currently disabled. The tail command needs auto-logging...' );
			\WP_CLI::confirm( 'Would you like to enable auto-logging and to resume command?', $assoc_args );
			Autolog::activate();
		}
		$filters = [];
		$count   = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( 0 > $count || 60 < $count ) {
			$count = 0;
		}
		$col = isset( $assoc_args['col'] ) ? (int) $assoc_args['col'] : 160;
		if ( 80 > $col ) {
			$col = 80;
		}
		if ( 400 < $col ) {
			$col = 400;
		}
		$filter = \json_decode( isset( $assoc_args['filter'] ) ? (string) $assoc_args['filter'] : '{}', true );
		if ( is_array( $filter ) ) {
			foreach ( [ 'channel', 'message', 'class', 'source', 'code', 'site_id', 'user_id', 'remote_ip', 'url', 'verb', 'server', 'referrer', 'file', 'line', 'classname', 'function' ] as $field ) {
				if ( array_key_exists( $field, $filter ) ) {
					$value = (string) $filter[ $field ];
					if ( '' === $value ) {
						continue;
					}
					switch ( $field ) {
						case 'source':
							$filters['component'] = $value;
							break;
						default:
							$filters[ $field ] = $value;
					}
				}
			}
		}
		$level = isset( $assoc_args['level'] ) ? (string) $assoc_args['level'] : 'info';
		if ( ! in_array( $level, [ 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency' ], true ) ) {
			$this->error( 10 );
		}
		$filters['level'] = $level;
		$mode             = isset( $assoc_args['format'] ) ? (string) $assoc_args['format'] : 'classic';
		$records          = SharedMemoryHandler::read();
		if ( 0 === $count ) {
			$logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_NAME, DECALOG_VERSION );
			$logger->notice( 'Live console launched.' );
			while ( true ) {
				$this->records_display( self::records_filter( SharedMemoryHandler::read(), $filters ), $mode, $assoc_args['theme'] ?? 'standard', $col );
				$this->flush();
			}
		} else {
			$this->records_display( array_slice( self::records_filter( $records, $filters ), -$count ), $mode, $assoc_args['theme'] ?? 'standard', $col );
		}
	}

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'WP-CLI.md', $attributes );
	}

}

add_shortcode( 'decalog-wpcli', [ 'Decalog\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'log', 'Decalog\Plugin\Feature\Wpcli' );
}
