<?php declare(strict_types=1);
/**
 * Backtraces records processing
 *
 * Adds backtrace specific record.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Processor;

use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Define the backtrace processor functionality.
 *
 * Adds backtrace specific record.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class BacktraceProcessor implements ProcessorInterface {

	/**
	 * Minimum logging level.
	 *
	 * @since  1.0.0
	 * @var    integer    $level    Minimum logging level.
	 */
	private $level;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param string|int $level The minimum logging level at which this Processor will be triggered
	 * @since   1.0.0
	 */
	public function __construct( $level = Logger::DEBUG ) {
		$this->level = Logger::toMonologLevel( $level );
	}

	/**
	 * Invocation of the processor.
	 *
	 * @since   1.0.0
	 * @param   array $record  Array or added records.
	 * @@return array   The modified records.
	 */
	public function __invoke( array $record ): array {
		if ( $record['level'] < $this->level ) {
			return $record;
		}
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		array_shift( $trace ); // skip first since it's always the current method.
		array_shift( $trace ); // the call_user_func call is also skipped.
		$trace = array_reverse( $trace );

		$record['extra']['trace'] = $trace;
		return $record;
	}
}
