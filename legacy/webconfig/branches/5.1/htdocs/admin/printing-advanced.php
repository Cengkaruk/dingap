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
require_once("../../api/Cups.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-printing-advanced.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$cups = new Cups();

try {
	if (isset($_POST['EnableBoot'])) {
		$cups->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$cups->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$cups->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$cups->SetRunningState(false);
	}
} catch (Exception $e) {
	 WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

try {
	$isrunning = $cups->GetRunningState();
} catch (Exception $e) {
	 WebDialogWarning($e->GetMessage());
}

WebDialogDaemon("cups");

if ($isrunning) {
	WebDialogInfo(WEB_LANG_CONNECT_TO_PRINTER_ADMINISTRATION . " &nbsp; " .
	"<a target='_blank' href='https://" . getenv("SERVER_ADDR") . ":631'>" . LOCALE_LANG_CONFIGURE . "</a>.");
}

WebFooter();

// vim: syntax=php ts=4
?>
