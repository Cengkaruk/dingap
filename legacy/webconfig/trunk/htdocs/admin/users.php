<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007-2010 Point Clark Networks.
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
require_once("../../api/Hostname.class.php");
require_once("../../api/User.class.php");
require_once("../../api/UserManager.class.php");
require_once("../../api/Organization.class.php");
require_once("../../api/Group.class.php");
require_once("../../api/GroupManager.class.php");
require_once("../../api/Shell.class.php");
require_once("../../api/Ssl.class.php");
require_once("../../api/Webconfig.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-users.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();
WebCheckCertificates();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$ldap = new Ldap();
$directory = new ClearDirectory();
$usermanager = new UserManager();
$userlocale = new User("notused"); // Just for locale
$groupmanager = new GroupManager();

$quota_options['50'] = 50 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['100'] = 100 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['200'] = 200 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['300'] = 300 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['400'] = 400 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['500'] = 500 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['600'] = 600 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['700'] = 700 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['800'] = 800 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['900'] = 900 . " " . LOCALE_LANG_MEGABYTES;
$quota_options['1000'] = 1 . " " . LOCALE_LANG_GIGABYTES;
$quota_options['2000'] = 2 . " " . LOCALE_LANG_GIGABYTES;
$quota_options['3000'] = 3 . " " . LOCALE_LANG_GIGABYTES;
$quota_options['4000'] = 4 . " " . LOCALE_LANG_GIGABYTES;
$quota_options[''] = LOCALE_LANG_UNLIMITED;

$username = isset($_POST['username']) ? strtolower($_POST['username']) : "";
$userinfo = isset($_POST['userinfo']) ? $_POST['userinfo'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : "";
$verify = isset($_POST['verify']) ? $_POST['verify'] : "";

try {
	if (isset($_POST['EnableBoot'])) {
		$ldap->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$ldap->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$ldap->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$ldap->SetRunningState(false);
	}
} catch (Exception $e) {
	WebDialogWarning($e->getMessage());
}

if (isset($_POST['AddUser'])) {
	try {
		$user = new User($username);

		// Don't create user if password/verify is bad
        if (!$user->IsValidPasswordAndVerify($password, $verify)) {
            $errors = $user->GetValidationErrors(true);
            WebDialogWarning($errors[0]);
		} else {
			// Add the user
			// TODO: ConvertFlags should be handled in the API?
			ConvertFlags($userinfo);
			$user->Add($userinfo);
			$user->ResetPassword($password, $verify, $_SESSION['user_login']);

			// Set group membership
			$grouplist = array();
			$groupinfo = isset($_POST['groupinfo']) ? $_POST['groupinfo'] : array();

			foreach ($groupinfo as $group => $state)
				$grouplist[] = $group;

			$groupmanager->UpdateGroupMemberships($username, $grouplist);

			// Create SSL Certificate (TODO -- move to User class)
			$ssl = new Ssl();

			if ($ssl->ExistsDefaultClientCertificate($username))
				$ssl->DeleteDefaultClientCertificate($username);

			$ssl->CreateDefaultClientCertificate($username, $password, $password);

			WebFormOpen();
			WebDialogInfo(WEB_LANG_USER_WAS_ADDED_HELP . "<br><br>" . WebButtonBack("Cancel"));
			WebFormClose();

			// Reset form variables
			$username = '';
			$password = '';
			$verify = '';
			$userinfo = NULL;
		}
	} catch (ValidationException $e) {
		WebDialogWarning(WebCheckErrors($user->GetValidationErrors()));
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} elseif (isset($_POST['Delete'])) {
	$username = key($_POST['Delete']);

	try {
		$user = new User($username);
		$user->Delete();

		// TODO: move to User class
		$ssl = new Ssl();
		$ssl->DeleteDefaultClientCertificate($username);
	} catch (ValidationException $e) {
		WebDialogWarning(WebCheckErrors($user->GetValidationErrors(true)));
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

} else if (isset($_POST['UpdateUser'])) {
	$username = key($_POST['UpdateUser']);

	try {
		$user = new User($username);
		$olduserinfo = $user->GetInfo();

		ConvertFlags($userinfo);
		$user->Update($userinfo);

		// Update password if set
		if (!empty($password) || !empty($verify))
			$user->ResetPassword($password, $verify, $_SESSION['user_login']);

		// Set group membership
		$grouplist = array();
		$groupinfo = isset($_POST['groupinfo']) ? $_POST['groupinfo'] : array();

		foreach ($groupinfo as $group => $state)
			$grouplist[] = $group;

		$groupmanager->UpdateGroupMemberships($username, $grouplist);

		$userinfo = null;
		unset($_POST);
	} catch (ValidationException $e) {
		WebDialogWarning(WebCheckErrors($user->GetValidationErrors(true)));
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} else if (isset($_POST['UnlockUser'])) {
	try {
		$user = new User(key($_POST['UnlockUser']));
		$user->Unlock();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
} else if (isset($_POST['HideConversionStatus'])) {
	try {
		$usermanager->HideConversionStatus();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////
//
// When an administrator adds a new user, the "Add User" form is shown again.
// This is a bit unusual for webconfig, but it makes sense in this case (and
// many administrators have requested it!)
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['DeleteUser'])) {
	DisplayDelete(key($_POST['DeleteUser']));
} else if (isset($_POST['EditUser'])) {
	DisplayAddEdit('edit', key($_POST['EditUser']), $password, $verify);
} else if (isset($_POST['UpdateUser'])) {
	DisplayAddEdit('edit', key($_POST['UpdateUser']), $password, $verify, $userinfo);
} else if (isset($_POST['AddUser'])) {
	DisplayAddEdit('add', $username, $password, $verify, $userinfo);
} else if (isset($_POST['Cancel'])) {
	DisplayAddInfobox();
	DisplayUsers();
} else if (isset($_POST['DisplayAdd'])) {
	DisplayAddEdit('add', $username, $password, $verify, $userinfo);
} else {
	DisplayAddInfobox();
	DisplayUsers();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAddInfobox()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAddInfobox()
{
	WebFormOpen();
	WebDialogInfo(WEB_LANG_ADD_USERS . "&#160; " . WebButtonAdd("DisplayAdd"));
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayUsers()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayUsers()
{
	global $directory;
	global $usermanager;
	global $quota_options;

	try {
		$webconfig = new Webconfig();
		$shellaccess = $webconfig->GetShellAccessState();
		$userlist = $usermanager->GetAllUserInfo();
   		$servicelist = $directory->GetServices();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return;
	}

	// Legend
	//-------

	$legend = "";

	if (in_array(ClearDirectory::SERVICE_TYPE_EMAIL, $servicelist))
		$legend .= WEBCONFIG_ICON_EMAIL . " " . USER_LANG_EMAIL . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_GOOGLE_APPS, $servicelist))
		$legend .= WEBCONFIG_ICON_GOOGLE_APPS . " " . USER_LANG_GOOGLE_APPS . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_FTP, $servicelist))
		$legend .= WEBCONFIG_ICON_FTP . " " . USER_LANG_FTP . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_OPENVPN, $servicelist))
		$legend .= WEBCONFIG_ICON_OPENVPN . " " . USER_LANG_OPENVPN . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_PPTP, $servicelist))
		$legend .= WEBCONFIG_ICON_PPTP . " " . USER_LANG_PPTP . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_PROXY, $servicelist))
		$legend .= WEBCONFIG_ICON_PROXY . " " . USER_LANG_PROXY . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_SAMBA, $servicelist))
		$legend .= WEBCONFIG_ICON_SAMBA . " " . USER_LANG_SAMBA . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_WEB, $servicelist))
		$legend .= WEBCONFIG_ICON_WEB . " " . USER_LANG_WEB . " &#160; &#160; ";

	if (in_array(ClearDirectory::SERVICE_TYPE_PBX, $servicelist))
		$legend .= WEBCONFIG_ICON_PBX . " " . USER_LANG_PBX . " &#160; &#160; ";

	if ($shellaccess)
		$legend .= WEBCONFIG_ICON_SHELL . " " . USER_LANG_SHELL . " &#160; &#160; ";

	$usertable = '';
	$counter = 0;

	foreach ($userlist as $username => $info) {
		if (isset($info['deleteMailbox']) && $info['deleteMailbox'])
			$rowclass = 'rowdisabled' . (($counter % 2) ? 'alt' : '');
		else
			$rowclass = 'rowenabled' . (($counter % 2) ? 'alt' : '');

		$counter++;

		$options = "";

		if (! empty($info['sambaFlag']))
			$options .= WEBCONFIG_ICON_SAMBA . " ";

		if (! empty($info['mailFlag']))
			$options .= WEBCONFIG_ICON_EMAIL . " ";

		if (! empty($info['googleAppsFlag']))
			$options .= WEBCONFIG_ICON_GOOGLE_APPS . " ";

		if (! empty($info['openvpnFlag']))
			$options .= WEBCONFIG_ICON_OPENVPN . " ";

		if (! empty($info['pptpFlag']))
			$options .= WEBCONFIG_ICON_PPTP . " ";

		if (! empty($info['ftpFlag']))
			$options .= WEBCONFIG_ICON_FTP . " ";

		if (! empty($info['proxyFlag']))
			$options .= WEBCONFIG_ICON_PROXY . " ";

		if (! empty($info['webFlag']))
			$options .= WEBCONFIG_ICON_WEB . " ";

		if (! empty($info['pbxFlag']))
			$options .= WEBCONFIG_ICON_PBX . " ";

		if (! empty($info['loginShell']) && (! preg_match("/nologin/", $info['loginShell'])))
			$options .= WEBCONFIG_ICON_SHELL . " ";

		if (isset($info['deleteMailbox']) && $info['deleteMailbox']) {
			$button = WEB_LANG_DELETE_IN_PROGRESS;
		} else {
			$button = WebButtonEdit("EditUser[$username]") . " " . WebButtonDelete("DeleteUser[$username]");
			if (isset($info['sambaAccountLocked']) && $info['sambaAccountLocked']) {
				$button .= WebButton("UnlockUser[$username]", USER_LANG_UNLOCK, WEBCONFIG_ICON_TOGGLE);
				$rowclass = 'alert';
			}
		}

		$usertable .= "
			<tr class='$rowclass'>
				<td>" . $username . "</td>
				<td>" . $info['firstName'] . " " . $info['lastName'] . "</td>
				<td>" . $options . "</td>
				<td>" . $button . "</td>
			</tr>
		";

	}

	if (empty($usertable))
		$usertable = '<tr><td colspan="6" align="center">'. LOCALE_LANG_ERRMSG_NO_ENTRIES .'</td></tr>';

	// Display list of user on the system
	//-----------------------------------

	WebFormOpen();
	WebTableOpen(WEB_LANG_USER_INFO_TITLE, "100%");
	WebTableHeader(LOCALE_LANG_USERNAME . "|" . USER_LANG_FULLNAME . "|" . USER_LANG_OPTIONS . "|");
	echo $usertable;
	if ($legend) 
		echo "<tr><td colspan='6' class='mytablelegend'>$legend</td>";
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAddEdit()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAddEdit($action, $username, $password = null, $verify = null, $userinfo = null)
{
	global $directory;
	global $usermanager;
	global $groupmanager;
	global $quota_options;

	try {
		$webconfig = new Webconfig();
		$shellaccess = $webconfig->GetShellAccessState();
   		$servicelist = $directory->GetServices();
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
		return ;
	}

	if (is_null($userinfo)) {
		if (! empty($username)) {
			try {
				$user = new User($username);
				$userinfo = $user->GetInfo();
			} catch (Exception $e) {
				WebDialogWarning($e->getMessage());
				return ;
			}
		} else {
			$userinfo = array(
				'lastName' => null,
				'firstName' => null,
				'title' =>  null,
				'organization' =>  null,
				'unit' =>  null,
				'roomNumber' =>  null,
				'street' =>  null,
				'postOfficeBox' =>  null,
				'postalCode' =>  null,
				'city' =>  null,
				'region' =>  null,
				'country' => null,
				'telephone' =>  null,
				'fax' => null,
				'mailquota' => '',
				'deleteMailbox' => '',
				'ftpFlag' => true,
				'mailFlag' => true,
				'googleAppsFlag' => true,
				'openvpnFlag' => true,
				'pptpFlag' => true,
				'proxyFlag' => true,
				'sambaFlag' => true,
				'webFlag' => true,
				'pbxFlag' => true,
				'pbxPresenceFlag' => true,
				'pbxExtension' => null,
			);

			try {
				$organization = new Organization();
				$default_street = $organization->GetStreet();
				$default_city = $organization->GetCity();
				$default_region = $organization->GetRegion();
				$default_country = $organization->GetCountry();
				$default_postalcode = $organization->GetPostalCode();
				$default_unit = $organization->GetUnit();
				$default_organization = $organization->GetName();
			} catch (Exception $e) {
				WebDialogWarning($e->getMessage());
				return ;
			}

			$userinfo['street'] = $default_street;
			$userinfo['city'] = $default_city;
			$userinfo['region'] = $default_region;
			$userinfo['country'] = $default_country;
			$userinfo['postalCode'] = $default_postalcode;
			$userinfo['unit'] = $default_unit;
			$userinfo['organization'] = $default_organization;
		}
	}

	if ($action == "add") {
		$tabletitle = WEB_LANG_ADD_USER_TITLE;
		$action_html = WebButtonAdd("AddUser") . " " . WebButtonCancel("Cancel");
		$userinfo_html = "<input size='30' type='text' name='username' value='$username' />";
	} else {
		$tabletitle = WEB_LANG_EDIT_INFO_TITLE;
		$action_html = WebButtonUpdate("UpdateUser[$username]") . " " . WebButtonCancel("Cancel");
		$userinfo_html = $username;
	}

	$addoptions = "";

	if (in_array(ClearDirectory::SERVICE_TYPE_EMAIL, $servicelist)) {
		$quota = isset($userinfo['mailquota']) ? $userinfo['mailquota'] : '';
		$emailon = isset($userinfo['mailFlag']) && $userinfo['mailFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_EMAIL . "</td>
				<td colspan='2' valign='bottom'><input type='checkbox' name='userinfo[mailFlag]' $emailon /></td>
			</tr>
		";
	}

	if (in_array(ClearDirectory::SERVICE_TYPE_GOOGLE_APPS, $servicelist)) {
		$appson = isset($userinfo['googleAppsFlag']) && $userinfo['googleAppsFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_GOOGLE_APPS . "</td>
				<td colspan='2'><input type='checkbox' name='userinfo[googleAppsFlag]' $appson /></td>
			</tr>
		";
	}

	if (in_array(ClearDirectory::SERVICE_TYPE_PBX, $servicelist)) {
		$pbxon = isset($userinfo['pbxFlag']) && $userinfo['pbxFlag'] ? "checked" : "";
		$presenceon = isset($userinfo['pbxPresenceFlag']) && $userinfo['pbxPresenceFlag'] ? "checked" : "";
		$extension = isset($userinfo['pbxExtension']) ? $userinfo['pbxExtension'] : '';
		//PBXME - javascript validation to extension field added
		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_PBX . "</td>
				<td colspan='2'>
					<input type='checkbox' name='userinfo[pbxFlag]' $pbxon /> &nbsp; " .
					USER_LANG_EXTENSION . "
					<input size='10' type='text' name='userinfo[pbxExtension]' value='$extension' onchange=\"checkExtension(users,this.value);\" id='extension'/> &nbsp; " .
					USER_LANG_PRESENCE . "
					<input type='checkbox' name='userinfo[pbxPresenceFlag]' $presenceon />
				</td>
			</tr>
		";
	}


	if (in_array(ClearDirectory::SERVICE_TYPE_PROXY, $servicelist)) {
		$proxyon = isset($userinfo['proxyFlag']) && $userinfo['proxyFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_PROXY . "</td>
				<td colspan='2'><input type='checkbox' name='userinfo[proxyFlag]' $proxyon /></td>
			</tr>
		";
	}

	if (in_array(ClearDirectory::SERVICE_TYPE_OPENVPN, $servicelist)) {
		$pptpon = isset($userinfo['openvpnFlag']) && $userinfo['openvpnFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_OPENVPN . "</td>
				<td colspan='2'><input type='checkbox' name='userinfo[openvpnFlag]' $pptpon /></td>
			</tr>
		";
	}

	if (in_array(ClearDirectory::SERVICE_TYPE_PPTP, $servicelist)) {
		$pptpon = isset($userinfo['pptpFlag']) && $userinfo['pptpFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_PPTP . "</td>
				<td colspan='2'><input type='checkbox' name='userinfo[pptpFlag]' $pptpon /></td>
			</tr>
		";
	}

	if (in_array(ClearDirectory::SERVICE_TYPE_SAMBA, $servicelist)) {
		$sambaon = isset($userinfo['sambaFlag']) && $userinfo['sambaFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_SAMBA . "</td>
				<td colspan='2'><input type='checkbox' name='userinfo[sambaFlag]' $sambaon /></td>
			</tr>
		";
	}

	if (in_array(ClearDirectory::SERVICE_TYPE_FTP, $servicelist)) {
		$ftpon = isset($userinfo['ftpFlag']) && $userinfo['ftpFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_FTP . "</td>
				<td colspan='2'><input type='checkbox' name='userinfo[ftpFlag]' $ftpon /></td>
			</tr>
		";
	}

	if (in_array(ClearDirectory::SERVICE_TYPE_WEB, $servicelist)) {
		$webon = isset($userinfo['webFlag']) && $userinfo['webFlag'] ? "checked" : "";

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_WEB . "</td>
				<td colspan='2'><input type='checkbox' name='userinfo[webFlag]' $webon /></td>
			</tr>
		";
	}

	if ($shellaccess) {
		try {
			$shell = new Shell();
			$shells = $shell->GetList();
		} catch (Exception $e) {
			WebDialogWarning($e->getMessage());
		}

		$addoptions .= "
			<tr>
				<td class='mytablesubheader' nowrap>" . USER_LANG_SHELL . "</td>
				<td colspan='2'>" . WebDropDownArray("userinfo[loginShell]", $userinfo['loginShell'], $shells) . "</td>
			</tr>
		";
	}

	$password = isset($password) ? $password : "";
	$verify = isset($verify) ? $verify : "";
	$title = isset($userinfo['title']) ? $userinfo['title'] : "";
	$org = isset($userinfo['organization']) ? $userinfo['organization'] : "";
	$orgunit = isset($userinfo['unit']) ? $userinfo['unit'] : "";
	$room = isset($userinfo['roomNumber']) ? $userinfo['roomNumber'] : "";
	$street = isset($userinfo['street']) ? $userinfo['street'] : "";
	$postalcode = isset($userinfo['postalCode']) ? $userinfo['postalCode'] : "";
	$city = isset($userinfo['city']) ? $userinfo['city'] : "";
	$region = isset($userinfo['region']) ? $userinfo['region'] : "";
	$country = isset($userinfo['country']) ? $userinfo['country'] : "";
	$tel = isset($userinfo['telephone']) ? $userinfo['telephone'] : "";
	$fax = isset($userinfo['fax']) ? $userinfo['fax'] : "";
	$aliases = isset($userinfo['aliases']) ? $userinfo['aliases'] : "";
	$firstname = isset($userinfo['firstName']) ? htmlspecialchars($userinfo['firstName'], ENT_QUOTES) : "";
	$lastname = isset($userinfo['lastName']) ? htmlspecialchars($userinfo['lastName'], ENT_QUOTES) : "";

	// Group options
	//--------------	

	$groups = array();
	$grouphtml = "";

	try {
		$nogroup = new Group("notused"); // Locale only
		$groups = $groupmanager->GetGroupList(Group::FILTER_NORMAL);
	} catch (Exception $e) {
		WebDialogWarning($e->getMessage());
	}

	if (count($groups) > 0) {
		$grouphtml = "
			<tr>
				<td colspan='3' class='mytableheader'>" . GROUP_LANG_USER_DEFINED_GROUPS . "</td>
			</tr>
		";

		foreach ($groups as $group) {
			$members = $group['members'];
			$checked = (isset($username) && in_array($username, $members)) ? "checked" : "";

			$grouphtml .= "
				<tr>
					<td class='mytablesubheader' nowrap>" . $group['group'] . "</td>
					<td colspan='2'>
						<input type='checkbox' name='groupinfo[" . $group['group'] . "]' $checked />" .
						$group['description'] . "
					</td>
				</tr>
			";
		}
	}

	// Show add user table
	//--------------------
	//PBXME - added new params to WebFormOpen
	WebFormOpen("users.php", "post", "users", "onSubmit='return CheckCCExtension(users);'");
	WebTableOpen($tabletitle);
	echo "
		<tr>
			<td colspan='3' class='mytableheader'>" . USER_LANG_USER_DETAILS . "</td>
		</tr>
		<tr>
			<td width='250' class='mytablesubheader' nowrap>" . LOCALE_LANG_USERNAME . "</td>
			<td colspan='2'>
				<input type='hidden' name='userinfo[webconfigFlag]' value='on' />
				$userinfo_html
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . USER_LANG_FIRST_NAME . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[firstName]' value='$firstname' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . USER_LANG_LAST_NAME . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[lastName]' value='$lastname' /></td>
		</tr>
		<tr>
			<td colspan='3' class='mytableheader'>" . LOCALE_LANG_PASSWORD . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td colspan='2'><input size='30' type='password' name='password' value='$password' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_VERIFY . "</td>
			<td colspan='2'><input size='30' type='password' name='verify' value='$verify' /></td>
		</tr>

		<tr>
			<td colspan='3' class='mytableheader'>" . ORGANIZATION_LANG_ADDRESS . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_STREET . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[street]' value='$street' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_ROOM_NUMBER . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[roomNumber]' value='$room' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_CITY . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[city]' value='$city' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_REGION . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[region]' value='$region' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_COUNTRY . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[country]' value='$country' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_POSTAL_CODE . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[postalCode]' value='$postalcode' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_ORGANIZATION . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[organization]' value='$org' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_ORGANIZATION_UNIT . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[unit]' value='$orgunit' /></td>
		</tr>
		<tr>
			<td colspan='3' class='mytableheader'>" . USER_LANG_PHONE_NUMBERS . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_PHONE . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[telephone]' value='$tel' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . ORGANIZATION_LANG_FAX . "</td>
			<td colspan='2'><input size='30' type='text' name='userinfo[fax]' value='$fax' /></td>
		</tr>
		";

		// FIXME: make generic, e.g. allow zarafa too
		if (in_array(ClearDirectory::SERVICE_TYPE_GOOGLE_APPS, $servicelist) ||
			in_array(ClearDirectory::SERVICE_TYPE_EMAIL, $servicelist) ) {

			echo "
				<tr>
					<td colspan='3' class='mytableheader'>" . USER_LANG_MAIL_SERVICES . "</td>
				</tr>
				<tr>
					<td class='mytablesubheader' nowrap>" . WEB_LANG_MAILBOX_QUOTA . "</td>
					<td colspan='2'>" . WebDropDownHash("userinfo[mailquota]", $quota, $quota_options) . "</td>
				</tr>
			";

			if ($action == "add") {
				echo "
					<tr>
						<td class='mytablesubheader' nowrap>" . USER_LANG_MAIL_ALIAS . "</td>
						<td colspan='2'><input size='30' type='text' name='userinfo[aliases]' value='$aliases' /></td>
					</tr>
				";
			} else {
				echo "
				<tr>
					<td class='mytablesubheader' nowrap>" . USER_LANG_MAIL_ALIASES . "</td>
					<td width='120'>
						<input style='width: 120px' type='text' name='alias' id='alias' value='' />
						<input type='hidden' name='aliasusername' id='aliasusername' value='$username' />
					</td>
					<td>" . WebButton('AddAliasButton', LOCALE_LANG_ADD, WEBCONFIG_ICON_PLUS, array('type' => 'button', 'onclick' => "AddAlias('$username')")) . "</td>

				</tr>
				<tr>
					<td class='mytablesubheader' nowrap><div id='whirly'>&nbsp; </div></td>
					<td colspan='2'><div id='aliaslist'></div></td>
				</tr>
				";
			}
		}

		if ($addoptions) {
			echo "
				<tr>
					<td colspan='3' class='mytableheader'>" . USER_LANG_SERVICES . "</td>
				</tr>
				$addoptions
			";
		}

		echo "
		$grouphtml
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td colspan='2'>$action_html</td> 
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDelete()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDelete($username)
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM, "400");
	echo "
		<tr>
			<td align='center'>
			<br />
			<p>" . WEBCONFIG_ICON_WARNING . " " . WEB_LANG_CONFIRM_DELETE . "<br>
			<b> $username </b></p>
			<br />". WebButtonDelete("Delete[$username]") . " " . WebButtonCancel("Cancel") . "
			</td>
		</tr>
	";
	WebTableClose("400");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// ConvertFlags()
//
///////////////////////////////////////////////////////////////////////////////

function ConvertFlags(&$userinfo)
{
	// Convert empty strings to null
	foreach ($userinfo as $key => $value) {
		if (empty($value))
			$userinfo[$key] = NULL;
	}

	// Convert "on/off" checkboxes to booleans
	$attribute_list = array(
		'ftpFlag',
		'mailFlag',
		'googleAppsFlag',
		'openvpnFlag',
		'pptpFlag',
		'sambaFlag',
		'webFlag',
		'webconfigFlag',
		'proxyFlag',
		'pbxFlag',
		'pbxPresenceFlag'
	);

	foreach ($attribute_list as $attribute) {
		if (isset($userinfo[$attribute]))
			$userinfo[$attribute] = true;
		else
			$userinfo[$attribute] = false;
	}
}

// vim: syntax=php ts=4
?>
