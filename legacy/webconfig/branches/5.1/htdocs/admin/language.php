<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2009 Point Clark Networks.
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
require_once("language.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////
//
// We handle the update here before anything is sent to the web browser.
// This lets us reload the page with the new language settings (which are
// set in the above "require_once" section).
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

$locale = new Locale();
$exceptionmsg = "";

try {
	if (isset($_POST['SetLocale'])) {
		$locale->SetLocale($_POST['langcode']);
		WebSetSession();
		WebForwardPage("/admin/language.php?forcereload=yes"); // Reload page for new locale tags
	}
} catch (Exception $e) {
	$exceptionmsg = $e->GetMessage();
}

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-language.png", WEB_LANG_PAGE_INTRO);
if ($exceptionmsg)
	WebDialogWarning($exceptionmsg);
DisplayLanguage();
WebFooter();

// vim: syntax=php ts=4
?>
