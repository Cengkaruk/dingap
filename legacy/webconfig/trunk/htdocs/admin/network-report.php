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
require_once("../../api/File.class.php");
require_once("../../api/Folder.class.php");
require_once("../../api/Mrtg.class.php");
require_once("../../api/Syswatch.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

$style = "
	<style type='text/css'>
		div.graph {
			margin: 30px 0;
			text-align: center;
		}
		div.graph img {
			margin: 1px 0;
		}
		div.graph table, div#legend table {
			font-size: .8em;
			margin: 0 auto;
		}
		div.graph table td {
			padding: 0 10px;
			text-align: right;
		}
		div table .in th, div table td span.in {
			color: #00cc00;
		}
		div table .out th, div table td span.out {
			color: #0000ff;
		}
		div#legend th {
			text-align: right;
		}
	</style>
";

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $style);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mrtg.png", WEB_LANG_PAGE_INTRO);

if (isset($_REQUEST['stat'])) {
	$stat = $_REQUEST['stat'];
} else {
	try {
		$syswatch = new Syswatch();
		$wans = $syswatch->GetInUseExternalInterfaces();
		$stat = empty($wans) ? 'tcp' : "net_" . $wans[0];
	} catch (Exception $e) {
		$stat = 'net_eth0';
	}
}
	
$file = new File(Mrtg::PATH_DATA . "/tcp.html");

if ($file->Exists()) {
	DisplayMenu($stat);
	DisplayStats($stat);
} else {
	WebDialogWarning(WEB_LANG_NO_STATS);
}

WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayMenu
//
///////////////////////////////////////////////////////////////////////////////

function DisplayMenu($statistic)
{
	// Network stats
	//--------------

	$statfiles = array();

	try {
		$folder = new Folder(Mrtg::PATH_DATA);
		if (! $folder->Exists())
			return;
		$statfiles = $folder->GetListing();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$options = "";

	foreach ($statfiles as $stat) {
		if (preg_match("/^(net_.*)\.log$/", $stat, $match)) {

			$url = $match[1];

			// Skip virtual interfaces (not supported)
			if (preg_match("/[:\.]/", $url))
				continue;

			$show = preg_replace("/net_/", "", $url);

			$selected = ($url == $statistic) ? "selected" : "";
			$options .= "<option value='$url' $selected>" . WEB_LANG_NETWORK_INTERFACE . " - $show</option>\n";
		}
	}

	$selected['tcp'] = "";
	$selected[$statistic] = "selected";

	$options .= "<option value='tcp' " . $selected['tcp'] . ">" . WEB_LANG_OPEN_CONNECTIONS . "</option>";

	$optionbox = "<select name='stat'>$options</select> " . WebButtonSelect("Select");

	// Help blurb
	//-----------

	if ($statistic == "tcp") 
		$blurb = WEB_LANG_OPEN_TCP_NOTE;
	else if (preg_match("/net_/", $statistic)) 
		$blurb = WEB_LANG_NETWORK_NOTE;

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(LOCALE_LANG_SELECT, "100%");
	echo "
	  <tr>
	    <td nowrap valign='middle' align='center'>$optionbox</td>
	    <td width='5'>&#160; </td>
	    <td class='help' width='400' valign='top'>$blurb</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayStats()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStats($statfile)
{
	// Security check
	//---------------

	if ((preg_match("/\.\./", $statfile)) || preg_match("/\//", $statfile))
		return;

	// Grab the HTML
	//--------------

	$contents = "";

	try {
		$file = new File(Mrtg::PATH_DATA . "/$statfile.html");
		$contents = $file->GetContents();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Get rid of header line
	$contents = preg_replace("/<h3>.*<\/h3>/si", "", $contents);

	// Remove header and footer
	$contents = preg_replace("/.*<\/strong>/si", "", $contents);
	$contents = preg_replace("/<\/body>\s*<\/html>.*/si", "", $contents);

	// Use our image wrapper
	$_SESSION['getimage_path'] = Mrtg::PATH_DATA;
	$time = time();
	$contents = preg_replace("/\s*src=./si", " src=\"/include/getimage.php?time=$time&amp;source=", $contents);

	echo $contents;
}

// vim: syntax=php ts=4
?>
