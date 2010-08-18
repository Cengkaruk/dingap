<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation.
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
require_once("../../api/Locale.class.php");
require_once("../../api/MediaTomb.class.php");
require_once("../../api/IfaceManager.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mediatomb.png", WEB_LANG_PAGE_INTRO);

$mediatomb = new MediaTomb();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['Update'])) {
	try {
		$mediatomb->SetInterface($_POST['iface']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogDaemon("mediatomb");
if ($mediatomb->RequiresInit())
	DisplaySetup();
else
	DisplayIframe();
WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUsage
//
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySetup()
{
	$ifacemanager = new IfaceManager();
	$interfaces = $ifacemanager->GetInterfaces(true, true);
	foreach ($interfaces as $interface)
		$iface_options[$interface] = $interface;
	WebFormOpen();
	WebTableOpen(WEB_LANG_SETTINGS, '60%');
	echo "<tr>";
	echo "<td class='mytablesubheader' width='50%'>" . WEB_LANG_INTERFACE . "</td>";
	echo "<td width='50%'>" . WebDropDownHash("iface", $interfaces[next($interfaces)], $iface_options) . "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>&#160;</td>";
	echo "<td>" . WebButtonUpdate("Update") . "</td>";
	echo "</tr>";
	WebTableClose('60%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUsage
//
//
///////////////////////////////////////////////////////////////////////////////

function DisplayIframe()
{
        echo "<iframe style='border:none;' src ='http://" . getenv("SERVER_ADDR") . ":50500' width='100%' height='800'>";
        echo "<p>" . WEB_LANG_IFRAME_NOT_SUPPORTED . "</p>";
        echo "</iframe>";
}

// vim: syntax=php ts=4
?>
