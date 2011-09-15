<?php

/**
 * Javascript helper for Nework Visualiser.
 *
 * @category   Apps
 * @package    Intrusion_Detection_Updates
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearcenter.com/support/documentation/clearos/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

clearos_load_language('base');

header('Content-Type: application/x-javascript');

echo "

$(document).ready(function() {
  get_traffic_data();
});

function get_traffic_data(realtime) {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: '/app/network_visualiser/ajax/get_traffic_data',
        success: function(data) {
            if (data.code != 0) {
                // Error will get displayed in sidebar
                clearos_alert('errmsg', data.errmsg);
                return;
            }
            for (var index = 0 ; index < data.logs.length; index++)
                report.fnAddData([
                  data.logs[index].code,
                  data.logs[index].description,
                  $.datepicker.formatDate('MM d, yy', new Date(data.logs[index].timestamp))
                ]);
            report.fnAdjustColumnSizing();
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined)
                clearos_alert('errmsg', xhr.responseText.toString());
        }
    });
}
function clearos_alert(id, message) {
  $('#theme-page-container').append('<div id=\"' + id + '\" title=\"" . lang('base_warning') . "\">' +
      '<p>' +
        '<span class=\"ui-icon ui-icon-alert\" style=\"float:left; margin:0 7px 50px 0;\"></span>' + message +
      '</p>' +
    '</div>'
  );
  $('#' + id).dialog({
    modal: true,
    buttons: {
      '" . lang('base_close') . "': function() {
        $(this).dialog('close');
      }
    }
  });
}

";

// vim: syntax=php ts=4
