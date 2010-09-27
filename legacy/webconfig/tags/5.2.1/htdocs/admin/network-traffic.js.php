<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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
require_once(GlobalGetLanguageTemplate('network-traffic.php'));

WebAuthenticate();

header('Content-Type:application/x-javascript');

echo "

function addBlock(params) {
	newXMLHttpRequest();
  	request.onreadystatechange = ruleAddResponse;
	request.send('addBlock=1&' + params);
}

function ruleAddResponse() {
	if (!window.request) {
		setTimeout('ruleAddResponse()', 1000);
	} else if (request.readyState == 4) {
		if (request.status != 200) {
			setTimeout('ruleAddResponse()', 1000);
		} else {
			if (request.responseXML == null) {
				alert('Unable to add block rule.');
				return;
			}
			var message = request.responseXML.getElementsByTagName('status')[0];
			var code = message.getElementsByTagName('code')[0].firstChild.nodeValue;
			if (code == 0) {
				alert('" . WEB_LANG_BLOCK_ADDED_TO_FIREWALL . "');
			} else {
				var err = message.getElementsByTagName('error')[0].firstChild.nodeValue;
				alert('" . WEB_LANG_ERR_CREATING_BLOCK . " ' + err); 
			}
		}
	}
}

function getStatus() {
	newXMLHttpRequest();
  	request.onreadystatechange = processPollRequest;
	params = 'interface=' + document.getElementById('interface').value + '&interval=' + document.getElementById('interval').value;
	request.send(params);
}

function processPollRequest() {
	if (!window.request) 
		setTimeout('getStatus()', 5000);
	else if (request.readyState == 4) {
		if (request.status == 200)
			updateTableRows();
		setTimeout('getStatus()', 5000);
	}
}

function updateTableRows() {
	if (request.responseXML == null)
		return;
	var message = request.responseXML.getElementsByTagName('networkactivity')[0];

	if (!message) return;

	var table = document.getElementById('traffic-inner');
	if (table == undefined)
		var table = document.getElementById('traffic');

	if (message.getElementsByTagName('entry').length == 0)
		return;

	// Delete any existing rows
	for (index = table.rows.length-1; index >= 1; index--)
	table.rows[index].style.display = 'none';

	for (var x= message.getElementsByTagName('entry').length -1 ; x >= 0; x--) {
		var src = '';
		var srcname = '';
		var srcport = '';
		var proto = '';
		var dst = '';
		var dstname = '';
		var dstport = '';
		var bandwidth = '';
		var bytes = '';
		try {
			src = message.getElementsByTagName('entry')[x].getElementsByTagName('src')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			srcname = message.getElementsByTagName('entry')[x].getElementsByTagName('srcname')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			srcport = message.getElementsByTagName('entry')[x].getElementsByTagName('srcport')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			proto = message.getElementsByTagName('entry')[x].getElementsByTagName('proto')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			dst = message.getElementsByTagName('entry')[x].getElementsByTagName('dst')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			dstname = message.getElementsByTagName('entry')[x].getElementsByTagName('dstname')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			dstport = message.getElementsByTagName('entry')[x].getElementsByTagName('dstport')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			bandwidth = message.getElementsByTagName('entry')[x].getElementsByTagName('totalbps')[0].firstChild.nodeValue;
		} catch (e) {}
		try {
			bytes = message.getElementsByTagName('entry')[x].getElementsByTagName('totalbytes')[0].firstChild.nodeValue;
		} catch (e) {}


		var row = table.insertRow(1);
		if (x%2 == 0)
			row.setAttribute('class', 'mytablealt');

		// Block Button
		var cell = row.insertCell(0);
		cell.setAttribute('align', 'center');
		/*
		if (proto == 'UDP' ||  proto == 'TCP')
			cell.innerHTML = \"<a href='JavaScript:addBlock(\\\"protocol=\" + proto + \"&dstip=\" + dst + \"&dstport=\" + dstport + \"&srcip=\" + src + \"&srcport=\" + srcport + \"\\\");'>" .  WEBCONFIG_ICON_CANCEL . "</a>\";
		else
			cell.innerHTML = '';
		*/

		if (document.getElementById('sort').value == 'totalbps') {
			// Bandwidth
			var cell = row.insertCell(0);
			cell.setAttribute('align', 'right');
			var text = document.createTextNode(formatNumber(bandwidth, 'bps'));
			cell.appendChild(text);
		} else {
			// Total Transfer
			var cell = row.insertCell(0);
			cell.setAttribute('align', 'right');
			var text = document.createTextNode(formatNumber(bytes, 'B'));
			cell.appendChild(text);
		}

		// Destination Port
		var cell = row.insertCell(0);
		cell.setAttribute('align', 'right');
		var text = document.createTextNode(dstport);
		cell.appendChild(text);

		// Destination Name
		var cell = row.insertCell(0);
		var text = document.createTextNode(dstname + ' (' + dst + ')');
		cell.appendChild(text);

		// Protocol
		var cell = row.insertCell(0);
		var text = document.createTextNode(proto);
		cell.appendChild(text);

		// Source Port
		var cell = row.insertCell(0);
		cell.setAttribute('align', 'right');
		var text = document.createTextNode(srcport);
		cell.appendChild(text);

		// Source Name
		var cell = row.insertCell(0);
		var text = document.createTextNode(srcname + ' (' + src + ')');
		cell.appendChild(text);

	}
}

function newXMLHttpRequest() {
	if (window.XMLHttpRequest) {
		window.request = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
   		// Try ActiveX
		try { 
			window.request = new ActiveXObject('Msxml2.XMLHTTP');
		} catch (e1) { 
			// first method failed 
			try {
				window.request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (e2) {
				 // both methods failed 
			} 
		}
 	}

//	if (block == undefined) {
		request.open('POST', '/admin/network-traffic.xml.php', true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
//	}
} 

function formatNumber (bytes, unit) {
	var value = parseFloat(bytes);
	if (value >= 1073741824) {
		display = (value / 1073741824).toFixed(1) + ' G' + unit;
	} else if (value >= 1048576) {
		display = (value / 1048576).toFixed(1) + ' M' + unit;
	} else if (value >= 1024) {
		display = (value / 1024).toFixed(1) + ' K' + unit;
	} else {
		display = value + ' ' + unit;
	}
	return display;
};

";
# vim: ts=4
