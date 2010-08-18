<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");

WebAuthenticate();

header('Content-Type: application/x-javascript');
readfile('../../js/common.js');

?>

function GetStatus()
{
	window.GetStatusRequest = NewXMLHttpRequest('/admin/protocol-filter-report.xml.php?request=status', true);
	if (!window.GetStatusRequest) {
		// TODO: Display error message on page, AJAX unavailable...
		return;
	}

	window.GetStatusRequest.onreadystatechange = OnGetStatusResult;
	window.GetStatusRequest.send(null);
}

// OnGetStatusResult: Process result of GetStatus() XML HTTP request
function OnGetStatusResult()
{
	if (!window.GetStatusRequest) return;
	if (window.GetStatusRequest.readyState != 4) return;
	if (window.GetStatusRequest.status == 401) {
		setTimeout('GetStatus()', 50);
		return;
	}
	if (window.GetStatusRequest.status != 200) {
		// TODO: Display error message
		setTimeout('GetStatus()', 1000);
		return;
	}

	var response_xml = window.GetStatusRequest.responseXML;
	if (!response_xml) return;

	var status_node = response_xml.getElementsByTagName('status')[0];
	if (!status_node) return;

	var table = document.getElementById('l7_status');
	if (!table) return null;

	var tbody = table.getElementsByTagName('TBODY')[1];
	if (!tbody) tbody = table.getElementsByTagName('TBODY')[0];
	if (!tbody) return null;

	// Get protocol filter entries
	for (var x = 0; x < status_node.getElementsByTagName('protocol').length; x++) {
		var protocol = status_node.getElementsByTagName('protocol')[x];

		var mark = protocol.getElementsByTagName('mark')[0].firstChild.nodeValue;
		var packets = protocol.getElementsByTagName('packets')[0].firstChild.nodeValue;
		var bytes = protocol.getElementsByTagName('bytes')[0].firstChild.nodeValue;

		var row = null;
		row = document.getElementById('l7_packets_' + mark);
		if (row) {
			if (row.innerHTML != packets) {
				row.innerHTML = packets;
				row.style.color = '#ff0000';
			} else
				row.style.color = '#000000';
		}
		row = document.getElementById('l7_bytes_' + mark);
		if (row) {
			if (row.innerHTML != bytes) {
				row.innerHTML = bytes;
				row.style.color = '#ff0000';
			} else
				row.style.color = '#000000';
		}
	}

	// Update protocol filter status every 1 second
	setTimeout('GetStatus()', 1000);
}

// vim: syntax=javascript ts=4
