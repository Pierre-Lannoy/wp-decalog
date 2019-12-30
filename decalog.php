<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       DecaLog
 * Plugin URI:        https://github.com/Pierre-Lannoy/wp-decalog
 * Description:       Capture and log events on your site. View them in your dashboard and send them to logging services.
 * Version:           1.7.2
 * Author:            Pierre Lannoy
 * Author URI:        https://pierre.lannoy.fr
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       decalog
 * Network:           true
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';
require_once __DIR__ . '/includes/features/class-watchdog.php';

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function decalog_activate() {
	Decalog\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function decalog_deactivate() {
	Decalog\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function decalog_uninstall() {
	Decalog\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function decalog_run() {
	Decalog\System\Cache::init();
	Decalog\System\Sitehealth::init();
	$plugin = new Decalog\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'decalog_activate' );
register_deactivation_hook( __FILE__, 'decalog_deactivate' );
register_uninstall_hook( __FILE__, 'decalog_uninstall' );
decalog_run();
