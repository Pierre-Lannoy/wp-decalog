<?php
/**
 * Standard PerfOps One admin bar handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.1.0
 */

namespace PerfOpsOne;

use PerfOpsOne\Resources;

/**
 * Standard PerfOps One admin bar handling.
 *
 * This class defines all code necessary to initialize and handle PerfOps One admin bar.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.1.0
 */

if ( ! class_exists( 'PerfOpsOne\AdminBar' ) ) {

	class AdminBar {

		/**
		 * The PerfOps One admin bar items.
		 *
		 * @since  2.1.0
		 * @var    array    $items    Maintains the PerfOps One admin bar items.
		 */
		private static $items = [];

		/**
		 * Are the menus already initialized.
		 *
		 * @since  2.1.0
		 * @var    boolean    $initialized    Maintains the menus initialization status.
		 */
		private static $initialized = false;


		/**
		 * Initialize the admin bar items.
		 *
		 * @since 2.1.0
		 */
		public static function initialize() {
			if ( ! self::$initialized ) {
				wp_register_style( PERFOO_ASSETS_ID, PERFOO_ASSETS_CSS, [], true );
				wp_enqueue_style( PERFOO_ASSETS_ID );
				add_action( 'admin_bar_menu', [ self::class, 'finalize' ], PHP_INT_MAX, 1 );
				self::$initialized = true;
			}
			self::$items = apply_filters( 'init_perfopsone_admin_bar', [] );
		}

		/**
		 * Dispatch the admin menus.
		 *
		 * @since 2.1.0
		 */
		public static function finalize( $admin_bar ) {
			if ( apply_filters( 'poo_hide_adminbar', false ) ) {
				return;
			}
			if ( 0 < count( self::$items ) ) {
				usort(
					self::$items,
					function( $a, $b ) {
						return strcmp( strtolower( $a['title'] ), strtolower( $b['title'] ) );
					}
				);
				$id = 'perfopsone-dashboard';
				$admin_bar->add_node(
					[
						'id'    => $id,
						'href'  => esc_url( admin_url( 'admin.php?page=' . $id ) ),
						'title' => '<span class="ab-icon poo-ico-logo" style="padding-top: 6px;"></span><span class="ab-label">' . PERFOO_PRODUCT_NAME . '</span>',
					]
				);
				foreach ( self::$items as $item ) {
					$admin_bar->add_node( array_merge( $item, [ 'parent' => $id ] ) );
				}
			}
			self::$initialized = true;
		}
	}
}

