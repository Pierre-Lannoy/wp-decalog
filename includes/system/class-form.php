<?php
/**
 * Forms handling
 *
 * Handles all forms operations and generation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Decalog\System;

/**
 * Define the forms functionality.
 *
 * Handles all forms operations and generation.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Form {

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get a input form field for number.
	 *
	 * @param   string  $id     The id (and the name) of the control.
	 * @param   integer $value  The current value.
	 * @param   integer $min    Minimal number for input.
	 * @param   integer $max    Maximal number for input.
	 * @param   integer $step   Step value for the control.
	 * @param   string  $description    Optional. A description to display.
	 * @param   string  $unit   Optional. A unit to display just after the control.
	 * @param   boolean $full_width     Optional. Is the control full width?
	 * @return  string  The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_input_integer($id, $value, $min, $max, $step, $description=null, $unit=null, $full_width=true)
	{

		if ($full_width) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '<input name="' . $id . '" type="number" step="' . $step . '" min="' . $min . '" max="' . $max . '"id="' . $id . '" value="' . $value . '"' . $width . '/>';
		if (isset($unit)) {
			$html .= '&nbsp;<label for="' . $id . '">' . $unit . '</label>';
		}
		if (isset($description)) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Get a text form field.
	 *
	 * @param   string  $id The id (and the name) of the control.
	 * @param   string  $value  The string to put in the text field.
	 * @param   string  $description    Optional. A description to display.
	 * @param   boolean $full_width     Optional. Is the control full width?
	 * @return  string The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_input_text($id, $value='', $description=null, $full_width=true)
	{
		if ($full_width) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '<input name="' . $id . '" type="text" id="' . $id . '" value="' . $value . '"' . $width . '/>';
		if (isset($description)) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Get a password form field.
	 *
	 * @param   string  $id The id (and the name) of the control.
	 * @param   string  $value  The string to put in the text field.
	 * @param   string  $description    Optional. A description to display.
	 * @param   boolean $full_width     Optional. Is the control full width?
	 * @return  string The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_input_password($id, $value='', $description=null, $full_width=true)
	{
		if ($full_width) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '<input name="' . $id . '" type="text" id="' . $id . '" value="' . $value . '"' . $width . '/>';
		if (isset($description)) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Get an unprepared select form field.
	 *
	 * @param   array   $list   The list of options.
	 * @param   string  $id The id (and the name) of the control.
	 * @param   int|string  $value  The string to put in the text field.
	 * @param   string  $description    Optional. A description to display.
	 * @param   boolean $full_width Optional. Is the control full width?
	 * @return  string  The HTML string ready to print.
	 * @since  1.0.0
	 */
	protected function field_select($list, $id, $value, $description=null, $full_width=true)
	{
		if ($full_width) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '';
		foreach ($list as $val) {
			$html .= '<option value="' . $val[0] . '"' . ( $val[0] === $value ? ' selected="selected"' : '') . '>' . $val[1] . '</option>';
		}
		$html = '<select' . $width . ' name="' . $id . '" id="' . $id . '">' . $html . '</select>';
		if (isset($description)) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

}
