<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
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

	$("#progress").html("whirylgig...");

	getData();

	function getData() {
        $.ajax({
            url: '/app/filescan/scan/status',
            method: 'GET',
            dataType: 'json',
            success : function(json) {
				showData(json);
				window.setTimeout(getData, 2000);
            },
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				$("#progress").html('Ooops: ' + textStatus);
				window.setTimeout(getData, 2000);
			}
        });
	}

	function showData(json) {
		$("#progress").html('showing data ' + json.age);
	}

});

var interval = 1000;
var parentObject;
var last_pos = 0;
var title = document.title;

function Initialize()
{
 	parentObject = document.getElementById('av_progress_bar');
	setTimeout('TimerTick()', interval);
}

function GetXmlHttp()
{
	if (window.XMLHttpRequest)
		window.xmlHttp = new XMLHttpRequest();
	else if (window.ActiveXObject) {
		// Try ActiveX
		try { 
			window.xmlHttp = new ActiveXObject('Msxml2.XMLHTTP');
		} catch (e1) { 
			// first method failed 
			try {
				window.xmlHttp = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (e2) {
				alert('No AJAX support detected.  Upgrade your web browser.');
			} 
		}
	}
}

function FindObjectPosition(obj)
{
	var left = 0;
	var top = 0;
	var width = 0;
	var height = 0;

	if (obj.offsetParent) {
		left = obj.offsetLeft;
		top = obj.offsetTop;
		width = obj.offsetWidth;
		height = obj.offsetHeight;

		while (obj = obj.offsetParent) {
			left += obj.offsetLeft;
			top += obj.offsetTop;
		}
	}

	return [left, top, width, height];
}

function UpdateProgress(percent)
{
	var offsetObj = document.getElementById('av_progress_bar');
	var offsetPos = FindObjectPosition(offsetObj);
	var pos = Math.floor(percent * offsetPos[2] / 100);

	if (percent == 100)
		document.title = title;
	else
		document.title = title + ' - Scanning ' + percent + '%';

	if (pos - last_pos > 0) {
		var slice = document.createElement('div');

		slice.className = 'progressbar';
		slice.style.left = (offsetPos[0] + last_pos) + 'px';
		slice.style.top = offsetPos[1] + 'px';
		slice.style.width = (pos - last_pos) + 'px';

		parentObject.appendChild(slice);
	}

	last_pos = pos;

	document.getElementById('av_progress_percent').firstChild.nodeValue = percent + '%';
}

function ClearProgress()
{
	var childNodes = parentObject.childNodes;;
	var childCount = childNodes.length;

	document.title = title;

	for (var i = 1; i < childCount; i++)
		parentObject.removeChild(parentObject.childNodes[1]);

	last_pos = 0;
	document.getElementById('av_progress_percent').firstChild.nodeValue = '0%';
}

function TimerTick()
{
	var url = 'avscan.php';

	GetXmlHttp();

	xmlHttp.onreadystatechange = LoadData;
	xmlHttp.open('GET', url, true);
	xmlHttp.send(null);
}

function LoadData()
{
	if (!window.xmlHttp)
		setTimeout('TimerTick()', interval);
	else if (xmlHttp.readyState == 4 || xmlHttp.readyState == 'complete') {
		eval(xmlHttp.responseText);
		setTimeout('TimerTick()', interval);
	}
}

function InsertVirus(filename, virusname, action)
{
	var tr;
	var td;
	var tbody = document.getElementById('av_report').getElementsByTagName('TBODY')[1];
	var i = 1;

	if (!tbody)
		tbody = document.getElementById('av_report').getElementsByTagName('TBODY')[0];

	for (; i < tbody.childNodes.length; i++) {
		tr = document.getElementById('av_virus' + i);
		if (!tr) break;
		if (tr.firstChild.firstChild.nodeValue == filename) return;
	}

	tr = document.createElement('TR');
	tr.id = 'av_virus' + i;

	td = document.createElement('TD');
	td.appendChild(document.createTextNode(filename));
	tr.appendChild(td);

	td = document.createElement('TD');
	td.appendChild(document.createTextNode(virusname));
	tr.appendChild(td);

	td = document.createElement('TD');
	td.innerHTML = action;
	tr.appendChild(td);

	tbody.appendChild(tr);
}

// vim: ts=4 syntax=javascript
