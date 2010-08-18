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

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/FreeRadius.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-google-apps.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$radius = new FreeRadius();

try {
    if (isset($_POST['EnableBoot'])) {
        $radius->SetBootState(true);
    } else if (isset($_POST['DisableBoot'])) {
        $radius->SetBootState(false);
    } else if (isset($_POST['StartDaemon'])) {
        $radius->SetRunningState(true);
    } else if (isset($_POST['StopDaemon'])) {
        $radius->SetRunningState(false);
	}
} catch (Exception $e) {
    WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogDaemon("radiusd");
// DisplayConfiguration();
WebDialogWarning("RADIUS is experimental.  Please see Howto document.");
Webfooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfiguration()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfiguration()
{
	global $radius;

	try {
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

// vi: syntax=php ts=4
?>
