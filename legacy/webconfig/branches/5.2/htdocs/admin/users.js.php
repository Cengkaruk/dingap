<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 Point Clark Networks.
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
//
// This is just a quick hack... this will soon be replaced by a more elegant
// framework in 6.0.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");

WebAuthenticate();
header('Content-Type:application/x-javascript');

echo "
function AddAlias(username)
{
	var whirly = document.getElementById('whirly');
	whirly.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';

	var alias = document.getElementById('alias');
	var aliasname = alias.value;

	var request = makeHttpObject();

	request.open('GET', '/admin/users.xml.php?AddAlias=yes&alias=' + aliasname + '&username=' + username, true);
	request.onreadystatechange=function() {
		if (request.readyState == 4) {
			if (request.responseText) {
				whirly.innerHTML = '<span class=\"alert\">' + request.responseText + '</span>';
			} else {
				var alias = document.getElementById('alias');
				alias.value = '';
				var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/users.xml.php?GetAliases=yes&username=' + username + '&nocache=' + new Date().getTime(), GetAliasesCallback);
			}
		}
	}
	request.send(null);
}

function DeleteAlias(username,alias)
{
	var whirly = document.getElementById('whirly');
	whirly.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';

	var request = makeHttpObject();
	request.open('GET', '/admin/users.xml.php?DeleteAlias=yes&alias=' + alias + '&username=' + username, true);
	request.send(null);

	var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/users.xml.php?GetAliases=yes&username=' + username + '&nocache=' + new Date().getTime(), GetAliasesCallback);
}

var GetAliasesCallback = {
	success : function(o) {
		var root = o.responseXML.documentElement; 

		var aliases_node = root.getElementsByTagName('aliases')[0];
		var aliases = (aliases_node && aliases_node.firstChild) ? aliases_node.firstChild.nodeValue : '';

		var username_node = root.getElementsByTagName('username')[0];
		var username = (username_node && username_node.firstChild) ? username_node.firstChild.nodeValue : '';

		var aliaslist = document.getElementById('aliaslist');
		aliaslist.innerHTML = aliases;

		var aliashtml = '';

		aliasarray = aliases.split(',');
        for (i = 0; i < aliasarray.length; i++) {
			if (aliasarray[i])
				aliashtml += '<div style=\"margin: 3px\"><input style=\"border: none; width: 120px\" type=text value=\"' + aliasarray[i] + '\"> <input id=\"alias_' + i + '\" value=\"" . LOCALE_LANG_DELETE . "\" type=\"button\" onclick=\"DeleteAlias(\'' + username + '\',\'' + aliasarray[i] + '\')\" class=\"ui-state-default ui-corner-all\"/></div>';
        }

		aliaslist.innerHTML = aliashtml;

		var whirly = document.getElementById('whirly');
		whirly.innerHTML = '';
	},

	failure : function(o) {
		var aliaslist = document.getElementById('aliaslist');
		aliaslist.innerHTML = '...';
	}
};

function GetAliases() {
	var whirly = document.getElementById('whirly');
	whirly.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';

	var username = document.getElementById('aliasusername');

	var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/users.xml.php?GetAliases=yes&username=' + username.value + '&nocache=' + new Date().getTime(), GetAliasesCallback);
};

function makeHttpObject() {
	try {return new XMLHttpRequest();}
	catch (error) {}
	try {return new ActiveXObject('Msxml2.XMLHTTP');}
	catch (error) {}
	try {return new ActiveXObject('Microsoft.XMLHTTP');}
	catch (error) {}

	throw new Error('Could not create HTTP request object.');
}

YAHOO.util.Event.onContentReady('aliaslist', GetAliases);
";

// vim: syntax=javascript ts=4
?>
