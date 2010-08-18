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
//
// There is some logic in this page (e.g. printer handling, modes) that should
// probably get pushed into some other higher level class.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Daemon.class.php");
require_once("../../api/Nmbd.class.php");
require_once("../../api/Organization.class.php");
require_once("../../api/Samba.class.php");
require_once("../../api/Winbind.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-samba.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$services = array('smb', 'nmb', 'winbind');

$nmbd = new Nmbd();
$samba = new Samba();
$winbind = new Winbind();

try {
	if (isset($_POST['ChangeDaemon'])) {
		$service = key($_POST['ChangeDaemon']);

		if (in_array($service, $services)) {
			$daemon = new Daemon(key($_POST['ChangeDaemon']));

			if ($daemon->GetRunningState())
				$daemon->SetRunningState(false);
			else
				$daemon->SetRunningState(true);
		}
	} else if (isset($_POST['StartDaemon'])) {
		$nmbd->SetRunningState(true);
		$samba->SetRunningState(true);
		$winbind->SetRunningState(true);
		$nmbd->SetBootState(true);
		$samba->SetBootState(true);
		$winbind->SetBootState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$nmbd->SetRunningState(false);
		$samba->SetRunningState(false);
		$winbind->SetRunningState(false);
		$nmbd->SetBootState(false);
		$samba->SetBootState(false);
		$winbind->SetBootState(false);
	} else if (isset($_POST['ChangePassword'])) {
		$password = isset($_POST['password']) ? $_POST['password'] : "";
		$verify = isset($_POST['verify']) ? $_POST['verify'] : "";

		$user = new User("winadmin");

		if (! $user->IsValidPasswordAndVerify($password, $verify)) {
			$errors = $user->GetValidationErrors(true);
			$_REQUEST['DisplayPassword'] = "yes";
			WebDialogWarning($errors[0]);
		} else {
			$user->ResetPassword($password, $verify, $_SESSION['user_login']);
			// TODO: temporary workaround
			$samba->_CleanSecretsFile($password);
			
		}
	} else if (isset($_POST['InitializeSamba'])) {
		$domain = isset($_POST['domain']) ? $_POST['domain'] : "";
		$netbiosname = isset($_POST['netbiosname']) ? $_POST['netbiosname'] : "";
		$password = isset($_POST['password']) ? $_POST['password'] : "";
		$verify = isset($_POST['verify']) ? $_POST['verify'] : "";

		// KLUDGE: Pre-validate.  The normal validation routines on an update
		// allows for good data to get saved, but bad data to be rejected with
		// a warning message.  With a required password field, this behavior is not ideal.

		$errormessage = "";
		$user = new User("notused");

		if (! $samba->IsValidWorkgroup($domain)) {
			$errors = $samba->GetValidationErrors(true);
			$errormessage = $errors[0];
		}

		if (! $samba->IsValidNetbiosName($netbiosname)) {
			$errors = $samba->GetValidationErrors(true);
			$errormessage = empty($errormessage) ? $errors[0] : $errormessage . "<br>" . $errors[0];
		}

		// TODO: put this validation in the API?
		if (strtoupper($domain) == strtoupper($netbiosname)) {
			$myerror = SAMBA_LANG_ERRMSG_SERVER_NAME_AND_DOMAIN_DUPLICATE;
			$errormessage = empty($errormessage) ?  $myerror : $errormessage . "<br>" . $myerror;
		}

		if (! $user->IsValidPasswordAndVerify($password, $verify)) {
			$errors = $user->GetValidationErrors(true);
			$errormessage = empty($errormessage) ? $errors[0] : $errormessage . "<br>" . $errors[0];
		}

		if ($errormessage) {
			WebDialogWarning($errormessage);
		} else {
			if (! $samba->IsDirectoryInitialized())
				$samba->InitializeDirectory($domain);

			$samba->InitializeLocalSystem($netbiosname, $domain, $password);

			$domain = "";
			$netbiosname = "";
			$password = "";
			$verify = "";
		}

	} else if (isset($_POST['UpdateGlobalConfig'])) {
		$winsserver = (isset($_POST['winsserver'])) ? $_POST['winsserver'] : "";
		$winssupport = (isset($_POST['winssupport'])) ? (bool)$_POST['winssupport'] : false;
		$netbiosname = (isset($_POST['netbiosname'])) ? $_POST['netbiosname'] : "";
		$serverstring = (isset($_POST['serverstring'])) ? $_POST['serverstring'] : "";
		$homes = (isset($_POST['homes'])) ? (bool)$_POST['homes'] : false;

		if ($winssupport)
			$winsserver = "";

		$samba->SetNetbiosName($netbiosname);
		$samba->SetWinsServerAndSupport($winsserver, $winssupport);
		$samba->SetServerString($serverstring);
		$samba->SetShareAvailability("homes", $homes);

		if (isset($_POST['printingmode']))
			$samba->SetPrintingMode($_POST['printingmode']);

		$errors = $samba->GetValidationErrors(true);

		if (! empty($errors))
			WebDialogWarning($errors);

		$samba->Reset();
		$nmbd->Reset();
		$winbind->Reset();
	} else if (isset($_POST['UpdateMode'])) {
		$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
		$domainlogons = isset($_POST['domainlogons']) ? $_POST['domainlogons'] : '';
		$logonpath = isset($_POST['logonpath']) ? $_POST['logonpath'] : '';
		$logondrive = isset($_POST['logondrive']) ? $_POST['logondrive'] : '';
		$logonscript = isset($_POST['logonscript']) ? $_POST['logonscript'] : '';
		$roamingprofiles = isset($_POST['roamingprofiles']) ? $_POST['roamingprofiles'] : '';

		$samba->SetMode($mode);

		if ($mode == Samba::MODE_PDC) {
			$pdcdomain = isset($_POST['pdcdomain']) ? $_POST['pdcdomain'] : '';
			$logonscript = isset($_POST['logonscript']) ? $_POST['logonscript'] : '';
			$logondrive = isset($_POST['logondrive']) ? $_POST['logondrive'] : '';
			$roamingprofiles = isset($_POST['roamingprofiles']) ? (bool)$_POST['roamingprofiles'] : false;

			$samba->SetWorkgroup($pdcdomain);
			$samba->SetLogonScript($logonscript);
			$samba->SetLogonDrive($logondrive);
			$samba->SetRoamingProfilesState($roamingprofiles);
		} else if ($mode == Samba::MODE_SIMPLE_SERVER) {
			$samba->SetWorkgroup($_POST['simdomain']);
		}

		$errors = $samba->GetValidationErrors(true);

		if (! empty($errors))
			WebDialogWarning($errors);

		$samba->Reset();
		$nmbd->Reset();
		$winbind->Reset();
	} else if (isset($_POST['DeleteShare'])) {
		$samba->DeleteShare(key($_POST['DeleteShare']));
	}

} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

try {
	$initialized = $samba->IsLocalSystemInitialized();
	if ($initialized) {
		if (isset($_REQUEST['DisplayPassword'])) {
			DisplayPassword($password, $verify);
		} else {
			DisplayDaemonStatus();
			// DisplayStatus();
			DisplayConfig($netbiosname, $serverstring, $winssupport, $winsserver);
			DisplayMode($mode, $pdcdomain, $bdcdomain, $simdomain, $domainlogons, $logonpath, $logondrive, $logonscript, $roamingprofiles);
			DisplayAllShares();
		}
	} else {
		DisplayInitialize($netbiosname, $domain, $password, $verify);
	}
} catch (SambaNotInitializedException $e) {
	// Could show something else here...
	DisplayInitialize($netbiosname, $domain, $password, $verify);
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDaemonStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDaemonStatus()
{
	global $samba;
	global $nmbd;
	global $winbind;

	try {
		$smbd_running = $samba->GetRunningState();
		$nmbd_running = $nmbd->GetRunningState();
		$winbind_running = $winbind->GetRunningState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	if ($smbd_running && $nmbd_running && $winbind_running) {
		$status_button = WebButtonToggle("StopDaemon", DAEMON_LANG_STOP);
		$status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
	} else {
		$status_button = WebButtonToggle("StartDaemon", DAEMON_LANG_START);
		$status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";
	}

	$content = "
		<form action='' method='post'>
		<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>
			<tr>
				<td nowrap align='right'><b>" . DAEMON_LANG_STATUS . " -</b>&#160; </td>
				<td nowrap><b>$status</b></td>
				<td width='10'>&#160; </td>
				<td width='100'>$status_button</td>
				<td width='10'>&#160; </td>
				<td rowspan='2'>" . DAEMON_LANG_WARNING_START . "</td>
			</tr>
		</table>
		</form>
	";

    // TODO: Merge with WebDialogDaemon
	WebDialogBox("dialogdaemon", WEBCONFIG_LANG_SERVER_STATUS, WEBCONFIG_DIALOG_ICON_DAEMON, $content);
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStatus()
{
	global $services;

	$status = "";

	foreach ($services as $service) {
		try {
			$daemon = new Daemon($service);
			$title = $daemon->GetTitle();
			$state = $daemon->GetRunningState();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}

		$state_html = ($state) ?  "<span class='ok'>" . DAEMON_LANG_RUNNING . "</span>" : "<span class='alert'>" . DAEMON_LANG_STOPPED . "</span>";

		$status .= "
			<tr>
				<td class='mytablesubheader'>" .  $title . " - $service</td>
				<td><span id='" . $service . "state'>" . $state_html . "</span></td>
				<td>" . WebButtonToggle("ChangeDaemon[$service]", LOCALE_LANG_TOGGLE) . "</td>
			</tr>
		";
	}

	WebFormOpen();
	WebTableOpen(SAMBA_LANG_WINDOWS_NETWORKING_SERVICES, "500");
	WebTableHeader("Service" . "|" . LOCALE_LANG_STATUS . "|");
	echo $status;
	WebTableClose("500");
	WebFormClose();

	echo "<span id='service-status'> </span>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayPassword()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayPassword($password, $verify)
{
	global $samba;

	WebFormOpen();
	WebTableOpen(SAMBA_LANG_WINDOWS_ADMINISTRATOR . " / " . Samba::CONSTANT_WINADMIN_USERNAME, "500");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input type='password' name='password' size='20' value='$password' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
			<td><input type='password' name='verify' size='20' value='$verify' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("ChangePassword") . " " .  WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayInitialize()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayInitialize($netbiosname, $domain, $password, $verify)
{
	global $samba;

	try {
		if (empty($domain))
			$domain = $samba->GetWorkgroup();

		if (empty($netbiosname))
			$netbiosname = $samba->GetNetbiosName();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(SAMBA_LANG_WINDOWS_DOMAIN_MANAGER);
	echo "
		<tr>
			<td class='mytableheader' nowrap colspan='2'>" . SAMBA_LANG_WINDOWS_NETWORK . "</td>
		</tr>
		<tr>
			<td width='175' nowrap class='mytablesubheader'>" . SAMBA_LANG_NETBIOSNAME . "</td>
			<td><input type='text' name='netbiosname' size='20' value='$netbiosname' /> " . WEB_LANG_WINDOWS_NETBIOS_EXAMPLE . "</td>
		</tr>
		<tr>
			<td width='175' nowrap class='mytablesubheader'>" . SAMBA_LANG_DOMAIN . "</td>
			<td><input type='text' name='domain' size='20' value='$domain' /> " . WEB_LANG_WINDOWS_DOMAIN_EXAMPLE . "</td>
		</tr>
		<tr>
			<td class='mytableheader' nowrap colspan='2'>" .
				SAMBA_LANG_WINDOWS_ADMINISTRATOR . " / " . Samba::CONSTANT_WINADMIN_USERNAME . "
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input type='password' name='password' size='20' value='$password' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
			<td><input type='password' name='verify' size='20' value='$verify' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("InitializeSamba") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayMode()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayMode($mode, $pdcdomain, $bdcdomain, $simdomain, $domainlogons, $logonpath, $logondrive, $logonscript, $roamingprofiles)
{
	global $samba;

	try {
		if (empty($logonpath))
			$logonpath = $samba->GetLogonPath();

		if (empty($logonhome))
			$logonhome = $samba->GetLogonHome();

		if (empty($logondrive))
			$logondrive = $samba->GetLogonDrive();

		if (empty($logonscript))
			$logonscript = $samba->GetLogonScript();

		if (empty($mode))
			$mode = $samba->GetMode();

		if (empty($roamingprofiles))
			$roamingprofiles = $samba->GetRoamingProfilesState();

		$domaincfg = $samba->GetWorkgroup();

		if (empty($pdcdomain))
			$pdcdomain = $domaincfg;

		if (empty($bdcdomain))
			$bdcdomain = $domaincfg;

		if (empty($simdomain))
			$simdomain = $domaincfg;

		$organization = new Organization();
		$dnsdomain = $organization->GetDomain();
		$pdcdns = strtolower($pdcdomain . "." . $dnsdomain);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$logondrives = array('L:', 'M:', 'N:', 'O:', 'P:', 'Q:', 'R:', 'S:', 'T:', 'U:', 'V:', 'W:', 'X:', 'Y:', 'Z:');

	$modes = array();
	$modes[Samba::MODE_PDC] = SAMBA_LANG_PDC;
//	$modes[Samba::MODE_BDC] = SAMBA_LANG_BDC;
	$modes[Samba::MODE_SIMPLE_SERVER] = SAMBA_LANG_SIMPLE_FILE_AND_PRINT;

	// Allow for configuration file hacking
	if ($mode == Samba::MODE_CUSTOM)
		$modes[Samba::MODE_CUSTOM] = SAMBA_LANG_CUSTOM_CONFIGURATION;

	WebFormOpen();
	WebTableOpen(SAMBA_LANG_MODE, "500");
	echo "
		<tr>
			<td width='175' nowrap class='mytablesubheader'>" . SAMBA_LANG_MODE . "</td>
			<td>" . WebDropDownHash('mode', $mode, $modes, 220, 'enablerows()', 'mode') . "</td>
		</tr>

		<tr id='mode_pdc_0'>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_DOMAIN . "</td>
			<td><input type='text' style='width: 220px' name='pdcdomain' value='$pdcdomain' /></td>
        </tr>
		<tr id='mode_pdc_1'>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_DNS_LOOKUPS . "</td>
			<td><input type='text' style='width: 220px' name='pdcdns' value='$pdcdns' readonly class='readonly' /></td>
		</tr>
		<tr id='mode_pdc_2'>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_LOGON_SCRIPT . "</td>
			<td><input type='text' style='width: 220px' name='logonscript' value='$logonscript' /></td>
		</tr>
		<tr id='mode_pdc_3'>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_ROAMING_PROFILES . "</td>
			<td>" . WebDropDownEnabledDisabled('roamingprofiles', $roamingprofiles) . "</td>
		</tr>
		<tr id='mode_pdc_4'>
			<td class='mytablesubheader'>" . SAMBA_LANG_LOGON_DRIVE . "</td>
			<td>" . WebDropDownArray('logondrive', $logondrive, $logondrives) . "</td>
		</tr>

		<tr id='mode_bdc_1'>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_DOMAIN . "</td>
			<td><span>$bdcdomain</span> &nbsp; " . WebUrlJump("windows.php", LOCALE_LANG_CONFIGURE) . "</td>
		</tr>

		<tr id='mode_sim_1'>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_DOMAIN . "</td>
			<td><input type='text' style='width: 220px' name='simdomain' value='$simdomain' /></td>
		</tr>

		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("UpdateMode") . "</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();

	echo "<script type='text/javascript'>enablerows();</script>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig($netbiosname, $serverstring, $winssupport, $winsserver)
{
	global $samba;

	try {
		if (empty($netbiosname))
			$netbiosname = $samba->GetNetbiosName();

		if (empty($serverstring))
			$serverstring = $samba->GetServerString();

		if (empty($winssupport))
			$winssupport = $samba->GetWinsSupport();

		if (empty($winserver))
			$winsserver = $samba->GetWinsServer();

		$printinfo = $samba->GetPrintingInfo();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	try {
		$homeshare = $samba->GetShareInfo("homes");
		$homes = (isset($homeshare['available']) && !$homeshare['available']) ? false : true;
	} catch (SambaShareNotFoundException $e) {
		$homes = false;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if (file_exists("../../api/Cups.class.php")) {
		$printingmodes[Samba::PRINTING_DISABLED] = LOCALE_LANG_DISABLED;
		$printingmodes[Samba::PRINTING_RAW] = SAMBA_LANG_RAW_PRINTING;
		$printingmodes[Samba::PRINTING_POINT_AND_CLICK] = SAMBA_LANG_POINT_AND_CLICK_PRINTING;

		// TODO: push this logic to the API
		if (empty($printingmode)) {
			if ($printinfo['enabled'] === false)
				$printingmode = Samba::PRINTING_DISABLED;
			else if ($printinfo['printers']['use client driver'])
				$printingmode = Samba::PRINTING_RAW;
			else
				$printingmode = Samba::PRINTING_POINT_AND_CLICK;
		}

		$printing_html = "
			<tr>
				<td nowrap class='mytablesubheader'>" . SAMBA_LANG_PRINTING . "</td>
				<td>" .  WebDropDownHash("printingmode", $printingmode, $printingmodes) . "</td>
			</tr>
		";
	} else {
		$printing_html = "";
	}

	WebFormOpen();
	WebTableOpen(LOCALE_LANG_GLOBAL_SETTINGS, "500");
	echo "
		<tr>
			<td width='175' nowrap class='mytablesubheader'>" . SAMBA_LANG_NETBIOSNAME . "</td>
			<td><input type='text' style='width: 220px' name='netbiosname' value='$netbiosname' /></td>
		</tr>
		<tr>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_SERVERSTRING . "</td>
			<td><input type='text' style='width: 220px' name='serverstring' value='$serverstring' /></td>
		</tr>
		$printing_html
		<tr>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_HOMES . "</td>
			<td>" .  WebDropDownEnabledDisabled("homes", $homes) . "</td>
		</tr>
		<tr>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_WINSSUPPORT . "</td>
			<td>" .
				WebDropDownEnabledDisabled("winssupport", $winssupport, 0, "togglewinsserver();", "winssupport") . "
				&nbsp; &nbsp; " . SAMBA_LANG_WINSSERVER .  "
				<input id='winsserver' type='text' name='winsserver' value='$winsserver' />
			</td>
		</tr>
		<tr>
			<td nowrap class='mytablesubheader'>" . SAMBA_LANG_ADMINISTRATOR_PASSWORD . "</td>
			<td>" . WebUrlJump("?DisplayPassword=true", LOCALE_LANG_UPDATE) . "</td>
		</tr>
		<tr>
			<td nowrap class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("UpdateGlobalConfig") . "</td>
		</tr>
	";
	WebTableClose("500");
	WebFormClose();

	echo "<script type='text/javascript'>togglewinsserver()</script>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAllShares()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAllShares()
{
	global $samba;

	try {
		$shares = $samba->GetShares();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$sharelist = "";

	$printing_installed = file_exists("../../api/Cups.class.php") ? true : false;

	foreach ($shares as $share) {
		$info = $samba->GetShareInfo($share);

		if (!$printing_installed && (($share == "printers") || ($share == "print$")))
			continue;

		$statusclass = (isset($info["available"]) && !$info["available"]) ? 'icondisabled' : 'iconenabled';
		$rowclass = 'rowenabled';
		$rowclass .= ($rowindex % 2) ? 'alt' : '';
		$rowindex++;

		$sharelist .= "
		  <tr class='$rowclass'>
			<td class='$statusclass'>&#160;</td>
			<td>$share</td>
			<td>" . $info["comment"] . "</td>
		  </tr>
		";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_SHARES_TITLE, "500");
	WebTableHeader("|" . SAMBA_LANG_SHARE . "|" . SAMBA_LANG_SERVERSTRING);
	echo $sharelist;
	WebTableClose("500");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
