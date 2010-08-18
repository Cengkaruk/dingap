<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
// Copyright 2005 Fernand Jonker -- Greylist feature
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
require_once("../../api/DansGuardian.class.php");
require_once("../../api/Daemon.class.php");
require_once("../../api/Iface.class.php");
require_once("../../api/Network.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallRedirect.class.php");
require_once("../../api/Locale.class.php");
require_once("../../api/FileGroup.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-dansguardian.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$dansguardian = new DansGuardian();
$squid = new Daemon("squid");
$group = new FileGroup("none", DansGuardian::FILE_CONFIG_GROUP_DEFAULT); // For language tags
$network = new Network(); // For language tags
$firewall = new Firewall();

// Daemon start/stop etc
//----------------------

if (isset($_POST['EnableBoot'])) {
	try {
		$dansguardian->SetBootState(true);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DisableBoot'])) {
	try {
		$dansguardian->SetBootState(false);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['StartDaemon'])) {
	if ($squid->GetRunningState()) {
		try {
			$dansguardian->SetRunningState(true);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else
		WebDialogWarning(WEB_LANG_SQUID_NOT_RUNNING . "<a href='squid.php'>" . LOCALE_LANG_GO . "</a>");
} else if (isset($_POST['StopDaemon'])) {
	try
	{
		$dansguardian->SetRunningState(false);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

// Main config
//------------

} else if (isset($_POST['UpdateConfig'])) {
	try {
		UpdateConfig(
			$_POST['naughtyness'],
			$_POST['pics'],
			$_POST['setbaneverything'],
			$_POST['setnoip'],
			$_POST['reporting'],
			$_POST['dglocale']
		);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

// Site rules
//-----------

} else if (isset($_POST['AddExceptionSite'])) {
	try {
		$dansguardian->AddExceptionSiteAndUrl($_POST['exceptionsite']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['DeleteExceptionSite'])) {
	try {
		$dansguardian->DeleteExceptionSiteAndUrl(key($_POST['DeleteExceptionSite']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['AddBannedSite'])) {
	try {
		$dansguardian->AddBannedSiteAndUrl($_POST['bansite']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['DeleteBannedSiteAndUrl'])) {
	try {
		$dansguardian->DeleteBannedSiteAndUrl(key($_POST['DeleteBannedSiteAndUrl']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['AddGreySite'])) {
	try {
		$dansguardian->AddGreySiteAndUrl($_POST['greysite']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['DeleteGreySite'])) {
	try {
		$dansguardian->DeleteGreySiteAndUrl(key($_POST['DeleteGreySite']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
	$DisplayBack = "dansguardian.php";

// IP rules
//---------

} else if (isset($_POST['AddExceptionIp'])) {
	try {
		$dansguardian->AddExceptionIp($_POST['exceptionip']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['DeleteExceptionIp'])) {
	try {
		$dansguardian->DeleteExceptionIp(key($_POST['DeleteExceptionIp']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['AddBannedIp'])) {
	try {
		$dansguardian->AddBannedIp($_POST['banip']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['DeleteBannedIp'])) {
	try {
		$dansguardian->DeleteBannedIp(key($_POST['DeleteBannedIp']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['AddExceptionGroup'])) {
	try {
		$dansguardian->AddExceptionIpGroup($_POST['exceptiongroup']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['AddBannedGroup'])) {
	try {
		$dansguardian->AddBannedIpGroup($_POST['bannedgroup']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['DeleteExceptionGroup'])) {
	try {
		$dansguardian->DeleteExceptionIpGroup(key($_POST['DeleteExceptionGroup']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['DeleteBannedGroup'])) {
	try {
		$dansguardian->DeleteBannedIpGroup(key($_POST['DeleteBannedGroup']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
	$DisplayBack = "dansguardian.php";

// File/MIME configuration updates
//--------------------------------

} else if (isset($_POST['SelectAllExtensions'])) {
	try {
		$extensions = array_keys($dansguardian->GetPossibleExtensions());
		$dansguardian->SetBannedExtensions($extensions);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['SelectNoExtensions'])) {
	try {
		$extensions = array();
		$dansguardian->SetBannedExtensions($extensions);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['SelectAllMime'])) {
	try {
		$mimes = array_keys($dansguardian->GetPossibleMimes());
		$dansguardian->SetBannedMimes($mimes);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['SelectNoMime'])) {
	try {
		$mimes = array();
		$dansguardian->SetBannedMimes($mimes);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['AddFileType'])) {
	try {
		$addextension = preg_replace("/^\./", "", $_POST['addextension']);
		echo "Adding: $addextension";
		$dansguardian->AddPossibleExtension($addextension, "");
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['AddMimeType'])) {
	try {
		$dansguardian->AddPossibleMime($_POST['addmime'], "");
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['UpdateMimeType'])) {
	try {
		$dansguardian->SetBannedMimes($_POST['mimes']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
	$DisplayBack = "dansguardian.php";
} else if (isset($_POST['UpdateFileType'])) {
	try {
		$dansguardian->SetBannedExtensions($_POST['extensions']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
	$DisplayBack = "dansguardian.php";

// Blacklists
//-----------

} else if (isset($_POST['UpdateBlacklists'])) {
	$enabledlist = array();
	if (isset($_POST['blacklist'])) {
		foreach ($_POST['blacklist'] as $listname => $state)
			$enabledlist[] = $listname;
	}

	try {
		$dansguardian->SetBlacklists($enabledlist);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayBlacklists'] = true;
	$DisplayBack = "dansguardian.php";

// Weighted Phrasing
//-----------------

} else if (isset($_POST['UpdateWeightedPhrasing'])) {
	$enabledlist = array();
	if (isset($_POST['phraselist'])) {
		foreach ($_POST['phraselist'] as $listname => $state)
			$enabledlist[] = $listname;
		try {
			$dansguardian->SetWeightedPhraseLists($enabledlist);
			$dansguardian->Reset();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	}
	$_POST['DisplayWeightedPhrasing'] = true;
	$DisplayBack = "dansguardian.php";

// Groups
//-------

} else if (isset($_POST['AddGroup'])) {
	try {
		$dansguardian->AddGroup($_POST['addgroup']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayEditGroup'][$_POST['addgroup']] = true;
} else if (isset($_POST['DeleteGroup'])) {
	try {
		$dansguardian->DeleteGroup(key($_POST['DeleteGroup']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_REQUEST['DisplayIpRules'] = true;
} else if (isset($_POST['AddGroupEntry'])) {
	try {
		$dansguardian->AddGroupEntry($_POST['groupname'], $_POST['addmember']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayEditGroup'][$_POST['groupname']] = true;
	$DisplayBack = "dansguardian.php?DisplayIpRules=true";
} else if (isset($_POST['DeleteGroupEntry'])) {
	try {
		$dansguardian->DeleteGroupEntry($_POST['groupname'], key($_POST['DeleteGroupEntry']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayEditGroup'][$_POST['groupname']] = true;
	$DisplayBack = "dansguardian.php?DisplaIpRules=true";

// Firewall transparent mode
//--------------------------

} else if (isset($_POST['EnableTransparent'])) {
	try {
		$firewall->SetSquidFilterPort("8080");
		$firewall->SetSquidTransparentMode(true);
		$firewall->Restart();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}


///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

try {
	$beta_dansguardian = new Daemon("dansguardian-av");
	if ($beta_dansguardian->GetRunningState()) {
		WebDialogWarning(WEB_LANG_CANNOT_HAVE_BASIC_AND_ADVANCED_SIMULTANEOUSLY);
		WebFooter();
		exit;
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}


if (isset($DisplayBack)) {
	WebDialogInfo("<a href='$DisplayBack'>" . WEBCONFIG_ICON_BACK . " " . LOCALE_LANG_BACK . "</a> &#160; -- &#160; " . WEBCONFIG_LANG_SAVED);
}

if (isset($_POST['DisplayFilesAndMimes'])) {
	DisplayFilesAndMimes();
} else if (isset($_POST['DisplayEditGroup'])) {
	DisplayEditGroup(key($_POST['DisplayEditGroup']));
} else if (isset($_REQUEST['DisplayIpRules'])) {
	DisplayIpRules();
	DisplayGroups();
} else if (isset($_POST['DisplaySiteRules'])) {
	DisplaySiteRules();
} else if (isset($_POST['DisplayBlacklists'])) {
	DisplayBlacklists();
} else if (isset($_POST['DisplayWeightedPhrasing'])) {
	DisplayWeightedPhrasing();
} else {
	WebDialogDaemon("dansguardian");
	try {
		SanityCheck();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	DisplaySummary();
	DisplayConfig();
}

WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_GOTO_ADVANCED, "500");
	echo "
	  <tr>
	   <td class='mytablesubheader' nowrap>" . WEB_LANG_FILE_EXTENSION_MIME_RESTRICTIONS . "</td>
	   <td nowrap width='100'>" . WebButtonEdit("DisplayFilesAndMimes") . "</td>
	  </tr>
	  <tr>
	   <td class='mytablesubheader' nowrap>" . WEB_LANG_WEB_SITE_CONTROL . "</td>
	   <td>" . WebButtonEdit("DisplaySiteRules") . "</td>
	  </tr>
	  <tr>
	   <td class='mytablesubheader' nowrap>" . DANSGUARDIAN_LANG_BAN_IP_LIST . " / " . DANSGUARDIAN_LANG_EXCEPTION_IP_LIST . "</td>
	   <td>" . WebButtonEdit("DisplayIpRules") . "</td>
	  </tr>
	  <tr>
	   <td class='mytablesubheader' nowrap>" . WEB_LANG_ACTIVE_CONTENT_SCANNING . "</td>
	   <td>" . WebButtonEdit("DisplayWeightedPhrasing") . "</td>
	  </tr>
	  <tr>
	   <td class='mytablesubheader' nowrap>" . DANSGUARDIAN_LANG_BLACKLISTS . "</td>
	   <td>" . WebButtonEdit("DisplayBlacklists") . "</td>
	  </tr>
	";
	WebTableClose("500");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBlacklists()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBlacklists()
{
	global $dansguardian;

	try {
		$allblacklists = $dansguardian->GetPossibleBlacklists();
		$activeblacklists = $dansguardian->GetBlacklists();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	if (count($allblacklists) == 0) return;

	$listout = "";
	$index = 0;

	foreach ($allblacklists as $blacklist) {
		if (in_array($blacklist['name'], $activeblacklists)) {
			$state = "checked";
			$rowclass = "rowenabled";
		} else {
			$state = "";
			$rowclass = "rowdisabled";
		}

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$listout .= "
		  <tr>
		    <td class='$rowclass'>
		      <input type='checkbox' name='blacklist[$blacklist[name]]' value='$state' $state />
			  <span class='$rowclass'>$blacklist[name]</span>
			</td>
		    <td class='$rowclass'>$blacklist[description]</td>
		  </tr>
		";
	}

	WebDialogInfo(WEB_LANG_BLACKLIST_HELP);
	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_BLACKLISTS, "100%");
	echo "
	  $listout
	  <tr>
	   <td colspan='2' align='center'>" . WebButtonUpdate("UpdateBlacklists") . " " . WebButtonCancel("Cancel") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// UpdateConfig()
//
///////////////////////////////////////////////////////////////////////////////

function UpdateConfig($naughtyness, $pics, $setbaneverything, $setnoip, $reporting, $dglocale)
{
	global $dansguardian;

	$dansguardian->SetNaughtynessLimit($naughtyness);
	$dansguardian->SetPics($pics);
	$dansguardian->SetReportingLevel($reporting);
	$dansguardian->SetLocale($dglocale);

	// TODO = move this logic to class file
	if ($setbaneverything)
		$dansguardian->AddBannedSiteAndUrl("**");
	else
		$dansguardian->DeleteBannedSiteAndUrl("**");

	if ($setnoip)
		$dansguardian->AddBannedSiteAndUrl("*ip");
	else
		$dansguardian->DeleteBannedSiteAndUrl("*ip");
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayWeightedPhrasing()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayWeightedPhrasing()
{
	global $dansguardian;

	$alllists = $dansguardian->GetPossibleWeightedPhrase();
	$activelists = $dansguardian->GetWeightedPhraseLists();

	if (count($alllists) == 0)
		return;

	$listout = "";
	$index = 0;

	foreach ($alllists as $phraselist) {
		if (in_array($phraselist[name], $activelists)) {
			$state = "checked";
			$rowclass = "rowenabled";
		} else {
			$state = "";
			$rowclass = "rowdisabled";
		}

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$listout .= "
		  <tr>
		    <td class='$rowclass'>
		      <input type='checkbox' name='phraselist[$phraselist[name]]' value='$state' $state />
			  <span class='$rowclass'>$phraselist[name]</span>
			</td>
		    <td nowrap class='$rowclass'>$phraselist[description]</td>
		  </tr>
		";
	}

	WebDialogInfo(WEB_LANG_PHRASELIST_HELP);
	WebFormOpen();
	WebTableOpen(WEB_LANG_ACTIVE_CONTENT_SCANNING, "450");
	echo "
	  $listout
	  <tr>
	   <td colspan='2' align='center'>" . WebButtonUpdate("UpdateWeightedPhrasing") . " " . WebButtonCancel("Cancel") . "</td>
	  </tr>
	";
	WebTableClose("450");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfig()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfig()
{
	global $dansguardian;

	$localelist = array();

	try {
		$banlist = $dansguardian->GetBannedSitesAndUrls();
		$exceptionlist = $dansguardian->GetExceptionIps();
		$naughtyness = $dansguardian->GetNaughtynessLimit();
		$pics = $dansguardian->GetPics();
		$reporting = $dansguardian->GetReportingLevel();
		$locale = $dansguardian->GetLocale();
		$localelist = $dansguardian->GetPossibleLocales();
	} catch(Exception $e) {
	}

	if (in_array("*ip", $banlist))
		$noip = true;
	if (in_array("**", $banlist))
		$baneverything = true;

	// Drop-downs
	//-----------

	if ($pics == "disabled") {
		$pics_off_selected = "selected";
	} else if ($pics == "noblocking") {
		$pics_verylow_selected = "selected";
	} else if ($pics == "youngadult") {
		$pics_low_selected = "selected";
	} else if ($pics == "teen") {
		$pics_medium_selected = "selected";
	} else if ($pics == "tooharsh") {
		$pics_high_selected = "selected";
	}
	$select_pics = "
	    <option value='disabled' $pics_off_selected>" . LOCALE_LANG_DISABLED . "</option>
	    <option value='noblocking' $pics_verylow_selected>" . LOCALE_LANG_VERYLOW . "</option>
	    <option value='youngadult' $pics_low_selected>" . LOCALE_LANG_LOW . "</option>
	    <option value='teen' $pics_medium_selected>" . LOCALE_LANG_MEDIUM . "</option>
	    <option value='tooharsh' $pics_high_selected>" . LOCALE_LANG_HIGH . "</option>
	";

	if ($reporting == -1) {
		$reporting_stealth_selected = "selected";
	} else if ($reporting == 0) {
		$reporting_denied_selected = "selected";
	} else if ($reporting == 1) {
		$reporting_short_selected = "selected";
	} else if ($reporting == 2) {
		$reporting_full_selected = "selected";
	} else if ($reporting == 3) {
		$reporting_custom_selected = "selected";
	}
	$select_reporting = "
	    <option value='-1' $reporting_stealth_selected>" . DANSGUARDIAN_LANG_STEALTH_MODE . "</option>
	    <option value='0' $reporting_denied_selected>" . DANSGUARDIAN_LANG_DENIED_MODE . "</option>
	    <option value='1' $reporting_short_selected>" . DANSGUARDIAN_LANG_SHORT_REPORT . "</option>
	    <option value='2' $reporting_full_selected>" . DANSGUARDIAN_LANG_FULL_REPORT . "</option>
	    <option value='3' $reporting_custom_selected>" . DANSGUARDIAN_LANG_CUSTOM_REPORT . "</option>
	";

	if ($naughtyness <= 50)
		$naughtyness_veryhigh_selected = "selected";
	else if ($naughtyness <= 100)
		$naughtyness_high_selected = "selected";
	else if ($naughtyness <= 150)
		$naughtyness_medium_selected = "selected";
	else if ($naughtyness <= 200)
		$naughtyness_low_selected = "selected";
	else if ($naughtyness <= 400)
		$naughtyness_verylow_selected = "selected";
	else
		$naughtyness_disabled_selected = "selected";

	$select_naughtyness = "
	    <option value='99999' $naughtyness_disabled_selected>" . LOCALE_LANG_DISABLED . "</option>
	    <option value='400' $naughtyness_verylow_selected>" . LOCALE_LANG_VERYLOW . "</option>
	    <option value='200' $naughtyness_low_selected>" . LOCALE_LANG_LOW . "</option>
	    <option value='150' $naughtyness_medium_selected>" . LOCALE_LANG_MEDIUM . "</option>
	    <option value='100' $naughtyness_high_selected>" . LOCALE_LANG_HIGH . "</option>
	    <option value='50' $naughtyness_veryhigh_selected>" . LOCALE_LANG_VERYHIGH . "</option>
	";

	if ($noip)
		$noip = "<input type='checkbox' name='setnoip' value='1' checked />";
	else
		$noip = "<input type='checkbox' name='setnoip' value='1' />";

	if ($baneverything)
		$banItAll = "<input type='checkbox' name='setbaneverything' value='1' checked />";
	else
		$banItAll = "<input type='checkbox' name='setbaneverything' value='1' />";

	$select_locale = WebDropDownArray("dglocale", $locale, $localelist);

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "500");
	echo "
	  <tr>
	    <td class='mytablesubheader' nowrap>" . LOCALE_LANG_LANGUAGE . "</td>
	    <td>$select_locale</td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . DANSGUARDIAN_LANG_NAUGHTYNESS_LIMIT . "</td>
	    <td><select name='naughtyness'>$select_naughtyness</select></td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . DANSGUARDIAN_LANG_PICS_LEVEL . "</td>
	    <td nowrap><select name='pics'>$select_pics</select></td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . DANSGUARDIAN_LANG_REPORTING_LEVEL . "</td>
	    <td nowrap><select name='reporting'>$select_reporting</select></td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . DANSGUARDIAN_LANG_NO_IPS . "</td>
	    <td nowrap>$noip </td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' nowrap>" . DANSGUARDIAN_LANG_BAN_EVERYTHING . "</td>
	    <td nowrap>$banItAll </td>
	  </tr>
	  <tr>
	    <td class='mytablesubheader' align='right'>&#160; </td>
	    <td>". WebButtonUpdate("UpdateConfig") . "</td>
	  </tr>
	";
	WebTableClose("500");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayFilesAndMimes()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayFilesAndMimes()
{
	global $dansguardian;

	try {
		$bannedextensions = $dansguardian->GetBannedExtensions();
		$bannedmimes = $dansguardian->GetBannedMimes();
	} catch (Exception $e) { }

	// File extensions
	//----------------

	$i = 0;
	$extensionlist = "<tr>";
	try {
		$ext = $dansguardian->GetPossibleExtensions();
	} catch (Exception $e) { }
	foreach ($ext as $key => $value) {
		if ($i == 6) {
			$extensionlist .= "</tr><tr>";
			$i = 0;
		}
		if (in_array($key, $bannedextensions))
			$checked = "checked";
		else
			$checked = "";

		$extensionlist .= "
		   <td><input type='checkbox' name='extensions[]' value='$key' $checked />$key</td>
		";
		$i++;
	}

	// MIME types
	//-----------

	$i = 0;
	$mimelist = "<tr>";
	try {
		$mimes = $dansguardian->GetPossibleMimes();
	} catch(Exception $e) { }
	foreach ($mimes as $key => $value) {
		if ($i == 3) {
			$mimelist .= "</tr><tr>";
			$i = 0;
		}

		if (in_array($key, $bannedmimes))
			$checked = "checked";
		else
			$checked = "";

		$mimelist .= "
		  <td>
		    <input type='checkbox' name='mimes[]' value='$key' $checked />
		    <a href='http://www.google.com/search?q=".$key."+mime+type' target='_blank'>$key</a>
		  </td>
		";
		$i++;
	}

	// HTML
	//-----

	// TODO: Get the file extension list from Antivirus

	WebFormOpen();
	WebTableOpen(WEB_LANG_EXTENSIONS, "100%");
	echo "
	  <tr>
	    <td colspan='6'>" . WEB_LANG_EXTENSIONS_EXPLAIN . "</td>
	  </tr>
	  <!-- <tr>
	    <td colspan='6'>" . WEB_LANG_EXTENSIONS_ADD . ":&#160;
	     <input type='text' name='addextension' value='' size='5' /> " . WebButtonAdd("AddFileType") . "
	    </td>
	  </tr> -->
	  $extensionlist
	  <tr>
	    <td colspan='3'>" .
	      WebButton("SelectAllExtensions", WEB_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK) .
	      WebButton("SelectNoExtensions", WEB_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK) . "
	    </td>
	    <td colspan='3' align='right'>" .
	      WebButtonUpdate("UpdateFileType") .
	      WebButtonCancel("Cancel") . "
	    </td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();

	WebFormOpen();
	WebTableOpen(WEB_LANG_MIME, "100%");
	echo "
	  <tr>
	    <td colspan='3'>" . WEB_LANG_MIME_EXPLAIN . "</td>
	  </tr>
	  <!-- <tr>
	    <td colspan='3'>" . WEB_LANG_MIME_ADD .":&#160;
	      <input type='text' name='addmime' value='' size=5 /> " . WebButtonAdd("AddMimeType") . "
	    </td>
	  </tr> -->
	  $mimelist
	  <tr>
	    <td colspan='2'>" .
	      WebButton("SelectAllMime", WEB_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK) .
	      WebButton("SelectNoMime", WEB_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK) . "
	    </td>
	    <td align='right'>" .
	      WebButtonUpdate("UpdateMimeType") .
	      WebButtonCancel("Cancel") . "
	    </td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}



///////////////////////////////////////////////////////////////////////////////
//
// DisplayGroups()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayGroups()
{
	global $dansguardian;

	$grouplist = array();
	try {
		$grouplist = $dansguardian->GetGroups();
	} catch(Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}

	sort($grouplist);

	foreach ($grouplist as $groupname) {
		$groups .= "
		  <tr>
		    <td width='70%' nowrap>$groupname</td>
		    <td nowrap>" .
		      WebButtonEdit("DisplayEditGroup[$groupname]") . "&#160; " .
		      WebButtonDelete("DeleteGroup[$groupname]") . "
		    </td>
		  </tr>
		";
	}

	WebFormOpen();
	WebTableOpen(FILEGROUP_LANG_EDIT, "350");
	echo "
	  $groups
	  <tr>
	    <td width='70%' nowrap><input style='width: 160px' type='text' name='addgroup' value='' /></td>
	    <td>" . WebButtonAdd("AddGroup") . "</td>
	  </tr>
	";
	WebTableClose("350");
	WebFormClose();
	echo "<p align='center'><a href='dansguardian.php'>" . WEBCONFIG_ICON_BACK . " " . LOCALE_LANG_BACK . "</a></p>";
}


function DisplayEditGroup($groupname)
{
	global $dansguardian;

	$items = array();
	try {
		$items = $dansguardian->GetGroupEntries($groupname);
	} catch(Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}

	foreach ($items as $item) {
		$members .= "
	  	  <tr>
		   <td width='70%' nowrap>$item</td>
		   <td nowrap>" . WebButtonDelete("DeleteGroupEntry[$item]") . "</td>
		  </tr>
		";
	}

	WebFormOpen();
	WebTableOpen(FILEGROUP_LANG_GROUP . ": " . $groupname, "400");
	echo "
	  $members
	  <tr>
	    <td width='70%' nowrap>
	      <input style='width: 160px' type='text' name='addmember' value='' />
	      <input type='hidden' name='groupname' value='$groupname' />
	    </td>
	    <td>" . WebButtonAdd("AddGroupEntry") . "</td>
	  </tr>
	";
	WebTableClose("400");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayIpRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayIpRules ()
{
	global $dansguardian;

	$banlist = array();
	$bangrouplist = array();
	$bangrouplistavailable = array();
	$exceptionlist = array();
	$exceptiongrouplist = array();
	$exceptiongrouplistavailable = array();

	$banlist = $dansguardian->GetBannedIps();
	$exceptionlist = $dansguardian->GetExceptionIps();
	$bangrouplist = $dansguardian->GetBannedIpsGroups();
	$exceptiongrouplist = $dansguardian->GetExceptionIpsGroups();


	// Groups drop-downs
	//------------------

	// This removes group that are "in use" from the available group pool:
	$groups = $dansguardian->GetGroups();
	foreach ($groups as $groupname) {
		if (! in_array($groupname, $bangrouplist))
			$bangrouplistavailable[] = $groupname;
		if (! in_array($groupname, $exceptiongrouplist))
			$exceptiongrouplistavailable[] = $groupname;
	}

	sort($banlist);
	sort($bangrouplist);
	sort($bangrouplistavailable);
	sort($exceptionlist);
	sort($exceptiongrouplist);
	sort($exceptiongrouplistavailable);

	if (count($bangrouplistavailable)) {
		$bannedpool = "
		  <tr>
	        <td width='70%' nowrap>" . WebDropDownArray("bannedgroup", "", $bangrouplistavailable, "150") . "</td>
	        <td>" . WebButtonAdd("AddBannedGroup") . "</td>
	      </tr>
		";
	} else {
		 $bannedpool = "";
	}

	if (count($exceptiongrouplistavailable)) {
		$exceptionpool = "
	      <tr>
	        <td width='70%' nowrap>" . WebDropDownArray("exceptiongroup", "", $exceptiongrouplistavailable, "150") . "</td>
	        <td>" . WebButtonAdd("AddExceptionGroup") . "</td>
	      </tr>
		";
	} else {
		 $exceptionpool = "";
	}


	// Build table rows for each category
	//-----------------------------------

	foreach ($banlist as $value) {
		$banned .= "
		  <tr>
		    <td width='70%' nowrap>$value</td>
		    <td>". WebButtonDelete("DeleteBannedIp[$value]") . "</td>
		  </tr>
		";
	}
	foreach ($exceptionlist as $value) {
		$allowed .= "
		  <tr>
		    <td width='70%' nowrap>$value</td>
		    <td>" . WebButtonDelete("DeleteExceptionIp[$value]") . "</td>
		  </tr>
		";
	}
	foreach ($bangrouplist as $key => $value) {
		if (!$value) continue;
		$bannedgroups .= "
		  <tr>
		    <td width='70%' nowrap>$value</td>
		    <td>". WebButtonDelete("DeleteBannedGroup[$value]") . "</td>
		  </tr>
		";
	}
	foreach ($exceptiongrouplist as $key => $value) {
		if (!$value) continue;
		$allowedgroups .= "
		  <tr>
		    <td width='70%' nowrap>$value</td>
		    <td>" . WebButtonDelete("DeleteExceptionGroup[$value]") . "</td>
		  </tr>
		";
	}


	// Write out HTML
	//---------------

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_BAN_IP_LIST, "100%");
	echo "
	  <tr>
	    <td class='mytableheader'>" . NETWORK_LANG_IP . "</td>
	    <td class='mytableheader'>" . FILEGROUP_LANG_GROUP . "</td>
	  </tr>
	  <tr>
	    <td width='50%'>
	      <table width='100%' cellpadding='0' cellspacing='0' border='0'>
	        $banned
	        <tr>
	          <td width='70%' nowrap><input style='width: 160px' type='text' name='banip' value='' /></td>
	          <td>" . WebButtonAdd("AddBannedIp") . "</td>
	       </tr>
	      </table>
	    </td>
	    <td width='50%'>
	      <table width='100%' cellpadding='0' cellspacing='0' border='0'>
	        $bannedgroups
	        $bannedpool
	      </table>
	    </td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();


	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_EXCEPTION_IP_LIST, "100%");
	echo "
	  <tr>
	    <td class='mytableheader'>" . NETWORK_LANG_IP . "</td>
	    <td class='mytableheader'>" . FILEGROUP_LANG_GROUP . "</td>
	  </tr>
	  <tr>
	    <td width='50%'>
	      <table width='100%' cellpadding='0' cellspacing='0' border='0'>
	        $allowed
	        <tr>
	          <td width='70%' nowrap><input style='width: 160px' type='text' name='exceptionip' value='' /></td>
	          <td>" . WebButtonAdd("AddExceptionIp") . "</td>
	        </tr>
	      </table>
	    </td>
	    <td width='50%'>
	      <table width='100%' cellpadding='0' cellspacing='0' border='0'>
	        $allowedgroups
            $exceptionpool
	      </table>
	    </td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplaySiteRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySiteRules ()
{
	global $dansguardian;

	$banlist = $dansguardian->GetBannedSitesAndUrls();
	$exceptionlist = $dansguardian->GetExceptionSitesAndUrls();
	$greylist = $dansguardian->GetGreySitesAndUrls();

	sort($banlist);
	sort($exceptionlist);
	sort($greylist);

	foreach ($banlist as $key => $value) {
		if (preg_match("/^\*\*$/", $value) || preg_match("/^\*ip$/", $value))
			continue;
		if ($value)
			$banned .= "
			  <tr>
			    <td width='70%' nowrap>$value</td>
			    <td nowrap>". WebButtonDelete("DeleteBannedSiteAndUrl[$value]") . "</td>
			  </tr>
			";
	}
	foreach ($exceptionlist as $key => $value) {
		if ($value)
			$allowed .= "
			  <tr>
			    <td width='70%' nowrap>$value</td>
			    <td nowrap>" . WebButtonDelete("DeleteExceptionSite[$value]") . "</td>
			  </tr>
			";
	}
	foreach ($greylist as $key => $value) {
		if ($value)
			$grey .= "
			  <tr>
			    <td width='70%' nowrap>$value</td>
			    <td nowrap>" . WebButtonDelete("DeleteGreySite[$value]") . "</td>
			  </tr>
			";
	}

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_BANNED_SITE_LIST, "100%");
	echo "
	  $banned
	  <tr>
	    <td width='70%' nowrap><input style='width: 160px' type='text' name='bansite' value='' /></td>
	    <td nowrap>" . WebButtonAdd("AddBannedSite") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_EXCEPTION_SITE_LIST, "100%");
	echo "
	  $allowed
	  <tr>
	    <td width='70%' nowrap><input style='width: 160px' type='text' name='exceptionsite' value='' /></td>
	    <td nowrap>" . WebButtonAdd("AddExceptionSite") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_GREY_SITE_LIST, "100%");
	echo "
	  $grey
	  <tr>
	    <td width='70%' nowrap><input style='width: 160px' type='text' name='greysite' value='' /></td>
	    <td nowrap>" . WebButtonAdd("AddGreySite") . "</td>
	  </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck()
{
	global $dansguardian;
	global $firewall;
	global $squid;

	$redirect = new FirewallRedirect();

	$fwmode = $firewall->GetMode();
	$laninterface = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_LAN);
	$extinterface = $firewall->GetInterfaceDefinition(Firewall::CONSTANT_EXTERNAL);
	$istransparent = $redirect->GetProxyTransparentState();
	$isfilterenabled = $redirect->GetProxyFilterPort();
	$isdansguardianrunning = $dansguardian->GetRunningState();
	$issquidrunning = $squid->GetRunningState();

	// Sanity Check #1
	//
	// If content filter is running, then squid should be running too
	//---------------------------------------------------------------

	if ($isdansguardianrunning && !$issquidrunning)
		WebDialogWarning(WEB_LANG_SQUID_NOT_RUNNING . " <a href='squid.php'>" . LOCALE_LANG_GO . "</a>");

	// Sanity Check #2
	//
	// We need to set the "this page has been filtered" URL.  The IP of
	// the LAN interface can change, so we kludge it a bit here.
	// Note: some users want to use their own custom page here, so we
	// only stomp on URLs that look like i) the DansGuardian default or
	// ii) the webconfig default.
	//---------------------------------------------------------------------

	if ($extinterface && (($fwmode == Firewall::CONSTANT_STANDALONE) || ($fwmode == Firewall::CONSTANT_TRUSTEDSTANDALONE)))
		$useinterface = $extinterface;
	else if ($laninterface)
		$useinterface = $laninterface;
	else
		return;

	$if = new Iface($useinterface);
	if ($if->IsConfigured()) {
		try {
			$ip = $if->GetLiveIp();
			if(!$ip) return;
		} catch (Exception $e) {
			return;
		}
	} else {
		return;
	}

	$ourconfig = $dansguardian->GetAccessDeniedUrl();

	if ($ip && (preg_match("/\/filtered.php/", $ourconfig) || preg_match("/YOURSERVER/", $ourconfig)))
		$dansguardian->SetAccessDeniedUrl("http://$ip:82/public/filtered.php");
}

?>
