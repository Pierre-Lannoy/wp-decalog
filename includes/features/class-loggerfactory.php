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

use Monolog\Logger;

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
		if ( $logger['running'] ) {
			$handler_def = $this->handler_types->get( $logger['handler'] );
			if ( $handler_def ) {
				$classname = $handler_def['namespace'] . '\\' . $handler_def['id'];
				if ( class_exists( $classname ) ) {
					$args = [];
					foreach ( $handler_def['init'] as $p ) {
						switch ( $p['type'] ) {
							case 'level':
								$args[] = (int) $logger['level'];
								break;
							case 'literal':
								$args[] = $p['value'];
								break;
							case 'configuration':
								$args[] = $logger['configuration'][ $p['value'] ];
								break;
							case 'compute':
								switch ( $p['value'] ) {
									case 'tablename':
										global $wpdb;
										$args[] = $wpdb->prefix . 'decalog_' . str_replace( '-', '', $logger['uuid'] );
										break;
								}
								break;
						}
					}
					$handler = $this->create_instance( $classname, $args );
				}
			}
			if ( $handler ) {
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
		$logger  = $this->standard_check( $logger );
		$handler = $this->handler_types->get( $logger['handler'] );
		if ( $handler && in_array( 'privacy', $handler['params'], true ) ) {
			$logger = $this->privacy_check( $logger );
		}
		if ( $handler && in_array( 'processors', $handler['params'], true ) ) {
			$logger = $this->processor_check( $logger );
		}
		if ( $handler && array_key_exists( 'configuration', $handler ) ) {
			$logger = $this->configuration_check( $logger, $handler['configuration'] );
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
	 * Clean the logger.
	 *
	 * @param   array $logger  The logger definition.
	 * @since    1.0.0
	 */
	public function clean( $logger ) {
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
	 * Check the standard part of the logger.
	 *
	 * @param   array $logger  The logger definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function standard_check( $logger ) {
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
			$logger['level'] = Logger::DEBUG;
		} elseif ( ! in_array( $logger['level'], EventTypes::$level_values, true ) ) {
			$logger['level'] = Logger::DEBUG;
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
	 * @param   array $logger  The logger definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function processor_check( $logger ) {
		if ( ! array_key_exists( 'processors', $logger ) ) {
			$logger['processors'] = [];
		}
		if ( 'WordpressHandler' === $logger['handler'] ) {
			$logger['processors'] = array_merge( [ 'WordpressProcessor', 'WWWProcessor', 'IntrospectionProcessor' ], $logger['processors'] );
		} else {
			$processors = [];
			foreach ( $logger['processors'] as $processor ) {
				if ( in_array( $processor, $this->processor_types->get_list(), true ) ) {
					$processors[] = $processor;
				}
			}
			$logger['processors'] = $processors;
		}
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
			}
		}
		return $logger;
	}

}
