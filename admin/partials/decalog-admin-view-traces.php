<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

use Decalog\Plugin\Feature\Traces;

wp_enqueue_style( DECALOG_ASSETS_ID );
wp_enqueue_script( DECALOG_ASSETS_ID );

$traceListTable = new Traces();
$traceListTable->prepare_items();

?>

<div class="wrap">
	<h2><?php echo sprintf( esc_html__( '%s Traces Viewer', 'decalog' ), DECALOG_PRODUCT_NAME );?></h2>
	<?php $traceListTable->views(); ?>
	<form id="events-filter" method="get" action="<?php echo admin_url('admin.php'); ?>">
		<input type="hidden" name="page" value="decalog-tviewer" />
		<?php $traceListTable->display(); ?>
	</form>
</div>
