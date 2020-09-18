<?php
/**
 * Global functions.
 *
 * @package Functions
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

/**
 * Multibyte String Pad
 *
 * Functionally, the equivalent of the standard str_pad function, but is capable of successfully padding multibyte strings.
 *
 * @param string $input The string to be padded.
 * @param int $length The length of the resultant padded string.
 * @param string $padding The string to use as padding. Defaults to space.
 * @param int $padType The type of padding. Defaults to STR_PAD_RIGHT.
 * @param string $encoding The encoding to use, defaults to UTF-8.
 *
 * @return string A padded multibyte string.
 * @since   2.0.0
 */
function decalog_mb_str_pad( $input, $length, $padding = ' ', $padType = STR_PAD_RIGHT, $encoding = 'UTF-8' ) {
	$result = $input;
	if ( ( $padding_required = $length - mb_strlen( $input, $encoding ) ) > 0 ) {
		switch ( $padType ) {
			case STR_PAD_LEFT:
				$result =
					mb_substr( str_repeat( $padding, $padding_required ), 0, $padding_required, $encoding ) .
					$input;
				break;
			case STR_PAD_RIGHT:
				$result =
					$input .
					mb_substr( str_repeat( $padding, $padding_required ), 0, $padding_required, $encoding );
				break;
			case STR_PAD_BOTH:
				$left_padding_length  = floor( $padding_required / 2 );
				$right_padding_length = $padding_required - $left_padding_length;
				$result             =
					mb_substr( str_repeat( $padding, $left_padding_length ), 0, $left_padding_length, $encoding ) .
					$input .
					mb_substr( str_repeat( $padding, $right_padding_length ), 0, $right_padding_length, $encoding );
				break;
		}
	}
	return $result;
}
