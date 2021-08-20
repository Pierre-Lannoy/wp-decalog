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

use Decalog\Plugin\Feature\HandlerTypes;

$logger_types = new HandlerTypes();

?>

<div id="normal-sortables" class="meta-box-sortables ui-sortable" style="overflow: hidden;">
	<div class="postbox ">
		<h3 class="hndle" style="cursor:default;"><span><?php esc_html_e( 'Please, select the type of logger you want to add', 'decalog' ); ?>&hellip;</span></h3>
		<div style="width: 100%;text-align: center;padding: 0px;" class="inside">
			<div style="display:grid;grid-template-columns: repeat(auto-fill, 120px);justify-content: center;">
				<style>
					.actionable:hover {border-radius:6px;cursor:pointer; -moz-transition: all .1s ease-in; -o-transition: all .1s ease-in; -webkit-transition: all .1s ease-in; transition: all .1s ease-in; background: #f5f5f5;border:1px solid #e0e0e0;}
					.actionable {border-radius:6px;cursor:pointer; -moz-transition: all .2s ease-in; -o-transition: all .2s ease-in; -webkit-transition: all .2s ease-in; transition: all .2s ease-in; background: transparent;border:1px solid transparent;}
				</style>
				<?php foreach ( $logger_types->get_all() as $logger ) { ?>
					<?php if ( 'system' !== $logger['class'] ) { ?>
						<div><img id="<?php echo $logger['id']; ?>" class="actionable" style="width:80px;" src="<?php echo $logger['icon']; ?>"/></div>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
		<div id="major-publishing-actions">
			<div id="tip-text">&nbsp;</div>
			<div class="clear"></div>
		</div>
	</div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(".actionable").mouseout(function() {
				$("#tip-text").html("&nbsp;");
			});
			<?php foreach ( $logger_types->get_all() as $logger ) { ?>
				$("#<?php echo $logger['id']; ?>").mouseover(function() {
					$("#tip-text").html("<strong><?php echo $logger['name']; ?></strong> - <?php echo ucfirst( $logger['help'] ); ?>");
				});
				$("#<?php echo $logger['id']; ?>").click(function() {
					<?php // phpcs:ignore ?>
					window.open('<?php echo add_query_arg( array( 'page'    => 'decalog-settings', 'action'  => 'form-edit', 'tab'     => 'loggers', 'handler' => $logger['id'], ), admin_url( 'admin.php' ) );?>', '_self');
				});
			<?php } ?>
		});
	</script>
</div>
