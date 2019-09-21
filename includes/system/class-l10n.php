<?php
/**
 * Localization handling
 *
 * Handles all localization operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

use WP_User;
use Decalog\System\I18n;

/**
 * Define the localization functionality.
 *
 * Handles all localization operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class L10n {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get the proper user locale.
	 *
	 * @param  int|WP_User $user_id User's ID or a WP_User object. Defaults to current user.
	 * @return string The locale of the user.
	 * @since  3.0.8
	 */
	public static function get_display_locale( $user_id = 0 ) {
		global $current_user;
		if ( ! empty( $current_user ) && 0 === $user_id ) {
			if ( $current_user instanceof WP_User ) {
				$user_id = $current_user->ID;
			}
			if ( is_object( $current_user ) && isset( $current_user->ID ) ) {
				$user_id = $current_user->ID;
			}
		}

		/*
		* @fixme how to manage ajax calls made from frontend?
		*/
		if ( function_exists( 'get_user_locale' ) ) {
			return get_user_locale( $user_id );
		} else {
			return get_locale();
		}
	}

	/**
	 * Get the language markup for links.
	 *
	 * @param array $langs Optional. Indicates the language in which the link is available.
	 * @return string The html string of the markup.
	 * @since 1.0.0
	 */
	public static function get_language_markup( $langs = [] ) {
		if ( count( $langs ) > 0 ) {
			return '<span style="white-space:nowrap;font-size:65%;vertical-align: super;line-height: 1em;"> (' . implode( '/', $langs ) . ')</span>';
		} else {
			return '';
		}
	}

	/**
	 * Returns an appropriately localized display name for a country.
	 *
	 * @since 1.0.0
	 *
	 * @param string $country The ISO-2 country code.
	 * @return string Display name of the region for the current locale.
	 */
	public static function get_country_name( $country ) {
		$result = $country;
		if ( I18n::is_extension_loaded() ) {
			$result = \Locale::getDisplayRegion( '-' . strtoupper( $country ), self::get_display_locale() );
		}
		return $result;
	}

}
