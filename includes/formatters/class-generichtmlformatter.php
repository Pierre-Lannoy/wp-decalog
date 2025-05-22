<?php declare(strict_types=1);
/**
 * Generic HTML formatter for Monolog
 *
 * Handles all features of generic HTML formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */

namespace Decalog\Formatter;

use Decalog\Plugin\Feature\ClassTypes;
use Decalog\Plugin\Feature\EventTypes;
use Decalog\Plugin\Feature\ChannelTypes;
use Decalog\System\Blog;
use Decalog\System\Environment;
use Decalog\System\Http;
use Decalog\System\User;
use Decalog\System\UserAgent;
use DLMonolog\Formatter\FormatterInterface;
use DLMonolog\Logger;
use Decalog\System\GeoIP;
use Decalog\System\EmojiFlag;
use Decalog\System\PHP;
use PODeviceDetector\API\Device;
use Decalog\System\Hash;

/**
 * Define the Monolog generic HTML formatter.
 *
 * Handles all features of generic HTML formatter for Monolog.
 *
 * @package Formatters
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.0
 */
class GenericHtmlFormatter implements FormatterInterface {

	/**
	 * Model.
	 *
	 * @since  3.4.0
	 * @var    integer    $model    The model ID.
	 */
	protected $model;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   int     $model      The model to use.
	 * @since    3.4.0
	 */
	public function __construct( int $model ) {
		$this->model = $model;
	}

	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format.
	 * @return string The formatted record.
	 * @since   3.4.0
	 */
	public function format( array $record ): string {
		$result = '';
		if ( array_key_exists( 'message', $record ) ) {
			$result .= '<strong>' . $record['message'] . '</strong>';
		}
		if ( array_key_exists( 'level', $record ) ) {
			if ( array_key_exists( $record['level'], EventTypes::$level_names ) ) {
				$result .= '<br/>' . EventTypes::$level_names[ $record['level'] ];
			}
			// CONTEXT
			if ( array_key_exists( 'context', $record ) ) {
				$context = $record['context'];
				if ( array_key_exists( 'code', $context ) ) {
					$result .= '&nbsp' . (int) $context['code'];
				}
				if ( array_key_exists( 'component', $context ) && array_key_exists( 'version', $context ) ) {
					$result .= ' (' . $context['component'] . ' ' . $context['version'] . ')';
				}
			}
		}
		if ( 0 !== $this->model ) {
			// EXTRA
			if ( array_key_exists( 'extra', $record ) ) {
				$extra = $record['extra'];
				if ( array_key_exists( 'userid', $extra ) && array_key_exists( 'username', $extra ) && array_key_exists( 'siteid', $extra ) ) {
					if ( 'anonymous' === $extra['username'] ) {
						$result .= '<br/>' . decalog_esc_html__( 'Anonymous user', 'decalog' );
					} elseif ( 0 === strpos( $extra['username'], '{' ) ) {
						$result .= '<br/>' . decalog_esc_html__( 'Pseudonymized user', 'decalog' );
					} elseif ( 0 !== (int) $extra['userid'] ) {
						$result .= '<br/>' . User::get_user_string( (int) $extra['userid'] );
					} else {
						$result .= '<br/>' . decalog_esc_html__( 'Deleted user', 'decalog' );
					}
					$result .= ' on ' . Blog::get_full_blog_name( (int) $extra['siteid'] );
				}
				if ( array_key_exists( 'ua', $extra ) && $extra['ua'] && is_string( $extra['ua'] ) ) {
					$result .= $this->device( $extra['ua'] );
				}
				if ( array_key_exists( 'ip', $extra ) && is_string( $extra['ip'] ) && array_key_exists( 'url', $extra ) && is_string( $extra['url'] ) && array_key_exists( 'http_method', $extra ) && is_string( $extra['http_method'] ) ) {
					$result .= $this->request( $extra );
				}
				if ( array_key_exists( 'file', $extra ) || array_key_exists( 'class', $extra ) || array_key_exists( 'function', $extra ) ) {
					$result .= $this->introspection( $extra );
				}
				if ( array_key_exists( 'trace', $extra ) && $extra['trace'] && is_array( $extra['trace'] ) ) {
					$result .= $this->trace( $extra['trace'] );
				}
			}
		}
		return '<div>' . $result . '</div>';
	}

