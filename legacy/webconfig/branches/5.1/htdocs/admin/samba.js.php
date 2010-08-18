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
require_once("../../api/Daemon.class.php");
require_once("../../api/Samba.class.php");

WebAuthenticate();

$daemon = new Daemon("notused"); // Just for locale

ob_start();
WebButtonToggle("StopDaemon", DAEMON_LANG_STOP);
$button_on = ob_get_clean();

/*
       $status_button = WebButtonToggle("StopDaemon", DAEMON_LANG_STOP);
        $status_button = WebButtonToggle("StartDaemon", DAEMON_LANG_START);
*/
 

header('Content-Type:application/x-javascript');

echo "
var getSambaInfo = {
	success : function(o) {
		var root = o.responseXML.documentElement; 

		var smb_node = root.getElementsByTagName('smb-state')[0];
		var smb_state_html = (smb_node.firstChild && (smb_node.firstChild.nodeValue) && (smb_node.firstChild.nodeValue == 1)) ? '<span class=ok>" . DAEMON_LANG_RUNNING . "</span>' : '<span class=alert>" . DAEMON_LANG_STOPPED . "$button_on</span>';
		var smb_state_element = document.getElementById('smbstate');
		smb_state_element.innerHTML = smb_state_html;

		var nmb_node = root.getElementsByTagName('nmb-state')[0];
		var nmb_state_html = (nmb_node.firstChild && (nmb_node.firstChild.nodeValue) && (nmb_node.firstChild.nodeValue == 1)) ? '<span class=ok>" . DAEMON_LANG_RUNNING . "</span>' : '<span class=alert>" . DAEMON_LANG_STOPPED . "</span>';
		var nmb_state_element = document.getElementById('nmbstate');
		nmb_state_element.innerHTML = nmb_state_html;

		var winbind_node = root.getElementsByTagName('winbind-state')[0];
		var winbind_state_html = (winbind_node.firstChild && (winbind_node.firstChild.nodeValue) && (winbind_node.firstChild.nodeValue == 1)) ? '<span class=ok>" . DAEMON_LANG_RUNNING . "</span>' : '<span class=alert>" . DAEMON_LANG_STOPPED . "</span>';
		var winbind_state_element = document.getElementById('winbindstate');
		winbind_state_element.innerHTML = winbind_state_html;

		var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/samba.xml.php?nocache=' + new Date().getTime(), getSambaInfo);
	},

	failure : function(o) {
		result.innerHTML = '';
	}
};

function Initialize()
{
	var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/samba.xml.php?nocache=' + new Date().getTime(), getSambaInfo);
}

YAHOO.util.Event.onContentReady('service-status', Initialize);

function togglewinsserver() {
  if (document.getElementById('winssupport').value == 1)
    disable('winsserver');
  else
    enable('winsserver');
}

function enable(id) {
  if (document.getElementById(id))
	document.getElementById(id).disabled = false;
}

function disable(id) {
  if (document.getElementById(id))
	document.getElementById(id).disabled = true;
}

function enablerows(prefix) {
	if (! prefix) {
		if (document.getElementById('mode').value == '" . Samba::MODE_PDC . "')
			prefix = 'pdc';
		else if (document.getElementById('mode').value == '" . Samba::MODE_BDC . "')
			prefix = 'bdc';
		else if (document.getElementById('mode').value == '" . Samba::MODE_SIMPLE_SERVER . "')
			prefix = 'sim';
		else
			prefix = 'sim';
	}
 
	var alltrs = document.getElementsByTagName('tr');

	for (var i = 0; i < alltrs.length; i++) {
		mode_row = alltrs[i].id.substring(0,5) ;
		if (mode_row == 'mode_') {
			row_prefix = alltrs[i].id.substring(5,8);
			var row = document.getElementById(alltrs[i].id);

			if (row_prefix == prefix) {
				row.style.display = '';
			} else {
				row.style.display = 'none';
			}
		}
	}
}

";

?>
