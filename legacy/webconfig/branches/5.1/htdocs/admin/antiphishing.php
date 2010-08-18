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
require_once("../../api/ClamAv.class.php");
require_once("../../api/AntimalwareUpdates.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . AntimalwareUpdates::CONSTANT_NAME . "'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-antiphishing.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$clamav = new ClamAv();

try {
    if (isset($_POST['UpdateAntiphishing'])) {
        $clamav->SetPhishingSignaturesState((bool)$_POST['signatures']);
        $clamav->SetPhishingScanUrlsState((bool)$_POST['scanurls']);
        $clamav->SetPhishingAlwaysBlockSslMismatch((bool)$_POST['blocksslmismatch']);
        $clamav->SetPhishingAlwaysBlockCloak((bool)$_POST['blockcloak']);
        $clamav->Reset();
    }
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebServiceStatus(AntimalwareUpdates::CONSTANT_NAME, "ClearSDN Antimalware Updates");
DisplayAntiphishingConfiguration();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAntiphishingConfiguration()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAntiphishingConfiguration()
{
	global $clamav;

	try {
		$signatures = $clamav->GetPhishingSignaturesState();
		$scanurls = $clamav->GetPhishingScanUrlsState();
		$blocksslmismatch = $clamav->GetPhishingAlwaysBlockSslMismatch();
		$blockcloak = $clamav->GetPhishingAlwaysBlockCloak();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(WEB_LANG_ANTIPHISHING_POLICIES);
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . CLAMAV_LANG_PHISHING_SCAN_WITH_SIGNATURES . "</td>
			<td>" . WebDropDownEnabledDisabled("signatures", $signatures) . "</td>
		</tr>
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . CLAMAV_LANG_PHISHING_SCAN_WITH_HEURISTICS . "</td>
			<td>" . WebDropDownEnabledDisabled("scanurls", $scanurls) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . CLAMAV_LANG_PHISHING_BLOCK_SSL_MISMATCH . "</td>
			<td>" . WebDropDownEnabledDisabled("blocksslmismatch", $blocksslmismatch) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . CLAMAV_LANG_PHISHING_BLOCK_CLOAK . "</td>
			<td>" . WebDropDownEnabledDisabled("blockcloak", $blockcloak) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp; </td>
			<td>" . WebButtonUpdate("UpdateAntiphishing") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

// vim: syntax=php ts=4
?>
