<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
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
require_once("../../api/Network.class.php");
require_once("../../api/Squid.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

WebHeader(WEB_LANG_PAGE_TITLE, "splash");
WebDialogInfo(WEB_LANG_PAGE_HELP);
DisplayWarning();
WebFooter("splash");

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayWarning()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayWarning() 
{
	$network = new Network(); // locale
	$squid = new Squid(); // locale

	$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
	$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
	$ip = isset($_REQUEST['ip']) ? $_REQUEST['ip'] : '';
	$ftpreply = isset($_REQUEST['ftpreply1']) ? $_REQUEST['ftpreply1'] : '';

	// Pull in localized warning message
	//----------------------------------

	$langtag = 'SQUID_LANG_ERRMSG_' . $code;
	$warning = defined($langtag) ? constant($langtag) : LOCALE_LANG_UNKNOWN;

	// Add FTP server reply if this was an FTP request
	//------------------------------------------------

	if (preg_match("/^ftp:\/\//", $url)) {
		$ftpwarning = "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_FTP_WARNING . "</td>
				<td>$ftpreply</td>
			</tr>
		";
	} else {
		$ftpwarning = "";
	}

	if ($ip) {
		$iprow = "
			<tr>
				<td class='mytablesubheader' nowrap>" . NETWORK_LANG_IP . "</td>
				<td>$ip</td>
			</tr>
		";
	} else {
		$iprow = "";
	}

	WebTableOpen(WEB_LANG_DETAILS, "600");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_URL . "</td>
			<td><a href='$url'>$url</a></td>
		</tr>
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . WEB_LANG_WARNING_MESSAGE . "</td>
			<td>$warning</td>
		</tr>
		$ftpwarning
		$iprow
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_CONNECTION_STATUS . "</td>
			<td><span id='wanstatus'>" . WEBCONFIG_ICON_LOADING . "</span></td>
		</tr>
	";
	WebTableClose("600");
}

// vim: syntax=php ts=4
?>
