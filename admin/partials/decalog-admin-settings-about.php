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

$intro      = sprintf( esc_html__( '%1$s is a free and open source plugin for WordPress. It integrates other free and open source works (as-is or modified) like: %2$s.', 'decalog' ), '<em>' . DECALOG_PRODUCT_NAME . '</em>', do_shortcode( '[decalog-libraries]' ) );
$trademarks = __( 'All brands, icons and graphic illustrations are registered trademarks of their respective owners.', 'decalog' );
$brands     = array( 'Automattic' );
$official   = sprintf( __( 'This plugin is not an official software from %s and, as such, is not endorsed or supported by these companies.', 'decalog' ), implode( ', ', $brands ) );

?>
<h2><?php echo esc_html( DECALOG_PRODUCT_NAME . ' ' . DECALOG_VERSION ); ?></h2>
<p><?php echo $intro; ?></p>
<h4><?php esc_html_e( 'Disclaimer', 'decalog' ); ?></h4>
<p><?php echo esc_html( $official ); ?></p>
<p><em><?php echo esc_html( $trademarks ); ?></em></p>
<hr/>
<h2><?php esc_html_e( 'Changelog', 'decalog' ); ?></h2>
<?php echo do_shortcode( '[decalog-changelog]' ); ?>
