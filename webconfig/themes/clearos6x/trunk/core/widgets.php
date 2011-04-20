<?php

/**
 * Widgets handler for the ClearOS Enterprise theme.
 *
 * @category  Theme
 * @package   ClearOS_Enterprise
 * @author    ClearFoundation <developer@clearfoundation.com>
 * @copyright 2011 ClearFoundation
 * @license   http://www.gnu.org/copyleft/lgpl.html GNU General Public License version 3 or later
 * @link      http://www.clearfoundation.com/docs/developer/theming/
 */

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

///////////////////////////////////////////////////////////////////////////////
// A N C H O R S
///////////////////////////////////////////////////////////////////////////////

/**
 * Anchor widget.
 *
 * @param string $url        URL
 * @param string $text       text to be shown on the anchor
 * @param string $importance prominence of the button
 * @param string $class      CSS class
 * @param array  $options    options
 *
 * @return HTML for anchor
 */

function theme_anchor($url, $text, $importance, $class, $options)
{
    $importance_class = ($importance === 'high') ? "theme-anchor-important" : "theme-anchor-unimportant";

    $id = isset($options['id']) ? ' id=' . $options['id'] : '';

    return "<a href='$url'$id class='theme-anchor $class $importance_class'>$text</a>";
}

///////////////////////////////////////////////////////////////////////////////
// B U T T O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Button widget.
 *
 * @param string $name       button name,
 * @param string $text       text to be shown on the anchor
 * @param string $importance prominence of the button
 * @param string $class      CSS class
 * @param array  $options    options
 *
 * @return HTML for button
 */

