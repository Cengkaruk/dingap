<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2009 Point Clark Networks.
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
require_once("../../api/Flexshare.class.php");
require_once("../../api/Group.class.php");
require_once("../../api/GroupManager.class.php");
require_once("../../api/UserManager.class.php");
require_once("../../api/Hostname.class.php");
require_once("../../api/HostnameChecker.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Icons
//
///////////////////////////////////////////////////////////////////////////////

define('FLEX_ICON_SECURE', ReplacePngTags('/images/icon-flexshare-secure.png', ''));
define('FLEX_ICON_INSECURE', ReplacePngTags('/images/icon-flexshare-insecure.png', ''));
define('FLEX_ICON_GENERAL', ReplacePngTags('/images/icon-flexshare-general.png', ''));
define('FLEX_ICON_RELOAD', ReplacePngTags('/images/icon-flexshare-reload.png', ''));
define('FLEX_ICON_SHOW_ALL', ReplacePngTags('/images/icon-flexshare-showall.png', ''));
define('FLEX_ICON_SHOW_FILTER', ReplacePngTags('/images/icon-flexshare-showfilter.png', ''));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-flexshare.png', WEB_LANG_PAGE_INTRO, true);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$flexshare = new Flexshare();

try {
	$flexshare->Initialize();
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

// $name is used in many places
if (isset($_REQUEST['name']))
	$name = strtolower($_REQUEST['name']);

// If cancel...unset name
if (isset($_POST['Cancel'])) {
	unset($name);
	unset($_POST);
}

if (isset($_POST['FlexshareReload'])) {
	// Recreate all virtual configs
	try {
		$flexshare->GenerateWebFlexshares();
		$flexshare->GenerateFtpFlexshares();
		$flexshare->GenerateFileFlexshares();
		$flexshare->GenerateEmailFlexshares();
	} catch (SslExecutionException $e) {
		WebDialogWarning($e->GetMessage() . " - " . WebUrlJump("certificates.php", LOCALE_LANG_CONFIGURE));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

if (isset($_POST['AddShare'])) {
	SanityCheck();
	try {
		$flexshare->AddShare($_POST['add_name'], $_POST['add_description'], $_POST['add_group']);
		$name = strtolower($_POST['add_name']);
	} catch (Exception $e) {
		unset($name);
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_REQUEST['EditShare'])) {
	$name = key($_REQUEST['EditShare']);
} else if (isset($_POST['ToggleShare'])) {
	try {
		$toggle = (current($_POST['ToggleShare']) == LOCALE_LANG_ENABLE) ? 1 : 0;
		$flexshare->ToggleShare(key($_POST['ToggleShare']), $toggle);
	} catch (SslExecutionException $e) {
		WebDialogWarning($e->GetMessage() . " - " . WebUrlJump("certificates.php", LOCALE_LANG_CONFIGURE));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DeleteShare']) && isset($_POST['Confirm'])) {
	try {
		$flexshare->DeleteShare($_POST['DeleteShare'], $_POST['delete_dir']);
		unset($_POST);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateEdit'])) {
	try {
		$flexshare->SetDescription($name, $_POST['description']);
		$flexshare->SetGroup($name, $_POST['group']);
		$flexshare->SetDirectory($name, $_POST['directory']);
		$flexshare->ToggleShare($name, $_POST['enabled']);
	} catch (SslExecutionException $e) {
		WebDialogWarning($e->GetMessage() . " - " . WebUrlJump("certificates.php", LOCALE_LANG_CONFIGURE));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateEditWeb'])) {
	try {
		$flexshare->SetWebServerName($name, $_POST['web_server_name']);
		$flexshare->SetWebOverridePort($name, $_POST['web_override_port'], $_POST['web_port']);
		$flexshare->SetWebReqSsl($name, $_POST['web_req_ssl']);
		$flexshare->SetWebReqAuth($name, $_POST['web_req_auth']);
		if ($_POST['web_req_auth'])
			$flexshare->SetWebRealm($name, $_POST['web_realm']);
		$flexshare->SetWebAccess($name, $_POST['web_access']);
		$flexshare->SetWebShowIndex($name, (bool) $_POST['web_show_index']);
		$flexshare->SetWebFollowSymLinks($name, (bool) $_POST['web_follow_symlinks']);
		$flexshare->SetWebAllowSSI($name, (bool) $_POST['web_allow_ssi']);
		$flexshare->SetWebHtaccessOverride($name, (bool) $_POST['web_htaccess_override']);
		if (isset($_POST['web_group_access']))
			$flexshare->SetWebGroupAccess($name, array_keys($_POST['web_group_access']));
		$flexshare->SetWebPhp($name, $_POST['web_php']);
		$flexshare->SetWebCgi($name, $_POST['web_cgi']);

		$flexshare->SetWebEnabled($name, $_POST['webstatus']);
	} catch (SslExecutionException $e) {
		WebDialogWarning($e->GetMessage() . " - " . WebUrlJump("certificates.php", LOCALE_LANG_CONFIGURE));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['UpdateEditFtp'])) {
	try {
		$flexshare->SetFtpServerUrl($name, $_POST['ftp_server_url']);
		$flexshare->SetFtpAllowPassive(
			$name,
			(bool)$_POST['ftp_allow_passive'],
			$_POST['ftp_passive_port_min'],
			$_POST['ftp_passive_port_max']
		);
		$flexshare->SetFtpOverridePort($name, $_POST['ftp_override_port'], $_POST['ftp_port']);
		$flexshare->SetFtpReqSsl($name, $_POST['ftp_req_ssl']);
		$flexshare->SetFtpReqAuth($name, true);
		$flexshare->SetFtpAllowAnonymous($name, $_POST['ftp_allow_anonymous']);
		$flexshare->SetFtpAnonymousPermission($name, $_POST['ftp_anonymous_permission']);
		$flexshare->SetFtpAnonymousGreeting($name, $_POST['ftp_anonymous_greeting']);
		$flexshare->SetFtpAnonymousUmask($name, $_POST['ftp_anonymous_umask']);
		$flexshare->SetFtpGroupAccess($name, array_keys($_POST['ftp_group_access']));
		$flexshare->SetFtpGroupOwner($name, $_POST['ftp_group_owner']);
		$flexshare->SetFtpGroupGreeting($name, $_POST['ftp_group_greeting']);
		$flexshare->SetFtpGroupPermission($name, $_POST['ftp_group_permission']);
		$flexshare->SetFtpGroupUmask($name, $_POST['ftp_group_umask']);
		$flexshare->SetFtpEnabled($name, $_POST['ftpstatus']);
	} catch (SslExecutionException $e) {
		WebDialogWarning($e->GetMessage() . " - " . WebUrlJump("certificates.php", LOCALE_LANG_CONFIGURE));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateEditFile'])) {
	try {
		$flexshare->SetFileComment($name, $_POST['file_comment']);
		$flexshare->SetFileAuditLog($name, $_POST['file_audit_log']);
		$flexshare->SetFileRecycleBin($name, $_POST['file_recycle_bin']);
		$flexshare->SetFilePublicAccess($name, $_POST['file_public_access']);
		$flexshare->SetFilePermission($name, $_POST['file_permission']);
		$flexshare->SetFileEnabled($name, $_POST['file_enabled']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateEditEmail'])) {
	try {
		$flexshare->SetEmailDir($name, $_POST['email_dir']);
		$flexshare->SetEmailPolicy($name, $_POST['email_policy']);
		$flexshare->SetEmailSave($name, $_POST['email_save']);
		$flexshare->SetEmailNotify($name, $_POST['email_notify']);
		$flexshare->SetEmailRestrictAccess($name, $_POST['email_restrict_access']);
		if ($_POST['email_restrict_access']) {
			$acl = array();
			if (is_array($_POST['email_acl']))
				$acl = array_keys($_POST['email_acl']);

			if ($_POST['email_add_acl'])
				$acl = array_merge($acl, explode("\n", $_POST['email_add_acl']));

			$flexshare->SetEmailAcl($name, $acl);
			unset($_POST['email_add_acl']);
		}

		$flexshare->SetEmailReqSignature($name, $_POST['email_req_signature']);
		$flexshare->SetEmailEnabled($name, $_POST['email_enabled']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($name)) {
	$activetab = isset($_REQUEST['activetab']) ? $_REQUEST['activetab'] : '';
	DisplayEdit($name, $activetab);
} else if (isset($_POST['DeleteShare'])) {
	DisplayDeleteShare(key($_POST['DeleteShare']));
} else {
	DisplaySummary();
	DisplayAdd();
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
	global $flexshare;

	try {
		$shares = $flexshare->GetShareSummary();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$legend = '';
	$start_req = '';
	$table_data = '';
	$reload_req = '';
	$rowindex = 0;
	$shares_exist = false;
	$email_share_enabled = false;

	for ($index = 0; $index < count($shares); $index++) {
		$access = '';

		// Hide internally created shares
		if (isset($shares[$index]['Internal']) && $shares[$index]['Internal'])
			continue;

		$shares_exist = true;

		if ($shares[$index]['Enabled']) {
			if (isset($shares[$index]['Web']) && $shares[$index]['Web']) {
				try {
					require_once('../../api/Httpd.class.php');
					$httpd = new Httpd();
					if (!$httpd->GetRunningState())
						$start_req = WEB_LANG_ERRMSG_HTTPD_NOT_RUNNING . "&#160;&#160;<a href='web-server.php'>" .
						FLEX_ICON_RELOAD . "&#160;" . WEB_LANG_START_SERVICE . "</a>";
				} catch (Exception $e) {
					WebDialogWarning($e->GetMessage());
				}
			}

			if (isset($shares[$index]['Ftp']) && $shares[$index]['Ftp']) {
				try {
					require_once('../../api/Proftpd.class.php');
					$proftpd = new ProFtpd();
					if (!$proftpd->GetRunningState())
						$start_req = WEB_LANG_ERRMSG_PROFTPD_NOT_RUNNING . "&#160;&#160;<a href='ftp.php'>" .
						FLEX_ICON_RELOAD . "&#160;" . WEB_LANG_START_SERVICE . "</a>";
				} catch (Exception $e) {
					WebDialogWarning($e->GetMessage());
				}
			}

			if (isset($shares[$index]['File']) && $shares[$index]['File']) {
				try {
					require_once('../../api/Samba.class.php');
					$smbd = new Samba();
					if (!$smbd->GetRunningState())
						$start_req = WEB_LANG_ERRMSG_SMBD_NOT_RUNNING . "&#160;&#160;<a href='samba.php'>" .
						FLEX_ICON_RELOAD . "&#160;" . WEB_LANG_START_SERVICE . "</a>";
				} catch (Exception $e) {
					WebDialogWarning($e->GetMessage());
				}
			}

			if (isset($shares[$index]['Email']) && $shares[$index]['Email']) {
				try {
					require_once('../../api/Postfix.class.php');
					require_once('../../api/Cyrus.class.php');
					$postfix = new Postfix();
					if (!$postfix->GetRunningState())
						$start_req = WEB_LANG_ERRMSG_SMTPD_NOT_RUNNING . "&#160;&#160;<a href='mail-smtp.php'>" .
						FLEX_ICON_RELOAD . "&#160;" . WEB_LANG_START_SERVICE . "</a>";
					$cyrus = new Cyrus();
					if (!$cyrus->GetRunningState())
						$start_req = WEB_LANG_ERRMSG_IMAPD_NOT_RUNNING . "&#160;&#160;<a href='mail-pop-imap.php'>" .
						FLEX_ICON_RELOAD . "&#160;" . WEB_LANG_START_SERVICE . "</a>";
				} catch (Exception $e) {
					WebDialogWarning($e->GetMessage());
				}
			}

			$toggle = LOCALE_LANG_DISABLE;
			$statusclass = 'iconenabled';
			$rowclass = 'rowenabled';
		} else {
			$toggle = LOCALE_LANG_ENABLE;
			$statusclass = 'icondisabled';
			$rowclass = 'rowdisabled';
		}

		$rowclass .= ($rowindex % 2) ? 'alt' : '';
		$rowindex++;

		if (isset($shares[$index]['Web']) && $shares[$index]['Web'])
			$access .= "<a href='flexshare.php?EditShare[" . $shares[$index]["Name"] . "]&amp;activetab=web'>" .
				WEBCONFIG_ICON_WEB . "</a>&#160;&#160;"; 

		if (isset($shares[$index]['Ftp']) && $shares[$index]['Ftp'])
			$access .= "<a href='flexshare.php?EditShare[" . $shares[$index]["Name"] . "]&amp;activetab=ftp'>" .
				WEBCONFIG_ICON_FTP . "</a>&#160;&#160;"; 

		if (isset($shares[$index]['File']) && $shares[$index]['File'])
			$access .= "<a href='flexshare.php?EditShare[" . $shares[$index]["Name"] . "]&amp;activetab=file'>" .
				WEBCONFIG_ICON_SAMBA . "</a>&#160;&#160;"; 

		if (isset($shares[$index]['Email']) && $shares[$index]['Email']) {
			$access .= "<a href='flexshare.php?EditShare[" . $shares[$index]["Name"] . "]&amp;activetab=email'>" .
				WEBCONFIG_ICON_EMAIL . "</a>&#160;&#160;"; 
			$email_share_enabled = true;
		}

		$table_data .= "
			<tr class='$rowclass'>
				<td class='$statusclass'>&#160; </td>
				<td nowrap>" . $shares[$index]['Name'] . "</td>
				<td>" . $shares[$index]['Description'] . "</td>
				<td>" . $shares[$index]['Group'] . "</td>
				<td nowrap>$access</td>
				<td nowrap>" . 
					WebButtonToggle("ToggleShare[" . $shares[$index]['Name']. "]", $toggle) . 
					WebButtonEdit("EditShare[" . $shares[$index]['Name'] . "]") . 
					WebButtonDelete("DeleteShare[" . $shares[$index]['Name'] . "]") . "
				</td>
			</tr>
		";
	}

	$legend .= WEBCONFIG_ICON_WEB . " " . WEB_LANG_WEB . " &#160; &#160; " .
			   WEBCONFIG_ICON_FTP . " " . WEB_LANG_FTP . " &#160; &#160; " .
			   WEBCONFIG_ICON_SAMBA . " " . WEB_LANG_FILE . " &#160; &#160; " .
			   WEBCONFIG_ICON_EMAIL . " " . WEB_LANG_EMAIL;

	if (! $shares_exist)
		$table_data = "<tr><td colspan='8' align='center'>" . WEB_LANG_NO_SHARES . "</td></tr>";

	WebFormOpen();

	// Display reloads as required
	// ---------------------------
	if ($reload_req)
		WebDialogInfo($reload_req);

	if ($start_req)
		WebDialogWarning($start_req);

	WebTableOpen(WEB_LANG_SUMMARY, "100%");
	WebTableHeader(
		"&#160; |" .
		WEB_LANG_NAME . "|" . 
		WEB_LANG_DESCRIPTION . "|" .
		WEB_LANG_GROUP_OWNER . "|" .
		WEB_LANG_ACCESS_OPTIONS . "|&#160; "
	);
	echo $table_data;
	echo "<tr><td colspan='8' class='mytablelegend'>$legend</td>";
	WebTableClose("100%");
	WebFormClose();

	$messages = array();

	if ($email_share_enabled) {
		$file = new File("../../api/Cyrus.class.php");
		if (! $file->Exists()) {
			WebDialogWarning(WEB_LANG_MODULE_MISSING_SMTP . "&#160;&#160;<a target='_blank' href='" .
				$_SESSION['system_online_help'] . "/" .
				$_SESSION['system_locale'] .
				"/admin/mail-pop-imap.php'>" .
				WEB_LANG_MORE_INFO . "</a>");
			return;
		}

		try {
			if (isset($_POST['action']))
				$messages = $flexshare->CheckMessages((bool)true, $_POST['action']);
			else
				$messages = $flexshare->CheckMessages((bool)true);
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}
	}

	if (count($messages) > 0 || isset($_POST['action']))
		DisplayPendingEmails($messages);
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdd()
{
	try {
		$groupmanager = new GroupManager();
		$groups = $groupmanager->GetAllGroups();

		$usermanager = new UserManager();
		$users = $usermanager->GetAllUsers();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$owners = array();

	foreach ($groups as $group)
		$owners[$group] = GROUP_LANG_GROUP . " - " . $group;

	foreach ($users as $user)
		$owners[$user] = GROUP_LANG_USER . " - " . $user;

	$add_name = isset($_POST['add_name']) ? $_POST['add_name'] : "";
	$add_description = isset($_POST['add_description']) ? $_POST['add_description'] : "";
	$add_group = isset($_POST['add_group']) ? $_POST['add_group'] : "";

	if (empty($add_group) && in_array(Group::CONSTANT_ALL_USERS_GROUP, $groups))
		$add_group = Group::CONSTANT_ALL_USERS_GROUP;

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_FLEX, "75%");
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . WEB_LANG_NAME . "</td>
			<td><input type='text' name='add_name' value='$add_name'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_DESCRIPTION . "</td>
			<td><input type='text' name='add_description' value='$add_description' style='width:250px'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_GROUP_OWNER . "</td>
			<td nowrap>" . WebDropDownHash("add_group", $add_group, $owners) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160;</td>
			<td nowrap>" . WebButtonAdd("AddShare") . "</td>
		</tr>
	";
	WebTableClose("75%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEdit()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEdit($name, $activetab)
{
	global $flexshare;

	try {
		$groupmanager = new GroupManager();
		$groups = $groupmanager->GetAllGroups();

		$usermanager = new UserManager();
		$users = $usermanager->GetAllUsers();

		$share = $flexshare->GetShare($name);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	try {
		$dir_options = $flexshare->GetDirOptions($name);
	} catch (Exception $e) {
		// TODO -- generates a no match exception
	}

	// Description
	//------------

	$description = htmlspecialchars($share['ShareDescription'], ENT_QUOTES);

	// Directory
	//----------

	$foundir = false;

	if (count($dir_options) > 1) {
		foreach ($dir_options as $opt => $display_opt) {
			if ($share['ShareDir'] == $opt)
				$foundir = true;
		}
		$dir_select = WebDropDownHash("directory", $share['ShareDir'], $dir_options);
	} else if (count($dir_options) == 1) {
		$foundir = true;
		$dir_select = key($dir_options);
	}

	if (!$foundir)
		WebDialogWarning(WEB_LANG_ERRMSG_DIR_NO_LONGER_EXISTS);

	// Group owner
	//------------

	$group_select = '';

	foreach ($groups as $group) {
		$selected = ($group === $share['ShareGroup']) ? "selected" : '';
		$group_select .= "<option value='" . $group . "' $selected>" . GROUP_LANG_GROUP . ' - ' . $group . "</option>\n";
	}

	foreach ($users as $group) {
		$selected = ($group === $share['ShareGroup']) ? "selected" : '';
		$group_select .= "<option value='" . $group . "' $selected>" . GROUP_LANG_USER . ' - ' . $group . "</option>\n";
	}

	if (empty($groups))
		$group_select = WEB_LANG_GROUP_REQUIRED . " - " . WebUrlJump("groups.php", LOCALE_LANG_CONFIGURE);
	else
		$group_select = "<select name='group'>$group_select</select>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_GENERAL_SETTINGS, "100%");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap width='35%'>" . WEB_LANG_NAME . "</td>
			<td>$name</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
			<td>" . WebDropDownEnabledDisabled("enabled", $share['ShareEnabled']) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_GROUP_OWNER . "</td>
			<td>$group_select</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_DESCRIPTION . "</td>
			<td><input type='text' name='description' value='$description' style='width:250px'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_DIR . "</td>
			<td>$dir_select</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td nowrap>" . 
				WebButtonUpdate("UpdateEdit") .
				WebButton("Cancel", LOCALE_LANG_RETURN_TO_SUMMARY, WEBCONFIG_ICON_BACK) . "
				<input type='hidden' name='name' value='$name'>
			</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();

	$tabicon = isset($share['FileEnabled']) && $share['FileEnabled'] ? WEBCONFIG_ICON_ENABLED : WEBCONFIG_ICON_DISABLED;
	$tabinfo['file']['title'] = $tabicon . " " . WEB_LANG_FILE;
	$tabinfo['file']['contents'] = GetFileEdit($name);

	$tabicon = isset($share['FtpEnabled']) && $share['FtpEnabled'] ? WEBCONFIG_ICON_ENABLED : WEBCONFIG_ICON_DISABLED;
	$tabinfo['ftp']['title'] = $tabicon . " " . WEB_LANG_FTP;
	$tabinfo['ftp']['contents'] = GetFtpEdit($name);

	$tabicon = isset($share['WebEnabled']) && $share['WebEnabled'] ? WEBCONFIG_ICON_ENABLED : WEBCONFIG_ICON_DISABLED;
	$tabinfo['web']['title'] = $tabicon . " " . WEB_LANG_WEB;
	$tabinfo['web']['contents'] = GetWebEdit($name);

	$tabicon = isset($share['EmailEnabled']) && $share['EmailEnabled'] ? WEBCONFIG_ICON_ENABLED : WEBCONFIG_ICON_DISABLED;
	$tabinfo['email']['title'] = $tabicon . " " . WEB_LANG_EMAIL;
	$tabinfo['email']['contents'] = GetEmailEdit($name);

	// Default tab
	if (empty($activetab)) {
		if (file_exists("samba.php"))
			$activetab = 'file';
		else if (file_exists("../../api/Proftpd.class.php"))
			$activetab = 'ftp';
		else if (file_exists("../../api/Httpd.class.php"))
			$activetab = 'web';
		else if (file_exists("../../api/Cyrus.class.php"))
			$activetab = 'email';
		else
			$activetab = 'file';
	}

	WebTab(WEB_LANG_ACCESS_OPTIONS, $tabinfo, $activetab);
}

///////////////////////////////////////////////////////////////////////////////
//
// GetWebEdit()
//
///////////////////////////////////////////////////////////////////////////////

function GetWebEdit($name)
{
	global $flexshare;

	if (!file_exists("../../api/Httpd.class.php")) {
		$hint = "<p style='padding:20px 0px 20px 0px; text-align: center'>" . 
			WEB_LANG_MODULE_MISSING_HTTP . "&#160;&#160;<a target='_blank' href='" .
			$_SESSION['system_online_help'] . "/" .
			$_SESSION['system_locale'] .
			"/admin/web-server.php'>" .
			WEB_LANG_MORE_INFO . "</a></p>";
		return $hint;
	}

	require_once("../../api/Httpd.class.php");

	$httpd = new Httpd();

	try {
		$share = $flexshare->GetShare($name);
		$options = $flexshare->GetWebAccessOptions();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Set variables and defaults
	//---------------------------

	$web_realm = isset($_POST['web_realm']) ? $_POST['web_realm'] : $share['WebReqAuth'];
	$web_access = isset($_POST['web_access']) ? $_POST['web_access'] : (int)$share['WebAccess'];
	$web_show_index = isset($_POST['web_show_index']) ? $_POST['web_show_index'] : $share['WebShowIndex'];
	$web_follow_symlinks = isset($_POST['web_follow_symlinks']) ? $_POST['web_follow_symlinks'] : $share['WebFollowSymLinks'];
	$web_override_port = isset($_POST['web_override_port']) ? $_POST['web_override_port'] : $share['WebOverridePort'];
	$web_htaccess_override = isset($_POST['web_htaccess_override']) ? $_POST['web_htaccess_override'] : $share['WebHtaccessOverride'];;
	$web_port = isset($_POST['web_port']) ? $_POST['web_port'] : (int)$share['WebPort'];
	$web_req_ssl = isset($_POST['web_req_ssl']) ? $_POST['web_req_ssl'] : $share['WebReqSsl'];
	$web_req_auth = isset($_POST['web_req_auth']) ? $_POST['web_req_auth'] : $share['WebReqAuth'];
	$web_allow_ssi = isset($_POST['web_allow_ssi']) ? $_POST['web_allow_ssi'] : $share['WebAllowSSI'];
	$web_php = isset($_POST['web_php']) ? $_POST['web_php'] : $share['WebPhp'];
	$web_cgi = isset($_POST['web_cgi']) ? $_POST['web_cgi'] : $share['WebCgi'];


	$access_options = '';

	foreach ($options as $opt => $display_opt) {
		$selected = ((int)$web_access == (int)$opt) ? "selected" : '';
		$access_options .= "<option value='$opt' $selected>$display_opt</option>";
	}

	$protocol = ($web_req_ssl) ?  "https" : "http";

	if ($web_override_port) {
		$override_port_options = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
	} else {
		$override_port_options = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";
	}

	if ((int)$share['WebPort'] > 0)
		$num = $share['WebPort'];
	else if ($web_req_ssl)
		$num = 443;
	else 
		$num = 80;

	$access = array();

	if (isset($share['WebGroupAccess']))
		$access = explode(" ", $share['WebGroupAccess']);

	// If default port, we want to show Apache Server Name...otherwise, allow entry.

	if ($web_override_port) {
		if (!$share['WebServerName'])
			$share['WebServerName'] = $httpd->GetServerName();
		$server_name = "<input type='text' name='web_server_name' value='" . $share['WebServerName'] .
			"' style='width:250px'>";
		$server_url = $protocol . "://" . $share['WebServerName'] . ":$web_port/flexshare/$name";
		$server_alt = $protocol . "://$name." . $share['WebServerName'] . ":$web_port";
	} else {
		$server_name = "<a href='web-server.php'>" . $httpd->GetServerName() . "</a>" .
			"<input type='hidden' name='web_server_name' value='" . $httpd->GetServerName() . "'>";
		$server_url = $protocol . "://" . $httpd->GetServerName() . "/flexshare/$name";
		$server_alt = $protocol . "://$name." . $httpd->GetServerName();
	}

	// If new config
	if (! isset($share['WebModified']) || !$share['WebModified'])
		$share['WebModified'] = time();

	// Set default realm
	if (! isset($share['WebRealm']))
		$share['WebRealm'] = FLEXSHARE_LANG_SHARE . ' - ' . htmlspecialchars($share['ShareDescription'], ENT_QUOTES);

	$contents = "
	<form action='flexshare.php' method='post' enctype='multipart/form-data'>
	  <table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>
		<tr>
			<td width='35%' class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
			<td>" . WebDropDownEnabledDisabled("webstatus", $share['WebEnabled']) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . HTTPD_LANG_SERVERNAME . "</td>
			<td>$server_name</td>
		</tr>
		<tr>
			<td class='mytablesubheader' valign='top' nowrap>" . WEB_LANG_SERVERURL. "</td>
			<td><a href='$server_url' target='_blank'>$server_url</a><br />
			<a href='$server_alt' target='_blank'>$server_alt</a></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ACCESS . "</td>
			<td><select name='web_access'>$access_options</select></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_SHOW_INDEX . "</td>
			<td>" . WebDropDownEnabledDisabled('web_show_index', $web_show_index) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_FOLLOW_SYM_LINKS . "</td>
			<td>" . WebDropDownEnabledDisabled('web_follow_symlinks', $web_follow_symlinks) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ALLOW_SSI . "</td>
			<td>" . WebDropDownEnabledDisabled('web_allow_ssi', $web_allow_ssi) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_HTACCESS_OVERRIDE . "</td>
			<td>" . WebDropDownEnabledDisabled('web_htaccess_override', $web_htaccess_override) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . "SSL / HTTPS" . "</td>
			<td>" . WebDropDownEnabledDisabled("web_req_ssl", $web_req_ssl, 0, "togglewebportnum();", "web_req_ssl") . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_OVERRIDE_PORT . "</td>
			<td>" . WebDropDownEnabledDisabled("web_override_port", $web_override_port, 0, "togglewebportnum();", "web_override_port") . "
			  &#160;&#160;" . WEB_LANG_PORT . ":&#160;&#160;
			  <input id='web_port' type='text' name='web_port' value='$num' style='width:40px' />
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_REQUIRE_AUTH . "</td>
			<td>" . WebDropDownEnabledDisabled("web_req_auth", $web_req_auth, 0, "togglewebreqauth();", "web_req_auth") . "</td>
		</tr>
		<tr>
		  <td class='mytablesubheader' nowrap>" . WEB_LANG_REALM . "</td>
		  <td>
			<input id='web_realm' type='text' name='web_realm' value='" .
			htmlspecialchars($share['WebRealm'], ENT_QUOTES) . "' style='width:250px' />
		  </td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_PHP . "</td>
			<td>" . WebDropDownEnabledDisabled('web_php', $web_php) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_CGI . "</td>
			<td>" . WebDropDownEnabledDisabled('web_cgi', $web_cgi) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td nowrap>" . 
				WebButtonUpdate("UpdateEditWeb") . "
				<input type='hidden' name='name' value='$name'>
				<input type='hidden' name='activetab' value='web'>
			</td>
		</tr>
	</table>
	</form>
	";

	if ($web_override_port)
		$contents .= "<script type='text/javascript'>enable('web_port');</script>";
	else
		$contents .= "<script type='text/javascript'>disable('web_port');</script>";

	if ($web_req_auth)
		$contents .= "<script type='text/javascript'>enable('web_realm');</script>";
	else
		$contents .= "<script type='text/javascript'>disable('web_realm');</script>";

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// GetFtpEdit()
//
///////////////////////////////////////////////////////////////////////////////

function GetFtpEdit($name)
{
	global $flexshare;

	if (!file_exists("../../api/Proftpd.class.php")) {
		$hint = "<p style='padding:20px 0px 20px 0px; text-align: center'>" . 
			WEB_LANG_MODULE_MISSING_FTP . "&#160;&#160;<a target='_blank' href='" .
			$_SESSION['system_online_help'] . "/" .
			$_SESSION['system_locale'] .
			"/admin/ftp.php'>" .
			WEB_LANG_MORE_INFO . "</a></p>";
		return $hint;
	}

	$ftp_greeting = $_POST['ftp_greeting'];
	$ftp_enabled = $_POST['ftp_enabled'];
	$ftp_allow_passive = $_POST['ftp_allow_passive'];
	$ftp_passive_port_min = $_POST['ftp_passive_port_min'];
	$ftp_passive_port_max = $_POST['ftp_passive_port_max'];
	$ftp_server_url = $_POST['ftp_server_url'];
	$ftp_override_port = $_POST['ftp_override_port'];
	$ftp_port = $_POST['ftp_port'];
	$ftp_req_ssl = $_POST['ftp_req_ssl'];
	$ftp_group_access = $_POST['ftp_group_access'];
	$ftp_group_owner = $_POST['ftp_group_owner'];
	$ftp_group_permission = $_POST['ftp_group_permission'];
	$ftp_group_umask = $_POST['ftp_group_umask'];
	$ftp_group_greeting = $_POST['ftp_group_greeting'];
	$ftp_allow_anonymous = $_POST['ftp_allow_anonymous'];
	$ftp_anonymous_permission = $_POST['ftp_anonymous_permission'];
	$ftp_anonymous_greeting = $_POST['ftp_anonymous_greeting'];
	$ftp_anonymous_umask = $_POST['ftp_anonymous_umask'];

	try {
		$share = $flexshare->GetShare($name);
		$options = $flexshare->GetFtpPermissionOptions();
		$umaskoptions = $flexshare->GetFtpUmaskOptions();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Get Defaults
	if ($ftp_server_url == null) {
		if ($share['FtpServerUrl'] == null) {
			$hostname = new Hostname();
			$ftp_server_url = $hostname->Get();
		} else {
			$ftp_server_url = $share['FtpServerUrl'];
		}
	}

	if ($ftp_allow_passive == null)
		$ftp_allow_passive = isset($share['FtpAllowPassive']) ? $share['FtpAllowPassive'] : true;

	if ($ftp_passive_port_min == null)
		$ftp_passive_port_min = $share['FtpPassivePortMin'];

	if ($ftp_passive_port_max == null)
		$ftp_passive_port_max = $share['FtpPassivePortMax'];

	if ($ftp_group_access == null)
		$ftp_group_access = $share['FtpGroupAccess'];

	if ($ftp_group_owner == null)
		$ftp_group_owner = $share['FtpGroupOwner'];

	if ($ftp_group_greeting == null) {
		if (isset($share['FtpGroupGreeting']) && $share['FtpGroupGreeting'] != '')
			$ftp_group_greeting = $share['FtpGroupGreeting'];
		else
			$ftp_group_greeting = FLEXSHARE_LANG_SHARE . ' - ' . htmlspecialchars($share['ShareDescription'], ENT_QUOTES);
	}
	
	if ($ftp_group_permission == null) {
		if (isset($share['FtpGroupPermission']) && !empty($share['FtpGroupPermission']))
			$ftp_group_permission = $share['FtpGroupPermission'];
		else
			$ftp_group_permission = Flexshare::PERMISSION_READ_WRITE;
	}

	if ($ftp_group_umask == null) {
		$ftp_group_umask['owner'] = substr($share['FtpGroupUmask'], 1, 1);
		$ftp_group_umask['group'] = substr($share['FtpGroupUmask'], 2, 1);
		$ftp_group_umask['world'] = substr($share['FtpGroupUmask'], 3, 1);
	}

	if ($ftp_allow_anonymous == null)
		$ftp_allow_anonymous = $share['FtpAllowAnonymous'];

	if ($ftp_anonymous_greeting == null) {
		if (isset($share['FtpAnonymousGreeting']) && $share['FtpAnonymousGreeting'] != '')
			$ftp_anonymous_greeting = $share['FtpAnonymousGreeting'];
		else
			$ftp_anonymous_greeting = FLEXSHARE_LANG_SHARE . ' - ' . htmlspecialchars($share['ShareDescription'], ENT_QUOTES);
	}

	if ($ftp_anonymous_permission == null) {
		if (isset($share['FtpAnonymousPermission']) && !empty($share['FtpAnonymousPermission']))
			$ftp_anonymous_permission = $share['FtpAnonymousPermission'];
		else
			$ftp_anonymous_permission = Flexshare::PERMISSION_READ; 
	}

	if ($ftp_anonymous_umask == null) {
		$ftp_anonymous_umask['owner'] = substr($share['FtpAnonymousUmask'], 1, 1);
		$ftp_anonymous_umask['group'] = substr($share['FtpAnonymousUmask'], 2, 1);
		$ftp_anonymous_umask['world'] = substr($share['FtpAnonymousUmask'], 3, 1);
	}

	if ($ftp_override_port == null)
		$ftp_override_port = $share['FtpOverridePort'];

	if ($ftp_port == null)
		$ftp_port = $share['FtpPort'];

	if ($ftp_req_ssl == null)
		$ftp_req_ssl = isset($share['FtpReqSsl']) ? $share['FtpReqSsl'] : true;

	if ($share['FtpEnabled']) {
		$state = LOCALE_LANG_YES;
	} else {
		$state = LOCALE_LANG_NO;
	}

	// Passive port range
	if ((int)$share['FtpPassivePortMin'] > 0)
		$min = $share['FtpPassivePortMin'];
	else
		$min = Flexshare::FTP_PASV_MIN;

	if ((int)$share['FtpPassivePortMax'] > 0)
		$max = $share['FtpPassivePortMax'];
	else
		$max = Flexshare::FTP_PASV_MAX;

	if ($ftp_allow_passive) {
		$allow_passive_options = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
	} else {
		$allow_passive_options = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";
	}

	// Port options
	if ((int)$share['FtpPort'] > 0 && $ftp_override_port)
		$num = $share['FtpPort'];
	else if ($ftp_req_ssl)
		$num = Flexshare::DEFAULT_PORT_FTPS;
	else
		$num = Flexshare::DEFAULT_PORT_FTP;

	if ($ftp_override_port) {
		$override_port_options = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
	} else {
		$override_port_options = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";
		if ($ftp_req_ssl)
			$port = "<input type='hidden' name='ftp_port' value='" . Flexshare::DEFAULT_PORT_FTPS . "'>";
		else
			$port = "<input type='hidden' name='ftp_port' value='" . Flexshare::DEFAULT_PORT_FTP . "'>";
	}

	if ($ftp_req_ssl)
		$ssl_options = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
	else
		$ssl_options = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
					   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";

	if ($ftp_allow_anonymous)
		$allow_anonymous_options = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
						   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
	else
		$allow_anonymous_options = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
						   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";

	$access = array();

	if (isset($share['FtpGroupAccess']))
		$access = explode(" ", $share['FtpGroupAccess']);

	$groupaccess = "<table border='0' align='center' width='100%' cellpadding='0' cellspacing='0' class='mytable'>";
	$group_owner_options = '';

	// Group permissions
	$group_permission_options = '';
	foreach ($options as $opt => $display_opt) {
		if (isset($share['ShareGroup']) && $share['ShareGroup'] != Flexshare::CONSTANT_USERNAME && !empty($share['ShareGroup'])) {
			$hide = array(Flexshare::PERMISSION_NONE, Flexshare::PERMISSION_WRITE, Flexshare::PERMISSION_WRITE_PLUS);
			if (in_array($opt, $hide))
				continue;
		}
		if ((int)$ftp_group_permission == (int)$opt)
			$group_permission_options .= "<option value='$opt' SELECTED>$display_opt</option>";
		else
			$group_permission_options .= "<option value='$opt'>$display_opt</option>";
	}

	// Anonymous login
	$anonymous_permission_options = '';
	foreach ($options as $opt => $display_opt) {
		if (isset($share['ShareGroup']) && $share['ShareGroup'] != Flexshare::CONSTANT_USERNAME && !empty($share['ShareGroup'])) {
			$hide = array(Flexshare::PERMISSION_NONE, Flexshare::PERMISSION_WRITE, Flexshare::PERMISSION_WRITE_PLUS);
			if (in_array($opt, $hide))
				continue;
		}
		if ((int)$ftp_anonymous_permission == (int)$opt)
			$anonymous_permission_options .= "<option value='$opt' SELECTED>$display_opt</option>";
		else
			$anonymous_permission_options .= "<option value='$opt'>$display_opt</option>";
	}

	// Upload permissions
	$owner = array('owner','group','world');
	foreach ($owner as $type) {
		foreach ($umaskoptions as $opt => $display_opt) {
			if ((int)$ftp_group_umask[$type] == (int)$opt)
				$group_umask_options[$type] .= "<option value='$opt' SELECTED>$display_opt</option>";
			else
				$group_umask_options[$type] .= "<option value='$opt'>$display_opt</option>";

			if ((int)$ftp_anonymous_umask[$type] == (int)$opt)
				$anonymous_umask_options[$type] .= "<option value='$opt' SELECTED>$display_opt</option>";
			else
				$anonymous_umask_options[$type] .= "<option value='$opt'>$display_opt</option>";
		}
	}

	// Greeting Message
	$group_greeting = "<textarea id='ftp_group_greeting' name='ftp_group_greeting'>" .
				htmlspecialchars($ftp_group_greeting, ENT_QUOTES) . "</textarea>";
	$anonymous_greeting = "<textarea id='ftp_anonymous_greeting' name='ftp_anonymous_greeting'>" .
				htmlspecialchars($ftp_anonymous_greeting, ENT_QUOTES) . "</textarea>";

	// If new config
	if (!$share['FtpModified'])
		$share['FtpModified'] = time();

	$contents = "
	<form action='flexshare.php' method='post' enctype='multipart/form-data'>
	<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>
	  <tr>
		<td width='35%' class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
		<td>" . WebDropDownEnabledDisabled("ftpstatus", $share['FtpEnabled']) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_SERVERURL . "</td>
		<td><input type='text' name='ftp_server_url' value='$ftp_server_url' style='width:250px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_FTP_REQ_SSL . "</td>
		<td>
		  <select id='ftp_req_ssl' name='ftp_req_ssl' onChange='toggleftpportnum(" . 
			Flexshare::DEFAULT_PORT_FTP . "," . Flexshare::DEFAULT_PORT_FTPS . ")'>
			$ssl_options
		  </select>
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_OVERRIDE_PORT . "</td>
		<td>
		  <select id='ftp_override_port' name='ftp_override_port' onChange='toggleftpport(" .
			Flexshare::DEFAULT_PORT_FTP . "," . Flexshare::DEFAULT_PORT_FTPS . ")'>
			$override_port_options
		  </select>
		  &#160;&#160;" . WEB_LANG_PORT . ":&#160;&#160;
		  <input id='ftp_port' type='text' name='ftp_port' value='$num' style='width:40px' />
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_ALLOW_PASSIVE_FTP . "</td>
		<td>
		  <select id='ftp_allow_passive' name='ftp_allow_passive' onChange='toggleftppassive()'>
			$allow_passive_options
		  </select>
		  &#160;&#160;" . WEB_LANG_PORT . ":&#160;&#160;
		  <input id='ftp_passive_port_min' type='text' name='ftp_passive_port_min' value='$min' style='width:40px' /> -
		  <input id='ftp_passive_port_max' type='text' name='ftp_passive_port_max' value='$max' style='width:40px' />
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_GROUP_PERMISSION . "</td>
		<td>
		  <select id='ftp_group_permission' name='ftp_group_permission' onChange='toggleftpgrouppermission()'>
			 $group_permission_options
		  </select>
		</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' valign='top' align='right'>" . WEB_LANG_GROUP_GREETING . "</td>
		<td>$group_greeting</td>
	  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ALLOW_ANONYMOUS . "</td>
			<td>
			  <select id='ftp_allow_anonymous' name='ftp_allow_anonymous' onChange='toggleftpallowanonymous()'>
				$allow_anonymous_options
			  </select>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_ANONYMOUS_PERMISSION . "</td>
			<td>
			  <select id='ftp_anonymous_permission' name='ftp_anonymous_permission' onChange='toggleftpanonymouspermission()'>
				$anonymous_permission_options
			  </select>
			</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' valign='top' align='right'>" . WEB_LANG_ANONYMOUS_GREETING . "</td>
			<td>$anonymous_greeting</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td nowrap>" . WebButtonUpdate("UpdateEditFtp") . "
			<input type='hidden' name='name' value='$name' /></td>
			<input type='hidden' name='activetab' value='ftp' />
		  </tr>
		</table>
	  </form>
	";

	if ($ftp_override_port)
		$contents .= "<script type='text/javascript'>enable('ftp_port');</script>";
	else
		$contents .= "<script type='text/javascript'>disable('ftp_port');</script>";

	$contents .= "<script type='text/javascript'>enable('ftp_group_greeting');</script>";
	$contents .= "<script type='text/javascript'>enable('ftp_group_permission');</script>";

	if ($ftp_allow_passive) {
		$contents .= "<script type='text/javascript'>enable('ftp_passive_port_min');</script>";
		$contents .= "<script type='text/javascript'>enable('ftp_passive_port_max');</script>";
	} else {
		$contents .= "<script type='text/javascript'>disable('ftp_passive_port_min');</script>";
		$contents .= "<script type='text/javascript'>disable('ftp_passive_port_max');</script>";
	}

	if ($ftp_allow_anonymous) {
		$contents .= "<script type='text/javascript'>enable('ftp_anonymous_greeting');</script>";
		$contents .= "<script type='text/javascript'>enable('ftp_anonymous_permission');</script>";
	} else {
		$contents .= "<script type='text/javascript'>disable('ftp_anonymous_greeting');</script>";
		$contents .= "<script type='text/javascript'>disable('ftp_anonymous_permission');</script>";
		$contents .= "<script type='text/javascript'>disable('ftp_anonymous_umask_1');</script>";
		$contents .= "<script type='text/javascript'>disable('ftp_anonymous_umask_2');</script>";
		$contents .= "<script type='text/javascript'>disable('ftp_anonymous_umask_3');</script>";
	}
	if ((isset($share['ShareGroup']) && $share['ShareGroup'] == Flexshare::CONSTANT_USERNAME) || empty($share['ShareGroup'])) {
		if ((int)$ftp_anonymous_permission != Flexshare::PERMISSION_READ && $ftp_allow_anonymous) {
			$contents .= "<script type='text/javascript'>enable('ftp_anonymous_umask_1');</script>";
			$contents .= "<script type='text/javascript'>enable('ftp_anonymous_umask_2');</script>";
			$contents .= "<script type='text/javascript'>enable('ftp_anonymous_umask_3');</script>";
		} else {
			$contents .= "<script type='text/javascript'>disable('ftp_anonymous_umask_1');</script>";
			$contents .= "<script type='text/javascript'>disable('ftp_anonymous_umask_2');</script>";
			$contents .= "<script type='text/javascript'>disable('ftp_anonymous_umask_3');</script>";
		}
	}

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// GetFileEdit()
//
///////////////////////////////////////////////////////////////////////////////

function GetFileEdit($name)
{
	global $flexshare;

	if (! file_exists("samba.php")) {
		$hint = "<p style='padding:20px 0px 20px 0px; text-align: center'>" . 
			WEB_LANG_MODULE_MISSING_SMB . "&#160;&#160;<a target='_blank' href='" .
			$_SESSION['system_online_help'] . "/" .
			$_SESSION['system_locale'] .
			"/admin/samba.php'>" .
			WEB_LANG_MORE_INFO . "</a></p>";
		return $hint;
	}

	require_once('../../api/Samba.class.php');

	try {
		$samba = new Samba();
		$host = $samba->GetNetbiosName();
		$share = $flexshare->GetShare($name);
		$file_permissions = $flexshare->GetFilePermissionOptions();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Set variables and defaults
	//---------------------------

	if (isset($_POST['file_enabled']))
		$file_enabled = $_POST['file_enabled'];
	else if (isset($share['FileEnabled']))
		$file_enabled = $share['FileEnabled'];
	else
		$file_enabled = false;

	if (isset($_POST['file_public_access']))
		$file_public_access = $_POST['file_public_access'];
	else if (isset($share['FilePublicAccess']))
		$file_public_access = $share['FilePublicAccess'];
	else
		$file_public_access = false;

	if (isset($_POST['file_recycle_bin']))
		$file_recycle_bin = $_POST['file_recycle_bin'];
	else if (isset($share['FileRecycleBin']))
		$file_recycle_bin = $share['FileRecycleBin'];
	else
		$file_recycle_bin = true;

	if (isset($_POST['file_audit_log']))
		$file_audit_log = $_POST['file_audit_log'];
	else if (isset($share['FileAuditLog']))
		$file_audit_log = $share['FileAuditLog'];
	else
		$file_audit_log = false;

	if (isset($_POST['file_comment']))
		$file_comment = $_POST['file_comment'];
	else if (isset($share['FileComment']))
		$file_comment = $share['FileComment'];
	else
		$file_comment = FLEXSHARE_LANG_SHARE . ' - ' . htmlspecialchars($share['ShareDescription'], ENT_QUOTES);

	if (isset($_POST['file_permission']))
		$file_permission = $_POST['file_permission'];
	else if (isset($share['FilePermission']))
		$file_permission = $share['FilePermission'];
	else
		$file_permission = Flexshare::PERMISSION_READ_WRITE;

	// Modified time (for new config)
	//-------------------------------

	if (! isset($share['FileModified']))
		$share['FileModified'] = time();

	$contents = "
	<form action='flexshare.php' method='post' enctype='multipart/form-data'>
	  <table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>
		<tr>
		  <td class='mytablesubheader' width='35%' nowrap>" . LOCALE_LANG_STATUS . "</td>
		  <td>" . WebDropDownEnabledDisabled("file_enabled", $file_enabled) . "</td>
		</tr>
		<tr>
		  <td class='mytablesubheader' nowrap>" . WEB_LANG_SHARE_NAME . "</td>
		  <td>$name</td>
		</tr>
		<tr>
		  <td class='mytablesubheader' nowrap>" . WEB_LANG_SERVERURL . "</td>
		  <td>\\\\" . $host . "\\" . $name . "</td>
		</tr>
		<tr>
		  <td class='mytablesubheader' nowrap>" . WEB_LANG_FILE_COMMENT . "</td>
		  <td><input type='text' name='file_comment' value='" . $file_comment . "' style='width:250px'></td>
		</tr>
		<tr>
		  <td class='mytablesubheader' nowrap>" . WEB_LANG_FILE_PERMISSION . "</td>
		  <td>" . WebDropDownHash("file_permission", $file_permission, $file_permissions) . "</td>
		</tr>
		<tr>
		  <td class='mytablesubheader' nowrap>" . SAMBA_LANG_RECYCLE_BIN . "</td>
		  <td>" . WebDropDownEnabledDisabled("file_recycle_bin", $file_recycle_bin) . "</td>
		</tr>
		<tr>
		  <td class='mytablesubheader' nowrap>" . SAMBA_LANG_AUDIT_LOG . "</td>
		  <td>" . WebDropDownEnabledDisabled("file_audit_log", $file_audit_log) . "</td>
		</tr>
";
// TODO -- re-implement
/*
		<tr>
		  <td class='mytablesubheader' nowrap>" . WEB_LANG_FILE_PUBLIC . "</td>
		  <td>" . WebDropDownEnabledDisabled("file_public_access", $file_public_access, 0, 
				"togglefilepublicaccess()", "file_public_access") . "
		  </td>
		</tr>
*/
	$contents .= "
		<tr>
		  <td class='mytablesubheader' nowrap>&#160;</td>
		  <td nowrap>" . 
			WebButtonUpdate("UpdateEditFile") . "
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='activetab' value='file'>
		  </td>
		</tr>
	  </table>
	</form>
	";

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// GetEmailEdit()
//
///////////////////////////////////////////////////////////////////////////////

function GetEmailEdit($name)
{
	global $flexshare;

	if (!file_exists("../../api/Cyrus.class.php")) {
		$hint = "<p style='padding:20px 0px 20px 0px; text-align: center'>" . 
			WEB_LANG_MODULE_MISSING_SMTP . "&#160;&#160;<a target='_blank' href='" .
			$_SESSION['system_online_help'] . "/" .
			$_SESSION['system_locale'] .
			"/admin/mail-pop-imap.php'>" .
			WEB_LANG_MORE_INFO . "</a></p>";
		return $hint;
	}

	try {
		$share = $flexshare->GetShare($name);
		$email_dirs = $flexshare->GetEmailDirOptions($name);
		$email_saves = $flexshare->GetEmailSaveOptions();
		$email_policies = $flexshare->GetEmailPolicyOptions();
 		$email_address = $flexshare->GetEmailAddress($name);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if (isset($_POST['email_enabled']))
		$email_enabled = $_POST['email_enabled'];
	else if (isset($share['EmailEnabled']))
		$email_enabled = $share['EmailEnabled']; 
	else
		$email_enabled = false;

	if (isset($_POST['email_dir']))
		$email_dir = $_POST['email_dir'];
	else if (isset($share['EmailDir']))
		$email_dir = $share['EmailDir'];
	else
		$email_dir = Flexshare::EMAIL_SAVE_PATH_ROOT;

	if (isset($_POST['email_policy']))
		$email_policy = $_POST['email_policy'];
	else if (isset($share['EmailPolicy']))
		$email_policy = $share['EmailPolicy'];
	else
		$email_policy = Flexshare::POLICY_DONOT_WRITE;

	if (isset($_POST['email_save']))
		$email_save = $_POST['email_save'];
	else if (isset($share['EmailSave']))
		$email_save = $share['EmailSave'];
	else
		$email_save = Flexshare::SAVE_AUTO;

	if (isset($_POST['email_notify']))
		$email_notify = $_POST['email_notify'];
	else if (isset($share['EmailNotify']))
		$email_notify = $share['EmailNotify'];
	else
		$email_notify = "";

	if (isset($_POST['email_restrict_access']))
		$email_restrict_access = $_POST['email_restrict_access'];
	else if (isset($share['EmailRestrictAccess']))
		$email_restrict_access = $share['EmailRestrictAccess'];
	else
		$email_restrict_access = false;

	if (isset($_POST['email_req_signature']))
		$email_req_signature = $_POST['email_req_signature'];
	else if (isset($share['EmailReqSignature']))
		$email_req_signature = $share['EmailReqSignature'];
	else
		$email_req_signature = false;

	if (isset($_POST['email_add_acl']))
		$email_add_acl = $_POST['email_add_acl'];
	else
		$email_add_acl = "";

	$acl = array();

	if (isset($share['EmailAcl']))
		$acl = explode(" ", $share['EmailAcl']);

	$emailacl = '';
	$numofemails = 1;

	foreach ($acl as $email) {
		$emailacl .= "<input id='emailacl-$numofemails' type='checkbox' name='email_acl[$email]' CHECKED />$email<br />";
		$numofemails++;
	}

	// If new config
	if (!isset($share['EmailModified']))
		$share['EmailModified'] = time();

	$contents = "
	<form action='flexshare.php' method='post' enctype='multipart/form-data'>
	<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>
	  <tr>
		<td width='35%' class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
		<td>" . WebDropDownEnabledDisabled("email_enabled", $email_enabled) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL_ADD . "</td>
		<td><a href='mailto:" . $email_address . "'>$email_address</a></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL_ATTACH_DIR . "</td>
		<td>" . WebDropDownHash("email_dir", $email_dir, $email_dirs, 250) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL_POLICY . "</td>
		<td>" . WebDropDownHash("email_policy", $email_policy, $email_policies, 250) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL_SAVE . "</td>
		<td>" . WebDropDownHash("email_save", $email_save, $email_saves, 250, 'toggleemailsave()', "email_save") . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_EMAIL_NOTIFY . "</td>
		<td><input id='email_notify' type='text' name='email_notify' value='$email_notify' style='width:250px' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . WEB_LANG_RESTRICT_ACCESS . "</td>
		<td>" . WebDropDownEnabledDisabled("email_restrict_access", $email_restrict_access, 0,
			"toggleemailrestrictaccess($numofemails)", "email_restrict_access") . "
		</td>
	  </tr>
	   <tr>
		 <td class='mytablesubheader' valign='top' align='right'>" . WEB_LANG_EMAIL_ACL . "</td>
		 <td>$emailacl
			<textarea id='email_add_acl' name='email_add_acl' cols='40' rows='3' style='width:250px;'>" . 
			$email_add_acl . "</textarea><br /><font class='small'>" . WEB_LANG_NOTE_ONEPERLINE . "</font
		</td>

	   </tr>
	   <tr>
		 <td class='mytablesubheader' nowrap>" . WEB_LANG_REQUIRE_SIGNATURE . "</td>
		 <td>" . WebDropDownEnabledDisabled("email_req_signature", $email_req_signature) . "</td>
	   </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td nowrap>" . WebButtonUpdate("UpdateEditEmail") . "
			<input type='hidden' name='name' value='$name'>
			<input type='hidden' name='activetab' value='email'>
			</td>
		  </tr>
		</table>
	  </form>
	";

	if ((int)$email_save == Flexshare::SAVE_REQ_CONFIRM)
		$contents .= "<script type='text/javascript'>enable('email_notify');</script>";
	else
		$contents .= "<script type='text/javascript'>disable('email_notify');</script>";

	if ($email_restrict_access) {
		$contents .= "<script type='text/javascript'>
		enable('email_add_acl');
		if (window.oEmailFilter)
		  oEmailFilter.set('disabled', false);
		for (i = 1; i <= $numofemails; i++)
		  enable('emailacl-' + i);
		</script>";
	} else {
		$contents .= "<script type='text/javascript'>
		disable('email_add_acl');
		if (window.oEmailFilter)
		  oEmailFilter.set('disabled', true);
		for (i = 1; i <= $numofemails; i++)
		  disable('emailacl-' + i);
		</script>";
	}

	return $contents;
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayToggleShare()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayToggleShare($name, $toggle)
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	echo "
	  <tr>
		<td align='center'>
		  <input type='hidden' name='ToggleShare[$name]' value='$toggle'>
		  <p>" . WEBCONFIG_ICON_INFO . " " . WEB_LANG_ENABLE_DELAY . "</p>
			". WebButtonToggle("Confirm", $toggle . " " . $name) . " " . WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("450");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDeleteShare()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDeleteShare($name)
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "450");
	echo "
	  <tr>
		<td align='center'>
		  <input type='hidden' name='DeleteShare' value='$name'>
		  <p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_ARE_YOU_SURE_DELETE . "<b> <i>" . $name . "</i></b>?</p>" .
		  "<p><input type='checkbox' name='delete_dir'>" .	WEB_LANG_DELETE_DIR_OPTION . "</p>" .
		  WebButtonDelete("Confirm") . " " . WebButtonCancel("Cancel") . "
		</td>
	  </tr>
	";
	WebTableClose("450");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayPendingEmails
//
///////////////////////////////////////////////////////////////////////////////

function DisplayPendingEmails($messages)
{
	global $flexshare;

	$data = '';
	$save_list = '';
	$counter = 0;

	while (is_array(current($messages))) {
		$key = key($messages);

		if (isset($messages[$key]['SavedFiles'])) {
			foreach ($messages[$key]['SavedFiles'] as $filename)
				$save_list .= "<li>" . $filename . "</li>"; 
			next($messages);
			continue;
		}

		if ($counter%2 != 0)
			$class = " bgcolor='#F3F3F3'";
		else
			$class = '';

		if ($messages[$key]['Ssl'])
			$ssl = FLEX_ICON_SECURE;
		else
			$ssl = FLEX_ICON_INSECURE;

		$data .= "<tr$class>" .
				 "<td valign='top'>" . $messages[$key]['Reply-To'] . "</td>" .
				 "<td valign='top'>" . $messages[$key]['Subject'] . "</td>" .
				 "<td valign='top'>" . $messages[$key]['Share'] . "</td>" .
				 "<td align='center'>$ssl</td>" .
				 "<td valign='top'>" . $flexshare->GetFormattedBytes((int)$messages[$key]['Size'], 1) . "</td>" .
				 "<td valign='top' align='center' nowrap>" .
				 WEBCONFIG_ICON_SAVE . "<input type='radio' name='action[$key]' value='save'>&#160;&#160;" .
				 WEBCONFIG_ICON_DELETE . "<input type='radio' name='action[$key]' value='delete'>" .
				 "</td>" .
				 "</tr>";

		next($messages);

		$counter++;
	}

	if (!$data)
		$data = "<tr><td colspan='6' align='center'>" . WEB_LANG_NO_MAIL . "</td></tr>";
	else
		$data .= "<tr><td colspan='5'>&#160;</td>" .
				 "<td nowrap align='center'>" . WebButtonUpdate('EmailAttachments') .
				 "</td></tr>";

	if ($save_list)
		WebDialogInfo(WEB_LANG_SAVED_FILES . '<ul>' . $save_list . '</ul>');

	WebFormOpen();
	WebTableOpen(WEB_LANG_MESSAGE_QUEUE, "100%");
	echo "
		<tr class='mytableheader'>
			<td>" . LOCALE_LANG_FROM . "</td>
			<td>" . FLEXSHARE_LANG_SUBJECT . "</td>
			<td>" . WEB_LANG_SHARE_NAME . "</td>
			<td align='center'>" . WEB_LANG_TLS . "</td>
			<td>" . FLEXSHARE_LANG_MSG_SIZE . "</td>
			<td>&nbsp; </td>
		</tr>
		$data
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
	// Add entry to hosts file if hostname is not valid

	try {
		$hostnamechecker = new HostnameChecker();
		$nameisok = $hostnamechecker->IsLookupable();
		if (!$nameisok)
			$hostnamechecker->AutoFix();
	} catch (Exception $e) {
		// Not fatal
	}
}

// vi: syntax=php ts=4
?>
