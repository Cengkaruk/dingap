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
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-diskusage.png", WEB_LANG_PAGE_INTRO);

// TODO: implement an API call instead of file_exists test. 
if (file_exists("/usr/webconfig/tmp/ps.db"))
	DisplayUsage();
else
	WebDialogWarning(WEB_LANG_DISK_USAGE_NOT_YET_AVAILABLE);

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUsage
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUsage()
{
	echo "<iframe style='border:none;' src='https://" . $_SERVER['HTTP_HOST'] . "/cgi/philesight.cgi' width='100%' height='800'>";
	echo "<p>" . WEB_LANG_IFRAME_NOT_SUPPORTED . "</p>";
	echo "</iframe>";
}

?>
