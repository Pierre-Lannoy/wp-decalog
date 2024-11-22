<?php
/**
 * Plugin updates handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\Plugin\Feature\Log;
use Decalog\Plugin\Feature\LoggerMaintainer;
use Decalog\System\Markdown;
use Decalog\System\Nag;
use Decalog\System\Option;
use Decalog\System\Cache;
use Decalog\System\Environment;
use Decalog\System\Role;
use Exception;

/**
 * Plugin updates handling.
 *
 * This class defines all code necessary to handle the plugin's updates.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Updater {

	private $name = DECALOG_PRODUCT_NAME;

	private $slug = DECALOG_SLUG;

	private $version = DECALOG_VERSION;

	private $product = DECALOG_PRODUCT_URL;

	/**
	 * Initializes the class, set its properties and performs
	 * post-update processes if needed.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$old = Option::network_get( 'version' );
		Option::network_set( 'version', DECALOG_VERSION );
		if ( DECALOG_VERSION !== $old ) {
			if ( '0.0.0' === $old ) {
				$this->install();
				// phpcs:ignore
				$message = sprintf( decalog_esc_html__( '%1$s has been correctly installed.', 'decalog' ), DECALOG_PRODUCT_NAME );
			} else {
				$this->update( $old );
				// phpcs:ignore
				$message = sprintf( decalog_esc_html__( '%1$s has been correctly updated from version %2$s to version %3$s.', 'decalog' ), DECALOG_PRODUCT_NAME, $old, DECALOG_VERSION );
				$logger  = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
				$logger->notice( $message );
				// phpcs:ignore
				$message .= ' ' . sprintf(decalog__( 'See <a href="%s">what\'s new</a>.', 'decalog' ), admin_url( 'admin.php?page=decalog-settings&tab=about' ) );
			}
			Nag::add( 'update', 'info', $message );
		}
		if ( ! ( defined( 'POO_SELFUPDATE_BYPASS' ) && POO_SELFUPDATE_BYPASS ) ) {
			add_filter( 'plugins_api', [ $this, 'plugin_info' ], PHP_INT_MAX, 3 );
			add_filter( 'site_transient_update_plugins', [ $this, 'info_update' ] );
			add_action( 'upgrader_process_complete', [ $this, 'info_reset' ], 10, 2 );
			add_filter( 'clean_url', [ $this, 'filter_logo' ], PHP_INT_MAX, 3 );
		}
	}

	/**
	 * Performs post-installation processes.
	 *
	 * @since 1.0.0
	 */
	private function install() {

	}

	/**
	 * Performs post-update processes.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since 1.0.0
	 */
	private function update( $from ) {
		// Starting 1.3.x, PushoverHandler is replaced by PshHandler.
		$loggers = Option::network_get( 'loggers', null );
		if ( isset( $loggers ) ) {
			foreach ( $loggers as &$logger ) {
				if ( array_key_exists( 'handler', $logger ) ) {
					if ( 'PushoverHandler' === $logger['handler'] ) {
						$logger['handler'] = 'PshHandler';
					}
				}
			}
			Option::network_set( 'loggers', $loggers );
		}
		// DecaLog handlers auto updating.
		$maintainer = new LoggerMaintainer();
		$maintainer->update( $from );
		// Updates MU-Plugin and dropin function.
		decalog_reset_earlyloading();
	}

	/**
	 * Get the changelog.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_changelog( $attributes ) {
		$md = new Markdown();

		return $md->get_shortcode( 'CHANGELOG.md', $attributes );
	}

	/**
	 * Acquires infos about update
	 *
	 * @return  object   The remote info.
	 */
	private function gather_info() {
		$remotes = Cache::get_global( 'data_update-infos' );
		if ( ! $remotes ) {
			$remotes = new \stdClass();

			$remote = wp_remote_get(
				str_replace( 'github.com', 'raw.githubusercontent.com', $this->product ) . '/refs/heads/master/plugin.json',
				[
					'timeout' => 10,
					'headers' => [
						'Accept' => 'application/vnd.github+json'
					]
				]
			);
			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}
			$plugin_info             = json_decode( wp_remote_retrieve_body( $remote ), true );
			$remotes->tested         = $plugin_info['tested'] ?? '7.0';
			$remotes->requires       = $plugin_info['requires'] ?? '6.2';
			$remotes->requires_php   = $plugin_info['requires_php'] ?? '7.1';
			$remotes->author         = $plugin_info['author'] ?? '<a href="https://perfops.one">Pierre Lannoy / PerfOps One</a>';
			$remotes->author_profile = $plugin_info['author_profile'] ?? 'https://profiles.wordpress.org/pierrelannoy/';

			$remote = wp_remote_get(
				'https://releases.perfops.one/' . $this->slug . '.json',
				[
					'timeout' => 10,
					'headers' => [
						'Accept' => 'application/vnd.github+json'
					]
				]
			);
			if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
				return false;
			}
			$release_info = json_decode( wp_remote_retrieve_body( $remote ), true );
			if ( array_key_exists( 'tag_name', $release_info ) && array_key_exists( 'name', $release_info ) && array_key_exists( 'published_at', $release_info ) && array_key_exists( 'body', $release_info ) && array_key_exists( 'assets', $release_info ) && is_array( $release_info['assets'] ) ) {
				$remotes->version      = $release_info['tag_name'];
				$remotes->download_url = $release_info['assets'][0]['browser_download_url'] ?? '-';
				$remotes->last_updated = substr( $release_info['published_at'], 0, strpos( $release_info['published_at'], 'T' ) );
				$remotes->changelog    = '## ' . $release_info['name'] . "\r\n" . $release_info['body'];
			} else {
				return false;
			}
			if ( '-' === $remotes->download_url ) {
				return false;
			}

			Cache::set_global( 'data_update-infos', $remotes, DAY_IN_SECONDS );
		}

		return $remotes;
	}

	/**
	 * Filters the url logo to be sure it is svg-inlined.
	 *
	 * @param string $good_protocol_url The cleaned URL to be returned.
	 * @param string $original_url The URL prior to cleaning.
	 * @param string $_context If 'display', replace ampersands and single quotes only.
	 *
	 * @return string $good_protocol_url The cleaned URL.
	 */
	function filter_logo( $good_protocol_url, $original_url, $_context ) {
		if ( 'https://data.' . $this->slug === $original_url ) {
			return Core::get_base64_logo();
		}

		return $good_protocol_url;
	}

	/**
	 * Filters the response for the current WordPress.org Plugin Installation API request.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string $action The type of information being requested from the Plugin Installation API.
	 * @param object $args Plugin API arguments.
	 * @@return false|object|array  The result object or array.
	 */
	function plugin_info( $res, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $res;
		}
		if ( $this->slug !== $args->slug ) {
			return $res;
		}
		$infos = $this->gather_info();
		if ( ! $infos ) {
			return $res;
		}
		$md                           = new Markdown();
		$res                          = new \stdClass();
		$res->name                    = $this->name;
		$res->homepage                = 'https://perfops.one/' . $this->slug;
		$res->slug                    = $this->slug;
		$res->is_community            = true;
		$res->external_repository_url = $this->product;
		$res->tested                  = $infos->tested;
		$res->requires                = $infos->requires;
		$res->requires_php            = $infos->requires_php;
		$res->last_updated            = $infos->last_updated;
		$res->author                  = $infos->author;
		$res->author_profile          = $infos->author_profile;
		$res->version                 = $infos->version;
		$res->download_link           = $infos->download_url;
		$res->trunk                   = $infos->download_url;
		$res->sections                = [
			'changelog' => $md->get_inline( $infos->changelog, [] ) . '<br/><br/><p><a target="_blank" href="' . $res->homepage . '-changelog">CHANGELOG »</a></p>',
		];
		$res->banners                 = [
			"low"  => str_replace( 'github.com', 'raw.githubusercontent.com', $this->product ) . '/refs/heads/master/.wordpress-org/banner-772x250.jpg',
			"high" => str_replace( 'github.com', 'raw.githubusercontent.com', $this->product ) . '/refs/heads/master/.wordpress-org/banner-1544x500.jpg'
		];
		return $res;
	}

	/**
	 * Updates infos transient
	 *
	 * @param object $transient The transient to update.
	 *
	 * @return  object   The updated transient.
	 */
	public function info_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$remote = $this->gather_info();
		if ( $remote && version_compare( $this->version, $remote->version, '<' ) && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) && version_compare( $remote->requires_php, PHP_VERSION, '<' ) ) {
			$res                                 = new \stdClass();
			$res->slug                           = $this->slug;
			$res->plugin                         = $this->slug . '/' . $this->slug . '.php';
			$res->new_version                    = $remote->version;
			$res->tested                         = $remote->tested;
			$res->package                        = $remote->download_url;
			$res->icons                          = [
				'svg' => 'https://data.' . $this->slug
			];
			$transient->response[ $res->plugin ] = $res;
		}

		return $transient;
	}

	/**
	 * Reset update infos
	 *
	 * @param Plugin_Upgrader $upgrader Upgrader instance.
	 * @param array $options Array of bulk item update data.
	 */
	public function info_reset( $upgrader, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			delete_transient( 'update-' . $this->slug );
		}
	}
}
