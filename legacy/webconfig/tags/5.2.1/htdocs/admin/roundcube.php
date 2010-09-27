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
require_once("../../api/RoundCube.class.php");
require_once("../../api/Httpd.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-roundcube.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

try {
	// Initialize, if necessary
	$roundcube = new RoundCube();
	$roundcube->RunBootstrap();
} catch (Exception $e) {
    WebDialogWarning($e->GetMessage());
	WebFooter();
	exit();
}

try {
	if (isset($_POST['UpdateConfig'])) {
		$roundcube->SetProductName($_POST['productname']);
		$roundcube->SetLogLogins($_POST['loglogins']);
		// Set Alias before WebEngine so reload takes new alias
		$roundcube->SetAlias($_POST['alias']);
		$roundcube->SetWebEngine($_POST['webengine']);

		// TODO: only do this if necessary, not every single time!
		$httpd = new Httpd();
		$httpd->Reset();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayServerSettings();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayServerSettings()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayServerSettings()
{
	global $roundcube;

	try {
		$productname = $roundcube->GetProductName();
		$loglogins = $roundcube->GetLogLogins();
		$engine = $roundcube->GetWebEngine();
		$engineoptions = $roundcube->GetWebEngineOptions();
		$alias = $roundcube->GetAlias();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	WebFormOpen();
	WebTableOpen(WEB_LANG_SERVER_SETTINGS);
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . WEB_LANG_DISPLAY_NAME . "</td>
			<td><input type='text' name='productname' value='$productname' style='width: 250px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_LOG_LOGINS . "</td>
			<td>" . WebDropDownEnabledDisabled("loglogins", $loglogins) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ENGINE . "</td>
			<td nowrap>" . WebDropDownHash('webengine', $engine, $engineoptions) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ALIAS . "</td>
			<td><input type='text' name='alias' value='$alias' style='width: 150px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp; </td>
			<td>" . WebButtonUpdate("UpdateConfig") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

// vim: syntax=php ts=4
?>
