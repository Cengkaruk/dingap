<?php

/**
 * Antivirus file scan javascript helper.
 *
 * @category   Apps
 * @package    File_Scan
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/file_scan/
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
//////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

$(document).ready(function() {

	getData();

	function getData() {
        $.ajax({
            url: '/app/file_scan/scan/info',
            method: 'GET',
            dataType: 'json',
            success : function(json) {
				showData(json);
				window.setTimeout(getData, 2000);
            },
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				$("#status").html('Ooops: ' + textStatus);
				window.setTimeout(getData, 2000);
			}
        });
	}

	function showData(info) {
		$("#progress").progressbar({
			value: Math.round(info.progress)
		});
		$("#state_text").html(info.state_text);
		$("#status_text").html(info.status);
		$("#error_count_text").html(info.error_count);
		$("#malware_count_text").html(info.malware_count);
		$("#last_result_text").html(info.last_result);
	}
});

// vim: ts=4 syntax=javascript
