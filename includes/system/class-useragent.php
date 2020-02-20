<?php
/**
 * UserAgent handling
 *
 * Handles all UserAgent operations and detection (via Device Detector).
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

use Decalog\System\Favicon;
use Feather;

/**
 * Define the UserAgent functionality.
 *
 * Handles all UserAgent operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class UserAgent {

	/**
	 * Initialize a device and return it.
	 *
	 * @param string $ua    Optional. The user-agent string.
	 * @return object The device object.
	 * @since    1.0.0
	 */
	public static function get( $ua = '' ) {
		if ( class_exists( 'PODeviceDetector\API\Device' ) && '-' !== $ua ) {
			return \PODeviceDetector\API\Device::get( $ua );
		} else {
			return new static();
		}
	}

	/**
	 * Get the url.
	 *
	 * @param   array   $fields Optional. The args to add.
	 * @param   boolean $escape  Optional. Forces url escaping.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	public static function get_analytics_url( $fields = [], $escape = true ) {
		if ( ! class_exists( 'PODeviceDetector\API\Device' ) ) {
			return '';
		}
		$params = [];
		foreach ( $fields as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'admin.php?page=podd-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . rawurlencode( $arg );
			}
		}
		$url = str_replace( '"', '\'\'', $url );
		if ( $escape ) {
			$url = esc_url( $url );
		}
		return $url;
	}

	/**
	 * Get the brand icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function brand_icon_base64() {
		return Feather\Icons::get_base64( 'x', 'none', '#73879C' );
	}

	/**
	 * Get the brand icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function brand_icon_image() {
		return '<img class="podd-brand-icon podd-brand-icon-none" style="width:16px;vertical-align:top;" src="' . $this->brand_icon_base64() . '" />';
	}

	/**
	 * Get the os icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function os_icon_base64() {
		return Feather\Icons::get_base64( 'x', 'none', '#73879C' );
	}

	/**
	 * Get the os icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function os_icon_image() {
		return '<img class="podd-os-icon podd-os-icon-none" style="width:16px;vertical-align:top;" src="' . $this->os_icon_base64() . '" />';
	}

	/**
	 * Get the browser icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function browser_icon_base64() {
		return Feather\Icons::get_base64( 'x', 'none', '#73879C' );
	}

	/**
	 * Get the browser icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function browser_icon_image() {
		return '<img class="podd-browser-icon podd-browser-icon-none" style="width:16px;vertical-align:top;" src="' . $this->browser_icon_base64() . '" />';
	}

	/**
	 * Get the bot icon base64 encoded.
	 *
	 * @return string  The icon base64 encoded.
	 * @since    1.0.0
	 */
	public function bot_icon_base64() {
		return Favicon::get_base64();
	}

	/**
	 * Get the bot icon html image tag.
	 *
	 * @return string  The icon, as html image, ready to print.
	 * @since    1.0.0
	 */
	public function bot_icon_image() {
		return '<img class="podd-bot-icon podd-bot-icon-none" style="width:16px;vertical-align:top;" src="' . $this->bot_icon_base64() . '" />';
	}

	/**
	 * @var boolean  True if it's a bot, false otherwise.
	 * @since   1.0.0
	 */
	public $class_is_bot = false;

	/**
	 * @var boolean  True if it's a desktop, false otherwise.
	 * @since   1.0.0
	 */
	public $class_is_desktop = false;

	/**
	 * @var boolean  True if it's a mobile, false otherwise.
	 * @since   1.0.0
	 */
	public $class_is_mobile = false;

	/**
	 * @var string  The name of the class translated if translation exists, else in english.
	 * @since   1.0.0
	 */
	public $class_full_type = '';

	/**
	 * @var boolean  True if it's a smartphone, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_smartphone = false;

	/**
	 * @var boolean  True if it's a featurephone, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_featurephone = false;

	/**
	 * @var boolean  True if it's a tablet, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_tablet = false;

	/**
	 * @var boolean  True if it's a phablet, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_phablet = false;

	/**
	 * @var boolean  True if it's a console, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_console = false;

	/**
	 * @var boolean  True if it's a portable media player, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_portable_media_player = false;

	/**
	 * @var boolean  True if it's a car browser, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_car_browser = false;

	/**
	 * @var boolean  True if it's a tv, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_tv = false;

	/**
	 * @var boolean  True if it's a smart display, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_smart_display = false;

	/**
	 * @var boolean  True if it's a camera, false otherwise.
	 * @since   1.0.0
	 */
	public $device_is_camera = false;

	/**
	 * @var string  The name of the device type translated if translation exists, else in english.
	 * @since   1.0.0
	 */
	public $device_full_type = '';

	/**
	 * @var boolean  True if it's a browser, false otherwise.
	 * @since   1.0.0
	 */
	public $client_is_browser = false;

	/**
	 * @var boolean  True if it's a feed reader, false otherwise.
	 * @since   1.0.0
	 */
	public $client_is_feed_reader = false;

	/**
	 * @var boolean  True if it's a mobile app, false otherwise.
	 * @since   1.0.0
	 */
	public $client_is_mobile_app = false;

	/**
	 * @var boolean  True if it's a PIM, false otherwise.
	 * @since   1.0.0
	 */
	public $client_is_pim = false;

	/**
	 * @var boolean  True if it's a library, false otherwise.
	 * @since   1.0.0
	 */
	public $client_is_library = false;

	/**
	 * @var boolean  True if it's a media player, false otherwise.
	 * @since   1.0.0
	 */
	public $client_is_media_player = false;

	/**
	 * @var string  The name of the client type translated if translation exists, else in english.
	 * @since   1.0.0
	 */
	public $client_full_type = '';

	/**
	 * @var boolean  True if device has touch enabled, false otherwise.
	 * @since   1.0.0
	 */
	public $has_touch_enabled = false;

	/**
	 * @var string  The OS name.
	 * @since   1.0.0
	 */
	public $os_name = '';

	/**
	 * @var string  The OS short name.
	 * @since   1.0.0
	 */
	public $os_short_name = '';

	/**
	 * @var string  The OS version.
	 * @since   1.0.0
	 */
	public $os_version = '';

	/**
	 * @var string  The OS platform.
	 * @since   1.0.0
	 */
	public $os_platform = '';

	/**
	 * @var string  The client type.
	 * @since   1.0.0
	 */
	public $client_type = '';

	/**
	 * @var string  The client name.
	 * @since   1.0.0
	 */
	public $client_name = '';

	/**
	 * @var string  The client short name.
	 * @since   1.0.0
	 */
	public $client_short_name = '';

	/**
	 * @var string  The client version.
	 * @since   1.0.0
	 */
	public $client_version = '';

	/**
	 * @var string  The client engine.
	 * @since   1.0.0
	 */
	public $client_engine = '';

	/**
	 * @var string  The client engine version.
	 * @since   1.0.0
	 */
	public $client_engine_version = '';

	/**
	 * @var string  The brand name.
	 * @since   1.0.0
	 */
	public $brand_name = '';

	/**
	 * @var string  The brand short name.
	 * @since   1.0.0
	 */
	public $brand_short_name = '';

	/**
	 * @var string  The model name.
	 * @since   1.0.0
	 */
	public $model_name = '';

	/**
	 * @var string  The bot name.
	 * @since   1.0.0
	 */
	public $bot_name = '';

	/**
	 * @var string  The bot category.
	 * @since   1.0.0
	 */
	public $bot_category = '';

	/**
	 * @var string  The bot category translated if translation exists, else in english.
	 * @since   1.0.0
	 */
	public $bot_full_category = '';

	/**
	 * @var string  The bot url.
	 * @since   1.0.0
	 */
	public $bot_url = '';

	/**
	 * @var string  The bot producer name.
	 * @since   1.0.0
	 */
	public $bot_producer_name = '';

	/**
	 * @var string  The bot producer url.
	 * @since   1.0.0
	 */
	public $bot_producer_url = '';

}