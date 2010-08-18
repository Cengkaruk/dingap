<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2010 Point Clark Networks.
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
require_once("../../api/ClearDirectory.class.php");
require_once("../../api/Daemon.class.php");
require_once("../../api/Ldap.class.php");
require_once("../../api/Organization.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-ldap.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$ldap = new Ldap();
$directory = new ClearDirectory();
$organization = new Organization();

$domain = isset($_POST['domain']) ? $_POST['domain'] : '';
$policy = isset($_POST['policy']) ? $_POST['policy'] : '';

try {
	if (isset($_POST['StartDaemon'])) {
		$ldap->SetRunningState(true);
	} else if (isset($_POST['Update'])) {
		$olddomain = $organization->GetDomain();
		$isinitialized = $directory->IsInitialized();

		$organization->SetDomain($domain);
		$ldap->SetBindPolicy($policy);
		$ldap->Reset();

		$errors = $organization->GetValidationErrors(true);

		if (empty($errors)) {

			if (!$isinitialized) {
				$directory->RunInitialize(ClearDirectory::MODE_STANDALONE, $domain);
			// Only update domain if it changes.
			} else if ($olddomain != $domain) {
				$directory->SetDomain($domain, true);
			}
		} else {
			WebDialogWarning($errors[0]);
		}
	}

} catch (Exception $e) {
	if (isset($olddomain))
		$organization->SetDomain($olddomain);
	WebDialogWarning($e->getMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayLdapSettings($domain);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayLdapSettings()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLdapSettings($domain)
{
	global $ldap;
	global $directory;
	global $organization;

	try {
		$isinitialized = $directory->IsInitialized();

		if ($isinitialized) {
			$basedn = $ldap->GetBaseDn();
			$binddn = $ldap->GetBindDn();
			$bindpassword = $ldap->GetBindPassword();
			$policy = $ldap->GetBindPolicy();
			$domain = $organization->GetDomain();
			$mode = $directory->GetMode();
			$modetext = $directory->modes[$mode];
		} else {
			$policy = Ldap::CONSTANT_LOCALHOST;
			if (empty($domain)) {
				$domain = $organization->GetDomain();
				if (empty($domain))
					$domain = $organization->SuggestDefaultDomain();
			}
		}
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$policy_options = array();
	$policy_options[Ldap::CONSTANT_LAN] = LOCALE_LANG_ENABLED;
	$policy_options[Ldap::CONSTANT_LOCALHOST] = LOCALE_LANG_DISABLED;

	if ($mode) {
		$moderow = "
			<tr>
				<td class='mytablesubheader' nowrap>" . CLEARDIRECTORY_LANG_MODE . "</td>
				<td>$modetext</td>
			</tr>
		";
	} else {
		$moderow = '';
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_LDAP_SETTINGS);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap width='200'>" . ORGANIZATION_LANG_DOMAIN . "</td>
			<td><input type='text' name='domain' size='25' value='$domain'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_LDAP_PUBLISH_POLICY . "</td>
			<td>" . WebDropDownHash("policy", $policy, $policy_options) . "</td>
		</tr>
		$moderow
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("Update") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();

	if ($isinitialized) {
		echo "
			<div id='directory-whirly' align='center'><br><br>" . WEBCONFIG_ICON_LOADING . " &nbsp; " .
			LOCALE_LANG_WAITING_FOR_DIRECTORY_SERVER . "
			</div>
			<div id='directory-info' align='center'>
		";

		// TODO: strange browser behaviour.  Table width columns hard coded for now.

		WebTableOpen(WEB_LANG_LDAP_INFORMATION);
		echo "
			<tr>
				<td class='mytablesubheader' nowrap width='200'>" . LDAP_LANG_BASE_DN . "</td>
				<td width='500'><span id='basedn'>$basedn</span></td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . LDAP_LANG_BIND_DN . "</td>
				<td><span id='binddn'>$binddn</span></td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . LDAP_LANG_BIND_PASSWORD . "</td>
				<td><span id='bindpassword'>$bindpassword</span></td>
			</tr>
		";
		WebTableClose();

		echo "</div>";
		echo "<script type='text/javascript'>hide('directory-info')</script>";
	}
}

?>
