<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
//////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * ClearOS 6.x default theme.
 *
 * @package Theme
 * @author {@link http://www.clearfoundation.com/ ClearFoundation}
 * @license http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @copyright Copyright 2010 ClearFoundation
 */

///////////////////////////////////////////////////////////////////////////////
// A N C H O R S
///////////////////////////////////////////////////////////////////////////////

/**
 * Anchor widget.
 *
 * @param string $url URL
 * @param string $text text to be shown on the anchor
 * @param string $importance prominence of the button
 * @param string $class CSS class
 * @param string $id ID
 * @return HTML for anchor
 */

function _anchor($url, $text, $importance, $class, $id)
{
	// FIXME: revisit importance
	$importance_class = ($importance === 'high') ? "clearos-anchor-important" : "clearos-anchor-unimportant";

	return "<a href='$url' id='$id' class='clearos-anchor $class $importance' data-role='button' data-inline='true'>$text</a>";
}

///////////////////////////////////////////////////////////////////////////////
// B U T T O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Button widget.
 *
 * @param string $name button name,
 * @param string $text text to be shown on the anchor
 * @param string $importance prominence of the button
 * @param string $class CSS class
 * @param string $id ID
 * @return HTML for button
 */

function _form_submit($name, $text, $importance, $class, $id)
{
	$importance_class = ($importance === 'high') ? "clearos-form-important" : "clearos-form-unimportant";

	// FIXME: revisit importance
	return "<input type='submit' name='$name' id='$id' value=\"$text\" class='clearos-form-submit $class $importance_class' />\n";
}

///////////////////////////////////////////////////////////////////////////////
// A N C H O R  A N D  B U T T O N  S E T S
///////////////////////////////////////////////////////////////////////////////

/**
 * Button set.
 *
 * @param array $buttons list of buttons in HTML format
 * @param string $id HTML ID
 * @return string HTML for button set
 */

function theme_button_set($buttons, $id)
{
	$button_html = '';

	// Tabs are just for clean indentation HTML output
	foreach ($buttons as $button)
		$button_html .= "\n\t\t\t" . trim($button);

	return "
		<div data-role='controlgroup' data-type='horizontal' id='$id'>$button_html
		</div>
	";

}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  V I E W
///////////////////////////////////////////////////////////////////////////////

/**
 * Text input field.
 *
 * @param string $value value of text input 
 * @param string $label label for text input field
 * @param string $input_id input ID
 * @param array $ids other optional HTML IDs
 * @return string HTML for field view
 */

function theme_field_view($value, $label, $input_id, $ids = NULL)
{
	$input_id_html = " id='" . $input_id . "'";
	$field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
	$label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";

	return "
		<div data-role='fieldcontain'$field_id_html>
			<label for='$input_id'$label_id_html>$label</label>
			<span id='$input_id'>$value</span>
		</div>
	";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  I N P U T
///////////////////////////////////////////////////////////////////////////////

/**
 * Text input field.
 *
 * @param string $name HTML name of text input element
 * @param string $value value of text input 
 * @param string $label label for text input field
 * @param string $error validation error message
 * @param string $input_id input ID
 * @param array $ids other optional HTML IDs
 * @return string HTML for text input field
 */

function theme_field_input($name, $value, $label, $error, $input_id, $ids = NULL)
{
	$input_id_html = " id='" . $input_id . "'";
	$field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
	$label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";
	$error_id_html = (is_null($ids['error'])) ? "" : " id='" . $ids['error'] . "'";

	$error_html = (empty($error)) ? "" : "<span class='FIXME_validation'$error_id_html>$error</span>";

	return "
		<div data-role='fieldcontain'$field_id_html>
			<label for='$input_id'$label_id_html>$label</label>
			<input type='text' name='$name' value='$value' id='$input_id'> $error_html
		</div>
	";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  D R O P D O W N
///////////////////////////////////////////////////////////////////////////////

/**
 * Dropdown field.
 *
 * @param string $name HTML name of text input element
 * @param string $value value of text input 
 * @param string $label label for text input field
 * @param string $input_id input ID
 * @param array $ids other optional HTML IDs
 * @return string HTML for dropdown
 */

function theme_field_dropdown($name, $selected, $label, $options, $input_id, $ids)
{
	$input_id_html = " id='" . $input_id . "'";
	$field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
	$label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";

	return "
		<div data-role='fieldcontain'$field_id_html>
			<label for='$input_id'$label_id_html>$label</label>
			" . form_dropdown($name, $options, $selected, $input_id_html) . " 
		</div>
	";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  T O G G L E
///////////////////////////////////////////////////////////////////////////////

function theme_field_toggle_enable_disable($name, $selected, $label, $options, $input_id, $ids)
{
	$input_id_html = " id='" . $input_id . "'";
	$field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
	$label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";

	return "
		<div data-role='fieldcontain'$field_id_html>
			<label for='$input_id'$label_id_html>$label</label>
			" . form_dropdown($name, $options, $selected, $input_id_html . " data-role='slider'") . " 
		</div>
	";
}

///////////////////////////////////////////////////////////////////////////////
// S U M M A R Y  T A B L E
///////////////////////////////////////////////////////////////////////////////

function theme_summary_table($title, $headers, $items)
{
	// FIXME: data-filter='true'

	// Tabs are just for clean indentation HTML output
	foreach ($items as $item)
		$item_html .= "\n\t\t\t\t<li><a href='" . $item['edit_anchor'] . "'>" . $item['title'] . "</li>";

	$html = "
		<div>
			<ul data-role='listview'>$item_html
			</ul>
		</div>
	";

	return $html;
}

///////////////////////////////////////////////////////////////////////////////
// C O N F I R M A T I O N  D I A L O G B O X
///////////////////////////////////////////////////////////////////////////////

function _dialogbox_confirm($message, $ok_anchor, $cancel_anchor)
{
// FIXME - icons and translate
    $class = 'ui-state-error';
    $iconclass = 'ui-icon-alert';

    echo "
        <div class='ui-widget'>
            <div class='ui-corner-all $class' style='margin-top: 20px; padding: 0 .7em;'>
                <p><span class='ui-icon $iconclass' style='float: left; margin-right: .3em;'></span>$message</p>
                <p>" . anchor_update($ok_anchor, 'OK') . ' ' . anchor_cancel($cancel_anchor) . "</p>
            </div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// S U M M A R Y  V I E W
///////////////////////////////////////////////////////////////////////////////

function _clearos_summary_page($links)
{
	$html = "
		<div>
			<ul data-role='listview'>
	";

	foreach ($links as $link => $title)
		$html .= "<li><a href='$link'>$title</a></li>\n";

	$html .= "
			</ul>
		</div>
	";

	return $html;
}

// vim: syntax=php ts=4
?>
