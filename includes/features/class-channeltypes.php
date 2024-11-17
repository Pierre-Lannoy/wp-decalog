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
	 * The list of available channels names.
	 *
	 * @since  1.0.0
	 * @var    array    $channel_names_en    Maintains the channels names.
	 */
	public static $channel_names_en = [];

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$channel_names_en['UNKNOWN'] = 'Unknown';
		self::$channel_names_en['CLI']     = 'Command Line Interface';
		self::$channel_names_en['CRON']    = 'Cron Job';
		self::$channel_names_en['AJAX']    = 'Ajax Request';
		self::$channel_names_en['XMLRPC']  = 'XML-RPC Request';
		self::$channel_names_en['API']     = 'Rest API Request';
		self::$channel_names_en['FEED']    = 'Atom/RDF/RSS Feed';
		self::$channel_names_en['WBACK']   = 'Site Backend';
		self::$channel_names_en['WFRONT']  = 'Site Frontend';
		self::$channel_names['UNKNOWN']    = decalog_esc_html__( 'Unknown', 'decalog' );
		self::$channel_names['CLI']        = decalog_esc_html__( 'Command Line Interface', 'decalog' );
		self::$channel_names['CRON']       = decalog_esc_html__( 'Cron Job', 'decalog' );
		self::$channel_names['AJAX']       = decalog_esc_html__( 'Ajax Request', 'decalog' );
		self::$channel_names['XMLRPC']     = decalog_esc_html__( 'XML-RPC Request', 'decalog' );
		self::$channel_names['API']        = decalog_esc_html__( 'Rest API Request', 'decalog' );
		self::$channel_names['FEED']       = decalog_esc_html__( 'Atom/RDF/RSS Feed', 'decalog' );
		self::$channel_names['WBACK']      = decalog_esc_html__( 'Site Backend', 'decalog' );
		self::$channel_names['WFRONT']     = decalog_esc_html__( 'Site Frontend', 'decalog' );
	}

}

ChannelTypes::init();
