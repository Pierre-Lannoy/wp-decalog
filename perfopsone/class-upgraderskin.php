<?php
/**
 * Standard PerfOps One installation skin handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace PerfOpsOne;

/**
 * Standard PerfOps One installation skin handling.
 *
 * This class defines all code necessary to initialize and handle PerfOps One plugins installation skin.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.2.0
 */

if ( ! class_exists( 'PerfOpsOne\UpgraderSkin' ) ) {
	class UpgraderSkin extends \WP_Upgrader_Skin {

		/**
		 * @since 2.2.0
		 *
		 * @param string|WP_Error $errors Errors.
		 */
		public function feedback( $string, ...$args ) {
			/* no output */
		}
	}
}

