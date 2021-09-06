<?php

namespace Decalog\Panel;

use Tracy\Dumper;

/**
 * Common basic model for other WP panels
 *
 * @author Martin Hlaváč
 */
abstract class AbstractPanel implements \Tracy\IBarPanel {

	/**
	 * The already initialized panels.
	 *
	 * @since  3.2.0
	 * @var    array    $panels    The panels.
	 */
	protected static $panels = [];

	/**
	 * The panel id.
	 *
	 * @since  3.2.0
	 * @var    string    $id    The panel id.
	 */
	protected $id = '';

	/**
	 * Get the panel icon.
	 *
	 * @since  3.2.0
	 * @return  null|string The encoded icon.
	 */
	protected function get_icon() {
		return null;
	}

	/**
	 * Get the panel name.
	 *
	 * @since  3.2.0
	 * @return  null|string The name.
	 */
	protected function get_name() {
		return null;
	}

	/**
	 * Get the panel title.
	 *
	 * @since  3.2.0
	 * @return  null|string The title.
	 */
	protected function get_title() {
		return null;
	}

	/**
	 * Get the panel tab.
	 * @return null|string  The tab.
	 */
	public function getTab() {
		return null;
	}

	/**
	 * Set the panel id.
	 *
	 * @since  3.2.0
	 */
	private function set_id() {
		$this->id = str_replace( 'panel', '', strtolower( ( new \ReflectionClass( $this ) )->getShortName() ) );
	}

	/**
	 * Get the content of tab.
	 *
	 * @return  null|string  The tab content, ready to print.
	 */
	public function get_standard_tab() {
		$this->set_id();
		if ( '' === $this->id || 'abstract' === $this->id || in_array( $this->id, self::$panels, true ) ) {
			return null;
		}
		self::$panels[] = $this->id;
		$output         = '<span' . ( ! empty( $this->get_name() ) ? ' title="' . $this->get_name() . '"' : '' ) . '>';
		if ( ! empty( $this->get_icon() ) ) {
			$output .= '<img src="' . $this->get_icon() . '" width="16" height="16" />';
		}
		if ( ! empty( $this->get_title() ) ) {
			$output .= $this->get_title();
		}
		$output .= '</span>';
		return $output;
	}

	/**
	 * Get the content of panel for objects.
	 *
	 * @param array $objects    The objects.
	 * @return  null|string  The panel content, ready to print.
	 */
	public function get_objects_panel( $objects ) {
		$output = null;
		if ( ! empty( $this->get_title() ) ) {
			$output .= '<h1>' . $this->get_title() . '</h1>';
		}
		$output .= '<div class="nette-inner">';
		foreach ( $objects as $object ) {
			$output .= Dumper::toHtml( $object, [ Dumper::COLLAPSE => ( 1 !== count( $objects ) ) ] );
		}
		$output .= '</div>';
		return $output;
	}

	/**
	 * Get the content of panel for a single object.
	 *
	 * @param mixed $object
	 * @return  null|string  The panel content, ready to print.
	 */
	public function get_single_object_panel( $object ) {
		$output = null;
		if ( ! empty( $this->get_title() ) ) {
			$output .= '<h1>' . $this->get_title() . '</h1>';
		}
		$output .= '<div class="nette-inner">';
		$output .= Dumper::toHtml( $object, [ Dumper::COLLAPSE => false ] );
		$output .= '</div>';
		return $output;
	}

	/**
	 * (HTML) table content of panel based on parameters array
	 *
	 * @param array $params
	 * @param string $title
	 * @return string
	 */
	public function getTablePanel( array $params, $title = null ) {
		$output = null;
		if ( ! empty( $title ) ) {
			$output .= "<h1>$title</h1>";
		}
		$output .= '<div class="nette-inner">';
		$output .= '<table>';
		$output .= '<thead>';
		$output .= '<tr>';
		$output .= '<th>' . __( 'Parameter' ) . '</th>';
		$output .= '<th>' . __( 'Value' ) . '</th>';
		$output .= '</tr>';
		$output .= '</thead>';
		foreach ( $params as $key => $value ) {
			$output .= '<tr>';
			$output .= "<td>$key:</td>";
			$output .= "<td>$value</td>";
			$output .= '</tr>';
		}
		$output .= '</table>';
		$output .= '</div>';
		return $output;
	}
}
