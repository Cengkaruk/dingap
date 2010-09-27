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
require_once("../../api/Os.class.php");
require_once("../../api/Register.class.php");
require_once("../../api/SoftwareUpdate.class.php");
require_once("../../api/SoftwareUpdates.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUpdateList()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUpdateLists($type, $description)
{
	$swlist = array();

	try {
		$software_update = new SoftwareUpdate();
		$software_ws = new SoftwareUpdates();

		// Bail right away if rpm is busy
		if ($software_update->IsBusy()) {
			WebDialogWarning(SOFTWAREUPDATE_LANG_ERRMSG_IN_PROGRESS);
			return;
		}

		$swlist = $software_ws->GetSoftwareUpdates();
	} catch (WebServicesNotRegisteredException $e) {
		$register = new Register(); // just for locale
		WebDialogWarning($e->GetMessage() . " - " . WebUrlJump("register.php", REGISTER_LANG_REGISTER));
		return;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$available = "";
	$available_contrib = "";
	$installed = "";

	ksort($swlist);

// fun
// contrib
// critical
// recommened
	foreach ($swlist as $software) {
		// KLUDGE -- processing contribs and official modules on same screen
		if ($software['type'] == SoftwareUpdates::TYPE_CONTRIB)
			$software['type'] = SoftwareUpdates::TYPE_MODULE;

		// Only list software for our desired type (critical, recommended, etc.).
		if ($software["type"] != $type) {
			continue;

		// Skip packages that are newer (e.g. a user has a more up-to-date rpm)
		} else if (($software["rpmcheck"] == SoftwareUpdates::CODE_OBSOLETE)) {
			if (($type == SoftwareUpdates::TYPE_MODULE) || ($type == SoftwareUpdates::TYPE_CONTRIB)) {
				$rpminfo = new Software($software["name"]);
				$install = strftime("%x", $rpminfo->GetInstallTime());
				$newversion = $rpminfo->GetVersion();
				$newrelease = $rpminfo->GetRelease();

				$installed .= "
					<tr>
						<td>" . $software["summary"] . "</td>
						<td nowrap>" . $newversion . "-" . $newrelease . "</td>
						<td nowrap>" . SOFTWAREUPDATE_LANG_MANUALLY_UPGRADED . "</td>
						<td>$install</td>
					</tr>
				";
			} else {
				continue; 
			}

		// Show installed package
		} else if ($software["rpmcheck"] == SoftwareUpdates::CODE_OK) {
			$rpminfo = new Software($software["name"], $software["version"], $software["release"]);
			$install = strftime("%x", $rpminfo->GetInstallTime());

			$installed .= "
				<tr>
					<td>" . $software["summary"] . "</td>
					<td nowrap>" . $software["version"] . "-" . $software["release"] . "</td>
					<td nowrap>" . strftime("%x", $software["date"]) . "</td>
					<td>$install</td>
				</tr>
			";

		// Show installed modules, but list any that require an update
		} else if ( ($software["rpmcheck"] == SoftwareUpdates::CODE_REQUIRED) &&
			  (($type == SoftwareUpdates::TYPE_MODULE) || ($type == SoftwareUpdates::TYPE_CONTRIB))) {

			$installed .= "
				<tr>
					<td>" . $software["summary"] . "</td>
					<td nowrap>" . $software["version"] . "-" . $software["release"] . "</td>
					<td nowrap>" . strftime("%x", $software["date"]) . "</td>
					<td><span class='alert'>" . SOFTWAREUPDATE_LANG_UPDATE_AVAILABLE . "<span></td>
				</tr>
			";

		} else if ( ($software["rpmcheck"] == SoftwareUpdates::CODE_REQUIRED) ||
			  ($software["rpmcheck"] == SoftwareUpdates::CODE_NOTINSTALLED) && ($type == SoftwareUpdates::TYPE_MODULE) ||
			  ($software["rpmcheck"] == SoftwareUpdates::CODE_NOTINSTALLED) && ($type == SoftwareUpdates::TYPE_CONTRIB))
		{
			$vendor = empty($software["vendor_name"]) ? "ClearFoundation" : $software["vendor_name"];

			$row = "
				<tr>
					<td>" . $software["summary"] . "</td>
					<td width='250'>" . $vendor . "</td>
					<td width='100'>" . $software["version"] . "-" . $software["release"] . "</td>
					<td width='100'>" . strftime("%x", $software["date"]) . "</td>
					<td width='40'><b><input type='checkbox' name='packagelist[$software[name]]' value='" . $software["summary"] . "' /></b></td>
				</tr>
			";

			if (empty($software["vendor_name"]))
				$available .= $row;
			else
				$available_contrib .= $row;
		}

	}

	if (!$installed)
		$installed = "<tr><td colspan='5' align='center'>" . SOFTWAREWEBSERVICE_LANG_ERRMSG_NOTHING_INSTALLED . "</td></tr>";

	if (!$available) {
		$available = "<tr><td colspan='5' align='center'>" . SOFTWAREWEBSERVICE_LANG_ERRMSG_NOTHING_AVAILABLE . "</td></tr>";
	} else {
		$available .= "
		  <tr>
		    <td colspan='4'>&#160;</td>
		    <td>". WebButtonGo("DisplayUpdateSelection") . "</td>
		  </tr>
		";
	}

	WebFormOpen();
	WebTableOpen($description, "100%");
	WebTableHeader(SOFTWAREUPDATE_LANG_DESC . "|Vendor|" . SOFTWAREUPDATE_LANG_VERSION . "|" . SOFTWAREUPDATE_LANG_RELEASED . "|");
	echo $available;
	WebTableClose("100%");
	WebFormClose();

	if (! empty($available_contrib)) {
		WebFormOpen();
		WebTableOpen("Third Party", "100%");
		WebTableHeader(SOFTWAREUPDATE_LANG_DESC . "|Vendor|" . SOFTWAREUPDATE_LANG_VERSION . "|" . SOFTWAREUPDATE_LANG_RELEASED . "|");
		echo $available_contrib;
		echo "
		  <tr>
		    <td colspan='4'>&#160;</td>
		    <td>". WebButtonGo("DisplayUpdateSelection") . "</td>
		  </tr>
		";
		WebTableClose("100%");
		WebFormClose();
	}


	WebTableOpen(SOFTWAREUPDATE_LANG_INSTALLATION_HISTORY, "100%");
	WebTableHeader(SOFTWAREUPDATE_LANG_DESC . "|" . SOFTWAREUPDATE_LANG_VERSION . "|" . SOFTWAREUPDATE_LANG_RELEASED . "|" . SOFTWAREUPDATE_LANG_INSTALLED);
	echo $installed;
	WebTableClose("100%");
}

///////////////////////////////////////////////////////////////////////////////
//
// HandleSoftwareUpdate()
//
///////////////////////////////////////////////////////////////////////////////

function HandleSoftwareUpdate($type, $description, $post)
{
	if (isset($post['DisplayUpdateSelection'])) {
		if ($post['packagelist']) {

			$packageinfo = "";
			$packagelist = "";

			foreach ($post['packagelist'] as $packagename => $packagedesc) {
				$packageinfo .= "$packagedesc<br>";
				$packagelist .= " $packagename";
			}

			WebFormOpen();
			WebTableOpen(WEB_LANG_SOFTWARE_INSTALL_TITLE, 500);
			echo "
			  <tr>
				<td align='center'><br />" . LOCALE_LANG_CONFIRM . "<br /><b>" . $packageinfo . "</b></td>
			  </tr>
			  <tr>
				<td align='center'>
				  <input type='hidden' name='packagelist' value='" . $packagelist . "' />" . WebButtonGo("Install") . "<br /><br />
				</td>
			  </tr>
			";
			WebTableClose("500");
			WebFormClose();
		} else {
			WebDialogWarning(WEB_LANG_NONE_SELECTED);
			DisplayUpdateLists($type, $description);
		}

	// Perform install and show the log
	//---------------------------------

	} else if (isset($post['Install'])) {
		$updates = new SoftwareUpdate();
		if ($updates->IsBusy()) {
			WebDialogWarning(SOFTWAREUPDATE_LANG_ERRMSG_IN_PROGRESS);
			return;
		}

		DisplayLog();

		try {
			// TODO: rethink how to implement 3rd party apt/yum repositories.
			// The following is just a quick and safe way for IPlex in 4.x.
			// For security purposes, we cannot use form variables to pass apt/yum details.
			// Instead, we cycle through the software update list again to find the
			// appropriate apt/yum details for the given package to be installed.

			$software_ws = new SoftwareUpdates();
			$swlist = $software_ws->GetSoftwareUpdates();
			$package_array =  preg_split("/\s+/", trim($post['packagelist']));

			foreach ($swlist as $software) {
				if ( (!empty($software['vendor_code'])) && in_array($software["name"], $package_array)) {
					$aptvendor = empty($software['repo_vendor']) ? "" : '[' . $software['repo_vendor'] . '] ';
					$aptfile = new File("/etc/yum.repos.d/" . $software['vendor_code'] . "-addons.repo", true);

					if ($aptfile->Exists())
						$aptfile->Delete();

					$aptfile->Create("root", "root", "0600");
					$aptfile->AddLines(
						"[" . $software['vendor_code'] . "-addons]\n" .
						"name=" . $software['vendor_name'] . "\n" .
						"baseurl=" . $software['repo_protocol'] . "://" .
						$software['repo_username'] . ':' .  $software['repo_password'] . '@' . $software['repo_url'] . '/' .
						$software['repo_path'] . "\n" .
						"gpgcheck=" . $software['repo_check'] . "\n"
					);
				}
			}

			$updates->Install($post['packagelist'], true);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}

	// Set auto update settings
	//-------------------------

	} else if (isset($post['UpdateConfig'])) {

		$updates = new SoftwareUpdates();

		try {
			if ($post['state'] == LOCALE_LANG_ENABLED)
				$updates->SetAutoUpdateState($type, true);
			else
				$updates->SetAutoUpdateState($type, false);

			$updates->SetAutoUpdateTime(rand(1,5), rand(0,59));
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		if (($type == SoftwareUpdates::TYPE_RECOMMENDED) || ($type == SoftwareUpdates::TYPE_CRITICAL))
			DisplayAutoUpdate($type, $description);

		DisplayUpdateLists($type, $description);

	// Display the log
	//----------------

	} else if (isset($post['DisplayLog'])) {
		DisplayLog();

	// Show list of available updates
	//-------------------------------

	} else {
		if (($type == SoftwareUpdates::TYPE_RECOMMENDED) || ($type == SoftwareUpdates::TYPE_CRITICAL))
			DisplayAutoUpdate($type, $description);

		DisplayUpdateLists($type, $description);
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayLog()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLog()
{
	$update = new SoftwareUpdate();

	try {
		$logentries = $update->GetLog();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$logentries = array_reverse($logentries);
	$contents = "";
	$count = 0;

	foreach ($logentries as $entry) {
		$contents = $entry . "<br />" . $contents;
		$count++;
		if ($count >= 20)
			break;
	}

	WebTableOpen(WEB_LANG_INSTALL_LOG, "100%");
	echo "
		<tr>
			<td><span id='log_window'>...</span></td>
		</tr>
		<tr>
			<td><span id='log_status'>" . WEBCONFIG_ICON_LOADING . " &nbsp; " . LOCALE_LANG_PLEASE_WAIT . "</span></td>
		</tr>
		<tr>
			<td class='mytableheader'><span id='log_time'></span>&#160; </td>
		</tr>
	";
	WebTableClose("100%");
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAutoUpdate()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAutoUpdate($type, $description)
{
	// Check auto-update state
	//------------------------

	$auto = new SoftwareUpdates();
	$status = array();

	try {
		$state = $auto->GetAutoUpdateState($type);
		$updatetime = $auto->GetAutoUpdateTime();
		$status = $auto->GetSubscriptionStatus(true);
	} catch (WebServicesNotRegisteredException $e) {
		return;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if (isset($status['isenabled']) && (!$status['isenabled']))
		return;

	// Create drop-down boxes
	//-----------------------

	if ($state)
		$state = LOCALE_LANG_ENABLED;
	else 
		$state = LOCALE_LANG_DISABLED;

	$state_list = array(LOCALE_LANG_ENABLED, LOCALE_LANG_DISABLED);
	$time_list = array();
	for ($i = 0; $i < 24; $i++) 
		$time_list[] = "$i:00";

	// TODO: Move localization from softwareupdate to softwarewebservice
	WebFormOpen();
	WebTableOpen(SOFTWAREWEBSERVICE_LANG_AUTO_UPDATE, "70%");
	echo "
	  <tr>
	    <td align='right'>" . $description . "</td>
	    <td>" . WebDropDownArray("state", $state, $state_list) . "</td>
	    <td>" . WebButtonUpdate("UpdateConfig") . "</td>
	  </tr>
	";
	WebTableClose("70%");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
