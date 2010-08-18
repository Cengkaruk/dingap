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
require_once("../../api/Daemon.class.php");
require_once("../../api/DaemonManager.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-daemon.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

try {
	if (isset($_POST['StopService'])) {
		$daemon = new Daemon(key($_POST['StopService']));
		$daemon->SetRunningState(false);
		sleep(3);
	} else if (isset($_POST['StartService'])) {
		$daemon = new Daemon(key($_POST['StartService']));
		$daemon->SetRunningState(true);
		sleep(3);
	} else if (isset($_POST['DisableBoot'])) {
		$daemon = new Daemon(key($_POST['DisableBoot']));
		$daemon->SetBootState(false);
	} else if (isset($_POST['EnableBoot'])) {
		$daemon = new Daemon(key($_POST['EnableBoot']));
		$daemon->SetBootState(true);
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayServices();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayServices()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayServices()
{
	try {
		$daemoninfo = new DaemonManager();
		$statusdata = $daemoninfo->GetStatusData();
		$metadata = $daemoninfo->GetMetaData();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$standardrows = array();
	$corerows = array();
	$nonstandardrows = array();

	foreach ($statusdata as $initd => $details) {
		if (! $statusdata[$initd]["installed"])
			continue;

		// Do not show the "kernel" daemons (they are not really daemons)
		if ($statusdata[$initd]["processname"] == "kernel")
			continue;

		// Do not show certain daemons
		if (
			($initd == "dovecot") ||
			($initd == "spamassassin") ||
			($initd == "webconfig")
			)
			continue;

		// Do not show clamd if mail antivirus is not installed  TODO: clean this up
		if (($initd == "clamd") && !file_exists("mail-antivirus.php"))
			continue;

		// Running status and onboot
		if ($statusdata[$initd]["running"])
			$status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
		else
			$status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";

		if ($statusdata[$initd]["onboot"])
			$onboot = "<span class='ok'><b>" . DAEMON_LANG_AUTOMATIC . "</b></span>";
		else
			$onboot = "<span class='alert'><b>" . DAEMON_LANG_MANUAL . "</b></span>";

		// Action
		if (isset($metadata[$initd]["url"])) {
			$action = WebUrlJump($metadata[$initd]["url"], LOCALE_LANG_CONFIGURE);
		} else {
			if ($statusdata[$initd]["running"])
				$action = WebButtonToggle("StopService[$initd]", DAEMON_LANG_STOP);
			else
				$action = WebButtonToggle("StartService[$initd]", DAEMON_LANG_START);

			if ($statusdata[$initd]["onboot"])
				$action .= WebButtonToggle("DisableBoot[$initd]", DAEMON_LANG_TO_MANUAL);
			else
				$action .= WebButtonToggle("EnableBoot[$initd]", DAEMON_LANG_TO_AUTO);
		}

		$therow = "
		  <tr>
			<td nowrap width='225'>" . $statusdata[$initd]["description"] . "</td>
			<td nowrap>$status</td>
			<td nowrap>$onboot</td>
			<td nowrap>$action</td>
		  </tr>
		";

		if (isset($metadata[$initd]["core"]) && $metadata[$initd]["core"])
			$corerows[] = $therow;
		else if (isset($metadata[$initd]["url"]))
			$standardrows[] = $therow;
		else
			$nonstandardrows[] = $therow;
	}

	sort($standardrows);
	sort($corerows);
	sort($nonstandardrows);

	$standard_output = "";
	foreach($standardrows as $row)
		$standard_output .= $row;

	$core_output = "";
	foreach($corerows as $row)
		$core_output .= $row;

	$nonstandard_output = "";
	foreach($nonstandardrows as $row)
		$nonstandard_output .= $row;

	if ($standard_output) {
		WebFormOpen();
		WebTableOpen(WEB_LANG_STANDARD_SERVICES, "100%");
		WebTableHeader(DAEMON_LANG_SERVICE . "|" . DAEMON_LANG_STATUS . "|" . DAEMON_LANG_ONBOOT . "|");
		echo $standard_output;
		WebTableClose("100%");
		WebFormClose();
	}

	if ($core_output) {
		WebFormOpen();
		WebTableOpen(WEB_LANG_CORE_SERVICES, "100%");
		WebTableHeader(DAEMON_LANG_SERVICE . "|" . DAEMON_LANG_STATUS . "|" . DAEMON_LANG_ONBOOT . "|");
		echo $core_output;
		WebTableClose("100%");
		WebFormClose();
	}

	if ($nonstandard_output) {
		WebFormOpen();
		WebTableOpen(WEB_LANG_NONSTANDARD_SERVICES, "100%");
		WebTableHeader(DAEMON_LANG_SERVICE . "|" . DAEMON_LANG_STATUS . "|" . DAEMON_LANG_ONBOOT . "|");
		echo $nonstandard_output;
		WebTableClose("100%");
		WebFormClose();
	}
}

// vim: syntax=php ts=4
?>
