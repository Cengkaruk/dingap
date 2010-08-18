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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/Daemon.class.php');
require_once('../../api/Layer7Filter.class.php');
require_once('../../api/FirewallLayer7Filter.class.php');
require_once('../../api/Firewall.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-protocolfilter.png', WEB_LANG_PAGE_INTRO);


///////////////////////////////////////////////////////////////////////////////
//
// M A I N
//
///////////////////////////////////////////////////////////////////////////////


try {
	$l7 = new Layer7Filter();
	$firewall = new FirewallLayer7Filter();

	$isrunning = $l7->GetRunningState();
	$isenabled = $firewall->GetProtocolFilterState();
} catch (Exception $e) { 
	WebDialogWarning($e->GetMessage());
}

if ((! $isrunning) || (! $isenabled))
	WebDialogInfo(LAYER7FILTER_LANG_PROTOCOL_FILTER_IS_DISABLED);
else
	DisplayStatus();

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStatus()
{
	global $l7;
	global $patterns;

	try {
		$l7->GetProtocols($groups, $patterns);
		$l7->GetStatus($patterns);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$index = 0;
	$data = "";

	foreach ($patterns as $pattern) {
		if (!$pattern['enabled'])
			continue;

		$class = ($index % 2) ? 'mytablealt' : '';

		$data .= sprintf("<tr class='%s'><td>%s</td><td id='l7_packets_%d'>%d</td><td id='l7_bytes_%d'>%d</td></tr>\n",
			$class, $pattern['desc'],
			$pattern['mark'], $pattern['packets'],
			$pattern['mark'], $pattern['bytes']);

		$index++;
	}

	if ($index == 0)
		$data = "<tr><td colspan='3' style='text-align: center'>" . LAYER7FILTER_LANG_NO_FILTERS . "</td></tr>\n";

	WebTableOpen(WEB_LANG_PAGE_TITLE, "100%", "l7_status");
	WebTableHeader(LAYER7FILTER_LANG_PROTOCOL_NAME . "|" . LOCALE_LANG_PACKETS . "|" . LOCALE_LANG_BYTES_FULL);
	echo "$data";
	WebTableClose();

	if ($index > 0) {
		echo "<script type='text/javascript'>\n";
		echo "GetStatus();\n";
		echo "</script>\n";
	}
}

// vim: ts=4
?>
