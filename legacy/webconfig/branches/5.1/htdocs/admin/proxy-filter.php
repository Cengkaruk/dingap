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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/ClamAv.class.php');
require_once('../../api/DansGuardianAv.class.php');
require_once('../../api/ContentFilterUpdates.class.php');
require_once('../../api/Daemon.class.php');
require_once('../../api/Iface.class.php');
require_once('../../api/Network.class.php');
require_once('../../api/Firewall.class.php');
require_once('../../api/FileGroup.class.php');
require_once('../../api/Freshclam.class.php');
require_once('../../api/UserManager.class.php');
require_once('../../api/GroupManager.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . ContentFilterUpdates::CONSTANT_NAME . "'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, true, $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-dansguardianav.png', WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$dansguardian = new DansGuardian();

$squid = new Daemon('squid');
$group = new FileGroup('none', DansGuardian::FILE_CONFIG_FILTER_GROUP); // For language tags
$network = new Network(); // For language tags
$firewall = new Firewall();
$group_id = (isset($_POST['group_id']) && !isset($_POST['Cancel'])) ? $_POST['group_id'] : 1;
$selected_users = array();

// Daemon start/stop etc
//----------------------

if (isset($_POST['EnableBoot'])) {
	try {
		$dansguardian->SetBootState(true);
		$freshclam = new Freshclam();
		$freshclam->SetBootState(true);
		$clamav = new ClamAv();
		$clamav->SetBootState(true);
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
			$freshclam = new Freshclam();
			$freshclam->SetRunningState(true);
			$clamav = new ClamAv();
			$clamav->SetRunningState(true);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	} else
		WebDialogWarning(WEB_LANG_SQUID_NOT_RUNNING . "<a href='proxy.php'>" . LOCALE_LANG_GO . "</a>");
} else if (isset($_POST['StopDaemon'])) {
	try
	{
		$dansguardian->SetRunningState(false);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

// Site rules
//-----------

} else if (isset($_POST['AddExceptionSite'])) {
	try {
		$dansguardian->AddExceptionSiteAndUrl($_POST['exceptionsite'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
} else if (isset($_POST['DeleteExceptionSite'])) {
	try {
		$dansguardian->DeleteExceptionSiteAndUrl(key($_POST['DeleteExceptionSite']), $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
} else if (isset($_POST['AddBannedSite'])) {
	try {
		$dansguardian->AddBannedSiteAndUrl($_POST['bansite'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
} else if (isset($_POST['DeleteBannedSiteAndUrl'])) {
	try {
		$dansguardian->DeleteBannedSiteAndUrl(key($_POST['DeleteBannedSiteAndUrl']), $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
} else if (isset($_POST['AddGreySite'])) {
	try {
		$dansguardian->AddGreySiteAndUrl($_POST['greysite'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;
} else if (isset($_POST['DeleteGreySite'])) {
	try {
		$dansguardian->DeleteGreySiteAndUrl(key($_POST['DeleteGreySite']), $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplaySiteRules'] = true;

// IP rules
//---------

} else if (isset($_POST['AddExceptionIp'])) {
	try {
		$dansguardian->AddExceptionIp($_POST['exceptionip'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['DeleteExceptionIp'])) {
	try {
		$dansguardian->DeleteExceptionIp(key($_POST['DeleteExceptionIp']), $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['AddBannedIp'])) {
	try {
		$dansguardian->AddBannedIp($_POST['banip'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['DeleteBannedIp'])) {
	try {
		$dansguardian->DeleteBannedIp(key($_POST['DeleteBannedIp']), $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['AddExceptionGroup'])) {
	try {
		$dansguardian->AddExceptionIpGroup($_POST['exceptiongroup'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['AddBannedGroup'])) {
	try {
		$dansguardian->AddBannedIpGroup($_POST['bannedgroup'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['DeleteExceptionGroup'])) {
	try {
		$dansguardian->DeleteExceptionIpGroup(key($_POST['DeleteExceptionGroup']), $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['DeleteBannedGroup'])) {
	try {
		$dansguardian->DeleteBannedIpGroup(key($_POST['DeleteBannedGroup']), $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayIpRules'] = true;

// File/MIME configuration updates
//--------------------------------

} else if (isset($_POST['AddFileType'])) {
	try {
		$extension = preg_replace('/^\./', '', $_POST['add_extension']);
		$dansguardian->AddUserExtension($extension, $_POST['extension_description']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['DeleteFileType'])) {
	try {
		$extension = preg_replace('/^\./', '', $_POST['delete_extension']);
		$dansguardian->DeleteUserExtension($extension);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['AddMimeType'])) {
	try {
		$dansguardian->AddUserMimeType($_POST['add_mime'], $_POST['mime_description']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['DeleteMimeType'])) {
	try {
		$dansguardian->DeleteUserMimeType($_POST['delete_mime']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['UpdateMimeType'])) {
	try {
		$dansguardian->SetBannedMimes($_POST['mimes'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;
} else if (isset($_POST['UpdateFileType'])) {
	try {
		$dansguardian->SetBannedExtensions($_POST['extensions'], $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayFilesAndMimes'] = true;

// Blacklists
//-----------

} else if (isset($_POST['UpdateBlacklists'])) {
	$enabledlist = array();
	if (isset($_POST['blacklist'])) {
		foreach ($_POST['blacklist'] as $listname => $state)
			$enabledlist[] = $listname;
	}

	try {
		$dansguardian->SetBlacklists($enabledlist, $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayBlacklists'] = true;

// Weighted Phrasing
//-----------------

} else if (isset($_POST['UpdateWeightedPhrasing'])) {
	$enabledlist = array();
	if (!isset($_POST['phraselist'])) $_POST['phraselist'] = array();
	foreach ($_POST['phraselist'] as $listname => $state)
		$enabledlist[] = $listname;

	try {
		$dansguardian->SetWeightedPhraseLists($enabledlist, $group_id);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayWeightedPhrasing'] = true;

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
	$_POST['DisplayIpRules'] = true;
} else if (isset($_POST['AddGroupEntry'])) {
	try {
		$dansguardian->AddGroupEntry($_POST['groupname'], $_POST['addmember']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayEditGroup'][$_POST['groupname']] = true;
} else if (isset($_POST['DeleteGroupEntry'])) {
	try {
		$dansguardian->DeleteGroupEntry($_POST['groupname'], key($_POST['DeleteGroupEntry']));
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
	$_POST['DisplayEditGroup'][$_POST['groupname']] = true;
} else if (isset($_POST['EditGroupEntry'])) {
	$group_id = (key($_POST['EditGroupEntry']));

// Filter Groups
// -------------

} else if (isset($_POST['AddFilterGroup'])) {
	try {
		$group_id = $dansguardian->AddFilterGroup($_POST['FilterGroupName']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateFilterGroup'])) {
	try {
		$dansguardian->SetGroupName($_POST['groupname'], $group_id);
		$dansguardian->SetFilterMode($_POST['groupmode'], $group_id);
		$dansguardian->SetReportingLevel($_POST['reportinglevel'], $group_id);
		$dansguardian->SetNaughtynessLimit($_POST['naughtynesslimit'], $group_id);
		$dansguardian->SetContentScan($_POST['disablecontentscan'], $group_id);
		$dansguardian->SetDeepUrlAnalysis($_POST['deepurlanalysis'], $group_id);
		$dansguardian->SetDownloadBlock($_POST['blockdownloads'], $group_id);
		if ($_POST['setbaneverything']) {
			$dansguardian->AddBannedSiteAndUrl('**', $group_id);
		} else {
			$dansguardian->DeleteBannedSiteAndUrl('**', $group_id);
		}
		if ($_POST['setnoip']) {
			$dansguardian->AddBannedSiteAndUrl('*ip', $group_id);
		} else {
			$dansguardian->DeleteBannedSiteAndUrl('*ip', $group_id);
		}

		$dansguardian->DeleteFilterGroupUsers($group_id);
		if (isset($_POST['inc_users'])) {
			foreach ($_POST['inc_users'] as $user)
				$dansguardian->AddFilterGroupUser($group_id, $user);
		}
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());

		// Set this global variable so we can reselect the users...
		if (isset($_POST['inc_users']))
			$selected_users = $_POST['inc_users'];
	}
} else if (isset($_POST['DeleteFilterGroup'])) {
	try {
		$dansguardian->DeleteFilterGroup($group_id);
		$group_id = 1;
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

// Global options
// --------------

} else if (isset($_POST['UpdateReverseLookups'])) {
	try {
		$dansguardian->SetReverseLookups($_POST['reverseaddresslookups']);
		$dansguardian->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

// Firewall transparent mode
//--------------------------

} else if (isset($_POST['EnableTransparent'])) {
	try {
		$firewall->SetSquidFilterPort('8080');
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
	$old_dansguardian = new Daemon('dansguardian');
	if ($old_dansguardian->GetRunningState()) {
		WebDialogWarning(WEB_LANG_CANNOT_HAVE_BASIC_AND_ADVANCED_SIMULTANEOUSLY);
		WebFooter();
		exit;
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if (isset($_POST['DisplayFilesAndMimes'])) {
	DisplayFilesAndMimes();
} else if (isset($_POST['DisplayEditGroup'])) {
	DisplayEditGroup(key($_POST['DisplayEditGroup']));
} else if (isset($_POST['DisplayIpRules'])) {
	DisplayIpRules();
} else if (isset($_POST['DisplaySiteRules'])) {
	DisplaySiteRules();
} else if (isset($_POST['DisplayBlacklists'])) {
	DisplayBlacklists();
} else if (isset($_POST['DisplayWeightedPhrasing'])) {
	DisplayWeightedPhrasing();
} else if ($group_id === "0") {
	DisplayAddFilterGroup();
} else {

	WebDialogDaemon('dansguardian-av');

	try {
		SanityCheck();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}


	if (isset($_POST['ConfirmDeleteFilterGroup'])) {
		DisplayConfirmDeleteFilterGroup($group_id);
		DisplayEditFilterGroup();
	} else {
		WebServiceStatus(ContentFilterUpdates::CONSTANT_NAME, "ClearSDN Content Filter Updates");
		DisplayConfig();
		DisplayEditFilterGroup();
	}
	DisplaySummary();
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
	global $dansguardian;

	try {
		$groups = $dansguardian->GetFilterGroups();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}

	echo "<div id='filtergroupsummary' style='display: none;'>";
	WebFormOpen();
	WebTableOpen(WEB_LANG_FILTER_GROUP_SUMMARY, '100%');
	WebTableHeader('|' . WEB_LANG_FILTER_GROUP . '|' . WEB_LANG_USERS . '|' .
		WEB_LANG_FILE_EXTENSION_MIME_RESTRICTIONS . '|' .
		WEB_LANG_WEB_SITE_CONTROL . '|' . WEB_LANG_ACTIVE_CONTENT_SCANNING . '|' .
		DANSGUARDIAN_LANG_BLACKLISTS . '|', '100%');

	$rowcounter = 0;
	foreach ($groups as $id => $group) {
		$users = 0;
		if ($id != 1) {
			try {
				$users = count($dansguardian->GetFilterGroupUsers($id));
			} catch (Exception $e) {
			}
		}
		$users = ($id == 1) ? LOCALE_LANG_ALL : $users;

		$total = 0;
		$bannedextensions = 0;
		try {
			$bannedextensions = count($dansguardian->GetBannedExtensions($id));
		} catch (Exception $e) { }
		$bannedmimes = 0;
		try {
			$bannedmimes = count($dansguardian->GetBannedMimeTypes($id));
		} catch (Exception $e) { }
		$total = $bannedextensions + $bannedmimes;
		if ($total) $bannedextensions = $total;

		$total = 0;
		$banlist = 0;
		try {
			$banlist = count($dansguardian->GetBannedSitesAndUrls($id));
		} catch (Exception $e) { }
		$exceptionlist = 0;
		try {
			$exceptionlist = count($dansguardian->GetExceptionSitesAndUrls($id));
		} catch (Exception $e) { }
		$greylist = 0;
		try {
			$greylist = count($dansguardian->GetGreySitesAndUrls($id));
		} catch (Exception $e) { }
		$total = $banlist + $exceptionlist + $greylist;
		if ($total) $banlist = $banlist;

		$weightedphrasing = 0;
		try {
			$weightedphrasing = count($dansguardian->GetWeightedPhraseLists($id));
		} catch (Exception $e) { }

		$blacklists = 0;
		try {
			$blacklists = count($dansguardian->GetBlacklists($id));
		} catch (Exception $e) { }

		if ($users > 0 || $id == 1) {
			$statusclass = 'iconenabled';
			$rowclass = 'rowenabled';
		} else {
			$statusclass = 'icondisabled';
			$rowclass = 'rowdisabled';
		}
		$rowclass .= ($rowcounter % 2) ? 'alt' : '';
		printf('<tr class=\'%s\'><td class=\'%s\'>&#160;</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			$rowclass, $statusclass, $group['groupname'], $users, $bannedextensions,
			$banlist, $weightedphrasing, $blacklists, WebButtonEdit('EditGroupEntry[' . $id . ']'));
		$rowcounter++;
	}

	WebTableClose('100%');
	WebFormClose();
	echo '</div>';
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBlacklists()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBlacklists()
{
	global $dansguardian;
	global $group_id;

	$allblacklists = array();
	try {
		$allblacklists = $dansguardian->GetPossibleBlacklists();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	if (count($allblacklists) == 0) return;

	$activeblacklists = array();
	try {
		$activeblacklists = $dansguardian->GetBlacklists($group_id);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$id = 0;
	$row = null;
	$state = null;
	$listout = '';

	foreach ($allblacklists as $blacklist) {
		if (in_array($blacklist['name'], $activeblacklists))
			$state = 'checked';
		else $state = null;

		$listout .= "
		  <tr>
		    <td " . (($row != null) ? "style='background: $row;'" : '') . ">
		      <input id='list$id' type='checkbox' name='blacklist[$blacklist[name]]' value='$state' $state />
			  $blacklist[name]
			</td>
		    <td " .  (($row != null) ? "style='background: $row;'" : '') . '>' . (($state != null) ? '<b>' : '') . $blacklist[description] . (($state != null) ? '</b>' : '') . '</td>
		  </tr>
		';
		$id++; if ($row == '#e5e5e5') $row = null; else $row = '#e5e5e5';
	}

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_BLACKLISTS, '80%');
	echo "
	  $listout
	  <tr>
	   <td colspan='2' align='center'>" .
	      WebButton('AllLists', WEB_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK, array('type' => 'button', 'onclick' => 'SelectAllLists()')) . '&nbsp;' .
	      WebButton('NoLists', WEB_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK, array('type' => 'button', 'onclick' => 'SelectNoLists()')) . '&nbsp;' .
	      WebButtonUpdate('UpdateBlacklists') . '&nbsp;' .
	      WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) .
		"<input type='hidden' name='group_id' value='$group_id'></td>
	  </tr>
	";
	WebTableClose('80%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayWeightedPhrasing()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayWeightedPhrasing()
{
	global $dansguardian;
	global $group_id;

	$alllists = array();
	$activelists = array();

	try {
		$alllists = $dansguardian->GetPossibleWeightedPhrase();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	try {
		$activelists = $dansguardian->GetWeightedPhraseLists($group_id);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	if (count($alllists) == 0) return;

	$id = 0;
	$row = null;
	$state = null;
	foreach ($alllists as $phraselist) {
		if (in_array($phraselist[name], $activelists))
			$state = 'checked';
		else $state = null;

		$listout .= "
		  <tr>
		    <td " . (($row != null) ? "style='background: $row;'" : '') . ">
		      <input id='phrase$id' type='checkbox' name='phraselist[$phraselist[name]]' value='$state' $state />
			  $phraselist[name]
			</td>
		    <td nowrap " . (($row != null) ? "style='background: $row;'" : '') . '>' . (($state != null) ? '<b>' : '') . $phraselist[description] . (($state != null) ? '</b>' : '') . "</td>
		  </tr>
		";
		$id++; if ($row == '#e5e5e5') $row = null; else $row = '#e5e5e5';
	}

	WebDialogInfo(WEB_LANG_PHRASELIST_HELP);
	WebFormOpen();
	WebTableOpen(WEB_LANG_ACTIVE_CONTENT_SCANNING, '80%');
	echo "
	  $listout
	  <tr>
	   <td colspan='2' align='center'>" .
	      WebButton('AllPhrases', WEB_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK, array('type' => 'button', 'onclick' => 'SelectAllPhrases()')) . '&nbsp;' .
	      WebButton('NoPhrases', WEB_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK, array('type' => 'button', 'onclick' => 'SelectNoPhrases()')) . '&nbsp;' .
	      WebButtonUpdate('UpdateWeightedPhrasing') . '&nbsp;' .
	      WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . "</td>
	   <input type='hidden' name='group_id' value='$group_id'>
	  </tr>
	";
	WebTableClose('80%');
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayFilterGroups()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayFilterGroups()
{
	global $dansguardian;

	try {
		$groups = $dansguardian->GetFilterGroups();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}

	echo '<div id="filtergroups">';
	WebFormOpen();
	WebTableOpen(WEB_LANG_GOTO_ADVANCED, '100%');
	WebTableHeader(WEB_LANG_FILTER_GROUP . '|' . WEB_LANG_ACTION, '100%');

	foreach ($groups as $id => $group) {
		printf('<tr><td>%d - %s</td>', $id, $group['groupname']);
		echo('<td nowrap>');
		echo WebButtonEdit(sprintf('DisplayEditFilterGroup[%d]', $id));
		if ($id != 1)
			echo WebButtonDelete(sprintf('DeleteFilterGroup[%d]', $id));
		echo '</td>';
	}

	WebTableClose('100%');
	WebFormClose();
	echo '</div>';
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayAddFilterGroup()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAddFilterGroup()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_FILTER_GROUP, '80%');
	echo "
		<tr>
			<td class='mytablesubheader' nowrap width='30%'>" . WEB_LANG_FILTER_GROUP . "</td>
			<td><input id='filtergroupname' type='text' name='FilterGroupName' value=''></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&nbsp;</td>
			<td nowrap>" .  WebButtonAdd('AddFilterGroup') . " " . WebButtonCancel('Cancel') . "</td>
		</tr>
	";
	WebTableClose('80%');
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditFilterGroup()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditFilterGroup()
{
	global $dansguardian;
	global $group_id;
	global $selected_users;

	$group = null;
	$groups = array();

	try {
		$groups = $dansguardian->GetFilterGroups();
		$fglist[0] = WEB_LANG_ADD_FILTER_GROUP;
		foreach ($groups as $id => $group)
			$fglist[$id] = $group['groupname'];
		$select_groups = WebDropDownHash('group_id', $group_id, $fglist, 0, 'form.submit();');
		$group = $dansguardian->GetFilterGroupConfiguration($group_id);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}

	echo '<div id="filtergroup" style="display: inline;">';
	WebFormOpen();
	WebTableOpen(sprintf('%s #%d: %s', WEB_LANG_EDIT_FILTER_GROUP, $group_id, $group['groupname']), '80%');
	echo "
		<tr>
			<td class='mytablesubheader' nowrap width='30%'>" . WEB_LANG_SELECT_FILTER_GROUP . "</td>
			<td>" . $select_groups . "</td>
		</tr>
	";

	$options = array();
	printf("<tr><td class='mytablesubheader' nowrap>%s</td>", WEB_LANG_FILTER_MODE);
	$options[0] = WEB_LANG_MODE_BANNED;
	$options[1] = WEB_LANG_MODE_FILTERED;
	$options[2] = WEB_LANG_MODE_UNFILTERED;
	printf('<td>%s</td></tr>',
		WebDropDownHash('groupmode', $group['groupmode'], $options));

	$options = array();
	printf("<tr><td class='mytablesubheader' nowrap>%s</td>", WEB_LANG_VIRUS_SCAN);
	$options['off'] = LOCALE_LANG_ON;
	$options['on'] = LOCALE_LANG_OFF;
	printf('<td>%s</td></tr>',
		WebDropDownHash('disablecontentscan', $group['disablecontentscan'], $options));

	$options = array();
	printf("<tr><td class='mytablesubheader' nowrap>%s</td>", WEB_LANG_DEEP_URL_ANALYSIS);
	$options['on'] = LOCALE_LANG_ON;
	$options['off'] = LOCALE_LANG_OFF;
	printf('<td>%s</td></tr>',
		WebDropDownHash('deepurlanalysis', $group['deepurlanalysis'], $options));

	$options = array();
	printf("<tr><td class='mytablesubheader' nowrap>%s</td>", WEB_LANG_BLOCK_DOWNLOADS);
	$options['on'] = LOCALE_LANG_ON;
	$options['off'] = LOCALE_LANG_OFF;
	if (!isset($group['blockdownloads'])) $group['blockdownloads'] = 'off';
	printf('<td>%s</td></tr>',
		WebDropDownHash('blockdownloads', $group['blockdownloads'], $options));

	$options = array();
	printf("<tr><td class='mytablesubheader' nowrap>%s</td>", WEB_LANG_ACTIVE_CONTENT_SCANNING_SENSITIVITY);
	$options[ 50] = LOCALE_LANG_VERYHIGH;
	$options[100] = LOCALE_LANG_HIGH;
	$options[150] = LOCALE_LANG_MEDIUM;
	$options[200] = LOCALE_LANG_LOW;
	$options[400] = LOCALE_LANG_VERYLOW;
	$options[99999] = LOCALE_LANG_DISABLED;
	printf('<td>%s</td></tr>',
		WebDropDownHash('naughtynesslimit', $group['naughtynesslimit'], $options));

	$options = array();
	printf("<tr><td class='mytablesubheader' nowrap>%s</td>", DANSGUARDIAN_LANG_REPORTING_LEVEL);
	$options[-1] = DANSGUARDIAN_LANG_STEALTH_MODE;
	$options[ 1] = DANSGUARDIAN_LANG_SHORT_REPORT;
	$options[ 2] = DANSGUARDIAN_LANG_FULL_REPORT;
	$options[ 3] = DANSGUARDIAN_LANG_CUSTOM_REPORT;
	printf('<td>%s</td></tr>',
		WebDropDownHash('reportinglevel', $group['reportinglevel'], $options));

	$banlist = array();

	try {
		$banlist = $dansguardian->GetBannedSitesAndUrls($group_id);
	} catch(Exception $e) { }

	if (in_array('*ip', $banlist)) $noip = true;
	if (in_array('**', $banlist)) $baneverything = true;

	printf("<tr><td class='mytablesubheader' nowrap>%s</td><td>", DANSGUARDIAN_LANG_NO_IPS);

	if ($noip)
		echo "<input type='checkbox' name='setnoip' value='1' checked /></td></tr>";
	else
		echo "<input type='checkbox' name='setnoip' value='1' /></td></tr>";

	printf("<tr><td class='mytablesubheader' nowrap>%s</td><td>", DANSGUARDIAN_LANG_BAN_EVERYTHING);

	if ($baneverything)
		echo "<input type='checkbox' name='setbaneverything' value='1' checked /></td></tr>";
	else
		echo "<input type='checkbox' name='setbaneverything' value='1' /></td></tr>";

	echo "
  <tr>
   <td nowrap class='mytablesubheader'>" . WEB_LANG_FILE_EXTENSION_MIME_RESTRICTIONS . '</td>
   <td nowrap>' . WebButtonEdit('DisplayFilesAndMimes') . "</td>
  </tr>
  <tr>
   <td nowrap class='mytablesubheader'>" . WEB_LANG_WEB_SITE_CONTROL . "</td>
   <td>" . WebButtonEdit("DisplaySiteRules") . "</td>
  </tr>
  <tr>
   <td nowrap class='mytablesubheader'>" . WEB_LANG_ACTIVE_CONTENT_SCANNING . "</td>
   <td>" . WebButtonEdit("DisplayWeightedPhrasing") . "</td>
  </tr>
  <tr>
   <td nowrap class='mytablesubheader'>" . DANSGUARDIAN_LANG_BLACKLISTS . "</td>
   <td>" . WebButtonEdit("DisplayBlacklists") . "</td>
  </tr>
	";

	$users = array();
	$members = array();
	$all_members = array();

	if ($group_id != 1) {
		try {
			$um = new UserManager();
			$members = $dansguardian->GetFilterGroupUsers($group_id);
			sort($members);
			try {
				for ($i = 2; $i <= DansGuardian::MAX_FILTER_GROUPS; $i++) {
					if ($i == $group_id) continue;
					$all_members = array_merge($all_members, $dansguardian->GetFilterGroupUsers($i));
				}
			} catch (Exception $e) { }
			$users = array_diff($um->GetAllUsers(UserManager::TYPE_PROXY), array_merge($all_members, $members));
			$users = array_merge($users, $members);
			sort($users);
		} catch (Exception $e) {
			WebTableClose('80%');
			WebFormClose();
			echo '</div>';
			WebDialogWarning($e->getMessage());
			return;
		}

		printf("<tr><td class='mytablesubheader' valign='top'>" . WEB_LANG_USERS . "</td><td width='50%%'><select %sstyle='width: 200px;' multiple size='12' name='inc_users[]'>", (!count($users)) ? 'disabled ' : '');
		foreach ($users as $user) {
			printf("<option%s>$user</option>", (in_array($user, $members)) ? ' selected' : '');
		}
		echo "</select><br /><font class='small'>" . WEB_LANG_MULTIPLE_SELECT . "</font></td></tr>";
	}

	echo "
	<tr>
		<td class='mytablesubheader'>&nbsp;</td>
	    <td nowrap><input type='hidden' name='groupname' value='" . ($group_id == 1 ? DANSGUARDIAN_LANG_DEFAULT_GROUP : $group['groupname']) . "' />" .
		WebButtonUpdate('UpdateFilterGroup');

	if ($group_id != 1) 
		echo WebButtonDelete('ConfirmDeleteFilterGroup');
	echo "</td></tr>";

	WebTableClose('80%');
	WebFormClose();
	echo '</div>';
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmDeleteFilterGroup()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmDeleteFilterGroup($group_id)
{
	global $dansguardian;
	$groups = $dansguardian->GetFilterGroups();
	$groupname = '';
	foreach ($groups as $id => $group) {
		if ($group_id == $id) {
			$groupname = $group['groupname'];
			break;
		}
	}
	WebFormOpen();
	WebDialogInfo(
		WEB_LANG_CONFIRM_DELETE_FILTER_GROUP . " <b><i>" . $groupname . "</i></b>?<br /><br />" .
		WebButtonConfirm('DeleteFilterGroup') . " " . WebButtonCancel('CancelDelete')
	);
	echo "<input type='hidden' name='group_id' value='$group_id'>";
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

	$reverseaddresslookups = $dansguardian->GetReverseLookups();
	if ($reverseaddresslookups != 'on' && $reverseaddresslookups != 'off')
		$reverseaddresslookups = 'off';

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "80%");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap width='30%'>" . WEB_LANG_BAN_IP_EXEMPT_USER_LIST . "</td>
			<td>" . WebButtonEdit('DisplayIpRules') . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap width='30%'>" . WEB_LANG_REVERSE_ADDRESS_LOOKUPS . "</td>
			<td>" .  WebDropDownHash('reverseaddresslookups', $reverseaddresslookups, array('on' => LOCALE_LANG_ON, 'off' => LOCALE_LANG_OFF)) . 
			WebButtonUpdate("UpdateReverseLookups") . "</td>
		</tr>
	    <tr>
			<td class='mytablesubheader' nowrap>&nbsp;</td>
			<td>" .
				WebButton('ToggleGroupSummary', WEB_LANG_TOGGLE_SUMMARY, WEBCONFIG_ICON_TOGGLE,

				array('type' => 'button', 'onclick' => "ToggleSummaryWeak()", 'id' => 'showsummary')) . "
			</td>
		</tr>
	";
	WebTableClose("80%");
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
	global $group_id;

	try {
		$bannedextensions = $dansguardian->GetBannedExtensions($group_id);
	} catch (Exception $e) { }
	try {
		$bannedmimes = $dansguardian->GetBannedMimeTypes($group_id);
	} catch (Exception $e) { }

	// File extensions
	//----------------

	$i = 0; $id = 0;
	$extensionlist = "<tr>";
	try {
		$ext = $dansguardian->GetExtensions();
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
		   <td><input id='file$id' type='checkbox' name='extensions[]' value='$key' $checked />$key</td>
		";
		$i++; $id++;
	}

	// User file extensions
	//---------------------

	$i = 0;
	$user_extensions = array();
	$user_extensionlist = '<tr>';
	try {
		$user_extensions = $dansguardian->GetUserExtensions();
	} catch (Exception $e) { }
	foreach ($user_extensions as $key => $value) {
		if ($i == 6) {
			$user_extensionlist .= '</tr><tr>';
			$i = 0;
		}
		if (in_array($key, $bannedextensions))
			$checked = 'checked';
		else
			$checked = '';

		$user_extensionlist .= "
		   <td><input id='file$id' type='checkbox' name='extensions[]' value='$key' $checked />$key</td>
		";
		$i++; $id++;
	}

	// MIME types
	//-----------

	$i = 0; $id = 0;
	$mimelist = '<tr>';
	try {
		$mimes = $dansguardian->GetMimeTypes();
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
		    <input id='mime$id' type='checkbox' name='mimes[]' value='$key' $checked />
		    <a href='http://www.google.com/search?q=$key+mime+type' target='_blank'>$key</a>
		  </td>
		";
		$i++; $id++;
	}

	// User MIME types
	//----------------

	$i = 0;
	$user_mimes = array();
	$user_mimelist = "<tr>";
	try {
		$user_mimes = $dansguardian->GetUserMimeTypes();
	} catch(Exception $e) { }
	foreach ($user_mimes as $key => $value) {
		if ($i == 3) {
			$user_mimelist .= "</tr><tr>";
			$i = 0;
		}

		if (in_array($key, $bannedmimes))
			$checked = "checked";
		else
			$checked = "";

		$user_mimelist .= "
		  <td>
		    <input id='mime$id' type='checkbox' name='mimes[]' value='$key' $checked />$key
		  </td>
		";
		$i++; $id++;
	}

	// HTML
	//-----

	// TODO: Get the file extension list from Antivirus

	WebFormOpen();
	WebTableOpen(WEB_LANG_EXTENSIONS, '100%');
	echo $extensionlist;
	if (count($user_extensions)) {
		echo "<tr><td colspan='6' class='mytableheader'>" . WEB_LANG_USER_EXTENSIONS . '</td></tr>';
		echo $user_extensionlist;
	}
	echo "
	  <tr>
	    <td colspan='6' align='center'>" .
	      WebButton('AllExtensions', WEB_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK, array('type' => 'button', 'onclick' => 'SelectAllExtensions()')) .
	      WebButton('NoExtensions', WEB_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK, array('type' => 'button', 'onclick' => 'SelectNoExtensions()')) . 
	      WebButtonUpdate("UpdateFileType") .
		  WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . '<br>' .
		  WEB_LANG_CUSTOM_EXTENSION . ": <input type='text' name='add_extension'> " . WebButtonAdd('AddFileType');
	if (count($user_extensions)) {
		$extension_array = array();
		foreach ($user_extensions as $key => $value) $extension_array[] = $key;
		echo ' ' . WebDropDownArray('delete_extension', $extension_array[0], $extension_array) . ' ' . WebButtonDelete('DeleteFileType');
	}
	echo "</td>
		<input type='hidden' name='group_id' value='$group_id'>
	  </tr>
	";
	WebTableClose('100%');
	WebFormClose();

	WebFormOpen();
	WebTableOpen(WEB_LANG_MIME, '100%');
	echo $mimelist;
	if (count($user_mimes)) {
		echo "<tr><td colspan='3' class='mytableheader'>" . WEB_LANG_USER_MIME_TYPES . '</td></tr>';
		echo $user_mimelist;
	}
	echo "<tr>
          <td colspan='3' align='center'>" . 
	      WebButton('AllMimes', WEB_LANG_SELECT_ALL, WEBCONFIG_ICON_CHECKMARK, array('type' => 'button', 'onclick' => 'SelectAllMimes()')) .
	      WebButton('NoMimes', WEB_LANG_SELECT_NONE, WEBCONFIG_ICON_XMARK, array('type' => 'button', 'onclick' => 'SelectNoMimes()')) .
	      WebButtonUpdate('UpdateMimeType') . 
		  WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . '<br>' .
          WEB_LANG_CUSTOM_TYPE . ": <input type='text' name='add_mime'> " . WebButtonAdd('AddMimeType');
	if (count($user_mimes)) {
		$mime_array = array();
		foreach ($user_mimes as $key => $value) $mime_array[] = $key;
		echo ' ' . WebDropDownArray('delete_mime', $mime_array[0], $mime_array) . ' ' . WebButtonDelete('DeleteMimeType');
	}
	echo "</td>
		<input type='hidden' name='group_id' value='$group_id'></tr>
	";
	WebTableClose('100%');
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
		      WebButtonEdit("DisplayEditGroup[$groupname]") . " " .
		      WebButtonDelete("DeleteGroup[$groupname]") . "
		    </td>
		  <tr>
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
	echo "<p align='center'>" . WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . "</p>";
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditGroup()
//
///////////////////////////////////////////////////////////////////////////////

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
		  <tr>
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

function DisplayIpRules()
{
	global $dansguardian;

	$banlist = array();
	$bangrouplist = array();
	$bangrouplistavailable = array();
	$exceptionlist = array();
	$exceptiongrouplist = array();
	$exceptiongrouplistavailable = array();

	try {
		$banlist = $dansguardian->GetBannedIps();
	} catch(Exception $e) {
		WebDialogWarning($e->getMessage());
	}
	try {
		$exceptionlist = $dansguardian->GetExceptionIps();
	} catch(Exception $e) {
		WebDialogWarning($e->getMessage());
	}
	sort($banlist);
	sort($exceptionlist);


	// Build table rows for each category
	//-----------------------------------

	foreach ($banlist as $value) {
		$banned .= "
		  <tr>
		    <td width='70%' nowrap>$value</td>
		    <td>". WebButtonDelete("DeleteBannedIp[$value]") . "</td>
		  <tr>
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

	// Write out HTML
	//---------------

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_BAN_IP_LIST, "400");
	WebTableHeader(NETWORK_LANG_IP . "|");
	echo "
		$banned
		<tr>
			<td width='70%' nowrap><input style='width: 160px' type='text' name='banip' value='' /></td>
			<td>" . WebButtonAdd("AddBannedIp") . "</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>" .  WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . "</td>
		</tr>
	";
	WebTableClose("400");
	WebFormClose();


	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_EXCEPTION_IP_LIST, "400");
	WebTableHeader(NETWORK_LANG_IP . "|");
	echo "
		$allowed
		<tr>
			<td width='70%' nowrap><input style='width: 160px' type='text' name='exceptionip' value='' /></td>
			<td>" . WebButtonAdd("AddExceptionIp") . "</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>" .  WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . "</td>
		</tr>
	";
	WebTableClose("400");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplaySiteRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySiteRules()
{
	global $group_id;
	global $dansguardian;

	try {
		$banlist = $dansguardian->GetBannedSitesAndUrls($group_id);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
	try {
		$exceptionlist = $dansguardian->GetExceptionSitesAndUrls($group_id);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
	try {
		$greylist = $dansguardian->GetGreySitesAndUrls($group_id);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	sort($banlist);
	sort($exceptionlist);
	sort($greylist);

	$rowindex = 0;
	foreach ($banlist as $key => $value) {
		if (preg_match("/^\*\*/", $value) || preg_match("/^\*ip/", $value))
			continue;

		if ($value) {
			$rowclass = 'rowenabled';
			$rowclass .= ($rowindex % 2) ? 'alt' : '';
			$rowindex++;
			$banned .= "
			  <tr class='$rowclass'>
			    <td width=70% nowrap>$value</td>
			    <td>". WebButtonDelete("DeleteBannedSiteAndUrl[$value]") . "</td>
			  <tr>
			";
		}
	}
	$rowindex = 0;
	foreach ($exceptionlist as $key => $value) {
		if ($value) {
			$rowclass = 'rowenabled';
			$rowclass .= ($rowindex % 2) ? 'alt' : '';
			$rowindex++;
			$allowed .= "
			  <tr class='$rowclass'>
			    <td width='70%' nowrap>$value</td>
			    <td>" . WebButtonDelete("DeleteExceptionSite[$value]") . "</td>
			  </tr>
			";
		}
	}
	$rowindex = 0;
	foreach ($greylist as $key => $value) {
		if ($value) {
			$rowclass = 'rowenabled';
			$rowclass .= ($rowindex % 2) ? 'alt' : '';
			$rowindex++;
			$grey .= "
			  <tr class='$rowclass'>
			    <td width='70%' nowrap>$value</td>
			    <td>" . WebButtonDelete("DeleteGreySite[$value]") . "</td>
			  </tr>
			";
		}
	}

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_BANNED_SITE_LIST, "80%");
	echo "
	  <tr>
	    <td colspan='2' class='mytableheader'>" . NETWORK_LANG_IP . " / " . NETWORK_LANG_HOSTNAME . "</td>
	  </tr>
	  $banned
	  <tr>
	    <td width='70%' nowrap><input style='width: 160px' type='text' name='bansite' value='' /></td>
	    <td>" .
          WebButtonAdd("AddBannedSite") .
	      WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . "
	      <input type='hidden' name='group_id' value='$group_id'>
        </td>
	  </tr>
	";
	WebTableClose("80%");
	WebFormClose();

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_EXCEPTION_SITE_LIST, "80%");
	echo "
	  <tr>
	    <td colspan='2' class='mytableheader'>" . NETWORK_LANG_IP . " / " . NETWORK_LANG_HOSTNAME . "</td>
	  </tr>
	  $allowed
	  <tr>
	    <td width='70%' nowrap><input style='width: 160px' type='text' name='exceptionsite' value='' /></td>
	    <td>" .
          WebButtonAdd("AddExceptionSite") .
	      WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . "
	      <input type='hidden' name='group_id' value='$group_id'>
        </td>
      </tr>
	";
	WebTableClose("80%");
	WebFormClose();

	WebFormOpen();
	WebTableOpen(DANSGUARDIAN_LANG_GREY_SITE_LIST, "80%");
	echo "
	  <tr>
	    <td colspan='2' class='mytableheader'>" . NETWORK_LANG_IP . " / " . NETWORK_LANG_HOSTNAME . "</td>
	  </tr>
	  $grey
	  <tr>
	    <td width='70%' nowrap><input style='width: 160px' type='text' name='greysite' value='' /></td>
	    <td>" .
          WebButtonAdd("AddGreySite") .
	      WebButton('GoBack', LOCALE_LANG_BACK, WEBCONFIG_ICON_BACK) . "
	      <input type='hidden' name='group_id' value='$group_id'>
        </td>
	  </tr>
	";
	WebTableClose("80%");

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
	global $dansguardian;

	try {
		$isdansguardianrunning = $dansguardian->GetRunningState();
		$issquidrunning = $squid->GetRunningState();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}

	// If content filter is running, then squid should be running too
	//---------------------------------------------------------------

	if ($isdansguardianrunning && !$issquidrunning)
		WebDialogWarning(WEB_LANG_SQUID_NOT_RUNNING . " <a href='proxy.php'>" . LOCALE_LANG_GO . "</a>");
}

// vi: ts=4 syntax=php
?>
