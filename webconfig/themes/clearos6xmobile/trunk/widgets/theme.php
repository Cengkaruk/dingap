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
// A N C H O R S
///////////////////////////////////////////////////////////////////////////////

function _anchor_theme($url, $id = NULL, $text, $class)
{
	$id = isset($id) ? " id='$id'" : '';

	return "<a href='$url' class='anchor $class' $id data-role='button' data-inline='true'>$text</a>";
// FIXME
//	return "<a href='$url'> $text</a>";
}

///////////////////////////////////////////////////////////////////////////////
// B U T T O N S
///////////////////////////////////////////////////////////////////////////////

function _form_submit_theme($name, $id = NULL, $text, $class)
{
	$id = (isset($id)) ? "id='$id'" : "";

	return "<input type='submit' name='$name' $id value=\"$text\" iiiclass='button $class' />";
}

///////////////////////////////////////////////////////////////////////////////
// A N C H O R  A N D  B U T T O N  S E T S
///////////////////////////////////////////////////////////////////////////////

function _button_set_open()
{
	return "<div data-role='controlgroup' data-type='horizontal'>";
}

function _button_set_close()
{
	return "</div>";
}

///////////////////////////////////////////////////////////////////////////////
// S E L E C T  B O X E S
///////////////////////////////////////////////////////////////////////////////

function _cos_form_dropdown($name, $options, $selected, $label)
{
	return  "
		<div data-role='fieldcontain'>
			<label for='$name' class='select'>$label</label>
			" . form_dropdown($name, $options, $selected) . " 
		</div>
	";
}

function _cos_form_toggle($name, $options, $selected, $label)
{
	return "
		<div data-role='fieldcontain'>
			<label for='$name'>$label</label>
			" . form_dropdown($name, $options, $selected, " data-role='slider'") . " 
		</div>
	";
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
// S U M M A R Y  T A B L E S
///////////////////////////////////////////////////////////////////////////////

function _summary_table_start($title)
{
	// FIXME: data-filter='true'
	$html = "
		<div>
			<ul data-role='listview'>
	";

	return $html;
}

function _summary_table_header($headers)
{
	// Save real estate
	return;
}

function _summary_table_items($items)
{
	$html = '';

	foreach ($items as $detail) {
		$html .= "<li>" . $detail['simple_link'] . "</li>\n";
/*
echo "<pre>";
print_r($detail);
				<li>" . anchor_custom('dhcp/edit/' . $interface, $interface, $interface . "/" . $subnetinfo['network']) . "</li>";  

*/
	}

	return $html;
}

function _summary_table_end()
{
	$html = "
			</ul>
		</div>
	";

	return $html;
}


?>
