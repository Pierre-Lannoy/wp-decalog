<?php
/**
 * DB panel for Tracy.
 *
 * Handles all base features for Tracy panels.
 *
 * @package Panels
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */

namespace Decalog\Panel;

use Decalog\System\Environment;
use Feather\Icons;

/**
 * DB panel for Tracy.
 *
 * Handles all base features for Tracy panels.
 *
 * @package Panels
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */
class DatabasePanel extends AbstractPanel {

	/**
	 * {@inheritDoc}
	 */
	protected function get_icon() {
		return Icons::get_base64( 'database', 'none', '#F1953E' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_name() {
		return Environment::mysql_model() . '&nbsp;' . Environment::mysql_version();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_title() {
		return Environment::mysql_model() . '&nbsp;' . Environment::mysql_version();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTab() {
		return $this->get_standard_tab();
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	public function getPanel() {
		global $wpdb;
		return $this->get_objects_panel( [ $wpdb ] );
	}
}
