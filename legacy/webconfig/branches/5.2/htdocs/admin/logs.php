<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
require_once("../../api/Folder.class.php");
require_once("../../api/File.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-logs.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

$filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : ".*";
$logfile = isset($_REQUEST['logfile']) ? $_REQUEST['logfile'] : "system";
$allcolumns = isset($_REQUEST['allcolumns']) ? "checked" : "";

if (! preg_match('/^[a-zA-Z0-9_.!:#=\/\s\$\*\-]*$/', $filter)) {
	WebDialogWarning('Invalid filter.');
	$filter = '';
} else {
	DisplayVarlogs($logfile, $filter, $allcolumns);
}

WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayVarlogs
//
///////////////////////////////////////////////////////////////////////////////

function DisplayVarlogs($logfile, $filter, $allcolumns)
{
	$maxbytes = 512000;

	// Validation
	//-----------

	if (preg_match("/\.\./", $logfile) || preg_match("/^\//", $logfile))
		return;

	// Set target file
	//----------------

	$dir = "/var/log";
	$fullpath = $dir . "/" . $logfile;

	// Get log listing
	//----------------

	try {
		$folder = new Folder($dir, true);
		$options['follow_symlinks'] = TRUE;
		$files = $folder->GetRecursiveListing($options);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$loglist = "";

	foreach ($files as $file) {
		if (preg_match("/(sa\/)|(ssl_)|(snort\/)|(ksyms)|(lastlog)|(rpmpkgs)|(wtmp)|(gz$)/", $file))
			continue;

		if ($file == $logfile)
			$selected = "selected";
		else
			$selected = "";

		$pathregex = preg_quote($dir, "/");
		$filevalue = preg_replace("/$pathregex\//", "", $file);
		$loglist .= "<option $selected value='$filevalue'>$filevalue\n";
	}

	// Display the result
	//-------------------

	try {
		$target = new File($fullpath, true);
		$result = $target->GetSearchResults($filter, $maxbytes);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$contents = "";
	$resultsize = 0;

	foreach ($result as $line) {
		$resultsize += strlen($line);
		if ($allcolumns)
			$contents .= $line . "<br />";
		else
			$contents .= substr($line, 0, 120) . "... <br />";
	}

	if (!$contents)
		$contents = "<br /><p align='center'>...</p><br />";

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "100%");
	echo "
	  <tr>
		<td width='30%' class='mytablesubheader' nowrap> " . WEB_LANG_FILE . "</td>
		<td><select name='logfile'>$loglist</select></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_FILTER . "</td>
		<td><input type='text' name='filter' value='$filter' size='10' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_ALL_COLUMNS . "</td>
		<td><input type='checkbox' name='allcolumns' $allcolumns /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&#160; </td>
		<td>" . WebButton("Display", WEB_LANG_DISPLAY, WEBCONFIG_ICON_GO) ."</td>
	  </tr>
	";
	WebTableClose("100%");

	if ($resultsize > $maxbytes) {
		WebDialogWarning(WEB_LANG_MAX_LINES);
		$resultsize_output = "100%";
	} else {
		$resultsize_output = round($resultsize*100/$maxbytes) . "%";
	}

	WebTableOpen(WEB_LANG_DISPLAY, "100%");
	echo "
	  <tr>
		<td class='mytableheader'>" . WEB_LANG_SEARCH_RESULT_SIZE . " -- " . $resultsize_output . "</td>
	  </tr>
	  <tr>
		<td class='small'>$contents</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
