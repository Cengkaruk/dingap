<?php

/**
 * Javascript helper for Nework Visualiser.
 *
 * @category   Apps
 * @package    Network_Visualiser
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

var timestamp = 0;
var display = 'totalbps';

$(document).ready(function() {
    $('#report tr:last:last td').html('<div class=\"theme-loading-normal\"></div>');
    if ($('#report_display').val() != undefined) {
        display = $('#report_display').val();
        get_traffic_data();
    }
});

function get_traffic_data() {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&display=' + display,
        url: '/app/network_visualiser/ajax/get_traffic_data',
        success: function(json) {
            if (json.code != 0) {
                // Error will get displayed in sidebar
                setTimeout('get_traffic_data()', 5000);
                return;
            }
            table_report.fnClearTable();
            for (var index = 0 ; index < json.data.length; index++) {
                if (display == 'totalbps')
                    field = '<span title=\"' + json.data[index].totalbps + '\"></span>' + format_number(json.data[index].totalbps);
                else
                    field = '<span title=\"' + json.data[index].totalbytes + '\"></span>' + format_number(json.data[index].totalbytes);
                table_report.fnAddData([
                    json.data[index].src,
                    json.data[index].srcport,
                    json.data[index].proto,
                    json.data[index].dst,
                    json.data[index].dstport,
                    field
                ]);
            }
            table_report.fnAdjustColumnSizing();
            if (timestamp != json.timestamp) {
                timestamp = json.timestamp;
                reset_scan();
                setTimeout('get_traffic_data()', 5000);
            }
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined)
                alert(xhr.responseText.toString());
        }
    });
}

function reset_scan() {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token'),
        url: '/app/network_visualiser/ajax/reset_scan',
        success: function(json) {
            if (json.code != 0) {
                alert(json.errmsg);
                return;
            }
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined)
                alert(xhr.responseText.toString());
        }
    });
}

var ben = true;
function format_number (bytes) {

    if (display == 'totalbytes') {
        var sizes = [" .
            "'" . lang('base_bytes') . "'," .
            "'" . lang('base_kilobytes') . "'," .
            "'" . lang('base_megabytes') . "'," .
            "'" . lang('base_gigabytes') . "'
        ];
    } else {
        bytes = bytes / 8;
        var sizes = [" .
            "'" . lang('base_bytes_per_second') . "'," .
            "'" . lang('base_kilobytes_per_second') . "'," .
            "'" . lang('base_megabytes_per_second') . "'," .
            "'" . lang('base_gigabytes_per_second') . "'
        ];
    }
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return ((i == 0)? (bytes / Math.pow(1024, i)) : (bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];
};

jQuery.fn.dataTableExt.oSort['title-numeric-asc']  = function(a,b) {
    var x = a.match(/title=\"*(-?[0-9\.]+)/)[1];
    var y = b.match(/title=\"*(-?[0-9\.]+)/)[1];
    x = parseFloat( x );
    y = parseFloat( y );
    return ((x < y) ? -1 : ((x > y) ?  1 : 0));
};

jQuery.fn.dataTableExt.oSort['title-numeric-desc'] = function(a,b) {
    var x = a.match(/title=\"*(-?[0-9\.]+)/)[1];
    var y = b.match(/title=\"*(-?[0-9\.]+)/)[1];
    x = parseFloat( x );
    y = parseFloat( y );
    return ((x < y) ?  1 : ((x > y) ? -1 : 0));
};
";

// vim: ts=4 syntax=javascript
?>
