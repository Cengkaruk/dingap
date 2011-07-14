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
 * Supported options:
 * - id 
 *
 * Classes:
 * - theme-anchor-add
 * - theme-anchor-cancel
 * - theme-anchor-delete
 * - theme-anchor-edit
 * - theme-anchor-next
 * - theme-anchor-ok
 * - theme-anchor-previous
 * - theme-anchor-view
 * - theme-anchor-custom (button with custom text)
 * - theme-anchor-dialog (button that pops up a javascript dialog box)
 * - theme-anchor-javascript (button that does some other javascript action)
 * 
 * @param string $url        URL
 * @param string $text       anchor text
 * @param string $importance importance of the button ('high' or 'low')
 * @param string $class      CSS class
 * @param array  $options    options
 *
 * @return HTML for anchor
 */

function theme_anchor($url, $text, $importance, $class, $options)
{
    $importance_class = ($importance === 'high') ? 'theme-anchor-important' : 'theme-anchor-unimportant';

    $id = isset($options['id']) ? ' id=' . $options['id'] : '';
    $text = htmlspecialchars($text, ENT_QUOTES);

    return "<a href='$url'$id class='theme-anchor $class $importance_class'>$text</a>";
}

function theme_anchor_dialog($url, $text, $importance, $class, $options)
{
    $importance_class = ($importance === 'high') ? 'theme-anchor-important' : 'theme-anchor-unimportant';

    $id = isset($options['id']) ? ' id=' . $options['id'] : '';
    $text = htmlspecialchars($text, ENT_QUOTES);

    return "<a href='$url'$id class='theme-anchor $class $importance_class'>$text</a>
<script type='text/javascript'>
  $(document).ready(function() {
  $('#" . $options['id'] . "_message').dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    closeOnEscape: true,
    width: 400,
    open: function(event, ui) {
    },
    close: function(event, ui) {
    }
  });
  });
  $('a#" . $options['id'] . "').click(function (e) {
    e.preventDefault();
    $('#" . $options['id'] . "_message').dialog('open');
  });

</script>
";
}

///////////////////////////////////////////////////////////////////////////////
// B U T T O N S
///////////////////////////////////////////////////////////////////////////////

/**
 * Button widget.
 *
 * Supported options:
 * - id 
 *
 * Classes:
 * - theme-form-add
 * - theme-form-delete
 * - theme-form-disable
 * - theme-form-next
 * - theme-form-ok
 * - theme-form-previous
 * - theme-form-update
 * - theme-form-custom (button with custom text)
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
    $importance_class = ($importance === 'high') ? 'theme-form-important' : 'theme-form-unimportant';

    $id = isset($options['id']) ? ' id=' . $options['id'] : '';
    $text = htmlspecialchars($text, ENT_QUOTES);

    return "<div style='height: 22px; display: inline;'><input type='submit' name='$name'$id value='$text' class='theme-form-submit ui-corner-all $class $importance_class' /><span class='theme-form-input'>&nbsp; </span></div>\n";
}

///////////////////////////////////////////////////////////////////////////////
// A N C H O R  A N D  B U T T O N  S E T S
///////////////////////////////////////////////////////////////////////////////

/**
 * Button set.
 *
 * Supported options:
 * - id 
 *
 * @param array $buttons list of buttons in HTML format
 * @param array $options options
 *
 * @return string HTML for button set
 */

