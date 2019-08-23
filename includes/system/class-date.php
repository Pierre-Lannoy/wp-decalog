<?php
/**
 * Date handling
 *
 * Handles all date operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the date functionality.
 *
 * Handles all date operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Date {

	/**
	 * Converts an UTC date into the correct format.
	 *
	 * @param   string  $ts The UTC MySql datetime to be converted.
	 * @param   string  $tz Optional. The timezone.
	 * @param   string  $format Optional. The date format.
	 * @return  string   Formatted date relative to the given timezone.
	 * @since    1.0.0
	 */
	public static function get_date_from_mysql_utc($ts, $tz='', $format='-') {
		if ($format == '-') {
			$format = get_option('date_format');
		}
		if ($tz != '') {
			$datetime = new \DateTime($ts, new \DateTimeZone('UTC'));
			$datetime->setTimezone(new \DateTimeZone($tz));
			return date_i18n($format, strtotime($datetime->format('Y-m-d H:i:s')));
		}
		else {
			return date_i18n($format, strtotime(get_date_from_gmt($ts)));
		}
	}

	/**
	 * Get the difference between now and a date, in human readable style (like "8 minutes ago" or "currently").
	 *
	 * @param   string $from The UTC MySql datetime from which the difference must be computed (as today).
	 * @return  string  Human readable time difference.
	 * @since    1.0.0
	 */
	public static function get_positive_time_diff_from_mysql_utc($from) {
		if (strtotime($from) < time()) {
			return sprintf( esc_html__('%s ago', 'decalog'), human_time_diff(strtotime($from)));
		}
		else {
			return esc_html__('currently', 'decalog');
		}
	}

	/**
	 * Get the difference between now and a date, in human readable style (like "8 minutes ago" or "in 19 seconds").
	 *
	 * @param   string $from The UTC MySql datetime from which the difference must be computed (as today).
	 * @return  string  Human readable time difference.
	 * @since    1.0.0
	 */
	public static function get_time_diff_from_mysql_utc($from) {
		if (strtotime($from) < time()) {
			return sprintf( esc_html__('%s ago', 'decalog'), human_time_diff(strtotime($from)));
		}
		else {
			return sprintf( esc_html__('in %s', 'decalog'), human_time_diff(strtotime($from)));
		}
	}

}
