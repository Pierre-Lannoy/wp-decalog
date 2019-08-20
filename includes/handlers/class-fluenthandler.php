<?php
/**
 * Fluentd handler for Monolog
 *
 * Handles all features of Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Handler;

use Monolog\Logger;
use Monolog\Handler\SocketHandler;
use Monolog\Formatter\FormatterInterface;
use Decalog\Formatter\FluentFormatter;

/**
 * Define the Monolog Fluentd handler.
 *
 * Handles all features of Fluentd handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class FluentHandler extends SocketHandler {

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new FluentFormatter();
	}
}
