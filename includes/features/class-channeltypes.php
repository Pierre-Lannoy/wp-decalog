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
	public static $channels = [ 'UNKNOWN', 'CLI', 'CRON', 'AJAX', 'XMLRPC', 'API', 'FEED', 'WBACK', 'WFRONT' ];

	/**
	 * The list of available channels names.
	 *
	 * @since  1.0.0
	 * @var    array    $channel_names    Maintains the channels names.
	 */
	public static $channel_names = [];

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$channel_names['UNKNOWN'] = esc_html__( 'Unknown', 'decalog' );
		self::$channel_names['CLI']     = esc_html__( 'Command Line Interface', 'decalog' );
		self::$channel_names['CRON']    = esc_html__( 'Cron Job', 'decalog' );
		self::$channel_names['AJAX']    = esc_html__( 'Ajax Request', 'decalog' );
		self::$channel_names['XMLRPC']  = esc_html__( 'XML-RPC Request', 'decalog' );
		self::$channel_names['API']     = esc_html__( 'Rest API Request', 'decalog' );
		self::$channel_names['FEED']    = esc_html__( 'Atom/RDF/RSS Feed', 'decalog' );
		self::$channel_names['WBACK']   = esc_html__( 'Site Backend', 'decalog' );
		self::$channel_names['WFRONT']  = esc_html__( 'Site Frontend', 'decalog' );

	}

}

ChannelTypes::init();
