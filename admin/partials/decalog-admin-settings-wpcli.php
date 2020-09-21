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

use Decalog\System\Environment;

wp_enqueue_style( DECALOG_ASSETS_ID );
wp_enqueue_script( DECALOG_ASSETS_ID );

?>
<?php echo do_shortcode( '[decalog-wpcli]' ); ?>
