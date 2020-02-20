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
	<form action="
	<?php
	echo esc_url(
		add_query_arg(
			array(
				'page'    => 'decalog-settings',
				'action'  => 'do-edit',
				'tab'     => 'loggers',
				'handler' => $current_logger['handler'],
				'uuid'    => $current_logger['uuid'],
			),
			admin_url( 'admin.php' )
		)
	);
	?>
	" method="POST">
		<?php do_settings_sections( 'decalog_logger_misc_section' ); ?>
		<?php do_settings_sections( 'decalog_logger_specific_section' ); ?>
		<?php do_settings_sections( 'decalog_logger_privacy_section' ); ?>
		<?php do_settings_sections( 'decalog_logger_details_section' ); ?>
		<?php wp_nonce_field( 'decalog-logger-edit' ); ?>
		<p><?php echo get_submit_button( esc_html__( 'Cancel', 'decalog' ), 'secondary', 'cancel', false ); ?>&nbsp;&nbsp;&nbsp;<?php echo get_submit_button( null, 'primary', 'submit', false ); ?></p>
	</form>
</div>
