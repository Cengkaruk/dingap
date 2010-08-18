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
require_once("../../api/NtpTime.class.php");

WebAuthenticate();

$ntptime = new NtpTime();  // Locale

header('Content-Type:application/x-javascript');
echo "
var setNtpTimeCallback = {
	success : function(o) {
		var root = o.responseXML.documentElement; 
		var offset_node = root.getElementsByTagName('offset')[0];
		var offset = (offset_node) ? '" . NTPTIME_LANG_OFFSET . ": ' + offset_node.firstChild.nodeValue : '';

		var result = document.getElementById('result');
		result.innerHTML = offset;

		gEnableButtons();
	},

	failure : function(o) {
		var result = document.getElementById('result');
		result.innerHTML = '';

		gEnableButtons();
	}
};

function setNtpTime() {
	var result = document.getElementById('result');
	result.innerHTML = '" . preg_replace("/'/", "\"", WEBCONFIG_ICON_LOADING) . "';

	gDisableButtons();

	var conn = YAHOO.util.Connect.asyncRequest('GET', '/admin/date.xml.php?nocache=' + new Date().getTime(), setNtpTimeCallback);
};
";

?>
