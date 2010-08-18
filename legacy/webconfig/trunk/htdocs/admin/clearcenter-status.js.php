<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 ClearCenter
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

if (isset($_REQUEST['service']))
	$service = $_REQUEST['service'];
else
	exit();

header('Content-Type:application/x-javascript');

echo "
	var getServiceStatusCallback = {
		success : function(o) {
			var root = o.responseXML.documentElement; 

			var state_node = root.getElementsByTagName('state')[0];
			var status_message_node = root.getElementsByTagName('status_message')[0];
			var expiry_node = root.getElementsByTagName('expiry')[0];
			var error_node = root.getElementsByTagName('error')[0];

        	var state_value = (state_node) ? state_node.firstChild.nodeValue : '';
        	var status_message_value = (status_message_node) ? status_message_node.firstChild.nodeValue : '';
        	var expiry_value = (expiry_node) ? expiry_node.firstChild.nodeValue : '';
        	var error_value = (error_node) ? error_node.firstChild.nodeValue : '';

			var state = document.getElementById('state');
			var status_message = document.getElementById('status_message');
			var expiry = document.getElementById('expiry');
			var error = document.getElementById('error');

			var servicestate = document.getElementById('clearos-service-state');
			var servicestatus = document.getElementById('servicestatus');

			if (servicestate) {
				if (state_value == '')
					servicestate.innerHTML = '...'
				else if (state_value == '1')
					servicestate.innerHTML = '<span class=ok>" . LOCALE_LANG_ENABLED . "</span>'
				else
					servicestate.innerHTML = '<span class=alert>" . LOCALE_LANG_DISABLED . "</span>'
			}

			if (state) {
				if (state_value == '1')
					state.innerHTML = '<span class=ok>" . LOCALE_LANG_ENABLED . "</span>';
				else
					state.innerHTML = '<span class=alert>" . LOCALE_LANG_DISABLED . "</span>';
			}

			if (expiry) {
				if (expiry_value == '0')
					expiry.innerHTML = '...';
				else
					expiry.innerHTML = expiry_value;
			}

			if (status_message) {
				if (status_message_value == '0')
					status_message.innerHTML = '...';
				else
					status_message.innerHTML = status_message_value;
			}

			if (error) {
				if (error == '0')
					error.innerHTML = '';
				else
					error.innerHTML = error_value;
			}

			if (servicestatus) {
				servicestatus.innerHTML = '';
			}
		},

		failure : function(o) {
			var servicestatus = document.getElementById('servicestatus');
			if (servicestatus) {
				servicestatus.innerHTML = '';
			}
		}
	};

	function getServiceStatus() {
		var servicestatus = document.getElementById('servicestatus');
		if (servicestatus) {
			servicestatus.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';
		}

		var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/clearcenter-status.xml.php?service=$service&nocache=' + new Date().getTime() , getServiceStatusCallback);
	};
"

?>
