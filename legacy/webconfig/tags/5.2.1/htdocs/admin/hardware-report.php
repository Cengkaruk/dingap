<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003 Point Clark Networks.
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
require_once("../../gui/ScreenScraper.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));


///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-status.png", WEB_LANG_PAGE_INTRO);
WebDialogWarning(WEB_LANG_MEMORY_USAGE_TIP);
DisplayStatus();
WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayStatus
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStatus()
{
	$html = new ScreenScraper();
	$locale = new Locale();
	$body = "";

	try {
		$code = $locale->GetLanguageCodeSimple();
		$longcode = $locale->GetLanguageCode();

		// KLUDGE: pt_BR is br in phpSysinfo land
		if ($longcode == "pt_BR")
			$code = "br";

		$body = $html->GetBody("https://localhost:81/include/phpsysinfo/index.php", "template=aq&lng=$code", "status.php");

	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Get rid of header line
	$body = preg_replace("/<h1>.*<\/h1>/si", "", $body);

	// Fix graphics path
	$body = preg_replace("/url\(templates/si", "url(/include/phpsysinfo/templates", $body);

	// Remove empty tags
	$body = preg_replace("/<center><\/center>/si", "", $body);

	// Reduce font size
	$body = preg_replace("/\"-1/si", "\"-2", $body);

	// Change the localhost IP to something more useful
	$body = preg_replace("/127.0.0.1/", getenv("SERVER_ADDR"), $body);

	// Add nowrap to table rows 
	$body = preg_replace("/<td align=.left. valign=.top.>/", "<td nowrap align='left' valign='top'>", $body);

	echo $body;
	echo "<br /><br />";
}

?>
