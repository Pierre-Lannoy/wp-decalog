<?php

namespace Decalog\Panel;

/**
 * Custom panel based on global $current_screen variable
 *
 * @author Martin Hlaváč
 */
class ScreenPanel extends AbstractPanel {

	/**
	 * {@inheritDoc}
	 */
	protected function get_name() {
		return 'Screen';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_title() {
		return 'Screen';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTab() {
		global $current_screen;
		if ( ! empty( $current_screen ) ) {
			return $this->get_standard_tab();
		}
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPanel() {
		global $current_screen;
		return $this->get_objects_panel( [ $current_screen ] );
	}
}
