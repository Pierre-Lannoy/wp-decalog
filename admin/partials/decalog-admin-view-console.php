<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use APCuManager\System\Option;
use Decalog\Plugin\Feature\Autolog;

if ( ! Option::network_get( 'livelog' ) ) {
	Autolog::activate();
}

wp_enqueue_style( DECALOG_LIVELOG_ID );
wp_enqueue_script( DECALOG_LIVELOG_ID );
?>

<div class="wrap">
	<h2><?php echo sprintf( esc_html__( '%s Live Events', 'decalog' ), DECALOG_PRODUCT_NAME );?></h2>

    <div class="decalog-logger-view">
    </div>

</div>
