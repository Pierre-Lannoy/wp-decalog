<?php declare(strict_types=1);
/**
 * PHP introspection records processing
 *
 * Adds PHP specific record, after removing unneeded traces.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Decalog\Processor;

use Decalog\System\PHP;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Define the backtrace processor functionality.
 *
 * Adds backtrace specific record.
 *
 * @package Processors
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class IntrospectionProcessor implements ProcessorInterface {

	/**
	 * Minimum logging level.
	 *
	 * @since  2.4.0
	 * @var    integer    $level    Minimum logging level.
	 */
	private $level;

	/**
	 * Classes to exclude.
	 *
	 * @since  2.4.0
	 * @var    array    $skip_classes    List of class partials.
	 */
	private $skip_classes = [
		'Monolog\\',
		'Decalog\\',
		'DecaLog\\',
		'System\\Logger',
		'Feature\\DecaLog',
		'Feature\\Capture',
	];

	/**
	 * Functions to exclude.
	 *
	 * @since  2.4.0
	 * @var    array    $skip_functions    List of functions.
	 */
	private $skip_functions = [
		'call_user_func',
		'call_user_func_array',
	];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @param string|int $level The minimum logging level at which this Processor will be triggered
	 * @since   2.4.0
	 */
	public function __construct( $level = Logger::DEBUG ) {
		$this->level = Logger::toMonologLevel( $level );
	}

	/**
	 * Invocation of the processor.
	 *
	 * @param   array $record  Array or added records.
	 * @return array   The modified records.
	 * @since   2.4.0
	 */
	public function __invoke( array $record ): array {
		if ( $record['level'] < $this->level ) {
			return $record;
		}
		if ( array_key_exists( 'context', $record ) && array_key_exists( 'phase', $record['context'] ) && 'bootstrap' === (string) $record['context']['phase'] ) {
			$file = null;
			$line = null;
			if ( isset( $record['message'] ) && preg_match( '/ at `\.\/(.*):([0-9]*)`\./iU', $record['message'], $matches ) ) {
				$file = $matches[1];
				$line = $matches[2];
			}
			$record['extra'] = array_merge(
				$record['extra'],
				[
					'file'     => $file,
					'line'     => $line,
					'class'    => null,
					'function' => null,
				]
			);
			return $record;
		}
		// phpcs:ignore
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		array_shift( $trace );
		array_shift( $trace );
		$i = 0;
		while ( $this->is_skipped( $trace, $i ) ) {
			if ( isset( $trace[ $i ]['class'] ) ) {
				foreach ( $this->skip_classes as $part ) {
					if ( strpos( $trace[ $i ]['class'], $part ) !== false ) {
						$i++;
						continue 2;
					}
				}
			} elseif ( in_array( $trace[ $i ]['function'], $this->skip_functions, true ) ) {
				$i++;
				continue;
			}
			break;
		}
		$record['extra'] = array_merge(
			$record['extra'],
			[
				'file'     => $trace[ $i - 1 ]['file'] ?? null,
				'line'     => $trace[ $i - 1 ]['line'] ?? null,
				'class'    => $trace[ $i ]['class'] ?? null,
				'function' => $trace[ $i ]['function'] ?? null,
			]
		);
		return $record;
	}

	/**
	 * Verify if a trace must be skipped.
	 *
	 * @param   array   $trace    The trace to verify.
	 * @param   integer $index    The index of the trace to verify.
	 * @return  boolean     True if the record must be skipped, false otherwise.
	 * @since   2.4.0
	 */
	private function is_skipped( array $trace, int $index ) {
		if ( ! isset( $trace[ $index ] ) ) {
			return false;
		}
		return isset( $trace[ $index ]['class'] ) || in_array( $trace[ $index ]['function'], $this->skip_functions, true );
	}
}
