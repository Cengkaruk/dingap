<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 Point Clark Networks.
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
require_once("../../api/FreeRadius.class.php");
require_once("../../api/GroupManager.class.php");
require_once("../../api/Network.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-radius.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$radius = new FreeRadius();
$network = new Network(); // Translations only

$secret = isset($_POST['secret']) ? $_POST['secret'] : '';
$address = isset($_POST['address']) ? $_POST['address'] : '';
$shortname = isset($_POST['shortname']) ? $_POST['shortname'] : '';

try {
    if (isset($_POST['EnableBoot'])) {
        $radius->SetBootState(true);
    } else if (isset($_POST['DisableBoot'])) {
        $radius->SetBootState(false);
    } else if (isset($_POST['StartDaemon'])) {
        $radius->SetRunningState(true);
    } else if (isset($_POST['StopDaemon'])) {
        $radius->SetRunningState(false);
    } else if (isset($_POST['UpdateConfiguration'])) {
		$group = isset($_POST['group']) ? $_POST['group'] : '';
		$radius->UpdateGroup($group);
		$radius->Reset();
    } else if (isset($_POST['AddClient'])) {
		$radius->AddClient($address, $secret, $shortname);
		$radius->Reset();
		$address = '';
		$secret = '';
		$shortname = '';
    } else if (isset($_POST['UpdateClient'])) {
		$radius->UpdateClient($address, $secret, $shortname);
		$radius->Reset();
		$address = '';
		$secret = '';
		$shortname = '';
    } else if (isset($_POST['Confirm'])) {
		$radius->DeleteClient(key($_POST['Confirm']));
		$radius->Reset();
	}
} catch (Exception $e) {
    WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['EditClient'])) {
	DisplayEditClient(key($_POST['EditClient']), '', '');
} else if (isset($_POST['DeleteClient'])) {
	DisplayConfirmation(key($_POST['DeleteClient']));
} else {
	WebDialogDaemon("radiusd");
	DisplayConfiguration($address, $secret, $shortname);
}
Webfooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfiguration()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfiguration($address, $secret, $shortname)
{
	global $radius;

	try {
		$group = $radius->GetGroup();
		$clients = $radius->GetClients();

		$groupmanager = new GroupManager();
		$grouplist = $groupmanager->GetAllGroups();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$clients_html = '';

	foreach ($clients as $client => $detail) {
		$clients_html .= "
			<tr>
				<td width='170'>" . $detail['shortname'] . "</td>
				<td>" . $client . "</td>
				<td>" . $detail['secret'] . "</td>
				<td>" . 
					WebButtonEdit("EditClient[" .  $client . "]") . " " . 
					WebButtonDelete("DeleteClient[" .  $client . "]") . "
				</td>
			</tr>
		";
	}

	// TODO: what's the expected sorting for RADIUS users?

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE);
	echo "
		<tr>
			<td width='170' class='mytablesubheader'>" . FREERADIUS_LANG_GROUP . "</td>
			<td>" . WebDropDownArray('group', $group, $grouplist) . "</td>
		</tr>
		<tr>
			<td width='170' class='mytablesubheader'>&nbsp; </td>
			<td>" . WebButtonUpdate('UpdateConfiguration') . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();


	WebFormOpen();
	WebTableOpen(FREERADIUS_LANG_CLIENTS);
	WebTableHeader(FREERADIUS_LANG_NICKNAME . '|' . NETWORK_LANG_IP . '|' . LOCALE_LANG_PASSWORD . '|' . LOCALE_LANG_ACTION);
	echo "
		$clients_html
		<tr>
			<td><input type='text' name='shortname' value='$shortname'></td>
			<td><input type='text' name='address' value='$address'></td>
			<td><input type='text' name='secret' value='$secret'></td>
			<td>" . WebButtonAdd("AddClient") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditClient()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditClient($address, $shortname, $secret)
{
	global $radius;

	try {
		$details = $radius->GetClientInfo($address);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$shortname = empty($shortname) ? $details['shortname'] : $shortname;
	$secret = empty($secret) ? $details['secret'] : $secret;

	WebFormOpen();
	WebTableOpen(FREERADIUS_LANG_CLIENT);
	echo "
		<tr>
			<td class='mytablesubheader'>" . NETWORK_LANG_IP . "</td>
			<td>$address<input type='hidden' name='address' value='$address'></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . FREERADIUS_LANG_NICKNAME . "</td>
			<td><input type='text' name='shortname' value='" . $details['shortname'] . "'></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input type='text' name='secret' value='" . $details['secret'] . "'></td>
		</tr>
		<tr>
			<td class='mytablesubheader'>" . LOCALE_LANG_PASSWORD . "</td>
			<td>" . WebButtonUpdate("UpdateClient") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmation()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmation($address)
{
	// TODO: translate
	WebFormOpen();
	WebDialogWarning("Are you sure you want to delete the following client: $address?" .
		WebButtonConfirm("Confirm[$address]") . " " .  WebButtonCancel("Cancel"));
	WebFormClose();
}

// vi: syntax=php ts=4
?>
