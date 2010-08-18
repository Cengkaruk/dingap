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

header('Content-Type:application/x-javascript');

echo "
var getNetworkInfo = {
	success : function(o) {
		ifaces = o.responseXML.getElementsByTagName('iface');
		elements = new Array('ip', 'iplog', 'link', 'speed', 'role', 'bootproto');
		
		for (inx = 0; inx < ifaces.length; inx++) {
			var name = ifaces[inx].getElementsByTagName('name')[0].firstChild.nodeValue;

			for (y in elements) {
				var data_xml = ifaces[inx].getElementsByTagName(elements[y])[0];
				var data_value = (data_xml && data_xml.firstChild) ? data_xml.firstChild.nodeValue : '';
				var data_html = document.getElementById(name + '_' + elements[y]);
				data_html.innerHTML = data_value;

				// Enable/disable whirligig when an IP address does not exist/exists
				if (elements[y] == 'ip') {
					var is_configured = ifaces[inx].getElementsByTagName('configured')[0].firstChild.nodeValue;
					var icon_html = document.getElementById(name + '_ipicon');

					if ((is_configured == 1) && (data_value == '')) {
						// Do not show whirligig if it is already whirlygigging
						if (! icon_html.innerHTML.match('images'))
							icon_html.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';
					} else {
						icon_html.innerHTML = '';
					}
				}
			}

		}

		var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/network.xml.php?nocache=' + new Date().getTime(), getNetworkInfo);
	},

	failure : function(o) {
	//	var result = document.getElementById('result');
	}
};

function Initialize()
{
	var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/network.xml.php?nocache=' + new Date().getTime(), getNetworkInfo);
}

YAHOO.util.Event.onContentReady('ifaces_ready', Initialize);

function hide(id) {
	if (document.getElementById(id))
		document.getElementById(id).style.display = 'none';
}

function show(id) {
	if (document.getElementById(id))
		document.getElementById(id).style.display = '';
}

function toggleNetworkRole() {
	if (document.getElementById) {
		if (document.getElementById('role').value == 'EXTIF') {
			show('gateway');
		} else {
			hide('gateway');
		}
	}
}

function toggleNetworkType() {
	if (document.getElementById) {
		if (document.getElementById('networktype').value == 'static') {
			hide('pppoe');  
			hide('dhcp');  
			show('static');
		} else if (document.getElementById('networktype').value == 'pppoe') {
			show('pppoe');  
			hide('dhcp');  
			hide('static');
		} else if (document.getElementById('networktype').value == 'dhcp') {
			hide('pppoe');  
			show('dhcp');  
			hide('static');
		}
	}
}
";

// vim: ts=4
?>
