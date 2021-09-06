<?php
/**
 * Abstract panel for Tracy.
 *
 * Handles all base features for Tracy panels.
 *
 * @package Panels
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
 */

namespace Decalog\Panel;

use Tracy\Dumper;

/**
 * Define the abstract panel for Tracy
 *
 * Handles all base features for Tracy panels.
 *
 * @package Panels
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.2.0
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
			$output .= '<img src="' . $this->get_icon() . '" width="16" height="16" />&nbsp;';
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
			$output .= '<h1>' . $this->get_name() . '</h1>';
		}
		$output .= '<div class="nette-inner">';
		foreach ( $objects as $object ) {
			$output .= Dumper::toHtml( $object, [ Dumper::COLLAPSE => ( 1 !== count( $objects ) ) ] );
		}
		$output .= '</div>';
		return $output;
	}

	/**
	 * Get the content of panel for an array of arrays.
	 *
	 * @param array     $array      The array of arrays.
	 * @param boolean   $headers    Optional. Prints the keys as header.
	 * @return  null|string  The panel content, ready to print.
	 */
	public function get_arrays_panel( $array, $headers = false ) {
		$output = null;
		if ( ! empty( $this->get_title() ) ) {
			$output .= '<h1>' . $this->get_name() . '</h1>';
		}
		$output .= '<div class="nette-inner">';
		if ( 0 === count( $array ) ) {
			$output .= '<p>No Items In This List</p>';
			$output .= '</div>';
			return $output;
		}
		$output .= '<table>';
		if ( $headers ) {
			$output .= '<thead>';
			$output .= '<tr>';
			foreach ( $array[0] as $key => $field ) {
				$output .= '<td>' . $key . '</td>';
			}
			$output .= '</tr>';
			$output .= '</thead>';
		}
		foreach ( $array as $line ) {
			$output .= '<tr>';
			foreach ( $line as $field ) {
				$output .= '<td>';
				if ( is_array( $field ) || is_object( $field ) ) {
					$output .= Dumper::toHtml( $field, [ Dumper::COLLAPSE => true ] );
				} else {
					$output .= $field;
				}
				$output .= '</td>';
			}
			$output .= '</tr>';
		}
		$output .= '</table>';
		$output .= '</div>';
		return $output;
	}
}
