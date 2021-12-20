<?php
/**
 * Standard PerfOps One menus handling.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace PerfOpsOne;

use Decalog\System\Plugin;
use Decalog\System\Conversion;
use PerfOpsOne\Resources;

/**
 * Standard PerfOps One menus handling.
 *
 * This class defines all code necessary to initialize and handle PerfOps One admin menus.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

if ( ! class_exists( 'PerfOpsOne\Menus' ) ) {

	class Menus {

		/**
		 * The PerfOps One admin menus.
		 *
		 * @since  2.0.0
		 * @var    array    $menus    Maintains the PerfOps One admin menus.
		 */
		private static $menus = [];

		/**
		 * The PerfOps One admin menus positions.
		 *
		 * @since  2.0.0
		 * @var    array    $menus    Maintains the PerfOps One admin menus positions.
		 */
		private static $menus_positions = [ 'records', 'consoles', 'tools', 'analytics', 'insights' ];

		/**
		 * The PerfOps One admin slugs.
		 *
		 * @since  2.0.0
		 * @var    array    $slugs    Maintains the PerfOps One admin slugs.
		 */
		private static $slugs = [];

		/**
		 * Are the menus already initialized.
		 *
		 * @since  2.0.0
		 * @var    boolean    $initialized    Maintains the menus initialization status.
		 */
		private static $initialized = false;

		/**
		 * Currently selected item.
		 *
		 * @since  2.0.0
		 * @var    array    $current_item    Maintains the currently selected item.
		 */
		private static $current_item = null;

		/**
		 * Currently selected menu.
		 *
		 * @since  2.0.0
		 * @var    string    $current_menu    Maintains the currently selected menu.
		 */
		private static $current_menu = null;

		
		/**
		 * Initialize the admin menus.
		 *
		 * @since 2.0.0
		 */
		public static function initialize() {
			if ( ! ( $page = filter_input( INPUT_GET, 'page' ) ) ) {
				$page = filter_input( INPUT_POST, 'page' );
			}
			foreach ( apply_filters( 'init_perfopsone_admin_menus', [] ) as $menu => $elements ) {
				foreach ( $elements as $item ) {
					if ( ! in_array( $item['slug'], self::$slugs, true ) ) {
						self::$slugs[]          = $item['slug'];
						self::$menus[ $menu ][] = $item;
						if ( $item['activated'] ) {
							if ( $page !== $item['slug'] ) {
								$hook_suffix = add_submenu_page( 'perfopsone' . $menu, $item['page_title'], $item['menu_title'], $item['capability'], $item['slug'], $item['callback'] );
								if ( isset( $item['post_callback'] ) && is_callable( $item['post_callback'] ) && $hook_suffix ) {
									call_user_func( $item['post_callback'], $hook_suffix );
								}
							} else {
								self::$current_item = $item;
								self::$current_menu = $menu;
							}
						}
					}
				}
			}
		}

		/**
		 * Dispatch the admin menus.
		 *
		 * @since 2.0.0
		 */
		public static function normalize() {
			$current = apply_filters( 'init_perfopsone_admin_menus', [] );
			foreach ( self::$menus_positions as $menu ) {
				if ( ! array_key_exists( $menu, $current ) || apply_filters( 'poo_hide_' . $menu . '_menu', false ) ) {
					remove_submenu_page( 'perfopsone-dashboard', 'perfopsone-' . $menu );
				}
			}
			if ( apply_filters( 'poo_hide_settings_menu', false ) ) {
				remove_submenu_page( 'perfopsone-dashboard', 'perfopsone-dashboard' );
			}
		}

		/**
		 * Dispatch the admin menus.
		 *
		 * @since 2.0.0
		 */
		public static function finalize() {
			if ( 0 === count( self::$menus ) || apply_filters( 'poo_hide_main_menu', false ) ) {
				return;
			}
			if ( ! self::$initialized ) {
				if ( apply_filters( 'poo_hide_settings_menu', false ) ) {
					self::$menus['settings'] = [];
				}
				add_menu_page( PERFOO_PRODUCT_NAME, PERFOO_PRODUCT_NAME, 'manage_options', 'perfopsone-dashboard', [ self::class, 'get_dashboard_page' ], Resources::get_menu_base64_logo(), 79 );
				add_submenu_page( 'perfopsone-dashboard', esc_html__( 'Control Center', 'decalog' ), __( 'Control Center', 'decalog' ), 'manage_options', 'perfopsone-dashboard', [ self::class, 'get_dashboard_page' ] );
			}
			if ( isset( self::$current_item ) && 'settings' === self::$current_menu ) {
				if ( self::$current_item['activated'] ) {
					$hook_suffix = add_submenu_page( 'perfopsone-dashboard', self::$current_item['page_title'], '&nbsp;&nbsp;' . self::$current_item['menu_title'] . '&nbsp;&nbsp;&nbsp;➜', self::$current_item['capability'], self::$current_item['slug'], self::$current_item['callback'] );
					if ( isset( self::$current_item['post_callback'] ) && is_callable( self::$current_item['post_callback'] ) && $hook_suffix ) {
						call_user_func( self::$current_item['post_callback'], $hook_suffix );
					}
				}
				self::$current_item = null;
			}
			foreach ( self::$menus_positions as $menu ) {
				if ( ! self::$initialized ) {
					switch ( $menu ) {
						case 'analytics':
							add_submenu_page( 'perfopsone-dashboard', esc_html__( 'Analytics', 'decalog' ), __( 'Analytics', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_analytics_page' ] );
							break;
						case 'tools':
							add_submenu_page( 'perfopsone-dashboard', esc_html__( 'Tools', 'decalog' ), __( 'Tools', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_tools_page' ] );
							break;
						case 'insights':
							add_submenu_page( 'perfopsone-dashboard', esc_html__( 'Reports', 'decalog' ), __( 'Reports', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_insights_page' ] );
							break;
						case 'records':
							add_submenu_page( 'perfopsone-dashboard', esc_html__( 'Catalogs', 'decalog' ), __( 'Catalogs', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_records_page' ] );
							break;
						case 'consoles':
							add_submenu_page( 'perfopsone-dashboard', esc_html__( 'Consoles', 'decalog' ), __( 'Consoles', 'decalog' ), 'manage_options', 'perfopsone-' . $menu, [ self::class, 'get_consoles_page' ] );
							break;
					}
				}
				if ( isset( self::$current_item ) && $menu === self::$current_menu ) {
					if ( self::$current_item['activated'] ) {
						$hook_suffix = add_submenu_page( 'perfopsone-dashboard', self::$current_item['page_title'], '&nbsp;&nbsp;' . self::$current_item['menu_title'] . '&nbsp;&nbsp;&nbsp;➜', self::$current_item['capability'], self::$current_item['slug'], self::$current_item['callback'] );
						if ( isset( self::$current_item['post_callback'] ) && is_callable( self::$current_item['post_callback'] ) && $hook_suffix ) {
							call_user_func( self::$current_item['post_callback'], $hook_suffix );
						}
					}
					self::$current_item = null;
				}
			}
			self::$initialized = true;
		}

		/**
		 * Get the dashboard main page.
		 *
		 * @since 2.0.0
		 */
		public static function get_dashboard_page() {
			if ( array_key_exists( 'settings', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['settings'] as $item ) {
					$i                = [];
					$d                = new Plugin( $item['plugin'] );
					$i['title']       = $item['menu_title'];
					$i['slug']        = $item['plugin'];
					$i['id']          = 'settings-' . $item['slug'];
					$i['icon']        = call_user_func( $item['icon_callback'] );
					$i['need_update'] = $d->waiting_update();
					$i['auto_update'] = $d->auto_update();
					if ( $item['activated'] && $d->is_detected() ) {
						$i['url'] = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
						$items[]  = $i;
					}
				}
				self::display_as_controls( $items );
			}
		}

		/**
		 * Get the analytics main page.
		 *
		 * @since 2.0.0
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
		 * @since 2.0.0
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
		 * @since 2.0.0
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
		 * @since 2.0.0
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
		 * Get the records main page.
		 *
		 * @since 2.0.0
		 */
		public static function get_consoles_page() {
			if ( array_key_exists( 'consoles', self::$menus ) ) {
				$items = [];
				foreach ( self::$menus['consoles'] as $item ) {
					$i          = [];
					$i['icon']  = call_user_func( $item['icon_callback'] );
					$i['title'] = $item['name'];
					$i['id']    = 'consoles-' . $item['slug'];
					if ( $item['activated'] ) {
						$i['text'] = $item['description'];
						$i['url']  = esc_url( admin_url( 'admin.php?page=' . $item['slug'] ) );
					} else {
						$i['text'] = esc_html__( 'This console is currently unavailable. Click here to see why.', 'decalog' );
						$i['url']  = $item['remedy'];
					}
					$items[] = $i;
				}
				self::display_as_bubbles( $items );
			}
		}

		/**
		 * Displays items as bubbles.
		 *
		 * @param array $items  The items to display.
		 * @since 2.0.0
		 */
		private static function display_as_controls( $items ) {
			uasort(
				$items,
				function ( $a, $b ) {
					if ( $a['title'] === $b['title'] ) {
						return 0;
					} return ( strtoupper( $a['title'] ) < strtoupper( $b['title'] ) ) ? -1 : 1;
				}
			);
			$disp        = '';
			$disp       .= '<div class="perfopsone-admin-wrap">';
			$disp       .= ' <div class="perfopsone-admin-inside">';
			$disp       .= '  <style>';
			$disp       .= '   .perfopsone-admin-wrap {width:100%;text-align:center;padding:0px;margin-top:0;}';
			$disp       .= '   .perfopsone-admin-inside {display:grid;grid-template-columns: repeat(auto-fill, 470px);justify-content: center;padding-top:10px;padding-right:20px;}';
			$disp       .= '   .perfopsone-admin-inside .poo-container {flex:none;padding:10px;cursor:default;}';
			$disp       .= '   .perfopsone-admin-inside .poo-actionable:hover {border-radius:6px;background: #f5f5f5;border:1px solid #e0e0e0;filter: grayscale(0%) opacity(100%);}';
			$disp       .= '   .perfopsone-admin-inside .poo-actionable {overflow:hidden;width:100%;height:100px;border-radius:6px;background: #f5f5f5;border:1px solid #e7e7e7;filter: grayscale(12%) opacity(88%);}';
			$disp       .= '   .perfopsone-admin-inside .poo-actionable {color:#73879C;width: 450px;}';
			$disp       .= '   .perfopsone-admin-inside .poo-actionable a {font-style:normal;text-decoration:none;color:inherit;}';
			$disp       .= '   .perfopsone-admin-inside .poo-icon {display:block;width:120px;float:left;padding-top:10px;}';
			$disp       .= '   .perfopsone-admin-inside .poo-title {height: 0;font-size:1.8em;font-weight: 600;margin-bottom: 10px;}';
			$disp       .= '   .perfopsone-admin-inside .poo-main {width:70%;display: grid;text-align:left;padding-top:18px;}';
			$disp       .= '   .perfopsone-admin-inside .poo-actions {font-size:larger;text-align: center; margin-top: 8px;}';
			$disp       .= '   .perfopsone-admin-inside .poo-action {border:1px solid #e7e7e7;border-radius:4px;padding: 4px 8px 7px 4px;margin: 0 4px;}';
			$disp       .= '   .perfopsone-admin-inside .poo-action img {vertical-align: middle;}';
			$disp       .= '   .perfopsone-admin-inside .poo-action:hover img {filter: invert(0%) sepia(86%) saturate(0%) hue-rotate(231deg) brightness(146%) contrast(127%);}';
			$disp       .= '   .perfopsone-admin-inside .poo-action:hover {color:#FFF;background:#73879C;}';
			$disp       .= '   .perfopsone-admin-inside .poo-links {margin-top: -6px; text-align:end;}';
			$disp       .= '   .perfopsone-admin-inside .poo-links-support:hover {filter: invert(59%) sepia(9%) saturate(1614%) hue-rotate(167deg) brightness(81%) contrast(94%);}';
			$disp       .= '   .perfopsone-admin-inside .poo-links-star:hover {filter: invert(6%) sepia(96%) saturate(320%) hue-rotate(0deg) brightness(115%) contrast(91%);}';
			$disp       .= '   .perfopsone-admin-inside .poo-links-contrib:hover {filter: invert(100%) sepia(36%) saturate(7379%) hue-rotate(121deg) brightness(0%) contrast(100%);}';
			$disp       .= '   .perfopsone-admin-inside .poo-util {display: grid;margin: 14px;min-width: fit-content;}';
			$disp       .= '   .perfopsone-admin-inside .poo-update {min-width: fit-content;}';
			$disp       .= '   .perfopsone-admin-inside .poo-autoupdate {min-width: fit-content; text-align: end;}';
			$disp       .= '   .perfopsone-admin-inside .poo-switch {max-width:100px;max-height:2em;}';
			$disp       .= '   .perfopsone-admin-inside .poo-need-update {display: inline-block;;border-radius:2px;background:rgb(255,147,8);color:rgb(255,255,255);text-transform: uppercase;font-weight: bolder;width: 106px;height: fit-content;font-size: x-small;}';
			$disp       .= '   .perfopsone-admin-inside .poo-noneed-update {display: inline-block;;border-radius:2px;background:rgb(68,93,159);color:rgb(255,255,255);text-transform: uppercase;font-weight: bolder;width: 106px;height: fit-content;font-size: x-small;}';
			$disp       .= '   .perfopsone-admin-inside a:focus {box-shadow:none;outline:none;}';
			$disp       .= '   .perfopsone-admin-inside .poo-switch-toggle {vertical-align: middle;cursor: pointer;}';
			$disp       .= '   .perfopsone-admin-inside .poo-switch-off img {filter: grayscale(70%) opacity(80%) brightness(140%)}';
			$disp       .= '   .perfopsone-admin-inside .poo-switch-on img {transform: rotate(180deg);}';
			$disp       .= '   .perfopsone-admin-inside .poo-blink img {animation: blinker 800ms infinite;}';
			$disp       .= '    @keyframes blinker {from {opacity:1} 50% {opacity: 0.2} to {opacity: 1}}';
			$disp       .= '    @media only screen and (max-width: 501px) {.perfopsone-admin-inside .poo-util {display:none;} .perfopsone-admin-inside .poo-actionable {width:100%} .perfopsone-admin-inside {grid-template-columns:max-content;}';
			$disp       .= '  </style>';
			$disp       .= '  <script type="text/javascript">';
			$disp       .= '   jQuery(document).ready( function($) {';
			$disp       .= '     $( ".poo-switch" ).on(';
			$disp       .= '       "click",';
			$disp       .= '       function() {';
			$disp       .= '         const toggle = $(this).find(".poo-switch-toggle");';
			$disp       .= '         var data = {';
			$disp       .= '           action: "poo_switch_autoupdate",';
			$disp       .= '           plugin: $( this ).data( "value" ),';
			$disp       .= '           nonce : "' . wp_create_nonce( 'poo-auto-update' ) . '"';
			$disp       .= '         };';
			$disp       .= '         $(toggle).addClass( "poo-blink" );';
			$disp       .= '         jQuery.post( ajaxurl, data, function ( response ) {';
			$disp       .= '           if ( response ) {';
			$disp       .= '             var cold = "poo-switch-on";';
			$disp       .= '             var cnew = "poo-switch-off";';
			$disp       .= '             if ( $(toggle).hasClass( "poo-switch-off" ) ) {';
			$disp       .= '               cold = "poo-switch-off";';
			$disp       .= '               cnew = "poo-switch-on";';
			$disp       .= '             }';
			$disp       .= '             $(toggle).removeClass( "poo-blink" );';
			$disp       .= '             if ( 200 == response) {';
			$disp       .= '               $(toggle).removeClass( cold );';
			$disp       .= '               $(toggle).addClass( cnew );';
			$disp       .= '             }';
			$disp       .= '           }';
			$disp       .= '         });';
			$disp       .= '       }';
			$disp       .= '     );';
			$disp       .= '   } );';
			$disp       .= '  </script>';
			$main_update = '<span class="poo-noneed-update">' . esc_html__( 'Up to date', 'decalog' ) . '</span>';
			foreach ( $items as $item ) {
				if ( $item['need_update'] ) {
					$update      = '<a href=" ' .admin_url( 'update-core.php' ) . '"><span class="poo-need-update">' . esc_html__( 'Need update', 'decalog' ) . '</span></a>';
					$main_update = $update;
				} else {
					$update = '<span class="poo-noneed-update">' . esc_html__( 'Up to date', 'decalog' ) . '</span>';
				}
				if ( ! current_user_can( 'update_plugins' ) || ! wp_is_auto_update_enabled_for_type( 'plugin' ) || ( is_multisite() && ! is_network_admin() ) ) {
					$auto = '';
				} else {
					$auto  = '<div class="poo-switch" data-value="' . $item['slug'] . '">' . esc_html__( 'auto-update', 'decalog' ) . ' &nbsp;';
					$auto .= '<span id="poo-switch-toggle-' . $item['slug'] . '" class="poo-switch-toggle poo-switch-' . ( $item['auto_update'] ? 'on' : 'off' ) . '"><img style="width:17px;padding:0;margin-top: 3px;" src="' . \Feather\Icons::get_base64( 'toggle-left', '#7EA7E4', '#394486', 2 ) . '" /></span>';
					$auto .= ' </div>';
				}
				$support  = '<a title="' . esc_html__( 'Get support', 'decalog' ) . '" alt="' . esc_html__( 'Get support', 'decalog' ) . '" class="poo-links-support" href="https://wordpress.org/support/plugin/' . $item['slug'] . '" target="_blank"><img style="width:16px;padding:0 4px;" src="' . \Feather\Icons::get_base64( 'message-circle', 'none', '#73879C', 3 ) . '" /></a>';
				$contrib  = '<a title="' . esc_html__( 'Contribute', 'decalog' ) . '" alt="' . esc_html__( 'Contribute', 'decalog' ) . '" class="poo-links-contrib" href="https://github.com/Pierre-Lannoy/wp-' . $item['slug'] . '" target="_blank"><img style="width:16px;padding:0 4px;" src="' . \Feather\Icons::get_base64( 'github', 'none', '#73879C', 3 ) . '" /></a>';
				$star     = '<a title="' . esc_html__( 'Make a review', 'decalog' ) . '" alt="' . esc_html__( 'Make a review', 'decalog' ) . '" class="poo-links-star" href="https://wordpress.org/support/plugin/' . $item['slug'] . '/reviews" target="_blank"><img style="width:16px;padding:0 4px;" src="' . \Feather\Icons::get_base64( 'star', 'none', '#73879C', 3 ) . '" /></a>';
				$settings = '<a class="poo-action" href="' . $item['url'] . '"/><img style="width:18px;padding:0 4px;" src="' . \Feather\Icons::get_base64( 'settings', 'none', '#73879C', 3 ) . '" />' . esc_html__( 'settings', 'decalog' ) . '</a>';
				$about    = '<a class="poo-action" href="' . $item['url'] . '&tab=about"/><img style="width:18px;padding:0 4px;" src="' . \Feather\Icons::get_base64( 'info', 'none', '#73879C', 3 ) . '" />' . esc_html__( 'about', 'decalog' ) . '</a>';
				$disp    .= '<div class="poo-container">';
				$disp    .= ' <div class="poo-actionable">';
				$disp    .= '   <div id="' . $item['id'] . '" style="display:flex;justify-content: center;">';
				$disp    .= '    <div class="poo-icon"><img style="width:80px" src="' . $item['icon'] . '"/></div>';
				$disp    .= '    <div class="poo-main">';
				$disp    .= '     <span class="poo-title">' . $item['title'] . '</span>';
				$disp    .= '     <span class="poo-actions">' . $settings . $about . '</span>';
				$disp    .= '    </div>';
				$disp    .= '    <div class="poo-util">';
				$disp    .= '     <span class="poo-update">' . $update . '</span>';
				$disp    .= '     <span class="poo-autoupdate">' . $auto . '</span>';
				$disp    .= '     <span class="poo-additional">&nbsp;</span>';
				$disp    .= '     <span class="poo-links">' . $support . $star . $contrib . '</span>';
				$disp    .= '    </div>';
				$disp    .= '   </div>';
				$disp    .= ' </div>';
				$disp    .= '</div>';
			}
			$site  = '<a class="poo-action" href="https://perfops.one" target="_blank"><img style="width:18px;padding:0 4px;vertical-align: bottom !important;" src="' . \Feather\Icons::get_base64( 'home', 'none', '#73879C', 3 ) . '" />' . esc_html__( 'all plugins', 'decalog' ) . '</a>';
			$disp .= '<div class="poo-container">';
			$disp .= ' <div class="poo-actionable">';
			$disp .= '   <div id="perfops-one" style="display:flex;justify-content: center;">';
			$disp .= '    <div class="poo-icon"><img style="width:80px;margin-top:-10px;" src="' . Resources::get_base64_logo() . '"/></div>';
			$disp .= '    <div class="poo-main">';
			$disp .= '     <span class="poo-title" style="margin-bottom: 36px !important;">PerfOps One</span>';
			$disp .= '     <span class="poo-actions">' . $site . '</span>';
			$disp .= '    </div>';
			$disp .= '    <div class="poo-util">';
			$disp .= '     <span class="poo-update">' . $main_update . '</span>';
			$disp .= '     <span class="poo-autoupdate">&nbsp;</span>';
			$disp .= '    </div>';
			$disp .= '   </div>';
			$disp .= ' </div>';
			$disp .= '</div>';
			$disp .= ' </div>';
			$disp .= '</div>';
			echo $disp;
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
			$disp .= '<div class="perfopsone-admin-wrap">';
			$disp .= ' <div class="perfopsone-admin-inside">';
			$disp .= '  <style>';
			$disp .= '   .perfopsone-admin-wrap {width:100%;text-align:center;padding:0px;margin-top:0;}';
			$disp .= '   .perfopsone-admin-inside {display:grid;grid-template-columns: repeat(auto-fill, 470px);justify-content: center;padding-top:10px;padding-right:20px;}';
			$disp .= '   .perfopsone-admin-inside .poo-container {flex:none;padding:10px;cursor:default;}';
			$disp .= '   .perfopsone-admin-inside .poo-actionable:hover {border-radius:6px;background: #f5f5f5;border:1px solid #e0e0e0;filter: grayscale(0%) opacity(100%);}';
			$disp .= '   .perfopsone-admin-inside .poo-actionable {overflow:hidden;width:100%;height:100px;border-radius:6px;background: #f5f5f5;border:1px solid #e7e7e7;filter: grayscale(12%) opacity(88%);}';
			$disp .= '   .perfopsone-admin-inside .poo-actionable {color:#73879C;width: 450px;}';
			$disp .= '   .perfopsone-admin-inside .poo-actionable a {font-style:normal;text-decoration:none;color:inherit;}';
			$disp .= '   .perfopsone-admin-inside .poo-icon {display:block;width:120px;float:left;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside .poo-title {height: 0;font-size:1.8em;font-weight: 600;margin-bottom: 10px;}';
			$disp .= '   .perfopsone-admin-inside .poo-text {display:grid;text-align:left;padding-top:16px;padding-right:16px;width: 100%;}';
			$disp .= '   .perfopsone-admin-inside .poo-description {font-size:1em;padding-top:10px;}';
			$disp .= '   .perfopsone-admin-inside a:focus {box-shadow:none;outline:none;}';
			$disp .= '    @media only screen and (max-width: 501px) {.perfopsone-admin-inside .poo-icon {padding-left:16px;} .perfopsone-admin-inside .poo-actionable {width:100%} .perfopsone-admin-inside {grid-template-columns:unset;} .perfopsone-admin-inside .poo-description {display:none}.perfopsone-admin-inside .poo-text {align-content: baseline;padding-top: 26px;text-align: center;}';

			$disp .= '  </style>';
			foreach ( $items as $item ) {
				$disp .= '<div class="poo-container">';
				$disp .= ' <div class="poo-actionable">';
				$disp .= '  <a href="' . $item['url'] . '"/>';
				$disp .= '   <div id="' . $item['id'] . '" style="display:flex;justify-content: center;">';
				$disp .= '    <div class="poo-icon"><img style="width:80px" src="' . $item['icon'] . '"/></div>';
				$disp .= '    <span class="poo-text">';
				$disp .= '     <span class="poo-title">' . $item['title'] . '</span>';
				$disp .= '     <span class="poo-description">' . $item['text'] . '</span>';
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
	}
}

