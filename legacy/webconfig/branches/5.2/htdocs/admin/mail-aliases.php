<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002 Point Clark Networks.
// Created by: Michel Scherhage [techlab@dhd4all.com]
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
require_once("../../api/Aliases.class.php");
require_once("../../api/Postfix.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-postfix.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$domain = isset($_POST['domain']) ? $_POST['domain'] : '';
$alias = isset($_POST['alias']) ? $_POST['alias'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$redirects = isset($_POST['redirects']) ? $_POST['redirects'] : array();

$postfix = new Postfix();

// TODO: last minute fix
try {
	$primary = $postfix->GetDomain();
	if ($primary == $domain)
		$domain = "";
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

$aliases = new Aliases($domain);

if (isset($_POST['Add'])) {
	try {
		$targets = (empty($redirects)) ? array() : $redirects;
		if (! empty($email))
			$targets[] = $email;

		$aliases->AddAlias($alias, $targets);
		$alias = '';
		$email = '';
		$redirects = array();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['Update'])) {

	try {
		$redirects = isset($_POST['current_redirects']) ? array_keys($_POST['current_redirects']) : array();
		$aliases->SetAlias($alias, $redirects);
		$displayedit = $alias;
		$email = '';
		$redirects = array();
	} catch (Exception $e) {
		$displayedit = $alias;
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['AddRedirect'])) {
	try {
		$aliases->AddRedirectUsers($alias, $redirects);
		$displayedit = $alias;
		$redirects = array();
	} catch (Exception $e) {
		$displayedit = $alias;
		WebDialogWarning($e->GetMessage());
	}

	$email = '';

} else if (isset($_POST['AddEmail'])) {
	try {
		$aliases->AddRedirectEmails($alias, array($email));
		$displayedit = $alias;
		$email = '';
	} catch (Exception $e) {
		$displayedit = $alias;
		WebDialogWarning($e->GetMessage());
	}

	$redirects = array();

} else if (isset($_POST['ConfirmDelete'])) {

	try {
		$aliases->DeleteAlias($alias);
		$alias = '';
		$email = '';
		$redirects = array();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

} else if (isset($_POST['CancelEdit'])) {
	$alias = '';
	$email = '';
	$redirects = array();
}

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebCheckUserDatabase();

if (isset($displayedit))
	DisplayEdit($domain, $displayedit, $email, $redirects);
else if (isset($_POST['EditAlias']))
	DisplayEdit($domain, key($_POST['EditAlias']), null, null);
else if (isset($_POST['Delete']))
	DisplayDelete($domain, key($_POST['Delete']));
else
	DisplayAliases($domain, $alias, $email, $redirects);

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAliases()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAliases($domain, $alias, $email, $redirects)
{
	$postfix = new Postfix();
	$network = new Network();

	try {
		$primary = $postfix->GetDomain();
		$virtuals = $postfix->GetVirtualDomains();

		// Get alias information for given domain (default to primary domain)
		if (empty($domain))
			$domain = $primary;

		$aliases = new Aliases($domain);	
		$user_list = $aliases->GetUsers();
		$alias_list = $aliases->GetAliases();
		$protected_list = $aliases->GetProtectedAliases();
	} catch (EngineException $e) {
		WebDialogWarning($e->GetMessage());
	}

	$all_domains = array_merge(array($primary), $virtuals);

	// Domain list dropdown (if more than one domain)
	//-----------------------------------------------

	$domain_options = '';

	if (count($all_domains) > 1) {
		foreach ($all_domains as $available) {
			$selected = ($domain == $available) ? 'selected' : '';
			$domain_options .= "<option value='$available' $selected>$available</option>";
		}
	}

	// Get aliases information for given domain
	//-----------------------------------------

	$alias_data = '';
	$rowindex = 0;

	foreach ($alias_list as $alias_item) {
		try {
			$redirect = implode(', ', $aliases->GetRedirect($alias_item));
		} catch (EngineException $e) {
			WebDialogWarning($e->GetMessage());
		}

		// Limit display of redirect to 50 characters
		if (strlen($redirect) > 50)
			$redirect = substr($redirect, 0, 50) . "...";

		// Skip flexshare aliases
		if (eregi('flex-', $alias_item))
			continue;

		// Skip spam training aliases
		if (preg_match('/train\./', $alias_item))
			continue;

		// Prevent deletion of req'd aliases or ones created by Flexshare
		$delete_button = (in_array($alias_item, $protected_list)) ? '' : WebButtonDelete("Delete[$alias_item]");

		$rowclass = 'rowenabled';
		$rowclass .= ($rowindex % 2) ? 'alt' : '';
		$rowindex++;
		$alias_data .= "
			<tr class='$rowclass'>
				<td nowrap>$alias_item</td>
				<td nowrap>" . $redirect . "</td>
				<td nowrap>" . WebButtonEdit("EditAlias[$alias_item]") . " " . $delete_button . "</td>
			</tr>
		";
	}

	if (! $alias_data)
		$alias_data = "<tr><td colspan='4' align='center'>" . WEB_LANG_TEXT_NO_ALIASES . "</td></tr>";

	// Create user multiselect list
	//-----------------------------

	$redirect_options = '';

	foreach ($user_list as $user) {
		$selected = (in_array($user, $redirects)) ? 'selected' : '';
		$redirect_options .= "<option value='$user' $selected>$user</option>\n";
	}

	// HTML
	//-----

	if (count($all_domains) > 1) {
		WebFormOpen();
		WebTableOpen(WEB_LANG_TEXT_SELECT_DOMAIN, '100%');
		echo "
			<tr>
				<td align='center' nowrap>
					<select name='domain'>$domain_options</select>" . 
					WebButtonGo("ChangeDomain") . "
				</td>
				<td class='help'>" . WEB_LANG_TEXT_USERS . "</td>
			</tr>
		";
		WebTableClose("100%");
		WebFormClose();
	}

	WebFormOpen();
	echo "<input type='hidden' name='domain' value='$domain' />";
	WebTableOpen(WEB_LANG_PAGE_TITLE, '100%');
	WebTableHeader(WEB_LANG_TEXT_ALIAS . "|" . WEB_LANG_TEXT_USERS_EMAIL . "|");
	echo $alias_data;
	WebTableClose("100%");
	WebFormClose();

	WebFormOpen();
	echo "<input type='hidden' name='domain' value='$domain' />";
    WebTableOpen(LOCALE_LANG_ADD, "75%");
    echo "
		<tr>
			<td width='30%' class='mytablesubheader' nowrap>" . WEB_LANG_TEXT_ALIAS . "</td>
			<td><input type='text' name='alias' value='" . $alias . "' style='width:250px' /></td>
		</tr>
		<tr>
			<td width='30%' class='mytablesubheader' nowrap valign='top'>" . WEB_LANG_USERS . "</td>
			<td><select name='redirects[]' size='5' style='width:250px' multiple>$redirect_options</select></td>
		</tr>
		<tr>
			<td width='30%' class='mytablesubheader' nowrap>" . WEB_LANG_EXTERNAL_EMAIL . "</td>
			<td><input type='text' name='email' value='" . $email . "' style='width:250px' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160;</td>
			<td nowrap>" . WebButtonAdd("Add") . "</td>
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

function DisplayEdit($domain, $alias, $email, $redirects)
{
	global $aliases;

	try {
		$users = $aliases->GetUsers();

		if (empty($redirects))
			$redirects = $aliases->GetRedirect($alias);
	} catch (EngineException $e) {
		WebDialogWarning($e->GetMessage());
	}

	// Create available user list
	//---------------------------

	$redirect_options = '';

	foreach ($users as $user) {
		if (in_array($user, $redirects))
			continue;

		$selected = (in_array($user, $redirects)) ? "selected" : "";
		$redirect_options .= "<option value='$user' $selected>$user</option>\n";
	}

	// Create existing redirects list
	//-------------------------------

	$redirect_data = '';

	foreach ($redirects as $redirect)
		$redirect_data .= "<input type='checkbox' name='current_redirects[$redirect]' checked /> $redirect<br/>";

	if (empty($redirect_data))
		$redirect_data = "...";

	// HTML 
	//-----

	WebFormOpen();
	echo "<input type='hidden' name='domain' value='$domain' />";
	echo "<input type='hidden' name='alias' value='$alias' />";

    WebTableOpen(LOCALE_LANG_EDIT, "100%");
    echo "
		<tr>
			<td width='30%' class='mytablesubheader' nowrap>" . WEB_LANG_TEXT_ALIAS . "</td>
			<td>$alias</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap valign='top'>" . WEB_LANG_REDIRECT . "</td>
			<td>$redirect_data</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&#160;</td>
			<td nowrap>" . WebButtonUpdate("Update") . WebButtonBack("CancelEdit") . "</td>
		</tr>
    ";
    WebTableClose("100%");

	echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>
            <tr>
               <td valign='top' width='40%'>
	";
    WebTableOpen(WEB_LANG_USERS, "100%");
    echo "
		<tr>
			<td><select name='redirects[]' size='5' style='width:100%' multiple>$redirect_options</select></td>
		</tr>
		<tr>
			<td nowrap>" . WebButtonAdd("AddRedirect") . "</td>
		</tr>
    ";
    WebTableClose("100%");

	echo "</td><td width='5%'>&#160;</td><td valign='top' width='40%'>";
    WebTableOpen(WEB_LANG_EXTERNAL_EMAIL, "100%");
    echo "
		<tr>
			<td><input type='text' name='email' value='" . $email . "' style='width:100%' /></td>
		</tr>
		<tr>
			<td nowrap>" . WebButtonAdd("AddEmail") . "</td>
		</tr>
    ";
    WebTableClose("100%");

	echo "</td>
        </tr>
      </table>
	";
    WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDelete()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDelete($domain, $alias)
{
	WebFormOpen();
    WebTableOpen(LOCALE_LANG_CONFIRM, "450");
    echo "
      <tr>
        <td align='center'>
	      <input type='hidden' name='domain' value='$domain' />
	      <input type='hidden' name='alias' value='$alias' />
          <p>" . WEBCONFIG_ICON_WARNING . " " . LOCALE_LANG_CONFIRM_DELETE . " <b><i>" . $alias . "</i></b>?</p>" .
          WebButtonDelete("ConfirmDelete") . " " . WebButtonCancel("Cancel") . "
        </td>
      </tr>
    ";
    WebTableClose("450");
    WebFormClose();
}

// vim: ts=4
?>
