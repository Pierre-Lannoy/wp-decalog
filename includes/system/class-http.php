<?php
/**
 * HTTP handling
 *
 * Handles all HTTP operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the HTTP functionality.
 *
 * Handles all HTTP operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Http {

	/**
	 * The list of available verbs.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array    $verbs    Maintains the verbs list.
	 */
	public static $verbs = [ 'get', 'head', 'post', 'put', 'delete', 'connect', 'options', 'trace', 'patch', 'unknown' ];


}
