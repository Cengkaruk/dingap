<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2009 Point Clark Networks.
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
require_once("../../api/ClearSdnService.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $style);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-dynamic-dns.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['update'])) {
	try {
		$sdn = new ClearSdnService();
		$sdn->SetDynamicDnsSettings((int)$_POST['enabled'], $_POST['subdomain'], $_POST['domain'], $_POST['ip']); 
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayDetails();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDetails()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDetails()
{
	$sdn = new ClearSdnService();
	WebFormOpen($_SERVER['PHP_SELF'], "post", "dynamic-dns");
	WebTableOpen(CLEARSDN_SERVICE_LANG_OVERVIEW, "100%", "clearsdn-overview");
	echo "
		<tr id='clearsdn-splash'>
		<td align='center'><img src='/images/icon-os-to-sdn.png' alt=''>
		<div id='whirly' style='padding: 10 0 10 0'>" . WEBCONFIG_ICON_LOADING . "</div>
		</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();

	WebTableOpen(CLEARSDN_SERVICE_LANG_HISTORY, "100%", "clearsdn-logs");
	echo "<tr id='clearsdn-nodata'><td align='center' colspan='3'>" . CLEARSDN_SERVICE_LANG_NO_DATA . "</td></tr>";
	WebTableClose("100%");
}

// vim: syntax=php ts=4
?>
