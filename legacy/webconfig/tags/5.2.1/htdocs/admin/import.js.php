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

WebAuthenticate();

header('Content-Type:application/x-javascript');

echo "

var interval = 2000;
var parentObject;
var last_pos = 0;
var title = document.title;
var useragent;

function Initialize(ua)
{
	useragent = ua;
 	parentObject = document.getElementById('import_progress_bar');
	setTimeout('TimerTick()', interval);
}

function GetXmlHttp()
{
	if (window.XMLHttpRequest)
		window.xmlHttp = new XMLHttpRequest();
	else if (window.ActiveXObject) {
		// Try ActiveX
		try { 
			window.xmlHttp = new ActiveXObject(\"Msxml2.XMLHTTP\");
		} catch (e1) { 
			// first method failed 
			try {
				window.xmlHttp = new ActiveXObject(\"Microsoft.XMLHTTP\");
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
	document.getElementById('import_progress_percent').firstChild.nodeValue = percent + '%';
}

function ClearProgress()
{
	var childNodes = parentObject.childNodes;;
	var childCount = childNodes.length;

	document.title = title;

	for (var i = 1; i < childCount; i++)
		parentObject.removeChild(parentObject.childNodes[1]);

	last_pos = 0;
	document.getElementById('import_progress_percent').firstChild.nodeValue = '0%';
}

function TimerTick()
{
	var url = 'import-status.php';

	GetXmlHttp();

	xmlHttp.onreadystatechange = LoadData;
	xmlHttp.open('GET', url, true);
	xmlHttp.setRequestHeader('User-Agent', useragent);
	xmlHttp.send(null);
}

function LoadData()
{
	if (!window.xmlHttp)
		return;
	else if (xmlHttp.readyState == 4 || xmlHttp.readyState == 'complete') {
		eval(xmlHttp.responseText);
		setTimeout('TimerTick()', interval);
	}
}

YAHOO.util.Event.onContentReady('import_progress_percent', Initialize);

";


// vi: ts=4
?>
