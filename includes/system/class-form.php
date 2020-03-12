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

namespace POSessions\System;

use Feather;

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
	 * Get an input form field for number.
	 *
	 * @param   string  $id     The id (and the name) of the control.
	 * @param   integer $value  The current value.
	 * @param   integer $min    Minimal number for input.
	 * @param   integer $max    Maximal number for input.
	 * @param   integer $step   Step value for the control.
	 * @param   string  $description    Optional. A description to display.
	 * @param   string  $unit   Optional. A unit to display just after the control.
	 * @param   boolean $full_width     Optional. Is the control full width?
	 * @param   boolean $enabled     Optional. Is the control enabled?
	 * @return  string  The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_input_integer( $id, $value, $min, $max, $step, $description = null, $full_width = true, $enabled = true, $unit = null ) {
		if ( $full_width ) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '<input' . ( $enabled ? '' : ' disabled' ) . ' name="' . $id . '" type="number" step="' . $step . '" min="' . $min . '" max="' . $max . '"id="' . $id . '" value="' . $value . '"' . $width . ' class="regular-text"/>';
		if ( isset( $unit ) ) {
			$html .= '&nbsp;<label for="' . $id . '">' . $unit . '</label>';
		}
		if ( isset( $description ) ) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Echoes an input form field for number.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_input_integer( $args ) {
		echo $this->field_input_integer( $args['id'], $args['value'], $args['min'], $args['max'], $args['step'], $args['description'], $args['full_width'], $args['enabled'] );
	}

	/**
	 * Get a text form field.
	 *
	 * @param   string  $id The id (and the name) of the control.
	 * @param   string  $value  The string to put in the text field.
	 * @param   string  $description    Optional. A description to display.
	 * @param   boolean $full_width     Optional. Is the control full width?
	 * @param   boolean $enabled     Optional. Is the control enabled?
	 * @param   string  $placeholder Optional. Placeholder of the input.
	 * @return  string The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_input_text( $id, $value = '', $description = null, $full_width = true, $enabled = true, $placeholder = '' ) {
		if ( $full_width ) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '<input' . ( $enabled ? '' : ' disabled' ) . ' class="regular-text" name="' . $id . '" placeholder="' . $placeholder . '" type="text" id="' . $id . '" value="' . $value . '"' . $width . ' class="regular-text"/>';
		if ( isset( $description ) ) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Echoes a text form field.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_input_text( $args ) {
		echo $this->field_input_text( $args['id'], $args['value'], $args['description'], $args['full_width'], $args['enabled'], $args['placeholder'] );
	}

	/**
	 * Get a text form field.
	 *
	 * @param   string  $id The id (and the name) of the control.
	 * @param   string  $value  The string to put in the text field.
	 * @param   string  $description    Optional. A description to display.
	 * @param   integer $columns     Optional. Number of columns.
	 * @param   integer $lines     Optional. Number of lines.
	 * @param   boolean $enabled     Optional. Is the control enabled?
	 * @return  string The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_input_textarea( $id, $value = '', $description = null, $columns = 80, $lines = 3, $enabled = true ) {
		$html = '<textarea' . ( $enabled ? '' : ' disabled' ) . ' name="' . $id . '" id="' . $id . '" cols="' . $columns . '" rows="' . $lines . '" class="regular-text code">' . $value . '</textarea>';
		if ( isset( $description ) ) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Echoes a text form field.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_input_textarea( $args ) {
		echo $this->field_input_textarea( $args['id'], $args['value'], $args['description'], $args['columns'], $args['lines'], $args['enabled'] );
	}

	/**
	 * Get a password form field.
	 *
	 * @param   string  $id The id (and the name) of the control.
	 * @param   string  $value  The string to put in the text field.
	 * @param   string  $description    Optional. A description to display.
	 * @param   boolean $full_width     Optional. Is the control full width?
	 * @param   boolean $enabled     Optional. Is the control enabled?
	 * @return  string The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_input_password( $id, $value = '', $description = null, $full_width = true, $enabled = true ) {
		if ( $full_width ) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '<input' . ( $enabled ? '' : ' disabled' ) . ' name="' . $id . '" type="text" id="' . $id . '" value="' . $value . '"' . $width . ' class="regular-text"/>';
		if ( isset( $description ) ) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Echoes a password form field.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_input_password( $args ) {
		echo $this->field_input_password( $args['id'], $args['value'], $args['description'], $args['full_width'], $args['enabled'] );
	}

	/**
	 * Get a select form field.
	 *
	 * @param   array      $list   The list of options.
	 * @param   string     $id The id (and the name) of the control.
	 * @param   int|string $value  The string to put in the text field.
	 * @param   string     $description    Optional. A description to display.
	 * @param   boolean    $full_width Optional. Is the control full width?
	 * @param   boolean    $enabled     Optional. Is the control enabled?
	 * @return  string  The HTML string ready to print.
	 * @since  1.0.0
	 */
	public function field_select( $list, $id, $value, $description = null, $full_width = true, $enabled = true ) {
		if ( $full_width ) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '';
		foreach ( $list as $val ) {
			$html .= '<option value="' . $val[0] . '"' . ( $val[0] == $value ? ' selected="selected"' : '' ) . ( isset( $val[2] ) && ! $val[2] ? ' disabled' : '' ) . '>' . $val[1] . '</option>';
		}
		$html = '<select' . $width . ( $enabled ? '' : ' disabled' ) . ' name="' . $id . '" id="' . $id . '">' . $html . '</select>';
		if ( isset( $description ) ) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Echoes a select form field.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_select( $args ) {
		echo $this->field_select( $args['list'], $args['id'], $args['value'], $args['description'], $args['full_width'], $args['enabled'] );
	}

	/**
	 * Get a radio form field.
	 *
	 * @param   array      $list   The list of options.
	 * @param   string     $id The id (and the name) of the control.
	 * @param   int|string $value  The string to put in the text field.
	 * @param   string     $description    Optional. A description to display.
	 * @param   boolean    $full_width Optional. Is the control full width?
	 * @param   boolean    $enabled     Optional. Is the control enabled?
	 * @return  string  The HTML string ready to print.
	 * @since  1.0.0
	 */
	public function field_radio( $list, $id, $value, $description = null, $full_width = true, $enabled = true ) {
		if ( $full_width ) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '';
		foreach ( $list as $val ) {
			$html .= '<label><input' . ( $enabled ? '' : ' disabled' ) . ' id="' . $id . '" name="' . $id . '" type="radio" value="' . $val[0] . '"' . ( $val[0] == $value ? ' checked="checked"' : '' ) . '/>' . $val[1] . '</label>';
			if ( $val !== end( $list ) ) {
				$html .= '<br/>';
			}
		}
		$html = '<fieldset' . $width . '>' . $html . '</fieldset>';
		if ( isset( $description ) ) {
			$html .= '<p class="description">' . $description . '</p>';
		}
		return $html;
	}

	/**
	 * Echoes a radio form field.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_radio( $args ) {
		echo $this->field_radio( $args['list'], $args['id'], $args['value'], $args['description'], $args['full_width'], $args['enabled'] );
	}

	/**
	 * Get a checkbox form field.
	 *
	 * @param   string  $text        The text of the checkbox.
	 * @param   string  $id          The id (and the name) of the control.
	 * @param   boolean $checked     Optional. Is the checkbox on?
	 * @param   string  $description Optional. A description to display.
	 * @param   string  $more        Optional. The content of a "more" box.
	 * @param   boolean $full_width  Optional. Is the control full width?
	 * @param   boolean $enabled     Optional. Is the control enabled?
	 * @return  string  The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_checkbox( $text, $id, $checked = false, $description = null, $more = null, $full_width = true, $enabled = true ) {
		if ( $full_width ) {
			$width = ' style="width:100%;"';
		} else {
			$width = '';
		}
		$html = '<fieldset' . $width . '><label><input' . ( $enabled ? '' : ' disabled' ) . ' name="' . $id . '" type="checkbox" value="1"' . ( $checked ? ' checked="checked"' : '' ) . '/>' . $text . '</label></fieldset>';
		if ( isset( $description ) ) {
			if ( isset( $more ) ) {
				$description .= '<img id="button-' . $id . '" style="cursor:pointer;vertical-align:middle;width:16px;margin-left:6px;" src="' . Feather\Icons::get_base64( 'help-circle', 'none', '#9999BB' ) . '" />';
			}
			$html .= '<p class="description">' . $description . '</p>';
		}
		if ( isset( $more ) ) {
			$html .= '<p id="more-' . $id . '" style="line-height:1.8em;word-break:break-word;font-size:smaller;padding:8px;border-radius:2px;background-color:#F9F9F9;display:none;">' . $more . '</p>';
			$html .= '<script>jQuery(document).ready(function($){$("#button-' . $id . '").click(function(){$("#more-' . $id . '").slideToggle(200);});});</script>';
		}
		return $html;
	}

	/**
	 * Echoes a checkbox form field.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_checkbox( $args ) {
		echo $this->field_checkbox( $args['text'], $args['id'], $args['checked'], $args['description'], array_key_exists( 'more', $args ) ? $args['more'] : null, $args['full_width'], $args['enabled'] );
	}

	/**
	 * Get a simple text in form field.
	 *
	 * @param   string $text   The text.
	 * @return  string  The HTML string ready to print.
	 * @since   1.0.0
	 */
	public function field_simple_text( $text ) {
		$html = $text;
		return $html;
	}

	/**
	 * Echoes a simple text in form field.
	 *
	 * @param   array $args   The call arguments.
	 * @since   1.0.0
	 */
	public function echo_field_simple_text( $args ) {
		echo $this->field_simple_text( $args['text'] );
	}

}
