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
clearos_load_language('network_visualiser');

header('Content-Type: application/x-javascript');

echo "

var timestamp = 0;
var display = 'totalbps';
var report_simple = 0;
var report_detailed = 1;
var report_graphical = 2;

$(document).ready(function() {
    if ($('#report_type').val() == report_simple)
        $('#report tr:last td:eq(2)').html('<div class=\"theme-loading-normal\"></div>');
    else if ($('#report_type').val() == report_detailed)
        $('#report tr:last td:eq(3)').html('<div class=\"theme-loading-normal\"></div>');

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
	    if ($('#report_type').val() == report_graphical) {
		graph_data(display, json);
                if (timestamp != json.timestamp) {
                    timestamp = json.timestamp;
                    reset_scan();
                    setTimeout('get_traffic_data()', 5000);
                }
                return;
            }
            table_report.fnClearTable();
            for (var index = 0 ; index < json.data.length; index++) {
                if (display == 'totalbps') {
                    if (isNaN(json.data[index].totalbps) || json.data[index].totalbps == 0)
                        continue;
                    field = '<span title=\"' + json.data[index].totalbps + '\"></span>' + format_number(json.data[index].totalbps);
                } else {
                    if (isNaN(json.data[index].totalbytes) || json.data[index].totalbytes == 0)
                        continue;
                    field = '<span title=\"' + json.data[index].totalbytes + '\"></span>' + format_number(json.data[index].totalbytes);
		}
		if ($('#report_type').val() == report_simple) {
			table_report.fnAddData([
			    json.data[index].src,
			    json.data[index].srcport,
			    json.data[index].dst,
			    field
			]);
		} else if ($('#report_type').val() == report_detailed) {
			table_report.fnAddData([
			    json.data[index].src,
			    json.data[index].srcport,
	                    json.data[index].proto,
			    json.data[index].dst,
	                    json.data[index].dstport,
			    field
			]);
		}
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

function format_number (bytes) {
    bits = bytes * 8;

    if (display == 'totalbytes') {
        var sizes = [" .
            "'" . lang('base_bits') . "'," .
            "'" . lang('base_kilobits') . "'," .
            "'" . lang('base_megabits') . "'," .
            "'" . lang('base_gigabits') . "'
        ];
    } else {
        var sizes = [" .
            "'" . lang('base_bits_per_second') . "'," .
            "'" . lang('base_kilobits_per_second') . "'," .
            "'" . lang('base_megabits_per_second') . "'," .
            "'" . lang('base_gigabits_per_second') . "'
        ];
    }
    var i = parseInt(Math.floor(Math.log(bits) / Math.log(1024)));
    return ((i == 0)? (bits / Math.pow(1024, i)) : (bits / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];
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

function graph_data(display, json) {
    var datapoints = new Array();
    for (var index = 0 ; index < json.data.length; index++) {
	if (display == 'totalbps')
	    total = json.data[index].totalbps; 
        else
	    total = json.data[index].totalbytes; 
        if (total == undefined || isNaN(total) || total == 0)
            continue;
	if (datapoints[json.data[index].src] == undefined)
	    datapoints[json.data[index].src] = parseInt(total/1024);
	else 
	    datapoints[json.data[index].src] += parseInt(total/1024);
    }

    var data = new Array();

    counter = 0;
    for (entry in datapoints) {
        data[counter] = [entry,datapoints[entry]]; 
        if (counter >= 10)
	    break;
	counter++;
    }

    unit = '" . lang('base_kilobytes') . "';
    if (display == 'totalbps')
    	unit = '" . lang('base_kilobytes_per_second') . "';

    var clear_plot = jQuery.jqplot ('clear-chart', [data],
    {
        seriesDefaults: {
            renderer: jQuery.jqplot.PieRenderer,
            rendererOptions: {
                showDataLabels: true,
                dataLabels: 'value',
                dataLabelFormatString: '%d ' + unit
            }
        },
        title: '" . lang('network_visualiser_top_users') . "', 
        legend: { show:true, location: 'ne' }
    });
    clear_plot.redraw();
}

";

// vim: ts=4 syntax=javascript
