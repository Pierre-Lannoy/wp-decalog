<?php

namespace Decalog\Panel;

/**
 * Custom panel based on global $current_screen variable
 *
 * @author Martin Hlaváč
 */
class LogPanel extends AbstractPanel {

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	public function getTab() {
		global $current_screen;
		if ( ! empty( $current_screen ) ) {
			//return parent::getSimpleTab( __( 'Current Screen' ) );
		}
		return null;
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	public function getPanel() {
		/** @var $current_screen \WP_Screen */
		global $current_screen;
		$output = parent::getObjectPanel( $current_screen );
		return $output;
	}
}
