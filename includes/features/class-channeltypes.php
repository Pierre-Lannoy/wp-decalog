<?php
/**
 * Channel types handling
 *
 * Handles all available channel types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin\Feature;

use Monolog\Logger;

/**
 * Define the channel types functionality.
 *
 * Handles all available channel types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ChannelTypes {

	/**
	 * The list of available channels.
	 *
	 * @since  1.0.0
	 * @var    array    $channels    Maintains the channels definitions.
	 */
	public static $channels = [];

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$channels = [
			[
				'tag'  => 'UNKNOWN',
				'name' => esc_html__('Unknown', 'decalog'),
			],
			[
				'tag'  => 'CLI',
				'name' => esc_html__('Command Line Interface', 'decalog'),
			],
			[
				'tag'  => 'CRON',
				'name' => esc_html__('Cron Job', 'decalog'),
			],
			[
				'tag'  => 'AJAX',
				'name' => esc_html__('Ajax Request', 'decalog'),
			],
			[
				'tag'  => 'XMLRPC',
				'name' => esc_html__('XML-RPC Request', 'decalog'),
			],
			[
				'tag'  => 'API',
				'name' => esc_html__('Rest API Request', 'decalog'),
			],
			[
				'tag'  => 'FEED',
				'name' => esc_html__('Atom/RDF/RSS Feed', 'decalog'),
			],
			[
				'tag'  => 'WBACK',
				'name' => esc_html__('Site Backend', 'decalog'),
			],
			[
				'tag'  => 'WFRONT',
				'name' => esc_html__('Site Frontend', 'decalog'),
			],
		];

	}

}

ChannelTypes::init();