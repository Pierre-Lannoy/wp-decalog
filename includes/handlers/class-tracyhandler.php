<?php
/**
 * Tracy handler for Monolog
 *
 * Handles all features of Tracy handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */

namespace Decalog\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;

/**
 * Define the Monolog Tracy handler.
 *
 * Handles all features of Tracy handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */
class TracyHandler extends AbstractProcessingHandler {

	/**
	 * The buffer.
	 *
	 * @since  3.2.0
	 * @var    array    $buffer    The buffer.
	 */
	private $buffer = [];

	/**
	 * The panels to load.
	 *
	 * @since  3.2.0
	 * @var    array    $panels    The panels.
	 */
	private static $panels = [ 'wordpress', 'screen' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    3.2.0
	 */
	public function __construct( $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		add_action( 'init', [ static::class, 'inita' ], PHP_INT_MAX, 0 );
/*
		add_action(
			'init',
			function() {
				require_once DECALOG_VENDOR_DIR . 'tracy/tracy.php';
				if ( ! \Tracy\Debugger::isEnabled() ) {
					\Tracy\Debugger::enable( \Tracy\Debugger::DEVELOPMENT );
					\Tracy\Debugger::$logSeverity = 0;
					\Tracy\Debugger::errorHandler();
					//\Tracy\Debugger::log( 'BOUH !');//, \Tracy\ILogger::ERROR );
				}

				},
			PHP_INT_MAX
		);*/


		/*require_once DECALOG_VENDOR_DIR . 'tracy/tracy.php';
		\Tracy\Debugger::enable( \Tracy\Debugger::DEVELOPMENT );
		\Tracy\Debugger::$logSeverity = E_ALL;
		\Tracy\Debugger::log( 'BOUH !', \Tracy\ILogger::ERROR );
		trigger_error('aaaa');*/
	}

	/**
	 * {@inheritDoc}
	 */
	public static function inita(): void {
		require_once DECALOG_VENDOR_DIR . 'tracy/tracy.php';
		if ( ! \Tracy\Debugger::isEnabled() ) {
			\Tracy\Debugger::enable( \Tracy\Debugger::DEVELOPMENT );
			foreach ( self::$panels as $panel ) {
				$panelclass = '\Decalog\Panel\\' . ucfirst( $panel ) . 'Panel';
				\Tracy\Debugger::getBar()->addPanel(new $panelclass);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new WordpressFormatter();
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle( array $record ): bool {
		if ( $record['level'] < $this->level ) {
			return false;
		}
		$this->buffer[] = $this->processRecord( $record );
		return false === $this->bubble;
	}

	/**
	 * Write the record in the table.
	 *
	 * @param   array $record    The record to write.
	 * @since    3.2.0
	 */
	protected function write( array $record ): void {
		// phpcs:ignore
		$messages = unserialize( $record['formatted'] );
		if ( is_array( $messages ) ) {
			foreach ( $messages as $message ) {
				if ( is_array( $message ) ) {
					//\Tracy\Debugger::log( $message );
				}
			}
		}
		error_log(print_r($this->buffer,true));
	}
}
