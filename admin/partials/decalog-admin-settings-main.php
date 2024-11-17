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

	<h2><?php echo esc_html( sprintf( decalog_esc_html__( '%s Settings', 'decalog' ), DECALOG_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>

	<h2 class="nav-tab-wrapper">
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'decalog-settings',
					'tab'  => 'loggers',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'loggers' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Loggers', 'decalog' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'decalog-settings',
					'tab'  => 'listeners',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
        " class="nav-tab <?php echo 'listeners' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Listeners', 'decalog' ); ?></a>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'decalog-settings',
					'tab'  => 'misc',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'decalog' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'decalog-settings',
					'tab'  => 'about',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'decalog' ); ?></a>
		<?php if ( class_exists( 'Decalog\Plugin\Feature\Wpcli' ) ) { ?>
            <a href="
            <?php
            echo esc_url(
                add_query_arg(
                    array(
                        'page' => 'decalog-settings',
                        'tab'  => 'wpcli',
                    ),
                    admin_url( 'admin.php' )
                )
            );
            ?>
            " class="nav-tab <?php echo 'wpcli' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;">WP-CLI</a>
		<?php } ?>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'decalog-settings',
					'tab'  => 'metrics',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'metrics' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'Metrics', 'decalog' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'decalog-settings',
					'tab'  => 'selfreg',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'selfreg' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'Self-Registration', 'decalog' ); ?></a>
	</h2>

	<?php if ( 'loggers' === $active_tab ) { ?>
		<?php include __DIR__ . '/decalog-admin-settings-loggers.php'; ?>
	<?php } ?>
	<?php if ( 'listeners' === $active_tab ) { ?>
		<?php include __DIR__ . '/decalog-admin-settings-listeners.php'; ?>
	<?php } ?>
	<?php if ( 'misc' === $active_tab ) { ?>
		<?php include __DIR__ . '/decalog-admin-settings-options.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/decalog-admin-settings-about.php'; ?>
	<?php } ?>
	<?php if ( 'wpcli' === $active_tab ) { ?>
		<?php wp_enqueue_style( DECALOG_ASSETS_ID ); ?>
		<?php echo do_shortcode( '[decalog-wpcli]' ); ?>
	<?php } ?>
	<?php if ( 'metrics' === $active_tab ) { ?>
		<?php wp_enqueue_style( DECALOG_ASSETS_ID ); ?>
		<p><?php esc_html_e( 'Here are, for each class and profile, the exposed metrics of your WordPress instance:', 'decalog' ); ?></p>
		<?php echo do_shortcode( '[decalog-metrics]' ); ?>
	<?php } ?>
	<?php if ( 'selfreg' === $active_tab ) { ?>
		<?php wp_enqueue_style( DECALOG_ASSETS_ID ); ?>
        <p><?php esc_html_e( 'Here are the third-party components that have self-registered with DecaLog:', 'decalog' ); ?></p>
		<?php echo do_shortcode( '[decalog-selfreg]' ); ?>
	<?php } ?>
</div>