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
var getUserStatus = {
	success : function(o) {
		var root = o.responseXML.documentElement; 

		var state_node = root.getElementsByTagName('directorystate')[0];
		var basedn_node = root.getElementsByTagName('basedn')[0];
		var binddn_node = root.getElementsByTagName('binddn')[0];
		var bindpassword_node = root.getElementsByTagName('bindpassword')[0];

		var state = state_node.firstChild.nodeValue;
		var basedn = basedn_node.firstChild.nodeValue;
		var binddn = binddn_node.firstChild.nodeValue;
		var bindpassword = bindpassword_node.firstChild.nodeValue;

		var basedn_html = document.getElementById('basedn');
		var binddn_html = document.getElementById('binddn');
		var bindpassword_html = document.getElementById('bindpassword');

		if (state == 'yes') {
			hide('directory-whirly')
			show('directory-info')
			basedn_html.innerHTML = basedn;
			binddn_html.innerHTML = binddn;
			bindpassword_html.innerHTML = bindpassword;
		} else {
			hide('directory-info')
			show('directory-whirly')
			basedn_html.innerHTML = '...';
			binddn_html.innerHTML = '...';
			bindpassword_html.innerHTML = '...';
		}

		var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/ldap.xml.php?nocache=' + new Date().getTime(), getUserStatus);
	},

	failure : function(o) {
	}
};

function Initialize()
{
	var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/ldap.xml.php?nocache=' + new Date().getTime(), getUserStatus);
}

YAHOO.util.Event.onContentReady('directory-info', Initialize);

function hide(id) {
  if (document.getElementById(id))
    document.getElementById(id).style.display = 'none';
}

function show(id) {
  if (document.getElementById(id)) {
    if (document.all)
      document.getElementById(id).style.display = 'inline';
    else
      document.getElementById(id).style.display = 'table-row';
  }
}

";
?>