function theme_button_set($buttons, $options)
{
    $id = isset($options['id']) ? ' id=' . $options['id'] : '';

    $button_html = '';

    $button_total = count($buttons);
    $count = 0;

    foreach ($buttons as $button) {
        $implant_first = '';
        $implant_middle = '';
        $implant_last = '';
        $count++;

        if ($count === 1)
            $implant_first = 'theme-button-set-first ';

        if ($count === $button_total)
            $implant_last = 'theme-button-set-last ';

        if (($count !== 1) && ($count !== $button_total))
            $implant_middle = 'theme-button-set-middle ';

        // KLUDGE: implant button set order
        $button = preg_replace("/class='/", "class='$implant_first$implant_middle$implant_last", $button);
        $button_html .= "\n" . trim($button);
    }

    return "
        <div class='theme-button-set'$id>$button_html
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  V I E W
///////////////////////////////////////////////////////////////////////////////

/**
 * Text input field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 *
 * @param string $label    label for text input field
 * @param string $text     text shown
 * @param string $name     name of text input element
 * @param string $value    value of text input 
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML for field view
 */

function theme_field_view($label, $text, $name = NULL, $value = NULL, $input_id, $options = NULL)
{
    $field_id_html = (is_null($options['field_id'])) ? '' : " id='" . $options['field_id'] . "'";
    $label_id_html = (is_null($options['label_id'])) ? '' : " id='" . $options['label_id'] . "'";
    $hide_field = (is_null($options['hide_field'])) ? '' : " theme-hidden";

    if (($name !== NULL) || ($value != NULL))
        $hidden_input = "<input type='hidden' name='$name' value='$value'>";

    return "
        <div$field_id_html class='theme-fieldview" . $hide_field . "'>
            <div class='left-field-content'><label for='$input_id'$label_id_html>$label</label></div>
            <div class='right-field-content'><span id='$input_id'>$text</span>$hidden_input</div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  I N P U T
///////////////////////////////////////////////////////////////////////////////

/**
 * Text input field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 * - error_id 
 *
 * @param string $name     name of text input element
 * @param string $value    value of text input 
 * @param string $label    label for text input field
 * @param string $error    validation error message
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML
 */

function theme_field_input($name, $value, $label, $error, $input_id, $options = NULL)
{
    return _theme_field_input_password($name, $value, $label, $error, $input_id, $options, 'text');
}

/**
 * Common text/password input field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 * - error_id 
 *
 * @access private
 * @param string $name     name of text input element
 * @param string $value    value of text input 
 * @param string $label    label for text input field
 * @param string $error    validation error message
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML
 */

function _theme_field_input_password($name, $value, $label, $error, $input_id, $options = NULL, $type)
{
    $field_id_html = (is_null($options['field_id'])) ? '' : " id='" . $options['field_id'] . "'";
    $label_id_html = (is_null($options['label_id'])) ? '' : " id='" . $options['label_id'] . "'";
    $error_id_html = (is_null($options['error_id'])) ? '' : " id='" . $options['error_id'] . "'";
    $hide_field = (is_null($options['hide_field'])) ? '' : " theme-hidden";

    $error_html = (empty($error)) ? "" : "<br/><span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html class='theme-field-$type" . $hide_field . "'>
            <div class='left-field-content'><label for='$input_id'$label_id_html>$label</label></div>
           <div class='right-field-content input-box'> <input type='$type' name='$name' value='$value' id='$input_id'> $error_html</div>
        </div>
    ";
}

/**
 * File upload input field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 * - error_id 
 *
 * @param string $name     name of text input element
 * @param string $value    value of text input 
 * @param string $label    label for text input field
 * @param string $error    validation error message
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML
 */

function theme_field_file($name, $value, $label, $error, $input_id, $options = NULL)
{
    return _theme_field_input_password($name, $value, $label, $error, $input_id, $options, 'file');
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  P A S S W O R D
///////////////////////////////////////////////////////////////////////////////

/**
 * Password input field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 * - error_id 
 *
 * @param string $name     name of pasword input element
 * @param string $value    value of pasword input 
 * @param string $label    label for pasword input field
 * @param string $error    validation error message
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML
 */

function theme_field_password($name, $value, $label, $error, $input_id, $options = NULL)
{
    return _theme_field_input_password($name, $value, $label, $error, $input_id, $options, 'password');
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  D R O P D O W N
///////////////////////////////////////////////////////////////////////////////

/**
 * Dropdown field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 * - error_id 
 *
 * @param string $name     name of dropdown element
 * @param string $value    value of dropdown 
 * @param string $label    label for dropdown field
 * @param string $error    validation error message
 * @param array  $values    hash list of values for dropdown
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML
 */

function theme_field_dropdown($name, $value, $label, $error, $values, $input_id, $options)
{
    $input_id_html = " id='" . $input_id . "'";
    $field_id_html = (is_null($options['field_id'])) ? "" : " id='" . $options['field_id'] . "'";
    $label_id_html = (is_null($options['label_id'])) ? "" : " id='" . $options['label_id'] . "'";
    $error_id_html = (is_null($options['error_id'])) ? "" : " id='" . $options['error_id'] . "'";

    $error_html = (empty($error)) ? "" : "<span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html class='theme-dropdown'>
            <div class='left-field-content'><label for='$input_id'$label_id_html>$label</label></div>
            <div class='right-field-content'>" . form_dropdown($name, $values, $value, $input_id_html) . " $error_html</div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  T O G G L E
///////////////////////////////////////////////////////////////////////////////

/**
 * Enable/disable toggle field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 * - error_id 
 *
 * @param string $name     name of toggle input element
 * @param string $value    value of toggle input 
 * @param string $label    label for toggle input field
 * @param string $error    validation error message
 * @param array  $values    hash list of values for dropdown
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML
 */

function theme_field_toggle_enable_disable($name, $selected, $label, $error, $values, $input_id, $options)
{
    $input_id_html = " id='" . $input_id . "'";
    $field_id_html = (is_null($options['field_id'])) ? "" : " id='" . $options['field_id'] . "'";
    $label_id_html = (is_null($options['label_id'])) ? "" : " id='" . $options['label_id'] . "'";
    $error_id_html = (is_null($options['error_id'])) ? "" : " id='" . $options['error_id'] . "'";

    $error_html = (empty($error)) ? "" : "<span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html class='theme-field-toggle'>
            <div class='left-field-content'><label for='$input_id'$label_id_html>$label</label></div>
            <div class='right-field-content'>" . form_dropdown($name, $values, $selected, $input_id_html) . " $error_html </div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// F I E L D  C H E C K B O X E S 
///////////////////////////////////////////////////////////////////////////////

/**
 * Checkbox field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
 * - error_id 
 *
 * @param string $name     name of checkbox element
 * @param string $value    value of checkbox 
 * @param string $label    label for checkbox field
 * @param string $error    validation error message
 * @param array  $values    hash list of values for dropdown
 * @param string $input_id input ID
 * @param array  $options  options
 *
 * @return string HTML
 */

function theme_field_checkbox($name, $value, $label, $options, $input_id, $options)
{
    $field_id_html = (is_null($options['field_id'])) ? "" : " id='" . $options['field_id'] . "'";
    $label_id_html = (is_null($options['label_id'])) ? "" : " id='" . $options['label_id'] . "'";
    $error_id_html = (is_null($options['error_id'])) ? "" : " id='" . $options['error_id'] . "'";

    $select_html = ($value) ? ' checked' : '';

    return "
        <div$field_id_html class='theme-field-checkboxes'>
            <div class='left-field-content'<label for='$input_id'$label_id_html>$label</label></div>
          <div class='right-field-content check'>  <input type='checkbox' name='$name' id='$input_id' $select_html></div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// R A D I O  S E T S
///////////////////////////////////////////////////////////////////////////////

// FIXME

///////////////////////////////////////////////////////////////////////////////
// P R O G R E S S  B A R S
///////////////////////////////////////////////////////////////////////////////

/**
 * Display a progress bar as part of a form field.
 *
 * Supported options:
 * - field_id 
 * - label_id 
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
            <div id='$id' class='theme-progress-bar'> </div>
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

/**
 * Form header.
 *
 * Supported options:
 * - id 
 *
 * @param string $title form title
 * @param array  $options options
 *
 * @return string HTML
 */

function theme_form_header($title, $options)
{
    $id_html = (is_null($options['id'])) ? '' : " id='" . $options['id'] . "'";

    // return "<div class='theme-form-header'$id_html>\n";

    // Pete update - hacked in form title in theme
    return "<div class='theme-form-header'$id_html><h3 style='position: relative;
margin-top: 0px;
margin-bottom: 0px;
top: 10px;
left: 14px;
color: #98BB60;
font-weight: normal;
font-size: 14px;
width: 100%;'>$title</h3><div class='theme-form-wrapper'>";
}

/**
 * Form footer.
 *
 * Supported options:
 * - id 
 *
 * @param array $options options
 *
 * @return string HTML
 */

function theme_form_footer($options)
{
    $id_html = (is_null($options['id'])) ? '' : " id='" . $options['id'] . "'";

    return "</div></div><div class='theme-form-footer'$id_html></div>\n";
}

///////////////////////////////////////////////////////////////////////////////
// T A B  V I E W
///////////////////////////////////////////////////////////////////////////////

/**
 * Tabular content.
 *
 * @param array $tabs tabs
 *
 * @return string HTML
 */

function theme_tab($tabs)
{
    $html = "<div id='tabs' class='ui-tabs ui-widget ui-widget-content ui-corner-all'>\n
<div>\n
<ul class='ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>\n
    ";

    $tab_content = "";
    foreach ($tabs as $key => $tab) {
        $html .= "<li class='ui-state-default ui-corner-top'>
<a href='#tabs-" . $key . "'>" . $tab['title'] . "</a></li>\n";
        $tab_content .= "<div id='tabs-" . $key .
"' class='clearos_tabs ui-tabs ui-widget ui-widget-content ui-corner-all'>" . $tab['content'] . "</div>";
    }
    $html .= "</ul>\n";
    $html .= $tab_content;
    $html .= "</div>\n";
    $html .= "</div>\n";
    $html .= "<script type='text/javascript'>
$(function(){
$('#tabs').tabs({
selected: 0
});
});
</script>";

    return $html;
}

///////////////////////////////////////////////////////////////////////////////
// L O A D I N G  I C O N
///////////////////////////////////////////////////////////////////////////////

/**
 * Loading/wait state in progress.
 *
 * @return string HTML
 */

function theme_loading()
{
    return "<div class='theme-loading'></div>\n";
}

///////////////////////////////////////////////////////////////////////////////
// S U M M A R Y  T A B L E
///////////////////////////////////////////////////////////////////////////////

/**
 * Summary table.
 *
 * @param string $title   table title
 * @param array  $anchors list anchors
 * @param array  $headers headers
 * @param array  $items   items
 * @param array  $options options
 *
 * @return string HTML
 */

function theme_summary_table($title, $anchors, $headers, $items, $options = NULL)
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

    // Anchors
    //--------

    $add_html = (empty($anchors)) ? '&nbsp; ' : button_set($anchors);

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

    // Size
    //-----

    $size_class = (count($items) > 10) ? 'theme-summary-table-large' : 'theme-summary-table-small';

    // Summary table
    //--------------

    return "

<div class='theme-summary-table-container ui-widget'>
  <div class='theme-summary-table-header ui-state-active ui-corner-top'>
    <div class='theme-summary-table-title'>$title</div>
    <div class='theme-summary-table-action'>$add_html</div>
  </div>
  <table cellspacing='0' cellpadding='2' width='100%' border='0' class='theme-summary-table $size_class display'>
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
// L I S T  T A B L E
///////////////////////////////////////////////////////////////////////////////

/**
 * List table.
 *
 * @param string $title   table title
 * @param array  $anchors list anchors
 * @param array  $headers headers
 * @param array  $items   items
 * @param array  $options options
 *
 * @return string HTML
 */

function theme_list_table($title, $anchors, $headers, $items, $options = NULL)
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

    // Item parsing
    //-------------

    // FIXME: clean up enabled/disabled toggle widget
    $dropdown_options = array(
        '0' => lang('base_disabled'),
        '1' => lang('base_enabled')
    );

    $item_html = '';

    foreach ($items as $item) {
        $item_html .= "\t<tr>\n";

        foreach ($item['details'] as $value)
            $item_html .= "\t\t" . "<td>$value</td>\n";

// FIXME: experimenting with checkboxes
        $select_html = ($item['state']) ? 'checked' : ''; 
        $item_html .= "\t\t<td><input type='checkbox' name='" . $item['name'] . "' $select_html></td>\n";
//        $item_html .= "\t\t<td>" . form_dropdown($item['name'], $dropdown_options, $item['state']) ."</td>";
        $item_html .= "\t</tr>\n";
    }

    // List table
    //-----------

    return "

<div class='theme-list-table-container ui-widget'>
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
   </tbody>
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
            <div class=' theme-confirmation-dialogbox ui-state-error' style='margin-top: 20px; padding: 0 .7em;'>
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
// I N F O  B O X
///////////////////////////////////////////////////////////////////////////////

/**
 * Displays a standard infobox.
 *
 * Infobox types:
 * - warning  (bad, but we can cope)
 * - highlight (here's something you should know...)
 *
 * @param string $type    type of infobox
 * @param string $title   table title
 * @param string $message message
 * @param array  $options options
 *
 * @return string HTML
 */

function theme_infobox($type, $title, $message, $options = NULL)
{
    if ($type === 'warning') {
        $class = 'ui-state-error';
        $iconclass = 'ui-icon-alert';
    } else if ($type === 'highlight') {
        $class = 'ui-state-default';
        $iconclass = 'ui-icon-info';
    }

    $id = isset($options['id']) ? ' id=' . $options['id'] : '';

    return "
        <div class='ui-widget' $id>
            <div class='ui-corner-all info-regular $class' style=' '>
                <h2>$title</h2>
                
                <div class='info-regular-text'>$message</div>
            </div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// C O N F I R M  A C T I O N  B O X
///////////////////////////////////////////////////////////////////////////////

function theme_confirm($confirm_uri, $cancel_uri, $message, $options)
{
    $class = 'ui-state-highlight';
    $iconclass = 'ui-icon-info';

    return "
        <div class='ui-widget'>
            <div class='ui-corner-all $class' style='margin-top: 20px; padding: 0 .7em;'>
                <span class='ui-icon $iconclass' style='float: left; margin-right: .3em;'>&nbsp; </span>
                $message
                <div>" . anchor_ok($confirm_uri) . anchor_cancel($cancel_uri) . "</div>
            </div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// C O N F I R M  D E L E T E  B O X
///////////////////////////////////////////////////////////////////////////////

function theme_confirm_delete($confirm_uri, $cancel_uri, $items, $message, $options)
{
    $class = 'ui-state-highlight';
    $iconclass = 'ui-icon-info';

    foreach ($items as $item)
        $items_html = "<li>$item</li>\n";

    $items_html = "<ul>\n$items_html\n</ul>\n";

    return "
        <div class='ui-widget'>
            <div class='ui-corner-all $class' style='margin-top: 20px; padding: 0 .7em;'>
                <span class='ui-icon $iconclass' style='float: left; margin-right: .3em;'>&nbsp; </span>
                $message
                <div>$items_html</div>
                <div>" . anchor_ok($confirm_uri) . anchor_cancel($cancel_uri) . "</div>
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
            <p class='breadcrumb'>" . $data['category'] . " &gt; " . $data['subcategory'] . " &gt; " . $data['name'] . "</p>
            <div class='help-box-content'>
            <div class='theme-app-icon'><img src='" . $data['icon_path'] . "' alt=''></div>
            <p class='help-description'>" . $data['description'] . "</p>
            <div class='help-assets'>
              <div class='help-assets-style'>
                <div class='help-assets-icons user-guide'><a target='_blank' href='" . $data['user_guide_url'] . "'>User Guide</a></div>
                <div class='help-assets-icons support'><a target='_blank' href='" . $data['support_url'] . "'>ClearCARE Support</a></div>
              </div>
            </div>
            </div>
  
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

    if (empty($data['tooltip'])) {
        $tooltip = '';
    } else {
        $tooltip = "
            <tr>
                <td colspan='2'><b>Tooltip</b> - " . $data['tooltip'] . "</td>
            </tr>
        ";
    }

    // FIXME: translate
    $html = theme_dialogbox_info("
        <h3>" . $data['name'] . "</h3>
        <table>
            <tr>
                <td><b>Version</b></td>
                <td>" . $data['version'] . '-' . $data['release'] . "</td>
            </tr>
            <tr>
                <td><b>Vendor</b></td>
                <td>" . $data['vendor'] . "</td>
            </tr>
            <tr>
                <td><b>End-of-life</b></td>
                <td>" . $data['subscription_expiration'] . "</td>
            </tr>
            $tooltip
        </table>
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
