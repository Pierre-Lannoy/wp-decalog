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

use DLMonolog\Logger;
use DLMonolog\Handler\AbstractProcessingHandler;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\WordpressFormatter;
use Decalog\Plugin\Feature\EventTypes;

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
	private static $buffer = [];

	/**
	 * The panels to load.
	 *
	 * @since  3.2.0
	 * @var    array    $panels    The panels.
	 */
	private static $panels = [ 'wordpress', 'database', 'current' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   integer $level    Optional. The min level to log.
	 * @param   boolean $bubble   Optional. Has the record to bubble?.
	 * @since    3.2.0
	 */
	public function __construct( $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		add_action( 'init', [ static::class, 'init' ], PHP_INT_MAX, 0 );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function init(): void {
		require_once DECALOG_VENDOR_DIR . 'tracy/tracy.php';
		if ( ! \Tracy\Debugger::isEnabled() ) {
			\Tracy\Debugger::enable( \Tracy\Debugger::DEVELOPMENT );
			foreach ( self::$panels as $panel ) {
				$panelclass = '\Decalog\Panel\\' . ucfirst( $panel ) . 'Panel';
				\Tracy\Debugger::getBar()->addPanel( new $panelclass() );
			}
		}
		$panelclass = '\Decalog\Panel\LogPanel';
		\Tracy\Debugger::getBar()->addPanel( new $panelclass() );
	}

	/**
	 * Get the events formatted for Tracy.
	 *
	 * @return   array      The formatted events.
	 * @since    3.2.0
	 */
	public static function get(): array {
		$result = [];
		foreach ( static::$buffer as $record ) {
			$event               = [];
			$event['Level']      = EventTypes::$level_emojis[ $record['level'] ] . '&nbsp;' . ucfirst( strtolower( EventTypes::$level_names[ $record['level'] ] ) ) . '&nbsp;' . $record['context']['code'];
			$event['Source']     = str_replace( ' ', '&nbsp;', $record['context']['component'] . ' ' . $record['context']['version'] );
			$event['Time']       = $record['datetime']->format( 'H:i:s.u' );
			$event['Message']    = ( 70 < strlen( $record['message'] ) ? substr( $record['message'], 0, 70 ) . '…' : $record['message'] );
			$event['Full Event'] = $record;
			$result[]            = $event;
		}
		return $result;
	}

	/**
	 * Count the events.
	 *
	 * @return   integer      The count of events.
	 * @since    3.2.0
	 */
	public static function count(): int {
		return count( static::$buffer );
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
		static::$buffer[] = $this->processRecord( $record );
		return false === $this->bubble;
	}

	/**
	 * Write the record in the table.
	 *
	 * @param   array $record    The record to write.
	 * @since    3.2.0
	 */
	protected function write( array $record ): void {
		// No write needed because buffer is outputted via Tracy panel.
	}
}
