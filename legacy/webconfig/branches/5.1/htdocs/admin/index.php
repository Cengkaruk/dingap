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
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-intro.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (file_exists("../../api/ClearSdnService.class.php")) {
	require_once("../../api/ClearSdnService.class.php");
	echo "<script type='text/javascript' src='/templates/standard-5.1/js/jquery/jquery.jqplot.min.js'></script>
	<script type='text/javascript' src='/templates/standard-5.1/js/jquery/jqplot.barRenderer.min.js'></script>
	<script type='text/javascript' src='/templates/standard-5.1/js/jquery/jqplot.pieRenderer.min.js'></script>
	<!--[if IE]><script type='text/javascript' src='/templates/standard-5.1/js/jquery/excanvas.min.js'></script><![endif]-->\n
	";

	$sdn = new ClearSdnService();
	echo "<table width='100%'>";
	echo "<tr>";
	echo "<td width='50%' valign='top'>";
	DisplaySystemOverview();
	DisplayInterfaces();
	echo "</td>";
	echo "<td width='50%' valign='top'>";
	WebTableOpen(CLEARSDN_SERVICE_LANG_SERVICES_TITLE, "100%", "clearsdn-alerts");
	echo "<tr id='clearsdn-splash'><td align='center'><img src='/images/icon-os-to-sdn.png' alt=''><div id='whirly' style='padding: 10 0 10 0'>" . WEBCONFIG_ICON_LOADING . "</div></td></tr>";
	WebTableClose("100%");
	echo "</td>";
	echo "</tr>";
	echo "</table>";
} else {
	DisplaySystemOverview();
	DisplayInterfaces();
}

// E-mail dashboard
if (file_exists("../../gui/PostfixReport.class.php")) {
	require_once("../../gui/PostfixReport.class.php");
	try {
		$report = new PostfixReport(PostfixReport::TIME_TODAY);
		$report->GetDashboardSummary();
	} catch (Exception $e) {
		// Do nothing
	}
}

// Intrusion prevention dashboard
if (file_exists("../../gui/SnortSamReport.class.php")) {
	require_once("../../gui/SnortSamReport.class.php");
	try {
		$report = new SnortSamReport();
		$report->GetDashboardSummary();
	} catch (Exception $e) {
		// Do nothing
	}
}

// PPTP dashboard
if (file_exists("../../gui/PptpdReport.class.php")) {
	require_once("../../gui/PptpdReport.class.php");
	try {
		$report = new PptpdReport();
		$report->GetDashboardSummary();
	} catch (Exception $e) {
		// Do nothing
	}
}

WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySystemOverview()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySystemOverview()
{
	$tablerow = "";

	// System date and timezone
	//-------------------------

	if (file_exists("../../api/Time.class.php")) {
		require_once("../../api/Time.class.php");

		try {
			$time = new Time();
			$timezone = $time->GetTimezone();
			date_default_timezone_set($timezone);
		} catch (Exception $e) {
			// Do nothing
		}

		$thedate = strftime("%b %e %Y");
		$thetime = strftime("%T %Z");

		$tablerow .= "
		  <tr>
			<td class='mytablesubheader' nowrap>" . TIME_LANG_TIME . "</td>
			<td nowrap>$thedate $thetime ($timezone)</td>
			<td nowrap>" . WebUrlJump("/admin/date.php", LOCALE_LANG_EDIT) . "</td>
		  </tr>
		";
	}


	// Locale
	//-------

	if (file_exists("../../api/Locale.class.php")) {
		require_once("../../api/Locale.class.php");

		try {
			$mylocale = new Locale();
			$langcode = $mylocale->GetLanguageCode();
			$languages = $mylocale->GetLanguageInfo();

			foreach ($languages as $language) {
				if ($language["code"] == $langcode)
					$langdesc = $language["description"];
			}

			$tablerow .= "
			  <tr>
				<td class='mytablesubheader' nowrap>" . LOCALE_LANG_LANGUAGE . "</td>
				<td nowrap>$langdesc - $langcode</td>
				<td nowrap>" . WebUrlJump("/admin/language.php", LOCALE_LANG_EDIT) . "</td>
			  </tr>
			";
		} catch (Exception $e) {
			// Do nothing
		}
	}

	// User info
	//----------

	if (file_exists("../../api/UserManager.class.php")) {
		require_once("../../api/UserManager.class.php");

		try {
			$usermanager = new UserManager();
			$alluserinfo = $usermanager->GetAllUsers();
			$usercount = 0;
			foreach ($alluserinfo as $userinfo)
				$usercount++;

			$tablerow .= "
			  <tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_NUMBER_OF_USERS . "</td>
				<td nowrap>$usercount</td>
				<td nowrap>" . WebUrlJump("/admin/users.php", LOCALE_LANG_EDIT) . "</td>
			  </tr>
			";
		} catch (Exception $e) {
			// Do nothing
		}
	}

	WebTableOpen(WEB_LANG_SYSTEM_OVERVIEW, "100%");
	echo $tablerow;
	WebTableClose("100%");
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayInterfaces()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayInterfaces()
{
	if (!  file_exists("../../api/IfaceManager.class.php"))
		return;

	require_once("../../api/IfaceManager.class.php");

	try {
		$interfaces = new IfaceManager();
		$ethlist = $interfaces->GetInterfaceDetails();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$ethsummary = "";

	foreach ($ethlist as $eth => $info) {

		// Skip interfaces used 'indirectly' (e.g. PPPoE, bonded interfaces)
		if (isset($info['master']))
			continue;

		// Skip 1-to-1 NAT interfaces
		if (isset($info['one-to-one-nat']) && $info['one-to-one-nat'])
			continue;

		// Skip non-configurable interfaces
		if (! $info['configurable'])
			continue;

		// Create summary
		//---------------

		$ip = isset($info['address']) ? $info['address'] : "";
		$speed = (isset($info['speed']) && $info['speed'] > 0) ? $info['speed'] . " " . LOCALE_LANG_MEGABITS : "";
		$role = isset($info['role']) ? $info['role'] : "";
		$roletext = isset($info['roletext']) ? $info['roletext'] : "";
		$typetext = isset($info['typetext']) ? $info['typetext'] : "";
		$bootproto = isset($info['ifcfg']['bootprototext']) ? $info['ifcfg']['bootprototext'] : "";

		if (isset($info['link'])) {
			if ($info['link'] == -1)
				$link = "";
			else if ($info['link'] == 0)
				$link = LOCALE_LANG_NO;
			else
				$link = LOCALE_LANG_YES;
		} else {
			$link = "";
		}

		$ethsummary .= "
		  <tr>
			<td>$eth</td>
			<td>$roletext <input type='hidden' name='role' value='$role' /></td>
			<td>$typetext</td>
			<td>$bootproto</td>
			<td>$ip</td>
			<td>$link</td>
			<td>$speed</td>
		  </tr>
		";
	}

	WebTableOpen(IFACE_LANG_INTERFACE, "100%");
	WebTableHeader("|" . FIREWALL_LANG_ROLE . "|" . IFACE_LANG_TYPE .
			"|" . IFACE_LANG_BOOTPROTO . "|" . NETWORK_LANG_IP .
			"|" . IFACE_LANG_LINK . "|" . IFACE_LANG_SPEED);
	echo $ethsummary;
	WebTableClose("100%");
}
// vim: syntax=php ts=4
?>
