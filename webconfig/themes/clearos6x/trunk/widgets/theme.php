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
 */

function _anchor($url, $text, $importance, $class, $id)
{
	// FIXME: add importance
	return "<a href='$url' id='$id' class='anchor $class'>$text</a>\n";
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
 */

function _form_submit($name, $text, $importance, $class, $id)
{
	// FIXME: add importance
	return "<input type='submit' name='$name' id='$id' value=\"$text\" class='button $class' />\n";
}

///////////////////////////////////////////////////////////////////////////////
// A N C H O R  A N D  B U T T O N  S E T S
///////////////////////////////////////////////////////////////////////////////

/**
 * Button widget.
 *
 * @param string $id ID
 */

function _button_set_open($id)
{
	return "<div class='buttonset' id='$id'>\n";
}

function _button_set_close()
{
	return "</div>\n";
}

///////////////////////////////////////////////////////////////////////////////
// F O R M  L A B E L S
///////////////////////////////////////////////////////////////////////////////

function _form_label($label, $name)
{
	return "<label for='$name'>$label</label>\n";
}

///////////////////////////////////////////////////////////////////////////////
// F O R M  I N P U T  B O X E S
///////////////////////////////////////////////////////////////////////////////

function _form_input($name, $value, $id)
{
	return "<input type='text' name='$name' value='$value' id='$id'>\n";
}

///////////////////////////////////////////////////////////////////////////////
// F O R M  V A L U E 
///////////////////////////////////////////////////////////////////////////////

function _form_value($value, $id)
{
	return "<span id='$id'>" . $value . "</span>\n";
}

///////////////////////////////////////////////////////////////////////////////
// V A L I D A T I O N  W A R N I N G
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// S E L E C T  B O X E S
///////////////////////////////////////////////////////////////////////////////

function _form_dropdown_start($name, $id)
{
	return "<select name='$name' id='$id'>\n";
}

function _form_dropdown_end()
{
	return "</select>\n";
}

///////////////////////////////////////////////////////////////////////////////
// T O G G L E  B O X E S
///////////////////////////////////////////////////////////////////////////////

function _form_toggle_start($name, $id)
{
	return "<select name='$name' id='$id'>\n";
}

function _form_toggle_end()
{
	return "</select>\n";
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

///////////////////////////////////////////////////////////////////////////////
// S U M M A R Y  T A B L E S
///////////////////////////////////////////////////////////////////////////////

function _summary_table_start($title)
{
	$html = "
		<div class='ui-widget clearos-summary-table-container'>
			<div class='clearos-summary-table-title ui-state-active ui-corner-top'>$title</div>
			<div class='clearos-summary-table-body ui-state-active ui-corner-bottom'>
				<table cellspacing='0' cellpadding='2' width='100%' border='0'>
	";

	return $html;
}

function _summary_table_header($headers)
{
	$html = '<tr>';

	foreach ($headers as $header)
		$html .= "<th>$header</th>";

	$html .= '</tr>';

	return $html;
}

function _summary_table_items($items)
{
	$html = '';

	foreach ($items as $details) {

		$html .= "<tr>";

		foreach ($details['details'] as $value)
			$html .= "<td>$value</td>";

		$html .= "</tr>";
	}

	return $html;
}

function _summary_table_end()
{
	$html = "
				</table>
			</div>
		</div>
	";

	return $html;
}

// vim: syntax=php ts=4
?>