function theme_form_submit($name, $text, $importance, $class, $options)
{
    $importance_class = ($importance === 'high') ? "theme-form-important" : "theme-form-unimportant";

    $id = isset($options['id']) ? ' id=' . $options['id'] : '';

    return "<input type='submit' name='$name'$id value=\"$text\" class='theme-form-submit $class $importance_class' />\n";
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
        <div class='theme-button-set' id='$id'>$button_html
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

function theme_field_view($label, $text, $name = NULL, $value = NULL, $input_id, $ids = NULL)
{
    $input_id_html = " id='" . $input_id . "'";
    $field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
    $label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";

    if (($name !== NULL) || ($value != NULL))
        $hidden_input = "<input type='hidden' name='$name' id='$input_id' value='$value'>";

    return "
        <div$field_id_html class='theme-fieldview'>
            <label for='$input_id'$label_id_html>$label</label>
            <span>$text</span>$hidden_input
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

    $error_html = (empty($error)) ? "" : "<span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html class='theme-field-input'>
            <label for='$input_id'$label_id_html>$label</label>
            <input type='text' name='$name' value='$value' id='$input_id'> $error_html
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  P A S S W O R D
///////////////////////////////////////////////////////////////////////////////
// TODO: merge with theme_field_input

/**
 * Password input field.
 *
 * @param string $name HTML name of text input element
 * @param string $value value of text input 
 * @param string $label label for text input field
 * @param string $error validation error message
 * @param string $input_id input ID
 * @param array $ids other optional HTML IDs
 * @return string HTML for text input field
 */

function theme_field_password($name, $value, $label, $error, $input_id, $ids = NULL)
{
    $input_id_html = " id='" . $input_id . "'";
    $field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
    $label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";
    $error_id_html = (is_null($ids['error'])) ? "" : " id='" . $ids['error'] . "'";

    $error_html = (empty($error)) ? "" : "<span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html class='theme-password'>
            <label for='$input_id'$label_id_html>$label</label>
            <input type='password' name='$name' value='$value' id='$input_id'> $error_html
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
 * @param string $error validation error message
 * @param string $input_id input ID
 * @param array $ids other optional HTML IDs
 * @return string HTML for dropdown
 */

function theme_field_dropdown($name, $selected, $label, $error, $options, $input_id, $ids)
{
    $input_id_html = " id='" . $input_id . "'";
    $field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
    $label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";
    $error_id_html = (is_null($ids['error'])) ? "" : " id='" . $ids['error'] . "'";

    $error_html = (empty($error)) ? "" : "<span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html class='theme-dropdown'>
            <label for='$input_id'$label_id_html>$label</label>
            " . form_dropdown($name, $options, $selected, $input_id_html) . " $error_html
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  T O G G L E
///////////////////////////////////////////////////////////////////////////////

function theme_field_toggle_enable_disable($name, $selected, $label, $error, $options, $input_id, $ids)
{
    $input_id_html = " id='" . $input_id . "'";
    $field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
    $label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";
    $error_id_html = (is_null($ids['error'])) ? "" : " id='" . $ids['error'] . "'";

    $error_html = (empty($error)) ? "" : "<span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html class='theme-field-toggle'>
            <label for='$input_id'$label_id_html>$label</label>
            " . form_dropdown($name, $options, $selected, $input_id_html) . " $error_html 
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  C H E C K B O X E S 
///////////////////////////////////////////////////////////////////////////////

/**
 * Checkbox field.
 *
 * @param string $name HTML name of text input element
 * @param boolean $selected selected flag
 * @param string $label label for text input field
 * @param string $input_id input ID
 * @param array $ids other optional HTML IDs
 * @return string HTML for checkbox
 */

function theme_field_checkbox($name, $selected, $label, $options, $input_id, $ids)
{
    $input_id_html = " id='" . $input_id . "'";
    $field_id_html = (is_null($ids['field'])) ? "" : " id='" . $ids['field'] . "'";
    $label_id_html = (is_null($ids['label'])) ? "" : " id='" . $ids['label'] . "'";
    $error_id_html = (is_null($ids['error'])) ? "" : " id='" . $ids['error'] . "'";
    $select_html = ($selected) ? ' checked' : '';

    return "
        <div$field_id_html class='theme-field-checkboxes'>
            <label for='$input_id'$label_id_html>$label</label>
            <input type='checkbox' name='$name' id='$input_id' $select_html>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// P R O G R E S S  B A R S
///////////////////////////////////////////////////////////////////////////////

/**
 * Display a progress bar as part of a form field.
 *
 * @param string $label   form field label
 * @param string $id      HTML ID
 * @param array  $options options
 *
 * @return string HTML for text input field
 */

function theme_field_progress_bar($label, $id, $options = array())
{
    $field_id_html = (is_null($options['field_id'])) ? "" : " id='" . $options['field_id'] . "'";
    $label_id_html = (is_null($options['label_id'])) ? "" : " id='" . $options['label_id'] . "'";

    return "
        <div$field_id_html class='theme-field-progress-bar'>
            <label for='$id'$label_id_html>$label</label>
            <div id='$id' class='theme-progress-bar'> </dive>
        </div>
    ";
}

/**
 * Display a progress bar as standalone entity.
 *
 * @param string $label   form field label
 * @param string $id      HTML ID
 * @param array  $options options
 *
 * @return string HTML output
 */

function theme_progress_bar($id, $options)
{
    return "<div id='$id' class='theme-progress-bar'> </div>";
} 


///////////////////////////////////////////////////////////////////////////////
// F O R M  H E A D E R / F O O T E R
///////////////////////////////////////////////////////////////////////////////

function theme_form_header($title, $id = NULL)
{
    $id_html = (is_null($id)) ? '' : " id=$id";

    return "<div class='theme-form-header'$id_html>\n";
}

function theme_form_footer($id = NULL)
{
    $id_html = (is_null($id)) ? '' : " id=$id";

    return "</div>\n<div class='theme-form-footer'$id_html></div>\n";
}

///////////////////////////////////////////////////////////////////////////////
// L I S T  T A B L E
///////////////////////////////////////////////////////////////////////////////

function theme_list_table($title, $anchors, $headers, $items, $legend = NULL)
{
    $columns = count($headers) + 1;

    // Header parsing
    //---------------

    // Tabs are just for clean indentation HTML output
    $header_html = '';

    foreach ($headers as $header)
        $header_html .= "\n\t\t" . trim("<th>$header</th>");

    // No title in the action header
    $header_html .= "\n\t\t" . trim("<th>&nbsp; </th>");

    // Add button
    //-----------

    $add_html = (empty($anchors)) ? '&nbsp; ' : button_set($anchors);

    // Legend parsing
    //---------------

    // FIXME
    $legend_html = '';

    if ($legend)    
        $legend_html = "\n   <tfoot><tr><td colspan='$columns' class='theme-list-table-legend'>$legend</td></tr></tfoot>";

    // Item parsing
    //-------------

    // FIXME: clean up enabled/disabled toggle widget
    $options = array(
        '0' => lang('base_disabled'),
        '1' => lang('base_enabled')
    );

    $item_html = '';

    foreach ($items as $item) {
        $item_html .= "\t<tr>\n";

        foreach ($item['details'] as $value)
            $item_html .= "\t\t" . "<td>$value</td>\n";

// pete
// FIXME: experimenting with checkboxes
        $select_html = ($item['state']) ? 'checked' : ''; 
        $item_html .= "\t\t<td><input type='checkbox' name='" . $item['name'] . " id='$input_id' $select_html>";
// FIXME: or use toggle switch?
//        $item_html .= "\t\t<td>" . form_dropdown($item['name'], $options, $item['state']) ."</td>";
        $item_html .= "\t</tr>\n";
    }

    // List table
    //-----------

    return "

<div class='theme-list-table-container ui-widget theme-list-table'>
  <div class='theme-list-table-header ui-state-active ui-corner-top'>
    <div class='theme-list-table-title'>$title</div>
    <div class='theme-list-table-action'>$add_html</div>
  </div>
  <table cellspacing='0' cellpadding='2' width='100%' border='0' class='theme-list-table display'>
   <thead>
    <tr>$header_html
    </tr>
   </thead>
   <tbody>
$item_html
   </tbody>$legend_html
  </table>
</div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// D I A L O G  B O X E S
///////////////////////////////////////////////////////////////////////////////

function theme_dialogbox_confirm_delete($message, $items, $ok_anchor, $cancel_anchor)
{
    $items_html = '';

    foreach ($items as $item)
        $items_html = '<li>' . $item . '</li>';

    return "
        <div class='ui-widget'>
            <div class='ui-corner-all theme-confirmation-dialogbox ui-state-error' style='margin-top: 20px; padding: 0 .7em;'>
                <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>$message</p>
                <ul>
                    $items_html
                </ul>
                <p>" . anchor_ok($ok_anchor, 'high') . ' ' . anchor_cancel($cancel_anchor, 'low') . "</p>
            </div>
        </div>
    ";
}

function theme_dialogbox_confirm($message, $ok_anchor, $cancel_anchor)
{
// FIXME - work in progress
// FIXME - icons and translate
    $class = 'ui-state-error';
    $iconclass = 'ui-icon-alert';

    return "
        <div class='ui-widget'>
            <div class='ui-corner-all theme-confirmation-dialogbox $class' style='margin-top: 20px; padding: 0 .7em;'>
                <p><span class='ui-icon $iconclass' style='float: left; margin-right: .3em;'></span>$message</p>
                <p>" . anchor_ok($ok_anchor, 'high') . ' ' . anchor_cancel($cancel_anchor, 'low') . "</p>
            </div>
        </div>
    ";
}

function theme_dialogbox_info($message)
{
// FIXME - work in progress
// FIXME - icons and translate
    $class = 'ui-state-highlight';

    return "
        <div class='ui-widget'>
            <div class='ui-corner-all theme-dialogbox-info $class'>
               $message
            </div>
        </div>
    ";
}

function theme_dialog_warning($message)
{
// FIXME - work in progress
// FIXME - icons and translate
    $class = 'ui-state-error';
    $iconclass = 'ui-icon-alert';

    return "
        <div class='ui-widget' style='margin: 10px'>
            <div class='ui-corner-all theme-dialogbox-info $class'>
               <span class='ui-icon $iconclass' style='float: left; margin-right: .3em;'></span>$message
            </div>
        </div>
    ";
}


///////////////////////////////////////////////////////////////////////////////
// S U M M A R Y  T A B L E
///////////////////////////////////////////////////////////////////////////////

function theme_summary_table($title, $anchors, $headers, $items, $legend = NULL)
{
    $columns = count($headers) + 1;

    // Header parsing
    //---------------

    // Tabs are just for clean indentation HTML output
    $header_html = '';

    foreach ($headers as $header)
        $header_html .= "\n\t\t" . trim("<th>$header</th>");

    // No title in the action header
    $header_html .= "\n\t\t" . trim("<th>&nbsp; </th>");

    // Add button
    //-----------

    $add_html = (empty($anchors)) ? '&nbsp; ' : button_set($anchors);

    // Legend parsing
    //---------------

    // FIXME
    $legend_html = '';

    if ($legend)    
        $legend_html = "\n   <tfoot><tr><td colspan='$columns' class='theme-summary-table-legend'>$legend</td></tr></tfoot>";

    // Item parsing
    //-------------

    $item_html = '';

    foreach ($items as $item) {
        $item_html .= "\t<tr>\n";

        foreach ($item['details'] as $value)
            $item_html .= "\t\t" . "<td>$value</td>\n";

        $item_html .= "\t\t<td>" . $item['anchors'] . "</td>";
        $item_html .= "\t</tr>\n";
    }

    // Summary table
    //--------------
    return "

<div class='theme-summary-table-container ui-widget'>
  <div class='theme-summary-table-header ui-state-active ui-corner-top'>
    <div class='theme-summary-table-title'>$title</div>
    <div class='theme-summary-table-action'>$add_html</div>
  </div>
  <table cellspacing='0' cellpadding='2' width='100%' border='0' class='theme-summary-table display'>
   <thead>
    <tr>$header_html
    </tr>
   </thead>
   <tbody>
$item_html
   </tbody>$legend_html
  </table>
</div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// I N F O  B O X
///////////////////////////////////////////////////////////////////////////////

/**
 * Displays simple info boxes.
 *
 * There are four types of info boxes:
 * - critical (not good... not good at all)
 * - warning  (bad, but we can cope)
 * - highlight (here's something you should know...)
 */

function theme_infobox($type, $title, $message)
{
    if ($type === 'critical') {
        $class = 'ui-state-error';
        $iconclass = 'ui-icon-alert';
    } else if ($type === 'warning') {
        $class = 'ui-state-highlight';
        $iconclass = 'ui-icon-info';
    } else if ($type === 'highlight') {
        $class = 'ui-state-default';
        $iconclass = 'ui-icon-info';
    }

    return "
        <div class='ui-widget'>
            <div class='ui-corner-all $class' style='margin-top: 20px; padding: 0 .7em;'>
                <h2>$title</h2>
                <span class='ui-icon $iconclass' style='float: left; margin-right: .3em;'>&nbsp; </span>
                $message
            </div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// H E L P  B O X
///////////////////////////////////////////////////////////////////////////////

/**
 * Displays a help box.
 *
 * The available data for display:
 * - $name - app name
 * - $category - category
 * - $subcategory - subcategory
 * - $description - description
 * - $user_guide_url - URL to the User Guide
 * - $support_url - URL to support
 */

function theme_help_box($data)
{
    // FIXME: translate
	$iconclass = 'ui-icon-info';

    return theme_dialogbox_info("
            <h3>Help Box</h3>
			<span class='ui-icon $iconclass' style='float: right; margin-right: 5px;'>&nbsp;</span>$message
            <p>" . $data['category'] . " &gt; " . $data['subcategory'] . " &gt; " . $data['name'] . "</p>
            <p>" . $data['description'] . "</p>
            $tooltip
            <ul>
                <li><a target='_blank' href='" . $data['user_guide_url'] . "'>User Guide</a></li>
                <li><a target='_blank' href='" . $data['support_url'] . "'>ClearCenter Support</a></li>
            </ul>
    ");
}

///////////////////////////////////////////////////////////////////////////////
// A P P  S U M M A R Y  B O X
///////////////////////////////////////////////////////////////////////////////

/**
 * Displays a summary box.
 *
 * The available data for display:
 * - $name - app name
 * - $tooltip -  tooltip
 * - $version - version number (e.g. 4.7)
 * - $release - release number (e.g. 31.1, so version-release is 4.7-31.1)
 * - $vendor - vendor
 * 
 * If this application is included in the Marketplace, the following
 * information is also available.
 *
 * - $subscription_expiration - subscription expiration (if applicable)
 * - $install_status - install status ("up-to-date" or "update available")
 * - $marketplace_chart - a relevant chart object
 */

function theme_summary_box($data)
{
    $tooltip = empty($data['tooltip']) ? '' : '<p><b>Tooltip -- </b>' . $data['tooltip'] . '</p>';

    // FIXME: translate
    $html = theme_dialogbox_info("
        <h3>" . $data['name'] . "</h3>
        <table>
            <tr>
                <td><b>Version</b></td>
                <td>" . $data['version'] . '-' . $data['release'] . "</td>
            </tr>
            <tr>
                <td><b>Status</b></td>
                <td>" . $data['install_status'] . "</td>
            </tr>
            <tr>
                <td><b>Subscription</b></td>
                <td>" . $data['subscription_expiration'] . "</td>
            </tr>
        </table>
		$tooltip

        <!-- Just an example chart -->

        <div id='theme-chart-info-box' style='height:200px; width:200px;'></div>

        <script type='text/javascript'>
            $.jqplot.config.enablePlugins = true;
            $.jqplot('theme-chart-info-box', [[[1, 2],[3,5.12],[5,13.1],[7,33.6],[9,85.9],[11,219.9]]]);
        </script>
    ");

    return $html;
}

///////////////////////////////////////////////////////////////////////////////
// C O N T R O L  P A N E L
///////////////////////////////////////////////////////////////////////////////

// Note: this theme does not use the "control panel" view so this function
// is here just for sanity checking during development!

function theme_control_panel($form_data)
{
    $items = '';


    foreach ($form_data as $form => $details)
        $items .= "<li><a rel='external' href='$form'>" . $details['title'] . "</a></li>\n";

    return "
        <div class='theme-control-panel'>
            <ul>
                $items
            </ul>
        </div>
    ";
}
