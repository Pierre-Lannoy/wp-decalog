<?php
/**
 * Logger consistency handling
 *
 * Handles all logger consistency operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Decalog\System\Option;
use DLMonolog\Logger;
use function Automattic\Jetpack\Creative_Mail\error_notice;

/**
 * Define the logger consistency functionality.
 *
 * Handles all logger consistency operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class LoggerFactory {

	/**
	 * Debugging flag.
	 *
	 * @since  3.2.0
	 * @var    $debugging    $debugging    True if debugging mode, false otherwise.
	 */
	public static $debugging = false;

	/**
	 * The HandlerTypes instance.
	 *
	 * @since  1.0.0
	 * @var    HandlerTypes    $handler_types    The handlers types.
	 */
	private $handler_types;

	/**
	 * The processorTypes instance.
	 *
	 * @since  1.0.0
	 * @var    processorTypes    $processor_types    The processors types.
	 */
	private $processor_types;

	/**
	 * The substitutable configuration items.
	 *
	 * @since  3.9.0
	 * @var    array    $substitutable    The configuration items that could be substituted by environment variables.
	 */
	private $substitutable = [ 'url', 'ftags', 'service', 'host', 'token', 'org', 'bucket', 'id' , 'cloudid', 'user', 'pass', 'index', 'key', 'ident', 'recipients', 'users', 'title', 'filename' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->handler_types   = new HandlerTypes();
		$this->processor_types = new ProcessorTypes();
	}

	/**
	 * Create an instance of $class_name.
	 *
	 * @param   string $class_name The class name.
	 * @param   array  $args   The param of the constructor for $class_name class.
	 * @return  boolean|object The instance of the class if creation was possible, null otherwise.
	 * @since    1.0.0
	 */
	private function create_instance( $class_name, $args = [] ) {
		if ( class_exists( $class_name ) ) {
			try {
				$reflection = new \ReflectionClass( $class_name );
				return $reflection->newInstanceArgs( $args );
			} catch ( \Exception $ex ) {
				return false;
			}
		}
		return false;
	}

	/**
	 * Create an instance of logger.
	 *
	 * @param   array $logger   The logger parameters.
	 * @return  null|object The instance of logger if creation was possible, null otherwise.
	 * @since    1.0.0
	 */
	public function create_logger( $logger ) {
		$logger  = $this->check( $logger );
		$handler = null;
		$debug   = false;
		if ( $logger['running'] ) {
			$handler_def = $this->handler_types->get( $logger['handler'] );
			if ( isset( $handler_def ) ) {
				$debug     = ( 'SpatierayHandler' !== $handler_def['id'] ) && ( 'debugging' === $handler_def['class'] );
				$classname = $handler_def['namespace'] . '\\' . $handler_def['id'];
				if ( class_exists( $classname ) ) {
					$args = [];
					foreach ( $handler_def['init'] as $p ) {
						switch ( $p['type'] ) {
							case 'uuid':
								$args[] = (string) $logger['uuid'];
								break;
							case 'level':
								$args[] = (int) $logger['level'];
								break;
							case 'literal':
								$args[] = $p['value'];
								break;
							case 'configuration':
								$value = $logger['configuration'][ $p['value'] ];
								if ( Option::network_get( 'env_substitution' ) && is_string( $value ) /*&& in_array( $value, $this->substitutable )*/ ) {
									if ( preg_match_all('/{(.*)}/U', $value,$matches ) ) {
										if ( 2 === count( $matches ) ) {
											foreach ($matches[1] as $match) {
												$env = getenv( $match );
												if ( is_string( $env ) ) {
													$value = str_replace( '{' . $match . '}', $env, $value );
												}
											}
										}
									}
								}
								$args[] = $value;
								break;
							case 'compute':
								switch ( $p['value'] ) {
									case 'tablename':
										$args[] = 'decalog_' . str_replace( '-', '', $logger['uuid'] );
										break;
								}
								break;
						}
					}
					$launchable = true;
					if ( array_key_exists( 'option', $handler_def['needs'] ) ) {
						foreach ( $handler_def['needs']['option'] as $option ) {
							if ( ! Option::network_get( $option ) ) {
								$launchable = false;
								break;
							}
						}
					}
					if ( array_key_exists( 'function_exists', $handler_def['needs'] ) ) {
						foreach ( $handler_def['needs']['function_exists'] as $function ) {
							if ( ! function_exists( $function ) ) {
								$launchable = false;
								break;
							}
						}
					}
					if ( $launchable ) {
						$handler = $this->create_instance( $classname, $args );
					}
				}
			}
			if ( $handler ) {
				static::$debugging = static::$debugging || $debug;
				foreach ( array_reverse( $logger['processors'] ) as $processor ) {
					$p_instance    = null;
					$processor_def = $this->processor_types->get( $processor );
					if ( $processor_def ) {
						$classname = $processor_def['namespace'] . '\\' . $processor_def['id'];
						if ( class_exists( $classname ) ) {
							$args = [];
							foreach ( $processor_def['init'] as $p ) {
								switch ( $p['type'] ) {
									case 'level':
										$args[] = (int) $logger['level'];
										break;
									case 'privacy':
										$args[] = (bool) $logger['privacy'][ $p['value'] ];
										break;
									case 'literal':
										$args[] = $p['value'];
										break;
								}
							}
							$p_instance = $this->create_instance( $classname, $args );
						}
					}
					if ( $p_instance ) {
						$handler->pushProcessor( $p_instance );
					}
				}
			}
		}
		return $handler;
	}

	/**
	 * Check if logger definition is compliant.
	 *
	 * @param   array   $logger  The logger definition.
	 * @param   boolean $init_handler   Optional. Init handlers needing it.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	public function check( $logger, $init_handler = false ) {
		$handler = $this->handler_types->get( $logger['handler'] );
		$logger  = $this->standard_check( $logger, $handler );
		if ( $handler ) {
			$logger = $this->privacy_check( $logger );
			$logger = $this->processor_check( $logger, $handler );
			$logger = $this->configuration_check( $logger, $handler['configuration'] ?? [] );
		}
		if ( $init_handler && array_key_exists( 'uuid', $logger ) ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$instance = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				$instance->initialize();
			}
		}
		return $logger;
	}

	/**
	 * Destroy the logger.
	 *
	 * @param   array $logger  The logger definition.
	 * @since    1.0.0
	 */
	public function destroy( $logger ) {
		if ( array_key_exists( 'uuid', $logger ) ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$instance = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				$instance->finalize();
			}
		}
	}

	/**
	 * Force purge the logger.
	 *
	 * @param   array $logger  The logger definition.
	 * @since    2.0.0
	 */
	public function purge( $logger ) {
		if ( array_key_exists( 'uuid', $logger ) ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$instance = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				$instance->force_purge();
			}
		}
	}

	/**
	 *Clean the logger.
	 *
	 * @param   array $logger  The logger definition.
	 * @return  integer     The number of deleted records.
	 * @since    2.0.0
	 */
	public function clean( $logger ) {
		if ( array_key_exists( 'uuid', $logger ) ) {
			$classname = 'Decalog\Plugin\Feature\\' . $logger['handler'];
			if ( class_exists( $classname ) ) {
				$instance = $this->create_instance( $classname );
				$instance->set_logger( $logger );
				return $instance->cron_clean();
			}
		}
	}

	/**
	 * Check the standard part of the logger.
	 *
	 * @param   array $logger   The logger definition.
	 * @param   array $handler  The handler definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function standard_check( $logger, $handler ) {
		if ( ! array_key_exists( 'name', $logger ) ) {
			$logger['name'] = esc_html__( 'Unnamed logger', 'decalog' );
		}
		if ( ! array_key_exists( 'running', $logger ) ) {
			$logger['running'] = false;
		}
		if ( ! array_key_exists( 'handler', $logger ) ) {
			$logger['handler'] = 'NullHandler';
		} elseif ( ! in_array( $logger['handler'], $this->handler_types->get_list(), true ) ) {
			$logger['handler'] = 'NullHandler';
		}
		if ( ! array_key_exists( 'level', $logger ) ) {
			$logger['level'] = $handler['minimal'] ?? 500;
		} elseif ( ! in_array( $logger['level'], EventTypes::$level_values, false ) ) {
			$logger['level'] = $handler['minimal'] ?? 500;
		} elseif ( $logger['level'] < ( $handler['minimal'] ?? 500 ) ) {
			$logger['level'] = $handler['minimal'] ?? 500;
		}
		return $logger;
	}

	/**
	 * Check the privacy part of the logger.
	 *
	 * @param   array $logger  The logger definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function privacy_check( $logger ) {
		if ( array_key_exists( 'privacy', $logger ) ) {
			if ( ! array_key_exists( 'obfuscation', $logger['privacy'] ) ) {
				$logger['privacy']['obfuscation'] = false;
			}
			if ( ! array_key_exists( 'pseudonymization', $logger['privacy'] ) ) {
				$logger['privacy']['pseudonymization'] = false;
			}
		} else {
			$logger['privacy']['obfuscation']      = false;
			$logger['privacy']['pseudonymization'] = false;
		}
		return $logger;
	}

	/**
	 * Check the processor part of the logger.
	 *
	 * @param   array $logger   The logger definition.
	 * @param   array $handler  The handler definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function processor_check( $logger, $handler ) {
		if ( ! array_key_exists( 'processors', $logger ) ) {
			$logger['processors'] = [];
		}
		$processors = [];
		foreach ( $logger['processors'] as $processor ) {
			if ( in_array( $processor, $this->processor_types->get_list(), true ) ) {
				$processors[] = $processor;
			}
		}
		if ( array_key_exists( 'processors', $handler ) ) {
			if ( array_key_exists( 'included', $handler['processors'] ) ) {
				$processors = array_merge( $processors, $handler['processors']['included'] );
			}
			if ( array_key_exists( 'excluded', $handler['processors'] ) ) {
				$processors = array_diff( $processors, $handler['processors']['excluded'] );
			}
		}
		$logger['processors'] = $processors;
		return $logger;
	}

	/**
	 * Check the configuration part of the logger.
	 *
	 * @param   array $logger  The logger definition.
	 * @param   array $configuration   The configuration definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function configuration_check( $logger, $configuration ) {
		if ( ! array_key_exists( 'configuration', $logger ) ) {
			$logger['configuration'] = [];
		}
		foreach ( $configuration as $key => $conf ) {
			if ( ! array_key_exists( $key, $logger['configuration'] ) ) {
				$logger['configuration'][ $key ] = $conf['default'];
			} else {
				if ( 'integer' === $conf['control']['cast'] && 'field_input_integer' === $conf['control']['type'] ) {
					if ( (integer) $logger['configuration'][ $key ] < $conf['control']['min'] || (integer) $logger['configuration'][ $key ] > $conf['control']['max'] ) {
						$logger['configuration'][ $key ] = $conf['default'];
					}
				}
				if ( 'field_select' === $conf['control']['type'] ) {
					$found = false;
					if ( 'integer' === $conf['control']['cast'] ) {
						foreach ( $conf['control']['list'] as $choice ) {
							if ( (integer) $logger['configuration'][ $key ] == (integer) $choice[0] ) {
								$found = true;
								break;
							}
						}
					} else {
						foreach ( $conf['control']['list'] as $choice ) {
							if ( (string) $logger['configuration'][ $key ] == (string) $choice[0] ) {
								$found = true;
								break;
							}
						}
					}
					if ( ! $found ) {
						$logger['configuration'][ $key ] = $conf['default'];
					}
				}
			}
		}
		return $logger;
	}

}
