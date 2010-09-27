<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2007 Point Clark Networks.
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
require_once("../../api/Ntpd.class.php");
require_once("date.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_TIME_TITLE, "/images/icon-date.png", WEB_LANG_TIME_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$ntptime = new NtpTime();
$ntpd = new Ntpd();

try {
	if (isset($_POST['SetTime'])) {
		$ntptime->SetTimeZone($_POST['timezone']);

		if ($_POST['autosync']) {
			$ntptime->DeleteAutoSync();
			$ntpd->SetRunningState(true);
			$ntpd->SetBootState(true);
		} else {
			$ntptime->SetAutoSync();
			$ntpd->SetRunningState(false);
			$ntpd->SetBootState(false);
		}
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayTime();
WebFooter();

// vim: ts=4
?>
