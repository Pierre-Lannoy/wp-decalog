<?php
/**
 * Standard PerfOpsOne menus handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace PerfOpsOne;

use Decalog\System\Plugin;
use Decalog\System\Conversion;

/**
 * Standard PerfOpsOne menus handling.
 *
 * This class defines all code necessary to initialize and handle PerfOpsOne admin menus.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

if ( ! class_exists( 'PerfOpsOne\AdminMenus' ) ) {
	class AdminMenus {

		/**
		 * The PerfOpsOne admin menus.
		 *
		 * @since  1.0.0
		 * @var    array    $menus    Maintains the PerfOpsOne admin menus.
		 */
		private static $menus = [];

		/**
		 * The PerfOpsOne admin slugs.
		 *
		 * @since  1.0.0
		 * @var    array    $slugs    Maintains the PerfOpsOne admin slugs.
		 */
		private static $slugs = [];

		/**
		 * Initialize the admin menus.
		 *
		 * @since 1.0.0
		 */
		public static function initialize() {
			foreach ( apply_filters( 'init_perfops_admin_menus', [] ) as $menu => $submenus ) {
				if ( ! in_array( 'perfopsone-' . $menu, self::$slugs, true ) ) {
					switch ( $menu ) {
						case 'analytics':
							add_menu_page( esc_html__( 'Dashboard', 'decalog' ), sprintf( esc_html__( '%s Analytics', 'decalog' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_analytics_page' ], 'dashicons-chart-bar', 81 );
							add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Dashboard', 'decalog' ), __( 'Dashboard', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_analytics_page' ], 0 );
							break;
						case 'tools':
							add_menu_page( esc_html__( 'Available Tools', 'decalog' ), sprintf( esc_html__( '%s Tools', 'decalog' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_tools_page' ], 'dashicons-admin-tools', 81 );
							add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Available Tools', 'decalog' ), __( 'Available Tools', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_tools_page' ], 0 );
							break;
						case 'insights':
							add_menu_page( esc_html__( 'Available Reports', 'decalog' ), sprintf( esc_html__( '%s Insights', 'decalog' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_insights_page' ], 'dashicons-lightbulb', 81 );
							add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Available Reports', 'decalog' ), __( 'Available Reports', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_insights_page' ], 0 );
							break;
						case 'records':
							add_menu_page( esc_html__( 'Available Catalogues', 'decalog' ), sprintf( esc_html__( '%s Records', 'decalog' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_records_page' ], 'dashicons-book', 81 );
							add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Available Catalogues', 'decalog' ), __( 'Available Catalogues', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_records_page' ], 0 );
							break;
						case 'settings':
							add_menu_page( esc_html__( 'Control Center', 'decalog' ), sprintf( esc_html__( '%s Settings', 'decalog' ), 'PerfOps' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_settings_page' ], 'dashicons-admin-settings', 81 );
							add_submenu_page( 'perfopsone-' . $menu, esc_html__( 'Control Center', 'decalog' ), __( 'Control Center', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_settings_page' ], 0 );
							break;
					}
					self::$slugs[] = 'perfopsone-' . $menu;
				}
				foreach ( $submenus as $submenu ) {
					if ( $submenu['activated'] ) {
						if ( ! in_array( $submenu['slug'], self::$slugs, true ) ) {
							$hook_suffix            = add_submenu_page( 'perfopsone-' . $menu, $submenu['page_title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], $submenu['callback'], $submenu['position'] );
							self::$slugs[]          = $submenu['slug'];
							self::$menus[ $menu ][] = $submenu;
							if ( isset( $submenu['post_callback'] ) && is_callable( $submenu['post_callback'] ) && $hook_suffix ) {
								call_user_func( $submenu['post_callback'], $hook_suffix );
							}
						}
					}
				}
			}
		}

		/**
		 * Get the analytics main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_analytics_page() {
			if ( array_key_exists( 'analytics', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['analytics'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'analytics-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
					} else {
						$i['text'] = esc_html__( 'This analytics feature is currently disabled. Click here to activate it.', 'decalog' );
						$i['url']  = $item['remedy'];
					}
					$items[] = $i;
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the tools main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_tools_page() {
			if ( array_key_exists( 'tools', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['tools'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'tools-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]   = $i;
					}
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the insights main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_insights_page() {
			if ( array_key_exists( 'insights', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['insights'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'insights-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]   = $i;
					}
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the records main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_records_page() {
			if ( array_key_exists( 'records', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['records'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'records-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]   = $i;
					}
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Get the settings main page.
		 *
		 * @since 1.0.0
		 */
		public static function get_settings_page() {
			if ( array_key_exists( 'settings', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['settings'] as $item ) {
					$i                = [];
					$d                = new Plugin( $item['plugin'] );
					$i['title']       = $d->get( 'Name' );
					$i['version']     = $item['version'];
					$i['text']        = $d->get( 'Description' );
					$i['wp_version']  = $d->get( 'RequiresWP' );
					$i['php_version'] = $d->get( 'RequiresPHP' );
					if ( $d->waiting_update() ) {
						$i['need_update'] = sprintf( esc_html__( 'Need to be updated to %s.', 'decalog' ), $d->waiting_update() );
					} else {
						$i['need_update'] = '';
					}
					if ( $d->is_required_wp_ok() ) {
						$i['need_wp_update'] = '';
						$i['ok_wp_update']   = esc_html__( 'OK', 'decalog' );
					} else {
						$i['need_wp_update'] = esc_html__( 'need update', 'decalog' );
						$i['ok_wp_update']   = '';
					}
					if ( $d->is_required_php_ok() ) {
						$i['need_php_update'] = '';
						$i['ok_php_update']   = esc_html__( 'OK', 'decalog' );
					} else {
						$i['need_php_update'] = esc_html__( 'need update', 'decalog' );
						$i['ok_php_update']   = '';
					}
					$i['icon'] = call_user_func( $item['icon_callback'] );
					$i['slug'] = $item['plugin'];
					$i['id']   = 'settings-' . $item['slug'];
					foreach ( [ 'installs', 'downloads', 'rating', 'reviews' ] as $key ) {
						$i[ $key ] = call_user_func( $item['statistics'], [ 'item' => $key ] );
					}
					if ( 0 < (int) $i['installs'] ) {
						$i['installs'] = sprintf( esc_html__( '%s+ installs.', 'decalog' ), Conversion::number_shorten( (int) $i['installs'], 0 ) );
					} else {
						$i['installs'] = '';
					}
					if ( 0 < (int) $i['downloads'] ) {
						$i['downloads'] = sprintf( esc_html__( '%s downloads.', 'decalog' ), Conversion::number_shorten( (int) $i['downloads'], 2 ) );
					} else {
						$i['downloads'] = '';
					}
					if ( 0 < (int) $i['reviews'] ) {
						$i['reviews'] = sprintf( esc_html__( '%s reviews.', 'decalog' ), Conversion::number_shorten( (int) $i['reviews'], 0 ) );
						$i['rating']  = $i['rating'] / 20;
					} else {
						$i['reviews'] = esc_html__( 'No review yet.', 'decalog' );
						$i['rating']  = 0.0;
					}
					$i['stars'] = '';
					if ( 0 < (int) $i['rating'] ) {
						for ( $k = 0; $k < (int) $i['rating']; $k++ ) {
							$i['stars'] .= '<span class="dashicons dashicons-star-filled" style="color:#FFA828;"></span>';
						}
					}
					if ( 0 < $i['rating'] - (int) $i['rating'] ) {
						$i['stars'] .= '<span class="dashicons dashicons-star-half" style="color:#FFA828;"></span>';
					} elseif ( 5 > $i['rating'] ) {
						$i['stars'] .= '<span class="dashicons dashicons-star-empty" style="color:#FFA828;"></span>';
					}
					if ( 5 > (int) $i['rating'] ) {
						for ( $k = 0; $k < 4 - (int) $i['rating']; $k++ ) {
							$i['stars'] .= '<span class="dashicons dashicons-star-empty" style="color:#FFA828;"></span>';
						}
					}
					if ( 0 < $i['rating'] ) {
						$i['rating'] = (string) round( $i['rating'], 1 ) . '/5';
					} else {
						$i['rating'] = '';
					}
					if ( $item['activated'] && $d->is_detected() ) {
						$i['url'] = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]  = $i;
					}
				}
				self::display_as_lines( $items );
			}
		}

		/**
		 * Displays items as bubbles.
		 *
		 * @param array $items  The items to display.
		 * @since 1.0.0
		 */
		private static function display_as_bubbles( $items ) {
			uasort(
				$items,
				function ( $a, $b ) {
					if ( $a['title'] === $b['title'] ) {
						return 0;
					} return ( strtoupper( $a['title'] ) < strtoupper( $b['title'] ) ) ? -1 : 1;
				}
			);
			$disp  = '';
			$disp .= '<div style="width:100%;text-align:center;padding:0px;margin-top:10px;margin-left:-10px;" class="perfopsone-admin-inside">';
			$disp .= ' <div style="display:flex;flex-direction:row;flex-wrap:wrap;justify-content:center;">';
			$disp .= '  <style>';
			$disp .= '   .perfopsone-admin-inside .po-container {flex:none;padding:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-actionable:hover {border-radius:6px;cursor:pointer; -moz-transition: all .2s ease-in; -o-transition: all .2s ease-in; -webkit-transition: all .2s ease-in; transition: all .2s ease-in; background: #f5f5f5;border:1px solid #e0e0e0;filter: grayscale(0%) opacity(100%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable {overflow:scroll;width:400px;height:120px;border-radius:6px;cursor:pointer; -moz-transition: all .4s ease-in; -o-transition: all .4s ease-in; -webkit-transition: all .4s ease-in; transition: all .4s ease-in; background: transparent;border:1px solid transparent;filter: grayscale(80%) opacity(66%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable a {font-style:normal;text-decoration:none;color:#73879C;}';
			$disp .= '   .perfopsone-admin-inside .po-icon {display:block;width:120px;float:left;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-text {display:grid;text-align:left;padding-top:16px;padding-right:16px;}';
			$disp .= '   .perfopsone-admin-inside .po-title {font-size:1.8em;font-weight: 600;}';
			$disp .= '   .perfopsone-admin-inside .po-description {font-size:1em;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside a:focus {box-shadow:none;outline:none;}';
			$disp .= '  </style>';
			foreach ( $items as $item ) {
				$disp .= '<div class="po-container">';
				$disp .= ' <div class="po-actionable">';
				$disp .= '  <a href="' . $item['url'] . '"/>';
				$disp .= '   <div id="' . $item['id'] . '">';
				$disp .= '    <span class="po-icon"><img style="width:100px" src="' . $item['icon'] . '"/></span>';
				$disp .= '    <span class="po-text">';
				$disp .= '     <span class="po-title">' . $item['title'] . '</span>';
				$disp .= '     <span class="po-description">' . $item['text'] . '</span>';
				$disp .= '    </span>';
				$disp .= '   </div>';
				$disp .= '  </a>';
				$disp .= ' </div>';
				$disp .= '</div>';
			}
			$disp .= ' </div>';
			$disp .= '</div>';
			echo $disp;
		}

		/**
		 * Displays items as lines.
		 *
		 * @param array $items  The items to display.
		 * @since 1.0.0
		 */
		private static function display_as_lines( $items ) {
			uasort(
				$items,
				function ( $a, $b ) {
					if ( $a['title'] === $b['title'] ) {
						return 0;
					} return ( strtoupper( $a['title'] ) < strtoupper( $b['title'] ) ) ? -1 : 1;
				}
			);
			$disp  = '';
			$disp .= '<div style="width:100%;text-align:center;padding:0px;margin-top:0;" class="perfopsone-admin-inside">';
			$disp .= ' <div style="display:flex;flex-direction:row;flex-wrap:wrap;justify-content:center;padding-top:10px;padding-right:20px;">';
			$disp .= '  <style>';
			$disp .= '   .perfopsone-admin-inside .po-container {width:100%;flex:none;padding:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-actionable:hover {border-radius:6px;-moz-transition: all .2s ease-in; -o-transition: all .2s ease-in; -webkit-transition: all .2s ease-in; transition: all .2s ease-in; background: #f5f5f5;border:1px solid #e0e0e0;filter: grayscale(0%) opacity(100%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable {overflow:hidden;width:100%;height:120px;border-radius:6px;-moz-transition: all .4s ease-in; -o-transition: all .4s ease-in; -webkit-transition: all .4s ease-in; transition: all .4s ease-in; background: transparent;border:1px solid transparent;filter: grayscale(80%) opacity(66%);}';
			$disp .= '   .perfopsone-admin-inside .po-actionable {color:#73879C;}';
			$disp .= '   .perfopsone-admin-inside .po-actionable a {font-style:normal;text-decoration:none;}';
			$disp .= '   .perfopsone-admin-inside .po-icon {display:block;width:120px;float:left;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside .po-text {width:70%;display: grid;text-align:left;padding-top:20px;padding-right:16px;}';
			$disp .= '   .perfopsone-admin-inside .po-title {height: 0;font-size:1.8em;font-weight: 600;}';
			$disp .= '   .perfopsone-admin-inside .po-stars {height:0;font-size:1.8em;font-weight: 600;}';
			$disp .= '   .perfopsone-admin-inside .po-version {font-size:0.6em;font-weight: 500;padding-left: 10px;vertical-align: middle;}';
			$disp .= '   .perfopsone-admin-inside .po-update {font-size:1.1em;font-weight: 400;color:#9B59B6;padding-top: 20px;}';
			$disp .= '   .perfopsone-admin-inside .po-description {font-size:1em;padding-top:0px;margin-bottom: -10px;}';
			$disp .= '   .perfopsone-admin-inside .po-requires {font-size:1em;}';
			$disp .= '   .perfopsone-admin-inside .po-link {padding-left:14px;font-size:0.6em;vertical-align: middle;color:#73879C;}';
			$disp .= '   .perfopsone-admin-inside .po-link a:hover {text-decoration:underline;}';
			$disp .= '   .perfopsone-admin-inside .po-needupdate {vertical-align:super;font-size:0.6em;color:#9B59B6;padding-left:2px;}';
			$disp .= '   .perfopsone-admin-inside .po-okupdate {vertical-align:super;font-size:0.6em;color:#3398DB;}';
			$disp .= '   .perfopsone-admin-inside .po-summary {width:140px;display:grid;text-align:left;margin-left:20px;padding-left:30px;top:20px;position:relative;padding-right:16px;}';
			$disp .= '   .perfopsone-admin-inside a:focus {box-shadow:none;outline:none;}';
			$disp .= '   @media (max-width: 960px) {';
			$disp .= '   .perfopsone-admin-inside .po-summary { display:none;}';
			$disp .= '   }';
			$disp .= '  </style>';
			foreach ( $items as $item ) {
				$links   = [];
				$links[] = '<a href="' . $item['url'] . '"/>' . __( 'settings', 'decalog' ) . '</a>';
				$links[] = '<a href="' . $item['url'] . '&tab=about"/>' . __( 'about', 'decalog' ) . '</a>';
				$links[] = '<a href="https://wordpress.org/support/plugin/' . $item['slug'] . '" target="_blank">' . __( 'support', 'decalog' ) . '</a>';
				$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-' . $item['slug'] . '" target="_blank">' . __( 'contribution', 'decalog' ) . '</a>';
				$disp   .= '<div class="po-container">';
				$disp   .= ' <div class="po-actionable">';
				$disp   .= '   <div id="' . $item['id'] . '" style="display:flex;justify-content: flex-start;">';
				$disp   .= '    <div class="po-icon"><img style="width:100px" src="' . $item['icon'] . '"/></div>';
				$disp   .= '    <div class="po-text">';
				$disp   .= '     <span class="po-title">' . $item['title'] . '<span class="po-version">' . $item['version'] . '</span><span class="po-link">' . implode(' | ',  $links) . '</span></span>';
				$disp   .= '     <span class="po-update">' . $item['need_update'] . '</span>';
				$disp   .= '     <span class="po-description">' . $item['text'] . '</span>';
				$disp   .= '     <span class="po-requires">' . sprintf( esc_html__( 'Requires at least PHP %1$s%2$s and WordPress %3$s%4$s.', 'decalog' ), $item['php_version'], '<span class="po-needupdate">' . $item['need_php_update'] . '</span><span class="po-okupdate">' . $item['ok_php_update'] . '</span>', $item['wp_version'], '<span class="po-needupdate">' . $item['need_wp_update'] . '</span><span class="po-okupdate">' . $item['ok_wp_update'] . '</span>' ) . '</span>';
				$disp   .= '    </div>';
				$disp   .= '    <div class="po-summary">';
				$disp   .= '     <span class="po-stars"><a href="https://wordpress.org/support/plugin/' . $item['slug'] . '/reviews" target="_blank">' . $item['stars'] . '</a></span>';
				$disp   .= '     <span class="po-requires">' . $item['reviews'] . '<br/>' . $item['installs'] . '<br/>' . $item['downloads'] . '</span>';
				$disp   .= '    </div>';
				$disp   .= '   </div>';
				$disp   .= ' </div>';
				$disp   .= '</div>';
			}
			$disp .= ' </div>';
			$disp .= '</div>';
			echo $disp;
		}
	}
}

