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

	private function niceDebugBacktrace($d)
	{


		$out = '';
		$c1width = strlen((string)(count($d) + 1));
		$c2width = 0;
		foreach ($d as &$f) {
			if (!isset($f['file'])) $f['file'] = '';
			if (!isset($f['line'])) $f['line'] = '';
			if (!isset($f['class'])) $f['class'] = '';
			if (!isset($f['type'])) $f['type'] = '';
			$f['file_rel'] = str_replace(BP . DS, '', $f['file']);
			$thisLen = strlen($f['file_rel'] . ':' . $f['line']);
			if ($c2width < $thisLen) $c2width = $thisLen;
		}
		foreach ($d as $i => $f) {
			$args = '';
			if (isset($f['args'])) {
				$args = array();
				foreach ($f['args'] as $arg) {
					if (is_object($arg)) {
						$str = get_class($arg);
					} elseif (is_array($arg)) {
						$str = 'Array';
					} elseif (is_numeric($arg)) {
						$str = $arg;
					} else {
						$str = "'$arg'";
					}
					$args[] = $str;
				}
				$args = implode(', ', $args);
			}
			$out .= sprintf(
				"[%{$c1width}s] %-{$c2width}s %s%s%s(%s)\n",
				$i,
				$f['file_rel'] . ':' . $f['line'],
				$f['class'],
				$f['type'],
				$f['function'],
				$args
			);
		}
		return $out;
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

		$record['extra']['trace']['callstack'] = $this->niceDebugBacktrace($trace);

		$record['extra']['trace']['wordpress'] = wp_debug_backtrace_summary(null, 0, false);
		return $record;
	}
}
