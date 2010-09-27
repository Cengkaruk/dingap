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
require_once("../../api/Daemon.class.php");
require_once("../../api/Httpd.class.php");
require_once("../../api/HostnameChecker.class.php");
require_once("../../api/Flexshare.class.php");
require_once("../../api/UserManager.class.php");
require_once("../../api/GroupManager.class.php");
require_once("../../api/Group.class.php");

require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-web-server.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$httpd = new Httpd();

if (isset($_POST['EnableBoot'])) {
	try {
		$httpd->SetBootState(true);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DisableBoot'])) {
	try {
		$httpd->SetBootState(false);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['StartDaemon'])) {
	try {
		$httpd->SetRunningState(true);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['StopDaemon'])) {
	try {
		$httpd->SetRunningState(false);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateConfig'])) {
	try {
		$httpd->SetServerName($_POST['servername']);
		$httpd->SetSslState((bool) $_POST['sslstate']);
		$httpd->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateWebSite'])) {
	try {
		$is_default = isset($_POST['is_default']) ? true : false;

		// TODO: a last minute fix before 4.2 release... clean up.
		if ($is_default) {
			try {
				$oldinfo = $httpd->GetDefaultHostInfo();

				require_once("../../api/Flexshare.class.php");
				$flexshare = new Flexshare();
				$flexshare->GetShare($oldinfo['servername']);
				$flexshare->DeleteShare($oldinfo['servername'], false);
			} catch (FlexshareNotFoundException $e) {
				// GetShare will trigger this exception on a virgin box
				// TODO: implement Flexshare.Exists($name) instead of this hack
			}

			$httpd->SetDefaultHost($_POST['domain'], $_POST['alias']);
		} else {
			$httpd->SetVirtualHost($_POST['domain'], $_POST['alias'], $_POST['docroot']);
		}

		$httpd->ConfigureUploadMethods(
			$_POST['domain'], $_POST['docroot'], $_POST['group_owner'], $_POST['ftp_enabled'], $_POST['file_enabled']
		);

		$httpd->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['AddVirtualHost'])) {
	try {
		$httpd->AddVirtualHost($_POST['newhost']);
		$_POST['EditVirtualHost'][$_POST['newhost']] = true; // Go right to the edit page
		$httpd->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DeleteVirtualHost'])) {
	try {
		$httpd->DeleteVirtualHost(key($_POST['DeleteVirtualHost']));
		$httpd->Reset();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['EditVirtualHost'])) {
	DisplayEdit(false, key($_POST['EditVirtualHost']));
} else if (isset($_POST['EditDefaultHost'])) {
	DisplayEdit(true);
} else {
	WebDialogDaemon("httpd");
	SanityCheck();
	DisplayConfig();
	DisplayVhosts();
}

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
	global $httpd;

	try {
		$sslstate = $httpd->GetSslState();
		$servername = $httpd->GetServerName();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}
	
	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "100%");
	echo "
		<tr>
			<td width='40%' class='mytablesubheader' nowrap>" . HTTPD_LANG_SERVERNAME . "</td>
			<td><input type='text' name='servername' value='$servername' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . HTTPD_LANG_SSLSTATE . "</td>
			<td>" . WebDropDownEnabledDisabled("sslstate", $sslstate) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("UpdateConfig") . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayVhosts()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayVhosts()
{
	global $httpd;

	$flexshare = new Flexshare();
	$groupmanager = new GroupManager();

	$required_service_not_running = array();
	$required_service_not_installed = null;

	// Make sure default domain is set
	//--------------------------------

	try {
		if ($httpd->IsDefaultSet()) {
			$defaultinfo = $httpd->GetDefaultHostInfo();
			$defaulthost = $defaultinfo["servername"];
		} else {
			$defaulthost = $httpd->GetServerName();
			$httpd->AddDefaultHost($defaulthost);
		}

		$groups = $groupmanager->GetAllGroups();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Virtual hosts
	//--------------

	try {
		$domains = $httpd->GetVirtualHosts();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$legend = WEBCONFIG_ICON_FTP . " " . WEB_LANG_FTP . " &#160; &#160; " . WEBCONFIG_ICON_SAMBA . " " . WEB_LANG_FILE;

	$domainlist = "";

	if ($domains) {

		foreach ($domains as $domain) {
			$group = "---";
			$access = "";
			try {
				$share = $flexshare->GetShare($domain);
				if ($share['ShareEnabled'] && $share['FtpEnabled']) {
					$access = WEBCONFIG_ICON_FTP;
					if (!file_exists("../../api/Proftpd.class.php")) {
						$required_service_not_installed = WEB_LANG_ERRMSG_FTP_NOT_INSTALLED;
					} else {
						require_once('../../api/Proftpd.class.php');
						$proftpd = new ProFtpd();
						if (!$proftpd->GetRunningState()) {
							$required_service_not_running['url'] = 'ftp.php';
							$required_service_not_running['name'] = $proftpd->GetTitle();
						}
					}
				}

				if ($share['ShareEnabled'] && $share['FileEnabled']) {
					if ($access)
						$access .= "&#160;&#160;";
					$access .= WEBCONFIG_ICON_SAMBA;
					// TODO: changed the way we detect modules
					if (!file_exists("samba.php")) {
						$required_service_not_installed = WEB_LANG_ERRMSG_SMB_NOT_INSTALLED;
					} else {
						require_once('../../api/Samba.class.php');
						$samba = new Samba();
						if (!$samba->GetRunningState()) {
							$required_service_not_running['url'] = 'samba.php';
							$required_service_not_running['name'] = $samba->GetTitle();
						}
					}
				}

				if ($access) {
					$groupobj = new Group($share['ShareGroup']);
					$prefix = ($groupobj->Exists()) ? GROUP_LANG_GROUP : GROUP_LANG_USER;
					$group = $prefix . " - " . $share['ShareGroup'];
				}
			} catch (Exception $e) {
				// Ignore
			}

			$domainlist .= "
			  <tr>
				<td>$domain</td>
				<td>$access</td>
				<td>$group</td>
				<td nowrap>" .
				  WebButtonEdit("EditVirtualHost[$domain]") .
				  WebButtonDelete("DeleteVirtualHost[$domain]") .
				"</td>
			  </tr>\n
			";
		}
	}

	$group = "---";
	$access = "";

	try {
		$share = $flexshare->GetShare($defaulthost);
		if ($share['ShareEnabled'] && $share['FtpEnabled']) {
			$access = WEBCONFIG_ICON_FTP;
			if (!file_exists("../../api/Proftpd.class.php")) {
				$required_service_not_installed = WEB_LANG_ERRMSG_FTP_NOT_INSTALLED;
			} else {
				require_once('../../api/Proftpd.class.php');
				$proftpd = new ProFtpd();
				if (!$proftpd->GetRunningState()) {
					$required_service_not_running['url'] = 'ftp.php';
					$required_service_not_running['name'] = $proftpd->GetTitle();
				}
			}
		}
		if ($share['ShareEnabled'] && $share['FileEnabled']) {
			if ($access)
				$access .= "&#160;&#160;";
			$access .= WEBCONFIG_ICON_SAMBA;
			// TODO: changed the way we detect modules
			if (!file_exists("samba.php")) {
				$required_service_not_installed = WEB_LANG_ERRMSG_SMB_NOT_INSTALLED;
			} else {
				require_once('../../api/Samba.class.php');
				$samba = new Samba();
				if (!$samba->GetRunningState()) {
					$required_service_not_running['url'] = 'samba.php';
					$required_service_not_running['name'] = $samba->GetTitle();
				}
			}
		}

		if ($access) {
			$groupobj = new Group($share['ShareGroup']);
			$prefix = ($groupobj->Exists()) ? GROUP_LANG_GROUP : GROUP_LANG_USER;
			$group = $prefix . " - " . $share['ShareGroup'];
		}

	} catch (Exception $e) {
		// Ignore
	}
	if (!empty($required_service_not_running)) {
		WebDialogWarning(
			WEB_LANG_ERRMSG_UPLOAD_SERVICE_NOT_RUNNING . "&#160;(" . $required_service_not_running['name'] . ")" .
			"&#160;-&#160;" . WebUrlJump($required_service_not_running['url'], LOCALE_LANG_CONFIGURE)
		);
	}
	if ($required_service_not_installed != null) {
		WebDialogWarning(
			$required_service_not_installed . "&#160;-&#160;" . WebUrlJump('modules.php', LOCALE_LANG_INSTALL)
		);
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_VIRTUAL_TITLE, "100%");
	WebTableHeader(HTTPD_LANG_WEB_SITE . "|" . WEB_LANG_UPLOAD_VIA . "|" .  WEB_LANG_UPLOAD_ACCESS . "|");
	echo "
		<tr>
			<td nowrap>$defaulthost (" . WEB_LANG_DEFAULT . ")</td>
			<td nowrap>$access</td>
			<td nowrap>$group</td>
			<td nowrap>" . WebButtonEdit("EditDefaultHost") . "</td>
		</tr>
		$domainlist
		<tr>
			<td nowrap colspan='3'><input type='text' name='newhost' value='' style='width:175;' /></td>
			<td nowrap>" . WebButtonAdd("AddVirtualHost") . "</td>
		</tr>
		<tr>
			<td colspan='4' class='mytablelegend'>$legend</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEdit()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEdit($is_default, $domain)
{
	global $httpd;

	$flexshare = new Flexshare();
	$groupmanager = new GroupManager();
	$usermanager = new UserManager();

	try {
		if ($is_default) {
			$info = $httpd->GetDefaultHostInfo();
			$domain = $info['servername'];
		} else {
			$info = $httpd->GetVirtualHostInfo($domain);
		}

		$groups = $groupmanager->GetAllGroups();
		$users = $usermanager->GetAllUsers();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	try {
		$share = $flexshare->GetShare($domain);
	} catch (FlexshareNotFoundException $e) {
		# Do nothing
		$share = array();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$file = new File("../../api/Proftpd.class.php");

	if (! $file->Exists()) {
		$ftp_select = WEB_LANG_FTP_NOT_AVAIL . " - " . WebUrlJump("modules.php", LOCALE_LANG_INSTALL);;
	} else {
		$ftp_enabled = 0;
		# FTP access
		if (isset($_POST['ftp_enabled'])) {
			$ftp_enabled = $_POST['ftp_enabled'];
		} else {
			if ($share['ShareEnabled'])
				$ftp_enabled = $share['FtpEnabled'];
		}
	
		if ($ftp_enabled)
			$ftp_select = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
						   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
		else
			$ftp_select = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
						   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";
		$ftp_select = "<select id='ftp' name='ftp_enabled' onChange='toggleOwner();'>$ftp_select</select>";
	}

	if (!file_exists("samba.php")) {
		$file_select = WEB_LANG_FILE_NOT_AVAIL . " - " . WebUrlJump("modules.php", LOCALE_LANG_INSTALL);;
	} else {
		$file_enabled = 0;
		# File  (Samba)access
		if (isset($_POST['file_enabled'])) {
			$file_enabled = $_POST['file_enabled'];
		} else {
			if ($share['ShareEnabled'])
				$file_enabled = $share['FileEnabled'];
		}
	
		if ($file_enabled)
			$file_select = "<option value='1' SELECTED>" . LOCALE_LANG_YES . "</option>" .
						   "<option value='0'>" . LOCALE_LANG_NO . "</option>";
		else
			$file_select = "<option value='1'>" . LOCALE_LANG_YES . "</option>" .
						   "<option value='0' SELECTED>" . LOCALE_LANG_NO . "</option>";
		$file_select = "<select id='file' name='file_enabled' onChange='toggleOwner();'>$file_select</select>";
	}

	$group_select = "";

	foreach ($groups as $group) {
		$selected = ($group == $share['ShareGroup']) ? "selected" : "";
		$group_select .= "<option value='" . $group . "' $selected>" . GROUP_LANG_GROUP . " - " . $group . "</option>\n";
	}

	foreach ($users as $group) {
		$selected = ($group == $share['ShareGroup']) ? "selected" : "";
		$group_select .= "<option value='$group' $selected>" . GROUP_LANG_USER . " - $group</option>\n";
	}

	if (empty($groups) && empty($users)) {
		$group_select = WEB_LANG_GROUP_REQUIRED . " - " . WebUrlJump("groups.php", LOCALE_LANG_CONFIGURE);
		$upload = "";
	} else {
		$group_select = "<select id='group_owner' name='group_owner'>$group_select</select>";
		$upload = "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_ALLOW_FTP_UPLOAD . "</td>
				<td>$ftp_select</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_ALLOW_FILE_SHARE . "</td>
				<td>$file_select</td>
			</tr>
		";
	}

	if ($is_default) {
		$servername = "<input type='text' name='domain' value='$info[servername]' />";
		$docroot = $info['docroot'] . "
			<input type='hidden' name='docroot' value='" . Httpd::PATH_DEFAULT . "' />
			<input type='hidden' name='is_default' value='yes' />";
	} else {
		$servername = $info['servername'] . "<input type='hidden' name='domain' value='$info[servername]' />";
		$docroot = $info['docroot'] . "<input type='hidden' name='docroot' value='" . $info['docroot'] . "' />" ;
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_VIRTUAL_TITLE, "75%");
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . HTTPD_LANG_WEB_SITE . "</td>
			<td>$servername</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>". HTTPD_LANG_ALIASES . "</td>
			<td><input type='text' name='alias' value='$info[aliases]' style='width:250px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . HTTPD_LANG_DOC_ROOT . "</td>
			<td>$docroot</td>
		</tr>
		$upload
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_UPLOAD_ACCESS . "</td>
			<td>$group_select</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" .  WebButtonUpdate("UpdateWebSite") . WebButtonBack("Back") . "</td> 
		</tr>
	";
	WebTableClose("75%");
	WebFormClose();

	echo "<script type='text/javascript' language='JavaScript'>toggleOwner();</script>";
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
		if (!$nameisok) {
			$hostnamechecker->AutoFix();
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

// vim: syntax=php ts=4
?>
