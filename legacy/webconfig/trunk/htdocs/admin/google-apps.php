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
require_once("../../api/GoogleApps.class.php");
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

$gapps = new GoogleApps();

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayConfiguration();
Webfooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfiguration()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfiguration($domain)
{
	global $gapps;

	try {
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE);
	echo "
		<tr>
			<td class='mytablesubheader' width='280'>" . GOOGLE_APPS_LANG_INTERNET_DOMAIN . "</td>
			<td><input name='domain' value='$domain'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' width='280'>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input name='password' value='$password'></td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

// vi: syntax=php ts=4
?>
