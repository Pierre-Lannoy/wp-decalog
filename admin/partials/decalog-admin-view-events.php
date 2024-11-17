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

use Decalog\Plugin\Feature\Events;

wp_enqueue_style( DECALOG_ASSETS_ID );
wp_enqueue_script( DECALOG_ASSETS_ID );

$eventListTable = new Events();
$eventListTable->prepare_items();

?>

<div class="wrap">
	<h2><?php echo sprintf( decalog_esc_html__( '%s Events Viewer', 'decalog' ), DECALOG_PRODUCT_NAME );?></h2>
	<?php $eventListTable->views(); ?>
	<form id="events-filter" method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="decalog-viewer" />
		<?php $eventListTable->display(); ?>
	</form>
</div>
