<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\Plugin;

use Decalog\Log;
use Decalog\Plugin\Feature\HandlerTypes;
use Decalog\Plugin\Feature\ProcessorTypes;
use Decalog\Plugin\Feature\LoggerFactory;
use Decalog\System\Assets;
use Decalog\System\UUID;
use Decalog\System\Option;
use Decalog\System\Form;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Decalog_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * The internal logger.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    DLogger    $logger    The plugin admin logger.
	 */
	protected $logger;

	/**
	 * The current logger.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array    $current_logger    The current logger.
	 */
	protected $current_logger;

	/**
	 * The current handler.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    array    $current_handler    The current handler.
	 */
	protected $current_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
		$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( DECALOG_ASSETS_ID, DECALOG_ADMIN_URL, 'css/decalog.min.css' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( DECALOG_ASSETS_ID, DECALOG_ADMIN_URL, 'js/decalog.min.js', [ 'jquery' ] );
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_submenu_page( 'options-general.php', sprintf( __( '%s Settings', 'decalog' ), DECALOG_PRODUCT_NAME ), DECALOG_PRODUCT_NAME, apply_filters( 'adr_manage_options_capability', 'manage_options' ), 'decalog-settings', [ $this, 'get_settings_page' ] );
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'decalog_logger_misc_section', null, [ $this, 'logger_misc_section_callback' ], 'decalog_logger_misc_section' );
		add_settings_section( 'decalog_logger_delete_section', null, [ $this, 'logger_delete_section_callback' ], 'decalog_logger_delete_section' );
		add_settings_section( 'decalog_logger_specific_section', null, [ $this, 'logger_specific_section_callback' ], 'decalog_logger_specific_section' );
		add_settings_section( 'decalog_logger_privacy_section', __( 'Privacy options', 'decalog' ), [ $this, 'logger_privacy_section_callback' ], 'decalog_logger_privacy_section' );
		add_settings_section( 'decalog_logger_details_section', __( 'Reported details', 'decalog' ), [ $this, 'logger_details_section_callback' ], 'decalog_logger_details_section' );
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		$this->current_handler = null;
		$this->current_logger  = null;
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $handler = filter_input( INPUT_GET, 'handler' ) ) ) {
			$handler = filter_input( INPUT_POST, 'handler' );
		}
		if ( ! ( $uuid = filter_input( INPUT_GET, 'uuid' ) ) ) {
			$uuid = filter_input( INPUT_POST, 'uuid' );
		}
		$nonce = filter_input( INPUT_GET, 'nonce' );
		if ( $uuid ) {
			$loggers = Option::get( 'loggers' );
			if ( array_key_exists( $uuid, $loggers ) ) {
				$this->current_logger         = $loggers[ $uuid ];
				$this->current_logger['uuid'] = $uuid;
			}
		}
		if ( $handler ) {
			$handlers              = new HandlerTypes();
			$this->current_handler = $handlers->get( $handler );
		} elseif ( $this->current_logger ) {
			$handlers              = new HandlerTypes();
			$this->current_handler = $handlers->get( $this->current_logger['handler'] );
		}
		if ( $this->current_handler && ! $this->current_logger ) {
			$this->current_logger = [
				'uuid'    => $uuid = UUID::generate_v4(),
				'name'    => __( 'New logger', 'decalog' ),
				'handler' => $this->current_handler['id'],
				'running' => Option::get( 'logger_autostart' ),
			];
		}
		if ( $this->current_logger ) {
			$factory              = new LoggerFactory();
			$this->current_logger = $factory->check( $this->current_logger );
		}
		$view = 'decalog-admin-settings-main';
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'loggers':
					switch ( $action ) {
						case 'form-edit':
							$current_logger  = $this->current_logger;
							$current_handler = $this->current_handler;
							$args            = compact( 'current_logger', 'current_handler' );
							$view            = 'decalog-admin-settings-logger-edit';
							break;
						case 'form-delete':
							$current_logger  = $this->current_logger;
							$current_handler = $this->current_handler;
							$args            = compact( 'current_logger', 'current_handler' );
							$view            = 'decalog-admin-settings-logger-delete';
							break;
						case 'do-edit':
							$this->save_current();
							break;
						case 'do-delete':
							$this->delete_current();
							break;
						case 'start':
							if ($nonce && $uuid && wp_verify_nonce( $nonce, 'decalog-logger-start-' . $uuid )) {
								$loggers = Option::get( 'loggers' );
								if ( array_key_exists( $uuid, $loggers ) ) {
									$loggers[ $uuid ]['running'] = true;
									Option::set( 'loggers', $loggers );
									$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
									$message      = sprintf( __( 'Logger %s has started.', 'decalog' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
									$code         = 0;
									add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
									$this->logger->notice( sprintf( 'Logger "%s" has started.', $loggers[ $uuid ]['name'] ), $code );
								}
							}
							break;
						case 'pause':
							if ($nonce && $uuid && wp_verify_nonce( $nonce, 'decalog-logger-pause-' . $uuid )) {
								$loggers = Option::get( 'loggers' );
								if ( array_key_exists( $uuid, $loggers ) ) {
									$message = sprintf( __( 'Logger %s has been paused.', 'decalog' ), '<em>' . $loggers[ $uuid ]['name'] . '</em>' );
									$code    = 0;
									$this->logger->notice( sprintf( 'Logger "%s" has been paused.', $loggers[ $uuid ]['name'] ), $code );
									$loggers[ $uuid ]['running'] = false;
									Option::set( 'loggers', $loggers );
									$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
									add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
								}
							}
					}
					break;
				case 'misc':
					break;
			}
		}
		include DECALOG_ADMIN_DIR . 'partials/' . $view . '.php';
	}

	/**
	 * Save the current logger as new or modified logger.
	 *
	 * @since 1.0.0
	 */
	private function save_current() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-logger-edit' ) ) {
				if ( array_key_exists( 'submit', $_POST ) ) {
					$this->current_logger['name']                        = ( array_key_exists( 'decalog_logger_misc_name', $_POST ) ? filter_input( INPUT_POST, 'decalog_logger_misc_name', FILTER_SANITIZE_STRING ) : $this->current_logger['name'] );
					$this->current_logger['level']                       = ( array_key_exists( 'decalog_logger_misc_level', $_POST ) ? filter_input( INPUT_POST, 'decalog_logger_misc_level', FILTER_SANITIZE_NUMBER_INT ) : $this->current_logger['level'] );
					$this->current_logger['privacy']['obfuscation']      = ( array_key_exists( 'decalog_logger_privacy_ip', $_POST ) ? true : false );
					$this->current_logger['privacy']['pseudonymization'] = ( array_key_exists( 'decalog_logger_privacy_name', $_POST ) ? true : false );
					$this->current_logger['processors']                  = [];
					$proc = new ProcessorTypes();
					foreach ( array_reverse( $proc->get_all() ) as $processor ) {
						if ( array_key_exists( 'decalog_logger_details_' . strtolower( $processor['id'] ), $_POST ) ) {
							$this->current_logger['processors'][] = $processor['id'];
						}
					}
					foreach ( $this->current_handler['configuration'] as $key => $configuration ) {
						$id = 'decalog_logger_details_' . strtolower( $key );
						if ( 'boolean' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? true : false );
						}
						if ( 'integer' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? filter_input( INPUT_POST, $id, FILTER_SANITIZE_NUMBER_INT ) : $this->current_logger['configuration'][ $key ] );
						}
						if ( 'string' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? filter_input( INPUT_POST, $id, FILTER_SANITIZE_STRING ) : $this->current_logger['configuration'][ $key ] );
						}
						if ( 'password' === $configuration['control']['cast'] ) {
							$this->current_logger['configuration'][ $key ] = ( array_key_exists( $id, $_POST ) ? filter_input( INPUT_POST, $id, FILTER_UNSAFE_RAW ) : $this->current_logger['configuration'][ $key ] );
						}
					}
					$uuid             = $this->current_logger['uuid'];
					$loggers          = Option::get( 'loggers' );
					$factory          = new LoggerFactory();
					$loggers[ $uuid ] = $factory->check( $this->current_logger, true );
					if ( array_key_exists( 'uuid', $loggers[ $uuid ] ) ) {
						unset( $loggers[ $uuid ]['uuid'] );
					}
					Option::set( 'loggers', $loggers );
					$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
					$message      = sprintf( __( 'Logger %s has been saved.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
					$code         = 0;
					add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
					$this->logger->notice( sprintf( 'Logger "%s" has been saved.', $this->current_logger['name'] ), $code );
				}
			} else {
				$message = sprintf( __( 'Logger %s has not been saved. Please try again.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
				$code    = 2;
				add_settings_error( 'adr_nonce_error', $code, $message, 'error' );
				$this->logger->warning( sprintf( 'Logger "%s" has not been saved.', $this->current_logger['name'] ), $code );
			}
		}
	}

	/**
	 * Delete the current logger.
	 *
	 * @since 1.0.0
	 */
	private function delete_current() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'decalog-logger-delete' ) ) {
				if ( array_key_exists( 'submit', $_POST ) ) {
					$uuid    = $this->current_logger['uuid'];
					$loggers = Option::get( 'loggers' );
					$factory = new LoggerFactory();
					$factory->clean( $this->current_logger );
					unset( $loggers[ $uuid ] );
					Option::set( 'loggers', $loggers );
					$this->logger = Log::bootstrap( 'plugin', DECALOG_PRODUCT_SHORTNAME, DECALOG_VERSION );
					$message      = sprintf( __( 'Logger %s has been removed.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
					$code         = 0;
					add_settings_error( 'decalog_no_error', $code, $message, 'updated' );
					$this->logger->notice( sprintf( 'Logger "%s" has been removed.', $this->current_logger['name'] ), $code );

				}
			} else {
				$message = sprintf( __( 'Logger %s has not been removed. Please try again.', 'decalog' ), '<em>' . $this->current_logger['name'] . '</em>' );
				$code    = 2;
				add_settings_error( 'adr_nonce_error', $code, $message, 'error' );
				$this->logger->warning( sprintf( 'Logger "%s" has not been removed.', $this->current_logger['name'] ), $code );
			}
		}
	}

	/**
	 * Callback for logger misc section.
	 *
	 * @since 1.0.0
	 */
	public function logger_misc_section_callback() {
		$icon  = '<img style="vertical-align:middle;width:34px;margin-top: -2px;padding-right:6px;" src="' . $this->current_handler['icon'] . '" />';
		$title = $this->current_handler['name'];
		echo '<h2>' . $icon . '&nbsp;' . $title . '</h2>';
		echo '<p style="margin-top: -10px;margin-left: 6px;">' . $this->current_handler['help'] . '</p>';
		$form = new Form();
		add_settings_field(
			'decalog_logger_misc_name',
			__( 'Name', 'decalog' ),
			[ $form, 'echo_field_input_text' ],
			'decalog_logger_misc_section',
			'decalog_logger_misc_section',
			[
				'id'          => 'decalog_logger_misc_name',
				'value'       => $this->current_logger['name'],
				'description' => __( 'Used only in admin dashboard.', 'decalog' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_logger_misc_section', 'decalog_logger_misc_name' );
		add_settings_field(
			'decalog_logger_misc_level',
			__( 'Minimal level', 'decalog' ),
			[ $form, 'echo_field_select' ],
			'decalog_logger_misc_section',
			'decalog_logger_misc_section',
			[
				'list'        => Log::get_levels(),
				'id'          => 'decalog_logger_misc_level',
				'value'       => $this->current_logger['level'],
				'description' => __( 'Minimal reported level. May be overridden by the "respect WP_DEBUG directive" option.', 'decalog' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_logger_misc_section', 'decalog_logger_misc_level' );
	}

	/**
	 * Callback for logger delete section.
	 *
	 * @since 1.0.0
	 */
	public function logger_delete_section_callback() {
		$icon  = '<img style="vertical-align:middle;width:34px;margin-top: -2px;padding-right:6px;" src="' . $this->current_handler['icon'] . '" />';
		$title = $this->current_handler['name'];
		echo '<h2>' . $icon . '&nbsp;' . $title . '</h2>';
		echo '<p style="margin-top: -10px;margin-left: 6px;">' . $this->current_handler['help'] . '</p>';
		$form = new Form();
		add_settings_field(
			'decalog_logger_delete_name',
			__( 'Name', 'decalog' ),
			[ $form, 'echo_field_input_text' ],
			'decalog_logger_delete_section',
			'decalog_logger_delete_section',
			[
				'id'          => 'decalog_logger_delete_name',
				'value'       => $this->current_logger['name'],
				'description' => null,
				'full_width'  => true,
				'enabled'     => false,
			]
		);
		register_setting( 'decalog_logger_delete_section', 'decalog_logger_delete_name' );
		add_settings_field(
			'decalog_logger_delete_level',
			__( 'Minimal level', 'decalog' ),
			[ $form, 'echo_field_select' ],
			'decalog_logger_delete_section',
			'decalog_logger_delete_section',
			[
				'list'        => Log::get_levels(),
				'id'          => 'decalog_logger_delete_level',
				'value'       => $this->current_logger['level'],
				'description' => null,
				'full_width'  => true,
				'enabled'     => false,
			]
		);
		register_setting( 'decalog_logger_delete_section', 'decalog_logger_delete_level' );
	}

	/**
	 * Callback for logger specific section.
	 *
	 * @since 1.0.0
	 */
	public function logger_specific_section_callback() {
		$form = new Form();
		if ( 'ErrorLogHandler' === $this->current_logger['handler'] ) {
			add_settings_field(
				'decalog_logger_specific_dummy',
				__( 'Log file', 'decalog' ),
				[ $form, 'echo_field_input_text' ],
				'decalog_logger_specific_section',
				'decalog_logger_specific_section',
				[
					'id'          => 'decalog_logger_specific_dummy',
					'value'       => ini_get( 'error_log' ),
					'description' => __( 'Value set in php.ini file.', 'decalog' ),
					'full_width'  => true,
					'enabled'     => false,
				]
			);
			register_setting( 'decalog_logger_specific_section', 'decalog_logger_specific_dummy' );
		}
		foreach ( $this->current_handler['configuration'] as $key => $configuration ) {
			$id   = 'decalog_logger_details_' . strtolower( $key );
			$args = [
				'id'          => $id,
				'text'        => __( 'Enabled', 'decalog' ),
				'checked'     => (bool) $this->current_logger['configuration'][ $key ],
				'value'       => $this->current_logger['configuration'][ $key ],
				'description' => $configuration['help'],
				'full_width'  => true,
				'enabled'     => $configuration['control']['enabled'],
				'list'        => ( array_key_exists( 'list', $configuration['control'] ) ? $configuration['control']['list'] : [] ),
			];
			foreach ( $configuration['control'] as $key => $control ) {
				if ( 'type' !== $key && 'cast' !== $key ) {
					$args[ $key ] = $control;
				}
			}
			add_settings_field(
				$id,
				$configuration['name'],
				[ $form, 'echo_' . $configuration['control']['type'] ],
				'decalog_logger_specific_section',
				'decalog_logger_specific_section',
				$args
			);
			register_setting( 'decalog_logger_specific_section', $id );
		}
	}

	/**
	 * Callback for logger privacy section.
	 *
	 * @since 1.0.0
	 */
	public function logger_privacy_section_callback() {
		$form = new Form();
		add_settings_field(
			'decalog_logger_privacy_ip',
			__( 'Remote IPs', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_logger_privacy_section',
			'decalog_logger_privacy_section',
			[
				'text'        => __( 'Obfuscation', 'decalog' ),
				'id'          => 'decalog_logger_privacy_ip',
				'checked'     => $this->current_logger['privacy']['obfuscation'],
				'description' => __( 'If checked, log will contain hash instead of real IPs.', 'decalog' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_logger_privacy_section', 'decalog_logger_privacy_ip' );
		add_settings_field(
			'decalog_logger_privacy_name',
			__( 'Users', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_logger_privacy_section',
			'decalog_logger_privacy_section',
			[
				'text'        => __( 'Pseudonymisation', 'decalog' ),
				'id'          => 'decalog_logger_privacy_name',
				'checked'     => $this->current_logger['privacy']['pseudonymization'],
				'description' => __( 'If checked, log will contain hashes instead of user IDs & names.', 'decalog' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'decalog_logger_privacy_section', 'decalog_logger_privacy_name' );
	}

	/**
	 * Callback for logger privacy section.
	 *
	 * @since 1.0.0
	 */
	public function logger_details_section_callback() {
		$form = new Form();
		$id   = 'decalog_logger_details_dummy';
		add_settings_field(
			$id,
			__( 'Standard', 'decalog' ),
			[ $form, 'echo_field_checkbox' ],
			'decalog_logger_details_section',
			'decalog_logger_details_section',
			[
				'text'        => __( 'Included', 'decalog' ),
				'id'          => $id,
				'checked'     => true,
				'description' => __( 'Allows to log standard DecaLog information.', 'decalog' ),
				'full_width'  => true,
				'enabled'     => false,
			]
		);
		register_setting( 'decalog_logger_details_section', $id );
		$proc = new ProcessorTypes();
		foreach ( array_reverse( $proc->get_all() ) as $processor ) {
			$id = 'decalog_logger_details_' . strtolower( $processor['id'] );
			add_settings_field(
				$id,
				$processor['name'],
				[ $form, 'echo_field_checkbox' ],
				'decalog_logger_details_section',
				'decalog_logger_details_section',
				[
					'text'        => __( 'Included', 'decalog' ),
					'id'          => $id,
					'checked'     => in_array( $processor['id'], $this->current_logger['processors'] ),
					'description' => $processor['help'],
					'full_width'  => true,
					'enabled'     => 'WordpressHandler' !== $this->current_logger['handler'] || 'BacktraceProcessor' === $processor['id'],
				]
			);
			register_setting( 'decalog_logger_details_section', $id );
		}
	}

}
