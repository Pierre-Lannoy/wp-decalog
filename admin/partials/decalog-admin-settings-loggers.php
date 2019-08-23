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

use Decalog\Plugin\Feature\Loggers;

$loggers = new Loggers();
$loggers->prepare_items();

$button = '<a href="#" class="page-title-action add-trigger">' . esc_html__( 'Add a Logger', 'decalog' ) . '</a>'

?>

<style>.tablenav{display:none !important;}</style>

<p>&nbsp;</p>
<p><?php echo $button; ?></p>
<div class="add-text" style="display:none;">
	<div id="wpcom-stats-meta-box-container" class="metabox-holder">
		<div class="postbox-container" style="width: 100%;margin-right: 10px;">
			<?php require DECALOG_ADMIN_DIR . 'partials/decalog-admin-settings-logger-choose.php'; ?>
		</div>
	</div>
</div>
<?php $loggers->display(); ?>

<script type="text/javascript">
	jQuery(document).ready(function($) {
		$(".add-trigger").click(function() {
			$(".add-text").slideToggle(400);
		});
	});
</script>
