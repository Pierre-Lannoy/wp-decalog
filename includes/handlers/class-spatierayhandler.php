<?php
/**
 * Spatie Ray handler for Monolog
 *
 * Handles all features of Spatie Ray handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */

namespace Decalog\Handler;

use DLMonolog\Logger;
use DLMonolog\Handler\AbstractProcessingHandler;
use DLMonolog\Formatter\FormatterInterface;
use Decalog\Formatter\GenericHtmlFormatter;
use Decalog\System\UUID;
use DLSpatie\Ray\Ray;

/**
 * Define the Monolog Spatie Ray handler.
 *
 * Handles all features of Spatie Ray handler for Monolog.
 *
 * @package Handlers
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */
class SpatieRayHandler extends AbstractProcessingHandler {

	/**
	 * Model.
	 *
	 * @since  3.4.0
	 * @var    integer    $model    The model ID.
	 */
	private $model;

	/**
	 * Settings.
	 *
	 * @since  3.4.0
	 * @var    \DLSpatie\Ray\Settings\Settings    $settings    The settings.
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   integer $format     The format to display.
	 * @param   integer $level      Optional. The min level to log.
	 * @param   boolean $bubble     Optional. Has the record to bubble?.
	 * @since    3.4.0
	 */
	public function __construct( $format, $level = Logger::DEBUG, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
		$this->settings = new \DLSpatie\Ray\Settings\Settings( [] );
		$this->model    = $format;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getDefaultFormatter(): FormatterInterface {
		return new GenericHtmlFormatter( $this->model );
	}

	/**
	 * Send the to Ray.
	 *
	 * @param   array $record    The record to send.
	 * @since    3.4.0
	 */
	protected function write( array $record ): void {
		$ray = new Ray( $this->settings,null, UUID::generate_v4() );
		$ray->html( $record['formatted'] );
		if ( array_key_exists( 'level', $record ) ) {
			switch ( $record['level'] ) {
				case Logger::DEBUG:
					$ray->gray();
					break;
				case Logger::INFO:
					$ray->blue();
					break;
				case Logger::NOTICE:
					$ray->green();
					break;
				case Logger::WARNING:
					$ray->orange();
					break;
				case Logger::ERROR:
				case Logger::CRITICAL:
					$ray->red();
					break;
				case Logger::ALERT:
				case Logger::EMERGENCY:
					$ray->purple();
					break;
			}
		}
		if ( array_key_exists( 'channel', $record ) ) {
			$ray->label( $record['channel'] );
		}
	}
}