	/**
	 * Get trace detail.
	 *
	 * @param  array   $trace     The trace records.
	 * @return string   The trace detail.
	 * @since   3.4.0
	 */
	private function trace( $trace ): string {
		$summary = decalog_esc_html__( 'WordPress backtrace', 'decalog' );
		if ( array_key_exists( 'error', $trace ) ) {
			$details = '<span style="margin-left:24px;">⚠&nbsp;' . $trace['error'] . '</span>';
		} elseif ( array_key_exists( 'wordpress', $trace ) && 0 < count( $trace['wordpress'] ) ) {
			$details = '';
			foreach ( array_reverse( $trace['wordpress'] ) as $idx => $item ) {
				$str = '<span style="font-family:sans-serif;font-weight:600">' . json_decode( sprintf( '"\u%04x"', 0 === $idx ? 9450 : ( 20 < $idx ? 12860 + $idx : 9311 + $idx ) ) ) . '</span>';
				$details .= ( 0 < $idx ? '<br/>' : '' ) . '<span style="margin-left:24px;">' . $str . '&nbsp;<code>' . $item . '</code></span>';
			}
		} else {
			$details = '<span style="margin-left:24px;">⚠&nbsp;' . decalog_esc_html__( 'No backtrace available', 'decalog' ) . '</span>';
		}
		return '<details><summary>' . $summary . '</summary><div>' . $details . '</div></details>';
	}

	/**
	 * Get php introspection detail.
	 *
	 * @param  array   $extra     The extra records.
	 * @return string   The php source detail.
	 * @since   3.4.0
	 */
	private function introspection( $extra ): string {
		$summary = decalog_esc_html__( 'PHP introspection', 'decalog' );
		if ( array_key_exists( 'file', $extra ) ) {
			$details = '<span style="margin-left:24px;">' . decalog_esc_html__( 'Source: ', 'decalog' ) . '<code>' . PHP::normalized_file( $extra['file'] ) . ':' . ( $extra['line'] ?? '' ) . '</code></span>';
		} else {
			$details = '<span style="margin-left:24px;">' . decalog_esc_html__( 'Source: ', 'decalog' ) . decalog_esc_html__( 'Unknown', 'decalog' ) . '</span>';
		}
		if ( array_key_exists( 'function', $extra ) ) {
			$details .= '<br/><span style="margin-left:24px;">' . decalog_esc_html__( 'Function: ', 'decalog' ) . '<code>' . $extra['function'] . '</code></span>';
		}
		if ( array_key_exists( 'class', $extra ) ) {
			$details .= '<br/><span style="margin-left:24px;">' . decalog_esc_html__( 'Class: ', 'decalog' ) . '<code>' . $extra['class'] . '</code></span>';
		}
		return '<details><summary>' . $summary . '</summary><div>' . $details . '</div></details>';
	}

