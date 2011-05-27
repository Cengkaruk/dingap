<?php

/**
 * Widgets for the ClearOS Enterprise mobile theme.
 *
 * @category  Theme
 * @package   ClearOS_Enterprise_Mobile
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

    return "<a rel='external' href='$url' id='$id' class='clearos-anchor $class $importance' data-role='button' data-inline='true'>$text</a>";
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

    return "<input type='submit' name='$name' id='$id' value=\"$text\" class='clearos-form-submit $class $importance_class' />\n";
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

    foreach ($buttons as $button)
        $button_html .= "\n" . trim($button);

    return "
        <div class='theme-button-group' data-role='controlgroup' data-type='horizontal' id='$id'>$button_html
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
    $field_id_html = (is_null($options['field_id'])) ? '' : " id='" . $options['field'] . "'";
    $label_id_html = (is_null($options['label_id'])) ? '' : " id='" . $options['label'] . "'";

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
    return _theme_field_input_password($name, $value, $label, $error, $input_id, $options = NULL, 'text');
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

    $error_html = (empty($error)) ? "" : "<span class='theme-validation-error'$error_id_html>$error</span>";

    return "
        <div$field_id_html>
            <label for='$input_id'$label_id_html>$label</label>
            <input type='text' name='$name' value='$value' id='$input_id'> $error_html
        </div>
    ";
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
    return _theme_field_input_password($name, $value, $label, $error, $input_id, $options = NULL, 'password');
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
        <div$field_id_html>
            <label for='$input_id'$label_id_html>$label</label>
            " . form_dropdown($name, $options, $selected, $input_id_html) . " $error_html
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
        <div$field_id_html>
            <label for='$input_id'$label_id_html>$label</label>
            " . form_dropdown($name, $options, $selected, $input_id_html . " data-role='slider'") . " $error_html
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
        <div$field_id_html>
            <label for='$input_id'$label_id_html>$label</label>
            <input type='checkbox' name='$name' id='$input_id' $select_html>
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
        <div$field_id_html>
            <label for='$input_id'$label_id_html>$label</label>
            <input type='range' name='slider' id='$input_id' value='0' min='0' max='100'  />
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
    return "<div id='$id' class='theme-progress-bar'> FIXME </div>";
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
    return "";
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
    return "";
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
    echo "FIXME: not supported";
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

    // Anchors
    //--------

    //  FIXME $add_html = (empty($anchors)) ? '&nbsp; ' : button_set($anchors);

    // Item parsing
    //-------------

    $item_html = '';

    foreach ($items as $item)
        $item_html .= "\n\t\t\t\t<li><a rel='external' href='" . $item['action'] . "'>" . $item['title'] . "</a></li>";

    // Summary table
    //--------------

    $search = (count($items) > 10) ? " data-filter='true'" : "";

    return "
        <div>
            <ul data-role='listview'$search>$item_html
            </ul>
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

    // Add button
    //-----------

    //  FIXME $add_html = (empty($anchors)) ? '&nbsp; ' : button_set($anchors);

    // Item parsing
    //-------------

    $item_html = '';

    foreach ($items as $item)
        $item_html .= "\n\t\t\t\t<li><a rel='external' href='" . $item['action'] . "'>" . $item['title'] . "</a></li>";

    // Summary table
    //--------------

    $search = (count($items) > 10) ? " data-filter='true'" : "";

    return "
        <div>
            <ul data-role='listview'$search>$item_html
            </ul>
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
 * - critical (not good... not good at all)
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

function theme_infobox($type, $title, $message)
{
    echo "FIXME: $title / $message";
}

///////////////////////////////////////////////////////////////////////////////
// C O N F I R M  D E L E T E  B O X
///////////////////////////////////////////////////////////////////////////////

function theme_confirm_delete($confirm_uri, $cancel_uri, $items, $message, $options)
{
    echo "FIXME: $message";
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
// FIXME: implement -- this is just copied from clearos6x
return;
    // FIXME: translate
    $tooltip = empty($data['tooltip']) ? '' : '<p><b>Tooltip -- </b>' . $data['tooltip'] . '</p>';

    return theme_dialogbox_info("
            <h3>Help Box</h3>
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
// FIXME: implement -- this is just copied from clearos6x
return;
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
// D I A L O G  B O X E S
///////////////////////////////////////////////////////////////////////////////

function theme_dialogbox_confirm($message, $ok_anchor, $cancel_anchor)
{
// FIXME - icons and translate
    $class = 'ui-state-error';
    $iconclass = 'ui-icon-alert';

    echo "
        <div class='ui-widget'>
            <div class='ui-corner-all $class' style='margin-top: 20px; padding: 0 .7em;'>
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
    $iconclass = 'ui-icon-info';

    return "
        <div class='ui-widget' style='margin: 10px'>
            <div class='ui-corner-all theme-dialogbox-info $class'>
               <span class='ui-icon $iconclass' style='float: left; margin-right: .3em;'></span>$message
            </div>
        </div>
    ";
}

///////////////////////////////////////////////////////////////////////////////
// C O N T R O L  P A N E L  S T Y L E  S U M M A R Y
///////////////////////////////////////////////////////////////////////////////

function theme_control_panel($forms)
{
    $items = '';

    foreach ($forms as $form => $details)
        $items .= "<li><a rel='external' href='/app/$form'>" . $details['title'] . "</a></li>\n";

    return "
        <div>
            <ul data-role='listview'>
                $items
            </ul>
        </div>
    ";
}
