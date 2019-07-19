<?php declare(strict_types=1);
/**
 * WordPress formatter for Monolog
 *
 * Handles all features of WordPress formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Formatter;

use Monolog\Formatter\FormatterInterface;

/**
 * Define the Monolog WordPress formatter.
 *
 * Handles all features of WordPress formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class WordpressFormatter implements FormatterInterface
{

	/**
	 * Formats a log record.
	 *
	 * @param  array  $record A record to format.
	 * @return string The formatted record.
	 * @since   1.0.0
	 */
	public function format(array $record): string
	{

		return '';
	}
	/**
	 * Formats a set of log records.
	 *
	 * @param  array  $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   1.0.0
	 */
	public function formatBatch(array $records): string
	{
		$message = '';
		foreach ($records as $record) {
			//$message .= $this->format($record);
		}
		return $message;
	}
}