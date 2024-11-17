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

use Decalog\System\Option;
use Decalog\Plugin\Feature\Autolog;

if ( ! Autolog::is_enabled() ) {
	Autolog::activate();
}

wp_localize_script(
	DECALOG_LIVELOG_ID,
	'livelog',
	[
		'restUrl'   => esc_url_raw( rest_url() . DECALOG_REST_NAMESPACE . '/livelog' ),
		'restNonce' => wp_create_nonce( 'wp_rest' ),
		'buffer'    => 200,
		'frequency' => 750,
	]
);

wp_enqueue_style( DECALOG_LIVELOG_ID );
wp_dequeue_style('media');
wp_dequeue_style('media-views');
wp_enqueue_script( DECALOG_LIVELOG_ID );
?>

<div class="wrap">
	<h2><?php echo sprintf( decalog_esc_html__( '%s Live Events', 'decalog' ), DECALOG_PRODUCT_NAME );?></h2>
    <div class="media-toolbar wp-filter decalog-pilot-toolbar" style="border-radius:4px;">
        <div class="media-toolbar-secondary" data-children-count="2">
            <div class="view-switch media-grid-view-switch">
                <span class="dashicons dashicons-controls-play decalog-control decalog-control-inactive" id="decalog-control-play"></span>
                <span class="dashicons dashicons-controls-pause decalog-control decalog-control-inactive" id="decalog-control-pause"></span>
            </div>
            <select id="decalog-select-level" class="attachment-filters">
                <option value="info"><?php echo decalog_esc_html__( 'All', 'decalog' );?></option>
                <option value="notice"><?php echo decalog_esc_html__( 'Notices & beyond', 'decalog' );?></option>
                <option value="error"><?php echo decalog_esc_html__( 'Errors & beyond', 'decalog' );?>
            </select>
            <select id="decalog-select-format" class="attachment-filters">
                <option value="wp"><?php echo decalog_esc_html__( 'WordPress details', 'decalog' );?></option>
                <option value="http"><?php echo decalog_esc_html__( 'HTTP request', 'decalog' );?></option>
                <option value="php"><?php echo decalog_esc_html__( 'PHP introspection', 'decalog' );?></option>
            </select>
            <div class="view-switch media-grid-view-switch" style="display: inline;">
                <span class="decalog-control-hint" style="float: right">initializing&nbsp;&nbsp;&nbsp;⚪</span>
            </div>
        </div></div>

    <div class="decalog-logger-view"><div id="decalog-logger-lines"></div></div>

</div>
