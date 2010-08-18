<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2009 Point Clark Networks.
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
require_once("../../api/IfaceManager.class.php");
require_once("../../api/JNetTop.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", "<script type='text/javascript' src='/admin/jnetstatus.js.php'></script>\n", 'getStatus()');
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-jnettop.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

$jnettop = new JNetTop();

DisplaySummary();

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	global $jnettop;
	global $interfaces;
	try {
		$interval_options = Array (5, 10, 15, 30, 60);
		$sort_options = Array ('totalbps' => JNETTOP_LANG_FIELD_TOTALBPS, 'totalbytes' => JNETTOP_LANG_FIELD_TOTALBYTES);
		$interfaces = new IfaceManager();
		$ethlist = $interfaces->GetInterfaceDetails();
		$isfirst = true;
		foreach ($ethlist as $eth => $details) {
			if ($isfirst)
				$interface = $eth;
			$interface_options[$eth] = $eth;
			$isfirst = false;
		}
		if (isset($_POST['interface']))
			$interface = $_POST['interface'];
		if (isset($_POST['interval']))
			$interval = $_POST['interval'];
		else
			$interval = 5;
		if (isset($_POST['sort']))
			$sort = $_POST['sort'];
		else
			$sort = 'totalbps';

		$jnettop->Init($interface, $interval);

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	WebFormOpen();
	WebTableOpen(WEB_LANG_SETTINGS, '60%');
	echo "<tr>";
	echo "<td class='mytablesubheader' width='50%'>" . WEB_LANG_INTERVAL . "</td>";
	echo "<td width='50%'>" . WebDropDownHash("interval", $interval, $interval_options, 0, null, "interval") . "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>" . WEB_LANG_INTERFACE . "</td>";
	echo "<td>" . WebDropDownHash("interface", $interface, $interface_options, 0, null, "interface") . "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>" . WEB_LANG_DISPLAY . "</td>";
	echo "<td>" . WebDropDownHash("sort", $sort, $sort_options, 0, null, "sort") . "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>&#160;</td>";
	echo "<td>" . WebButtonUpdate("Update") . "</td>";
	echo "</tr>";
	WebTableClose('60%');
	WebFormClose();

	$fields = $jnettop->GetFields();

	WebTableOpen(WEB_LANG_TRAFFIC_SUMMARY, '100%', 'traffic');
	echo "<tr>";
	foreach ($fields as $field) { 
		$align="align = 'left'";
		if (preg_match("/^SRC$|^DST$/i", $field))
			continue;
		if (preg_match("/^TOTALBPS$/i", $field) && $sort == 'totalbytes')
			continue;
		if (preg_match("/^TOTALBYTES$/i", $field) && $sort == 'totalbps')
			continue;
		if (eregi("PORT|TOTALBPS|TOTALBYTES", $field))
			$align="align = 'right'";
		echo "<td class='mytableheader'$align NOWRAP>" . constant(JNETTOP_LANG_FIELD_ . strtoupper($field)) . "</td>";
	}
	echo "<td class='mytableheader' align='center'>" . WEB_LANG_BLOCK . "</td>";
	echo "<tr>";
	echo "<td colspan='" . count($fields) . "' align='center'>" . WEBCONFIG_ICON_LOADING . "</td>";
	echo "</tr>";
	WebTableClose('100%');
}

// vim: ts=4
?>
