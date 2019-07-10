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

// phpcs:ignore
$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'loggers' );

?>

<div class="wrap">

	<h2><?php esc_html( sprintf( __( '%s Settings', 'decalog' ), DECALOG_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>

	<h2 class="nav-tab-wrapper">
		<a href="?page=decalog-settings&tab=loggers" class="nav-tab <?php echo 'loggers' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Loggers', 'decalog' ); ?></a>
		<a href="?page=decalog-settings&tab=misc" class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'decalog' ); ?></a>
		<a href="?page=decalog-settings&tab=about" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'decalog' ); ?></a>
	</h2>

	<?php if ( 'loggers' === $active_tab ) { ?>
		<?php include __DIR__ . '/decalog-admin-settings-loggers.php'; ?>
	<?php } ?>
	<?php if ( 'misc' === $active_tab ) { ?>
		<?php include __DIR__ . '/decalog-admin-settings-misc.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/decalog-admin-settings-about.php'; ?>
	<?php } ?>
</div>
