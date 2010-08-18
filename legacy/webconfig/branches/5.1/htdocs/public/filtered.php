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
require_once(GlobalGetLanguageTemplate(__FILE__));

$url = isset($_REQUEST['DENIEDURL']) ? $_REQUEST['DENIEDURL'] : "";
$rawreason = isset($_REQUEST['REASON']) ? $_REQUEST['REASON'] : "";

// URL
//----

$url = preg_replace("/&/", "&amp;", $url);

if (strlen($url) > 60)
	$showurl = substr($url, 0, 60) . " ... ";
else
	$showurl = $url;

// Reason
//-------

// Format is -- <reason title>:<reason summary> (details)
list($reason, $details) = split("\(", $rawreason, 2);
list($title, $summary) = split(":", $reason, 2);

if (is_numeric(trim($summary)))
	$summary = "page score: $summary";

// Reformat the details section
$details = trim($details);
$details = preg_replace("/\(-/", "-(", $details);

if ($details && !preg_match("/^[\+\-]/", $details))
	$details = "&gt $details";

$details = preg_replace("/\+-/", "<br />~ ", $details);
$details = preg_replace("/-/", "<br />- ", $details);
$details = preg_replace("/\+/", "<br />+ ", $details);
$details = trim($details);
$details = preg_replace("/\)$/", "", $details);

if (! empty($summary))
	$title = "$title -- $summary";

// HTML
//-----

if (empty($details)) {
	$detailshtml = "";
} else {
	$detailshtml = "
        <tr>
            <td class='mytablesubheader' nowrap>&nbsp; </td>
            <td>$details</td>
        </tr>
	";
}

if (file_exists('filtered.inc.php')) {
	include('filtered.inc.php');
} else {
	WebHeader(WEB_LANG_PAGE_TITLE, "splash");
	WebDialogWarning(WEB_LANG_PAGE_INTRO);
    WebTableOpen(WEB_LANG_PAGE_TITLE, "600");
    echo "
        <tr>
            <td class='mytablesubheader' nowrap width='200'>" . LOCALE_LANG_URL . "</td>
            <td><a href='$url'>$showurl</a></td>
        </tr>
        <tr>
            <td width='200' class='mytablesubheader' nowrap>" . LOCALE_LANG_DESCRIPTION . "</td>
            <td>$title</td>
        </tr>
		$detailshtml
    ";
    WebTableClose("600");
	WebFooter("splash");
}

// vim: ts=4
?>
