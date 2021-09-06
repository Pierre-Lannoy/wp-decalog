<?php
/**
 * Current panel for Tracy.
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
 * Current panel for Tracy.
 *
 * Handles all base features for Tracy panels.
 *
 * @package Panels
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */
class CurrentPanel extends AbstractPanel {

	/**
	 * {@inheritDoc}
	 */
	protected function get_icon() {
		return Icons::get_base64( 'check-square', 'none', '#50AD58' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_name() {
		global $post_type;
		return $post_type;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_title() {
		global $pagenow;
		return $pagenow;
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
		global $authordata;
		$objects[] = [
			'Variable'      => '$authordata',
			'Current Value' => $authordata,
		];
		global $current_screen;
		$objects[] = [
			'Variable'      => '$current_screen',
			'Current Value' => $current_screen,
		];
		global $menu;
		$objects[] = [
			'Variable'      => '$menu',
			'Current Value' => $menu,
		];
		global $wp_meta_boxes;
		$objects[] = [
			'Variable'      => '$wp_meta_boxes',
			'Current Value' => $wp_meta_boxes,
		];
		global $pages;
		$objects[] = [
			'Variable'      => '$pages',
			'Current Value' => $pages,
		];
		global $post;
		$objects[] = [
			'Variable'      => '$post',
			'Current Value' => $post,
		];
		global $posts;
		$objects[] = [
			'Variable'      => '$posts',
			'Current Value' => $posts,
		];
		global $wp_query;
		$objects[] = [
			'Variable'      => '$wp_query',
			'Current Value' => $wp_query,
		];
		return $this->get_arrays_panel( $objects );
	}
}
