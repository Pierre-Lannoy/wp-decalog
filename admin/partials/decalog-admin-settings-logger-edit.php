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


?>

<div class="wrap">

	<h2><?php esc_html( sprintf( __( '%s Settings', 'decalog' ), DECALOG_PRODUCT_NAME ) ); ?></h2>

	<form action="<?php echo esc_url(add_query_arg(array('page' => 'adr-settings', 'action' => 'do-edit', 'tab' => 'misc'), admin_url('options-general.php'))); ?>" method="POST">
		<?php do_settings_sections('decalog_logger_privacy_section'); ?>
		<?php wp_nonce_field('adr-settings-misc'); ?>
		<?php echo get_submit_button();?>
	</form>

</div>
