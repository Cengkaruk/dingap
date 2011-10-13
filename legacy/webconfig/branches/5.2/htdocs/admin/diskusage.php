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
require_once("../../api/Philesight.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

// Initialize common variables
$cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '';
$path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '/';


$ps = new Philesight();

// Handle image request
//---------------------

if ($cmd === 'img') {
	$png = $ps->GetImage($path);
	header("Content-type: image/png");
	echo $png;
	return;
}

// Handle image map query
//-----------------------

$matches = array();

if (preg_match('/\?(\d+),(\d+)/', $_SERVER['QUERY_STRING'], $matches))
	$path = $ps->GetPath($path, $matches[1], $matches[2]);

// Show warning if no data is available
//-------------------------------------

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-diskusage.png", WEB_LANG_PAGE_INTRO);

if (! $ps->Initialized()) {
	WebDialogWarning(WEB_LANG_DISK_USAGE_NOT_YET_AVAILABLE);
} else {
	DisplayUsage($path);
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUsage
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUsage($path)
{
	echo "<p><a href='/admin/diskusage.php?path=$path&amp;'>";
	echo "<img width='650' height='650' src='/admin/diskusage.php?cmd=img&amp;path=$path' ismap='ismap' alt='-' />";
	echo "</a></p>";
}

?>
