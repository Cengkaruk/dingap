<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2005 Point Clark Networks.
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
require_once('../../api/HostnameChecker.class.php');
require_once('../../api/Proftpd.class.php');
require_once('../../api/UserManager.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-ftp.png', WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$proftpd = new Proftpd();

try {
	if (isset($_POST['EnableBoot'])) {
		$proftpd->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$proftpd->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$proftpd->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$proftpd->SetRunningState(false);
	} else if (isset($_POST['UpdateConfig'])) {
		$proftpd->SetServerName($_POST['servername']);
		$proftpd->SetMaxInstances($_POST['maxinstances']);
		$proftpd->SetPort($_POST['port']);
		$proftpd->Reset();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogDaemon("proftpd");
SanityCheck();
DisplayConfig();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig()
{
	global $proftpd;

	try {
		$servername = $proftpd->GetServerName();
		$maxinstances = $proftpd->GetMaxInstances();
		$port = $proftpd->GetPort();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "400");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . PROFTP_LANG_SERVERNAME . "</td>
		<td><input type='text' name='servername' value='$servername' size='40' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . PROFTP_LANG_MAXINSTANCES . "</td>
		<td><input type='text' name='maxinstances' value='$maxinstances' size='10' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . PROFTP_LANG_PORT . "</td>
		<td><input type='text' name='port' value='$port' size='10' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'>&#160; </td>
		<td>". WebButtonUpdate('UpdateConfig') . "</td>
	  </tr>
	";
	WebTableClose("400");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck()
{
	// Add entry to hosts file if hostname is not valid

	try {
		$hostnamechecker = new HostnameChecker();
		$nameisok = $hostnamechecker->IsLookupable();
		if (!$nameisok)
			$hostnamechecker->AutoFix();
	} catch (Exception $e) {
		// Not fatal
	}
}

// vim: ts=4
?>
