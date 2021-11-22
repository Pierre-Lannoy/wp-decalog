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
	 * The default (en_US) country names.
	 *
	 * @since  1.0.0
	 * @var    array    $countries    Maintains the country names.
	 */
	public static $countries = [
		'00' => '[unknown]',
		'01' => '[loopback]',
		'A0' => '[private network]',
		'A1' => '[anonymous proxy]',
		'A2' => '[satellite]',
		'AF' => 'Afghanistan',
		'AX' => 'Åland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AC' => 'Ascension Island',
		'AP' => 'Asia/Pacific countries',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'PW' => 'Belau',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BQ' => 'Bonaire, Saint Eustatius and Saba',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory',
		'BN' => 'Brunei',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'IC' => 'Canary Islands',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'EA' => 'Ceuta, Melilla',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CP' => 'Clipperton Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo (Brazzaville)',
		'CD' => 'Congo (Kinshasa)',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CW' => 'Curaçao',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DG' => 'Diego Garcia',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EU' => 'European Union',
		'EZ' => 'Eurozone',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'FX' => 'France, Metropolitan',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard Island and McDonald Islands',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'CI' => 'Ivory Coast',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Laos',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MK' => 'North Macedonia',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'MP' => 'Northern Mariana Islands',
		'KP' => 'North Korea',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PS' => 'Palestinian Territory',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RU' => 'Russia',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barthélemy',
		'SH' => 'Saint Helena',
		'KN' => 'Saint Kitts and Nevis',
		'LC' => 'Saint Lucia',
		'MF' => 'Saint Martin (French part)',
		'SX' => 'Saint Martin (Dutch part)',
		'PM' => 'Saint Pierre and Miquelon',
		'VC' => 'Saint Vincent and the Grenadines',
		'SM' => 'San Marino',
		'ST' => 'São Tomé and Príncipe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia/Sandwich Islands',
		'KR' => 'South Korea',
		'SS' => 'South Sudan',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard and Jan Mayen',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syria',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TA' => 'Tristan da Cunha',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom (UK)',
		'UK' => 'United Kingdom (UK)',
		'UN' => 'United Nations (UN)',
		'US' => 'United States (US)',
		'UM' => 'United States (US) Minor Outlying Islands',
		'SU' => 'Union of Soviet Socialist Republics (USSR)',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VA' => 'Vatican',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam',
		'VG' => 'Virgin Islands (British)',
		'VI' => 'Virgin Islands (US)',
		'WF' => 'Wallis and Futuna',
		'EH' => 'Western Sahara',
		'WS' => 'Samoa',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
		'AA' => '[reserved]',
		'QM' => '[reserved]',
		'QN' => '[reserved]',
		'QO' => '[reserved]',
		'QP' => '[reserved]',
		'QQ' => '[reserved]',
		'QR' => '[reserved]',
		'QS' => '[reserved]',
		'QT' => '[reserved]',
		'QU' => '[reserved]',
		'QV' => '[reserved]',
		'QW' => '[reserved]',
		'QX' => '[reserved]',
		'QY' => '[reserved]',
		'QZ' => '[reserved]',
		'XA' => '[reserved]',
		'XB' => '[reserved]',
		'XC' => '[reserved]',
		'XD' => '[reserved]',
		'XE' => '[reserved]',
		'XF' => '[reserved]',
		'XG' => '[reserved]',
		'XH' => '[reserved]',
		'XI' => '[reserved]',
		'XJ' => '[reserved]',
		'XK' => '[reserved]',
		'XL' => '[reserved]',
		'XM' => '[reserved]',
		'XN' => '[reserved]',
		'XO' => '[reserved]',
		'XP' => '[reserved]',
		'XQ' => '[reserved]',
		'XR' => '[reserved]',
		'XS' => '[reserved]',
		'XT' => '[reserved]',
		'XU' => '[reserved]',
		'XV' => '[reserved]',
		'XW' => '[reserved]',
		'XX' => '[reserved]',
		'XY' => '[reserved]',
		'XZ' => '[reserved]',
		'ZZ' => '[reserved]',
	];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Try to get the "default" locale for a country.
	 *
	 * @param  string $country The country code.
	 * @return string The probable locale.
	 * @since  1.0.0
	 */
	public static function get_main_lang_code( $country ) {
		if ( I18n::is_extension_loaded() ) {
			$subtags = \ResourceBundle::create( 'likelySubtags', 'ICUDATA', false );
			$country = \Locale::canonicalize( 'und_' . $country );
			$locale  = $subtags->get( $country ) ? $subtags->get( $country ) : $subtags->get( 'und' );
			return \Locale::getPrimaryLanguage( $locale );
		}
		return '';
	}

	/**
	 * Returns an appropriately localized display name for a lang.
	 *
	 * @since 1.0.0
	 *
	 * @param string    $country    The ISO-2 country code.
	 * @param string    $lang       The locale.
	 * @param string    $locale     Optional. The locale string.
	 * @return string Display name of the region for the current locale.
	 */
	public static function get_main_lang_name( $country, $lang, $locale = null ) {
		if ( ! isset( $locale ) ) {
			$locale = self::get_display_locale();
		}
		if ( 'self' === $locale ) {
			$locale = self::get_main_lang_code( $country );
		}
		$result = '[unknown]';
		if ( I18n::is_extension_loaded() ) {
			$tmp = \Locale::getDisplayLanguage( $lang, $locale );
			if ( $tmp !== $country ) {
				$result = $tmp;
			}
		}
		return $result;
	}

	/**
	 * Get the proper user locale.
	 *
	 * @param  int|WP_User $user_id User's ID or a WP_User object. Defaults to current user.
	 * @return string The locale of the user.
	 * @since  1.0.0
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
			return '<span style="white-space:nowrap;font-size:65%;vertical-align: super;line-height: 1em;">&nbsp;(' . implode( '/', $langs ) . ')</span>';
		} else {
			return '';
		}
	}

	/**
	 * Returns an appropriately localized display name for a country.
	 *
	 * @since 1.0.0
	 *
	 * @param string    $country The ISO-2 country code.
	 * @param string    $locale     Optional. The locale string.
	 * @return string Display name of the region for the current locale.
	 */
	public static function get_country_name( $country, $locale = null ) {
		if ( ! isset( $locale ) ) {
			$locale = self::get_display_locale();
		}
		if ( 'self' === $locale ) {
			$locale = self::get_main_lang_code( $country );
		}
		if ( array_key_exists( $country, self::$countries ) ) {
			$result = self::$countries[ $country ];
		} else {
			$result = '[unknown]';
		}
		if ( I18n::is_extension_loaded() && false === strpos( $result, ']' ) ) {
			$tmp = \Locale::getDisplayRegion( '-' . strtoupper( $country ), $locale );
			if ( $tmp !== $country ) {
				$result = $tmp;
			}
		}
		return $result;
	}

}
