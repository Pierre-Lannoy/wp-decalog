<?php
/**
 * Initialization of PerfOps One globals.
 *
 * @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/autoload.php';

if ( ! defined( 'PERFOO_INITIALIZED' ) ) {
	define( 'PERFOO_PRODUCT_NAME', 'PerfOps One' );
	define( 'PERFOO_PRODUCT_URL', 'https://perfops.one' );
	define( 'PERFOO_PRODUCT_SHORTNAME', 'PerfOps One' );
	define( 'PERFOO_INITIALIZED', true );
	define( 'PERFOO_ASSETS_ID', 'poo-assets' );
	define( 'PERFOO_ASSETS_CSS', plugins_url( 'perfopsone/assets/perfopsone.min.css', plugin_basename( dirname( __FILE__ ) ) ) );
}