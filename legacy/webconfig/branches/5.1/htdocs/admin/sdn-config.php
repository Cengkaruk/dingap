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
require_once("../../gui/Charts.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-sdn-config.png", WEB_LANG_PAGE_INTRO);


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['cookie_reset'])) {
	unset($_SESSION['clearsdn_cookie']);
} else if (isset($_POST['service_reset'])) {
	try {
		require_once("../../api/ClearSdnService.class.php");
		$service = new ClearSdnService();
		$service->DeleteCache();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['store_reset'])) {
	try {
		require_once("../../api/ClearSdnStore.class.php");
		$store = new ClearSdnStore();
		$store->DeleteCache();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayOverview();

///////////////////////////////////////////////////////////////////////////////
//
// DisplayOverview()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayOverview()
{
	$service_stats = null;
	$store_stats = null;
	if (file_exists("../../api/ClearSdnService.class.php")) {
		require_once("../../api/ClearSdnService.class.php");
		$service = new ClearSdnService();
		$service_stats = $service->GetCacheStats();
	}
	if (file_exists("../../api/ClearSdnStore.class.php")) {
		require_once("../../api/ClearSdnStore.class.php");
		$store = new ClearSdnStore();
		$store_stats = $store->GetCacheStats();
	}
	echo "<table width='100%'>";
	echo "<tr>";
	echo "<td width='50%' valign='top'>";
	try {
		WebFormOpen();
		WebTableOpen(WEB_LANG_WEB_SERVICES, "100%");
		echo "<tr>";
		echo "<td colspan='3' class='mytableheader'>" . WEB_LANG_SDN_COOKIES . "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='mytablesubheader' width='50%'>" . WEB_LANG_COOKIES . "</td>";
		echo "<td width='5%'>" . count($_SESSION['clearsdn_cookie']) . "</td>";
		echo "<td width='45%'>" . (count($_SESSION['clearsdn_cookie']) > 0 ? WebButtonReset('cookie_reset') : "&nbsp;") . "</td>";
		echo "<tr>";
		echo "<td colspan='3' class='mytableheader'>" . WEB_LANG_CACHE . "</td>";
		echo "</tr>";
		if (is_array($service_stats)) {
			echo "<tr>";
			echo "<td class='mytablesubheader'>" . WEB_LANG_SERVICE_CACHE . "</td>";
			echo "<td>" . count($service_stats) . "</td>";
			echo "<td>" . (count($service_stats) > 0 ? WebButtonReset('service_reset') : "&nbsp;") . "</td>";
			echo "</tr>";
		}
		if (is_array($store_stats)) {
			echo "<tr>";
			echo "<td class='mytablesubheader'>" . WEB_LANG_STORE_CACHE . "</td>";
			echo "<td>" . count($store_stats) . "</td>";
			echo "<td>" . (count($store_stats) > 0 ? WebButtonReset('store_reset') : "&nbsp;") . "</td>";
			echo "</tr>";
		}
		WebTableClose("100%");
		WebFormClose();
	echo "<script type='text/javascript' src='/templates/standard-5.1/js/jquery/jquery.jqplot.min.js'></script>
	<script type='text/javascript' src='/templates/standard-5.1/js/jquery/jqplot.barRenderer.min.js'></script>
	<script type='text/javascript' src='/templates/standard-5.1/js/jquery/jqplot.pieRenderer.min.js'></script>
	<!--[if IE]><script type='text/javascript' src='/templates/standard-5.1/js/jquery/excanvas.min.js'></script><![endif]-->\n
	";

	echo "</td>";
	echo "<td width='50%' valign='top'>";
	WebTableOpen(CLEARSDN_SERVICE_LANG_SERVICES_TITLE, "100%", "clearsdn-alerts");
	echo "<tr id='clearsdn-splash'><td align='center'><img src='/images/icon-os-to-sdn.png' alt=''><div id='whirly' style='padding: 10 0 10 0'>" . WEBCONFIG_ICON_LOADING . "</div></td></tr>";
	WebTableClose("100%");
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

// vim: syntax=php ts=4
?>
