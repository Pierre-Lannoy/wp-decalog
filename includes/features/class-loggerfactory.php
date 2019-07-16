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
	 * List of the available levels.
	 *
	 * @since    1.0.0
	 * @var string[] $levels Logging levels.
	 */
	protected $levels = [
		Logger::DEBUG,
		Logger::INFO,
		Logger::NOTICE,
		Logger::WARNING,
		Logger::ERROR,
		Logger::CRITICAL,
		Logger::ALERT,
		Logger::EMERGENCY,
	];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->handler_types = new HandlerTypes();
		$this->processor_types = new ProcessorTypes();
	}

	/**
	 * Check if logger definition is compliant.
	 *
	 * @param   array  $logger  The logger definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	public function check( $logger ) {
		$logger = $this->standard_check( $logger );
		$handler = $this->handler_types->get($logger['handler']);
		if ($handler && in_array('privacy', $handler['params'])) {
			$logger = $this->privacy_check( $logger );
		}
		if ($handler && in_array('processors', $handler['params'])) {
			$logger = $this->processor_check( $logger );
		}
		if ($handler && array_key_exists('configuration', $handler)) {
			$logger = $this->configuration_check( $logger , $handler['configuration']);
		}
		return $logger;
	}

	/**
	 * Check the standard part of the logger.
	 *
	 * @param   array  $logger  The logger definition.
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
		} elseif ( ! in_array( $logger['handler'], $this->handler_types->get_list() ) ) {
			$logger['handler'] = 'NullHandler';
		}
		if ( ! array_key_exists( 'level', $logger ) ) {
			$logger['level'] = Logger::DEBUG;
		} elseif ( ! in_array( $logger['level'], $this->levels ) ) {
			$logger['level'] = Logger::DEBUG;
		}
		return $logger;
	}

	/**
	 * Check the privacy part of the logger.
	 *
	 * @param   array  $logger  The logger definition.
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
			$logger['privacy']['obfuscation'] = false;
			$logger['privacy']['pseudonymization'] = false;
		}
		return $logger;
	}

	/**
	 * Check the processor part of the logger.
	 *
	 * @param   array  $logger  The logger definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function processor_check( $logger ) {
		if ( ! array_key_exists( 'processors', $logger ) ) {
			$logger['processors'] = [];
		}
		if ('WordpressHandler' === $logger['handler']) {
			$logger['processors'] = ['IntrospectionProcessor', 'WWWProcessor', 'WordpressProcessor'];
		} else {
			$processors = [];
			foreach ($logger['processors'] as $processor) {
				if (in_array($processor, $this->processor_types->get_list())) {
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
	 * @param   array  $logger  The logger definition.
	 * @param   array  $configuration   The configuration definition.
	 * @return  array   The checked logger definition.
	 * @since    1.0.0
	 */
	private function configuration_check( $logger, $configuration ) {
		if (!array_key_exists('configuration', $logger)) {
			$logger['configuration'] = [];
		}
		foreach ($configuration as $key=>$conf) {
			if (!array_key_exists($key, $logger['configuration'])) {
				$logger['configuration'][$key] = $conf['default'];
			}
		}
		return $logger;
	}

}