	/**
	 * Get request detail.
	 *
	 * @param  array   $extra     The extra records.
	 * @return string   The request detail.
	 * @since   3.4.0
	 */
	private function request( $extra ): string {
		$summary = '';
		$details = '';
		if ( array_key_exists( 'ip', $extra ) && is_string( $extra['ip'] ) ) {
			$icon = '';
			if ( 0 === strpos( $extra['ip'], '{' ) ) {
				$extra['ip'] = decalog_esc_html__( 'obfuscated IP', 'decalog' );
			} else {
				if ( filter_var( $extra['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
					$geoip = new GeoIP();
					$icon  = EmojiFlag::get( $geoip->get_iso3166_alpha2( $extra['ip'] ) ) . '&nbsp;';
				}
			}
			$summary = decalog_esc_html__( 'HTTP Request', 'decalog' );
			if ( array_key_exists( 'server', $extra ) && is_string( $extra['server'] ) ) {
				$extra['server'] .= ' ';
			} else {
				$extra['server'] = '';
			}
			$details .= '<span style="margin-left:24px;">' . sprintf( decalog_esc_html__( '%s to %s from %s', 'decalog' ), $extra['http_method'] ?? 'UKN', $extra['server'], $icon . $extra['ip'] ) . '</span>';
		} else {
			return '';
		}
		if ( array_key_exists( 'url', $extra ) ) {
			$details .= '<br/><span style="margin-left:24px;">' . decalog_esc_html__( 'Url: ', 'decalog' ) . $extra['url'] . '</span>';
		}
		if ( array_key_exists( 'referrer', $extra ) && is_string( $extra['referrer'] ) ) {
			$details .= '<br/><span style="margin-left:24px;">' . decalog_esc_html__( 'Referrer: ', 'decalog' ) . $extra['referrer'] . '</span>';
		}
		if ( '' !== $summary ) {
			return '<details><summary>' . $summary . '</summary><div>' . $details . '</div></details>';
		}
		return '';
	}

	/**
	 * Get device detail.
	 *
	 * @param  string   $ua     The user agent.
	 * @return string   The device detail.
	 * @since   3.4.0
	 */
	private function device( $ua ): string {
		$device  = UserAgent::get( $ua );
		$summary = '';
		$details = '';
		if ( $device->class_is_bot ) {
			$summary = decalog_esc_html__( 'Bot details', 'decalog' );
			$details  .= '<span><img style="width:20px;float:left;padding-right:6px;margin-left:24px;padding-top:3px;" src="' . $device->bot_icon_base64() . '" />';
			$details  .= ( 1 < strlen( $device->bot_name ) ? $device->bot_name : decalog_esc_html__( 'Unknown', 'decalog' ) ) . ' (' . ( 1 < strlen( $device->bot_producer_name ) ? $device->bot_producer_name : decalog_esc_html__( 'Unknown', 'decalog' ) ) . ')</span>';
		} elseif ( $device->class_is_desktop || $device->class_is_mobile ) {
			$summary = decalog_esc_html__( 'Device details', 'decalog' );
			$details  .= '<span><img style="width:20px;float:left;padding-right:6px;margin-left:24px;padding-top:3px;" src="' . $device->brand_icon_base64() . '" />';
			$details  .= ( '-' !== $device->brand_name && '' !== $device->brand_name ? $device->brand_name : decalog_esc_html__( 'Generic', 'decalog' ) ) . ( '-' !== $device->model_name ? ' ' . $device->model_name : '' ) . '</span>';
			$details  .= '<br/><span><img style="width:20px;float:left;padding-right:6px;margin-left:24px;padding-top:3px;" src="' . $device->os_icon_base64() . '" />';
			$details  .= ( '-' !== $device->os_name ? $device->os_name : decalog_esc_html__( 'Unknown', 'decalog' ) ) . ( '-' !== $device->os_version ? ' ' . $device->os_version : '' ) . '</span>';
			if ( $device->client_is_browser ) {
				$details .= '<br/><span><img style="width:20px;float:left;padding-right:6px;margin-left:24px;padding-top:3px" src="' . $device->browser_icon_base64() . '" />';
				$details .= ( '-' !== $device->client_name ? $device->client_name : decalog_esc_html__( 'Generic', 'decalog' ) ) . ( '-' !== $device->client_version ? ' ' . $device->client_version : '' ) . '</span>';
			}
		} elseif ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			$summary = decalog_esc_html__( 'Client details', 'decalog' );
			if ( '' !== $device->client_name ) {
				$details .= '<span style="margin-left:24px;">' . ( '-' !== $device->client_name ? $device->client_name : decalog_esc_html__( 'Generic', 'decalog' ) ) . ( '-' !== $device->client_version ? ' ' . $device->client_version : '' ) . ' (' . $device->client_full_type . ')</span>';
			} else {
				$details .= '<span style="margin-left:24px;">' . decalog_esc_html__( 'Local shell', 'decalog' ) . '</span>';
			}
		}
		if ( '' !== $summary ) {
			return '<details><summary>' . $summary . '</summary><div>' . $details . '</div></details>';
		}
		return '';
	}

	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format.
	 * @return string The formatted set of records.
	 * @since   3.4.0
	 */
	public function formatBatch( array $records ): string {
		if ( 0 < count( $records ) ) {
			return $this->format( $records[0] );
		}
		return '';
	}
}
