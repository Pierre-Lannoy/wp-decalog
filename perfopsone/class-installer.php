<?php
/**
 * Standard PerfOps One installation handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace PerfOpsOne;

require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

/**
 * Standard PerfOps One installation handling.
 *
 * This class defines all code necessary to initialize and handle PerfOps One plugins installation.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.2.0
 */

if ( ! class_exists( 'PerfOpsOne\Installer' ) ) {
	class Installer {

		/**
		 * Install and activate a PerfOps One plugin.
		 *
		 * @var     string      $slug           The plugin's slug.
		 * @var     boolean     $activation     Optional. Activate after install.
		 * @return  string      Empty string if it was ok, An error string if not.
		 * @since 2.2.0
		 */
		public static function do( $slug, $activation = false ) {
			$result = self::install( $slug );
			if ( $activation && '' === $result ) {
				return self::activate( $slug );
			}
			return $result;
		}

		/**
		 * Install a PerfOps One plugin.
		 *
		 * @var     string      $slug           The plugin's slug.
		 * @return  string      Empty string if it was ok, An error string if not.
		 * @since 2.2.0
		 */
		private static function install( $slug ) {
			$api      = plugins_api(
				'plugin_information',
				[
					'slug'   => $slug,
					'fields' => [
						'short_description' => false,
						'requires'          => false,
						'sections'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					],
				]
			);
			$skin     = new \PerfOpsOne\UpgraderSkin( [ 'api' => $api ] );
			$upgrader = new \Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );
			if ( is_wp_error( $result ) ) {
				return $result->get_error_message();
			}
			return '';
		}

		/**
		 * Activate a PerfOps One plugin.
		 *
		 * @var     string      $slug           The plugin's slug.
		 * @since 2.2.0
		 */
		private static function activate( $slug ) {
			$result = activate_plugin( $slug . '/' . $slug . '.php' );
			if ( is_wp_error( $result ) ) {
				return $result->get_error_message();
			}
			return '';
		}
	}
}

