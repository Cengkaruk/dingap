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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/ClearDirectory.class.php');
require_once('../../api/Daemon.class.php');
require_once('../../api/FirewallRedirect.class.php');
require_once('../../api/HostnameChecker.class.php');
require_once('../../api/Software.class.php');
require_once('../../api/Squid.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-proxy.png', WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$squid = new Squid();
$directory = new ClearDirectory();
$firewall = new FirewallRedirect();
$adzapper = new Software('adzapper');
$dansguardian = new Daemon('dansguardian');
$dansguardianav = new Daemon("dansguardian-av");

$nickname = isset($_POST['nickname']) ? $_POST['nickname'] : "";
$host = isset($_POST['host']) ? $_POST['host'] : "";

SanityCheckHostname();

try {
	if (isset($_POST['EnableBoot'])) {
		$squid->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$squid->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$squid->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$squid->SetRunningState(false);
	} else if (isset($_POST['UpdateGeneralConfig'])) {
		$squid->SetMaxObjectSize($_POST['maxobjectsize']);
		$squid->SetCacheSize($_POST['cachesize']);
		$squid->SetMaxFileDownloadSize($_POST['maxfilesize']);
		$squid->Reset();
	} else if (isset($_POST['ResetCache'])) {
		$squid->ResetCache();
	} else if (isset($_POST['SetMode'])) {

		if (isset($_POST['transparent']) && ((bool) $_POST['transparent']))
			$firewall->SetProxyTransparentState(true);
		else
			$firewall->SetProxyTransparentState(false);

		if (isset($_POST['content_filter']) && ((bool) $_POST['content_filter']))
			$firewall->SetProxyFilterPort(8080);
		else
			$firewall->SetProxyFilterPort("");

		if (isset($_POST['banner_filter']) && ((bool) $_POST['banner_filter']))
			$squid->SetAdzapperState(true);
		else
			$squid->SetAdzapperState(false);

		if (isset($_POST['authentication']) && ((bool) $_POST['authentication'])) {
			$squid->SetBasicAuthenticationInfoDefault();
			$squid->SetAuthenticationState(true);
		} else {
			$squid->SetAuthenticationState(false);
		}

		$squid->Reset();
		$firewall->Restart();
	} else if (isset($_POST['AddProxyBypass'])) {
		$firewall->AddProxyBypass($_POST['nickname'], $_POST['host']);
		$errors = $firewall->GetValidationErrors(true);

		if (empty($errors)) {
			$host = "";
			$nickname = "";
			$firewall->Restart();
		} else {
			WebDialogWarning($errors);
		}
	} else if (isset($_POST['DeleteProxyBypass'])) {
		$firewall->DeleteProxyBypass(key($_POST['DeleteProxyBypass']));
		$firewall->Restart();
	} else if (isset($_POST['ToggleProxyBypass'])) {
		list($enabled, $host) = explode("|", key($_POST['ToggleProxyBypass']));
		$firewall->ToggleEnableProxyBypass(($enabled) ? false : true, $host);
		$firewall->Restart();
		$host = "";
		$nickname = "";
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebDialogDaemon('squid');
SanityCheck();
DisplayConfig();
DisplayProxyBypass($nickname, $host);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig()
{
	global $squid;
	global $adzapper;
	global $dansguardian;
	global $dansguardianav;
	global $firewall;
	global $directory;

	try {
		$maxobjectsize = $squid->GetMaxObjectSize();
		$maxfilesize = $squid->GetMaxFileDownloadSize();
		$cachesize = $squid->GetCacheSize();

		$mode = $firewall->GetMode();
		$filter_port = $firewall->GetProxyFilterPort();
		$is_transparent = $firewall->GetProxyTransparentState();
		$is_adzapper_installed = $adzapper->IsInstalled();
		$is_dansguardian_installed = $dansguardian->IsInstalled();
		$is_dansguardian_av_installed = $dansguardianav->IsInstalled();
		$is_user_auth = $squid->GetAuthenticationState();
		$banner_filter_state = $squid->GetAdzapperState();
		$is_directory_initialized = $directory->IsInitialized();
	} catch (CustomConfigurationException $e) {
		WebDialogWarning($e->GetMessage());
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$maxobjectsize_dropdown = WebDropDownBytes("maxobjectsize", $maxobjectsize, "1048576", "943718400", false);
	$maxfilesize_dropdown = WebDropDownBytes("maxfilesize", $maxfilesize, "1048576", "943718400", true);
	$cachesize_dropdown = WebDropDownBytes("cachesize", $cachesize, "102400000", "536870912000", false);

	// User authentication
	//--------------------

	$authentication_dropdown = WebDropDownEnabledDisabled("authentication", $is_user_auth);

	// Transparent mode
	//-----------------

	if (($mode != Firewall::CONSTANT_STANDALONE) && ($mode != Firewall::CONSTANT_TRUSTEDSTANDALONE)) {
		$transparent_dropdown = WebDropDownEnabledDisabled("transparent", $is_transparent);
		$transparent_extra = "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_TRANS_MODE . "</td>
				<td nowrap>$transparent_dropdown</td>
			</tr>
		";
	} else {
		$transparent_extra = "";
	}

	// Content filter
	//---------------

	if ($is_dansguardian_installed || $is_dansguardian_av_installed) {
		$content_filter = empty($filter_port) ? false : true;
		$content_filter_dropdown = WebDropDownEnabledDisabled("content_filter", $content_filter);
		$content_filter_extra = "
			<tr>
				<td class='mytablesubheader' nowrap>" . SQUID_LANG_CONTENT_FILTER . "</td>
				<td nowrap>$content_filter_dropdown</td>
			</tr>
		";
	} else {
		$content_filter_extra = "";
	}

	// Banner ad blocker
	//------------------

	if ($is_adzapper_installed) {
		$banner_filter_dropdown = WebDropDownEnabledDisabled("banner_filter", $banner_filter_state);
		$banner_filter_extra = "
			<tr>
				<td class='mytablesubheader' nowrap>" . SQUID_LANG_BANNER_AND_POPUP_FILTER . "</td>
				<td nowrap>$banner_filter_dropdown</td>
			</tr>
		";
	} else {
		$banner_filter_extra = "";
	}

	// User Authentication
	//--------------------

	if ($is_directory_initialized) {
		$authentication_extra = "
			<tr>
				<td class='mytablesubheader' nowrap>" . SQUID_LANG_USER_AUTHENTICATION . "</td>
				<td>$authentication_dropdown</td>
			</tr>
		";
	} else {
		$authentication_extra = "";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE);
	echo "
		<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_CONFIG_GENERAL_TITLE . "</td>
		</tr>
		<tr>
			<td width='250' class='mytablesubheader' nowrap>" . SQUID_LANG_MAX_CACHE_SIZE . "</td>
			<td>$cachesize_dropdown</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . SQUID_LANG_MAX_CACHE_OBJECT_SIZE . "</td>
			<td>$maxobjectsize_dropdown</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . SQUID_LANG_MAX_FILE_DOWNLOAD_SIZE . "</td>
			<td>$maxfilesize_dropdown</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&#160; </td>
			<td valign='top'>". WebButtonUpdate('UpdateGeneralConfig') . "</td>
		</tr>
		<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_MODE . "</td>
		</tr>
		$transparent_extra
		$content_filter_extra
		$banner_filter_extra
		$authentication_extra
		<tr>
			<td class='mytablesubheader' nowrap>&#160; </td>
			<td>" . WebButtonUpdate('SetMode') . "</td>
		</tr>
		<tr>
			<td class='mytableheader' colspan='2'>" . SQUID_LANG_CACHE . "</td>
		</tr>
        <tr>
            <td class='mytablesubheader' nowrap>" . WEB_LANG_RESET_CACHE . "</td>
            <td valign='top'>". WebButtonGo('ResetCache') . "</td>
        </tr>
    ";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayProxyBypass()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayProxyBypass($nickname, $host)
{
	global $firewall;

	$rules = array();

	try {
		$rules = $firewall->GetProxyBypassList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	foreach ($rules as $rule) {
		$name = (strlen($rule['name'])) ? $rule['name'] : "-";

		if ($rule['enabled']) {
			$toggle = LOCALE_LANG_DISABLE;
			$iconclass = "iconenabled";
			$rowclass = "rowenabled";
		} else {
			$toggle = LOCALE_LANG_ENABLE;
			$iconclass = "icondisabled";
			$rowclass = "rowdisabled";
		}

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$list .= "
			<tr class='$rowclass'>
				<td class='$iconclass'>&nbsp; </td>
				<td nowrap>$name</td>
				<td nowrap>" . $rule['host'] . "</td>
				<td nowrap>" .
					WebButtonDelete("DeleteProxyBypass[" . $rule['host'] . "]") .
					WebButtonToggle("ToggleProxyBypass[" . $rule['enabled'] . "|" . $rule['host'] . "]", $toggle) . "
				</td>
			</tr>
		";
	}

	if (!$list)
		$list = "<tr><td colspan='3' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_BYPASS_TITLE);
	WebTableHeader("|" . FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_DOMAIN_IP . "|" . LOCALE_LANG_STATUS . "|");
	echo "
		$list
		<tr>
			 <td>&nbsp; </td>
			 <td><input type='text' name='nickname' size='15' value='$nickname' /></td>
			 <td><input type='text' name='host' size='20' value='$host' /></td>
			 <td nowrap>" . WebButtonAdd('AddProxyBypass') . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck()
{
	global $squid;
	global $firewall;
	global $dansguardian;
	global $dansguardianav;

	try {
		$mode = $firewall->GetMode();
		$filter_port = $firewall->GetProxyFilterPort();
		$is_transparent = $firewall->GetProxyTransparentState();
		$is_dg_running = $dansguardian->GetRunningState();
		$is_dg_av_running = $dansguardianav->GetRunningState();
		$is_dg_av_installed = $dansguardianav->IsInstalled();
		$is_squid_running = $squid->GetRunningState();
		$authentication_state = $squid->GetAuthenticationState();

	} catch (Exception $e) {
		// Not fatal
		return;
	}

	// Sanity checks that are only relevant on gateway boxes
	//------------------------------------------------------

	if (($mode != Firewall::CONSTANT_STANDALONE) && ($mode != Firewall::CONSTANT_TRUSTEDSTANDALONE)) {

		// Sanity Check #1
		//
		// If transparent mode is on, proxy *must* be running or it will be
		// impossible to browse the web.
		//-------------------------------------------------------------------

		if (!$is_squid_running && $is_transparent)
			WebDialogWarning(WEB_LANG_TRANS_WARNING);

		// Sanity Check #2
		//
		// If transparent mode is on, user authentication is not supported.
		//-------------------------------------------------------------------

		if ($is_transparent && $authentication_state)
			WebDialogWarning(WEB_LANG_TRANSPARENT_AND_USER_WARNING);
	}

	// Sanity Check #3
	//
	// If the content filter is enabled but not running, it will be
	// impossible to browse the web.
	//-------------------------------------------------------------------

	if (!($is_dg_running || $is_dg_av_running) && $filter_port) {
		$button = WebUrlJump("proxy-filter.php", SQUID_LANG_CONTENT_FILTER);
		WebDialogWarning(WEB_LANG_DANSGUARDIAN_NOT_RUNNING . " - " . $button);
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// SanityCheckHostname()
//
// Add entry to hosts file if hostname is not valid... otherwise proxy
// will fail to start.
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheckHostname()
{

	try {
		$hostnamechecker = new HostnameChecker();
		if (! $hostnamechecker->IsLookupable())
			$hostnamechecker->AutoFix();
	} catch (Exception $e) {
		// Not fatal
	}
}


function WebDropDownBytes($variable, $value, $low, $high, $nolimit, $width = 0)
{

	$bytes["1048576"] = "1 " . LOCALE_LANG_MEGABYTES;
	$bytes["2097152"] = "2 " . LOCALE_LANG_MEGABYTES;
	$bytes["3145728"] = "3 " . LOCALE_LANG_MEGABYTES;
	$bytes["4194304"] = "4 " . LOCALE_LANG_MEGABYTES;
	$bytes["5242880"] = "5 " . LOCALE_LANG_MEGABYTES;
	$bytes["6291456"] = "6 " . LOCALE_LANG_MEGABYTES;
	$bytes["7340032"] = "7 " . LOCALE_LANG_MEGABYTES;
	$bytes["8388608"] = "8 " . LOCALE_LANG_MEGABYTES;
	$bytes["9437184"] = "9 " . LOCALE_LANG_MEGABYTES;
	$bytes["10485760"] = "10 " . LOCALE_LANG_MEGABYTES;
	$bytes["20971520"] = "20 " . LOCALE_LANG_MEGABYTES;
	$bytes["31457280"] = "30 " . LOCALE_LANG_MEGABYTES;
	$bytes["41943040"] = "40 " . LOCALE_LANG_MEGABYTES;
	$bytes["52428800"] = "50 " . LOCALE_LANG_MEGABYTES;
	$bytes["62914560"] = "60 " . LOCALE_LANG_MEGABYTES;
	$bytes["73400320"] = "70 " . LOCALE_LANG_MEGABYTES;
	$bytes["83886080"] = "80 " . LOCALE_LANG_MEGABYTES;
	$bytes["94371840"] = "90 " . LOCALE_LANG_MEGABYTES;
	$bytes["104857600"] = "100 " . LOCALE_LANG_MEGABYTES;
	$bytes["209715200"] = "200 " . LOCALE_LANG_MEGABYTES;
	$bytes["314572800"] = "300 " . LOCALE_LANG_MEGABYTES;
	$bytes["419430400"] = "400 " . LOCALE_LANG_MEGABYTES;
	$bytes["524288000"] = "500 " . LOCALE_LANG_MEGABYTES;
	$bytes["629145600"] = "600 " . LOCALE_LANG_MEGABYTES;
	$bytes["734003200"] = "700 " . LOCALE_LANG_MEGABYTES;
	$bytes["838860800"] = "800 " . LOCALE_LANG_MEGABYTES;
	$bytes["943718400"] = "900 " . LOCALE_LANG_MEGABYTES;
	$bytes["1073741824"] = "1 " . LOCALE_LANG_GIGABYTES;
	$bytes["2147483648"] = "2 " . LOCALE_LANG_GIGABYTES;
	$bytes["3221225472"] = "3 " . LOCALE_LANG_GIGABYTES;
	$bytes["4294967296"] = "4 " . LOCALE_LANG_GIGABYTES;
	$bytes["5368709120"] = "5 " . LOCALE_LANG_GIGABYTES;
	$bytes["6442450944"] = "6 " . LOCALE_LANG_GIGABYTES;
	$bytes["7516192768"] = "7 " . LOCALE_LANG_GIGABYTES;
	$bytes["8589934592"] = "8 " . LOCALE_LANG_GIGABYTES;
	$bytes["9663676416"] = "9 " . LOCALE_LANG_GIGABYTES;
	$bytes["10737418240"] = "10 " . LOCALE_LANG_GIGABYTES;
	$bytes["21474836480"] = "20 " . LOCALE_LANG_GIGABYTES;
	$bytes["32212254720"] = "30 " . LOCALE_LANG_GIGABYTES;
	$bytes["42949672960"] = "40 " . LOCALE_LANG_GIGABYTES;
	$bytes["53687091200"] = "50 " . LOCALE_LANG_GIGABYTES;
	$bytes["64424509440"] = "60 " . LOCALE_LANG_GIGABYTES;
	$bytes["75161927680"] = "70 " . LOCALE_LANG_GIGABYTES;
	$bytes["85899345920"] = "80 " . LOCALE_LANG_GIGABYTES;
	$bytes["96636764160"] = "90 " . LOCALE_LANG_GIGABYTES;
	$bytes["107374182400"] = "100 " . LOCALE_LANG_GIGABYTES;
	$bytes["214748364800"] = "200 " . LOCALE_LANG_GIGABYTES;
	$bytes["322122547200"] = "300 " . LOCALE_LANG_GIGABYTES;
	$bytes["429496729600"] = "400 " . LOCALE_LANG_GIGABYTES;
	$bytes["536870912000"] = "500 " . LOCALE_LANG_GIGABYTES;
	$bytes["644245094400"] = "600 " . LOCALE_LANG_GIGABYTES;
	$bytes["751619276800"] = "700 " . LOCALE_LANG_GIGABYTES;
	$bytes["858993459200"] = "800 " . LOCALE_LANG_GIGABYTES;
	$bytes["966367641600"] = "900 " . LOCALE_LANG_GIGABYTES;

	if ($nolimit)
		$bytes[Squid::CONSTANT_UNLIMITED] = LOCALE_LANG_UNLIMITED;

	$found = false;
	$options = "";

	foreach ($bytes as $actual => $show) {
		if ($actual == Squid::CONSTANT_UNLIMITED) {
			// no op
		} else if ($actual < $low) {
			continue;
		} else if ($actual > $high) {
			continue;
		}

		if ($value == $actual) {
			$options .= "<option value='$actual' selected>$show</option>\n";
			$found = true;
		} else {
			$options .= "<option value='$actual'>$show</option>\n";
		}
	}

	if (!$found) {
		$show = round($value / 1048576, 1) . " " . LOCALE_LANG_MEGABYTES;
		$options = "<option value='$value' selected>$show</option>\n" . $options;
	}

	if ($width) {
		$width = $width . "px";
		return "<select style='width: $width' name='$variable'>$options</select>\n";
	} else {
		return "<select name='$variable'>$options</select>";
	}
}

?>
