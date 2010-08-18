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
var getOrganizationInfo = {
	success : function(o) {
		var root = o.responseXML.documentElement; 
		var hostname_node = root.getElementsByTagName('hostname')[0];
		var hostname = (hostname_node) ? hostname_node.firstChild.nodeValue : '';

		var hostname_html = document.getElementById('hostname');
		try {
			hostname_html.innerHTML = '<input type=\'text\' name=\'hostname\' style=\'width: 200px\' value=\'' + hostname + '\'>';
		} catch (ie_weirdness) {}

		gEnableButtons();
	},

	failure : function(o) {
		var hostname = document.getElementById('hostname');
		hostname.innerHTML = '';

		gEnableButtons();
	}
};

function Initialize()
{
    var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/organization.xml.php?nocache=' + new Date().getTime(), getOrganizationInfo);

	gDisableButtons();
}

YAHOO.util.Event.onContentReady('hostname', Initialize);

";

?>
