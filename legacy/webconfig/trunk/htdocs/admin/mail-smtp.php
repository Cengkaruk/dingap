<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2010 Point Clark Networks.
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
require_once("../../api/ClearDirectory.class.php");
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/Postfix.class.php");
require_once("../../api/UserManager.class.php");
require_once("../../api/MailFilter.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

$postfix = new Postfix();

try {
	$mode = $postfix->GetMode();

	if ($mode == Postfix::CONSTANT_MODE_SERVER) {
		WebHeader(WEB_LANG_PAGE_TITLE);
		WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-postfix.png", WEB_LANG_PAGE_INTRO);
	} else {
		WebHeader(WEB_LANG_PAGE_TITLE_FORWARD);
		WebDialogIntro(WEB_LANG_PAGE_TITLE_FORWARD, "/images/icon-postfix.png", WEB_LANG_PAGE_INTRO_FORWARD);
	}
} catch (Exception $e) {
	// If we don't know the mode, we can't really continue
	WebHeader(WEB_LANG_PAGE_TITLE);
	WebDialogWarning($e->GetMessage());
	WebFooter();
	exit;
}

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new FirewallIncoming();

if (file_exists("../../api/Amavis.class.php")) {
	require_once("../../api/Amavis.class.php");
	$amavis = new Amavis();
}

$virtual_domain = isset($_POST['virtual_domain']) ? $_POST['virtual_domain'] : ""; 
$destination_domain = isset($_POST['destination_domain']) ? $_POST['destination_domain'] : ""; 
$forward_domain = isset($_POST['forward_domain']) ? $_POST['forward_domain'] : ""; 
$forward_server = isset($_POST['forward_server']) ? $_POST['forward_server'] : ""; 
$forward_port = isset($_POST['forward_port']) ? $_POST['forward_port'] : ""; 
$trusted_network = isset($_POST['trusted_network']) ? $_POST['trusted_network'] : ""; 
$relay_host = isset($_POST['relay_host']) ? $_POST['relay_host'] : ""; 

try {
	if (isset($_POST['EnableBoot'])) {
		$postfix->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$postfix->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$postfix->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$postfix->SetRunningState(false);
	} else if (isset($_POST['UpdateConfig'])) {
		$postfix->SetHostname($_POST['hostname']);

		if (isset($_POST['primarycatchall'])) {
			$mailfilter = new MailFilter();
			$mailfilter->SetCatchAllMailbox($_POST['primarycatchall']);

			// You have do disable local_recipient_maps for catchall.
			// This will significantly increase the load on a mail server.

			if (empty($_POST['primarycatchall']))
				$postfix->SetLocalRecipientMaps(Postfix::DEFAULT_LOCAL_RECIPIENT_MAPS);
			else
				$postfix->SetLocalRecipientMaps("");
		}

		if (isset($_POST['messagesize']))
			$postfix->SetMaxMessageSize($_POST['messagesize']);

		if (isset($_POST['smtpauth'])) {
			if ($_POST['smtpauth'] == "on")
				$postfix->SetSmtpAuthenticationState(true);
			else
				$postfix->SetSmtpAuthenticationState(false);
		}

		$postfix->Reset();
	} else if (isset($_POST['AddRelayHost'])) {
		$postfix->AddRelayHost($_POST['relay_host']);
		$postfix->Reset();
	} else if (isset($_POST['DeleteRelayHost'])) {
		$postfix->DeleteRelayHost(key($_POST['DeleteRelayHost']));
		$postfix->Reset();
	} else if (isset($_POST['AddTrustedNetwork'])) {
		$postfix->AddTrustedNetwork($_POST['trusted_network']);
		$postfix->Reset();
	} else if (isset($_POST['DeleteTrustedNetwork'])) {
		$postfix->DeleteTrustedNetwork(key($_POST['DeleteTrustedNetwork']));
		$postfix->Reset();
	} else if (isset($_POST['AddDestination'])) {
		$postfix->AddDestination($_POST['destination_domain']);
		$postfix->Reset();
		if (isset($amavis))
			$amavis->Reset();
	} else if (isset($_POST['DeleteDestination'])) {
		$postfix->DeleteDestination(key($_POST['DeleteDestination']));
		$postfix->Reset();
		if (isset($amavis))
			$amavis->Reset();
	} else if (isset($_POST['AddForwarder'])) {
		$postfix->AddForwarder($_POST['forward_domain'], $_POST['forward_server'], $_POST['forward_port']);
		$postfix->Reset();
		if (isset($amavis))
			$amavis->Reset();
	} else if (isset($_POST['DeleteForwarder'])) {
		$postfix->DeleteForwarder(key($_POST['DeleteForwarder']));
		$postfix->Reset();
		if (isset($amavis))
			$amavis->Reset();
	} else if (isset($_POST['DeleteVirtual'])) {
		$postfix->DeleteVirtualDomain(key($_POST['DeleteVirtual']));
		$postfix->Reset();
		if (isset($amavis))
			$amavis->Reset();
	} else if (isset($_POST['UpdateVirtual'])) {
		$postfix->SetUserAccessVirtual($_POST['virtualdomain'], $_POST['usernames']);
		$postfix->SetCatchallVirtual($_POST['virtualdomain'], $_POST['catchall']);
		$postfix->Reset();
	} else if (isset($_POST['AddVirtual'])) {
		$postfix->AddVirtualDomain($_POST['virtual_domain']);
		$postfix->Reset();
		if (isset($amavis))
			$amavis->Reset();
		$_POST["EditVirtual[$virtual_domain]"] = true;
	} else if (isset($_POST['OpenFirewall'])) {
		$firewall->AddAllowPort("smtpmail", "TCP", "25");
		$firewall->Restart();
	}

	$errors = $postfix->GetValidationErrors(true);

	if (empty($errors)) {
		$virtual_domain = "";
		$destination_domain = "";
		$forward_domain = "";
		$forward_server = "";
		$forward_port = "";
		$trusted_network = "";
		$relay_host = "";
	} else {
		WebDialogWarning($errors);
	}

} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

SanityCheck();

WebDialogDaemon("postfix");
DisplayConfig($trusted_network, $relay_host);

if ($mode == Postfix::CONSTANT_MODE_SERVER) {
	DisplayDestinationDomains($destination_domain);
	DisplayForwarding($forward_domain, $forward_server, $forward_port);
} else {
	DisplayForwarding($forward_domain, $forward_server, $forward_port);
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

function DisplayConfig($trusted_network, $relay_host)
{
	global $postfix;
	global $mode;

	$mailfilter = new MailFilter();
	$usermanager = new UserManager();

	try {
		$domain = $postfix->GetDomain();
		$hostname = $postfix->GetHostname();
		$relayhosts = $postfix->GetRelayHosts();
		$trusted = $postfix->GetTrustedNetworks();
		$catchall = $mailfilter->GetCatchAllMailbox();
		$userlist = $usermanager->GetAllUsers(ClearDirectory::SERVICE_TYPE_EMAIL);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Trusted Networks
	//-----------------

	if ($trusted) {
		$trustednetwork = "";
		foreach ($trusted as $network) {
			if ($network == "127.0.0.0/8") // Don't show loopback to user
				continue;
			$trustednetwork .= "
			  <tr>
				<td align='right'>$network</td>
				<td>" . WebButtonDelete("DeleteTrustedNetwork[$network]") . "</td>
			  </tr>
			";
		}
	}

	// Relay hosts
	//------------

	$relayhostslist = "";

	if (! empty($relay_host)) {
		$relayhostslist = "
			<tr>
			  <td align='right'><input class='textbox' size='20' type='text' name='relay_host' value='$relay_host' /></td>
			  <td>" . WebButtonAdd("AddRelayHost") . "</td>
			</tr>
		";
	} else if ($relayhosts) {
		$relayhostslist = "";
		foreach ($relayhosts as $host) {
			$relayhostslist .= "
			  <tr>
				<td align='right'>$host</td>
				<td>" . WebButtonDelete("DeleteRelayHost[$host]") . "</td>
			  </tr>
			";
		}
	} else {
		$relayhostslist = "
			<tr>
			  <td align='right'><input class='textbox' size='20' type='text' name='relay_host' value='$relay_host' /></td>
			  <td>" . WebButtonAdd("AddRelayHost") . "</td>
			</tr>
		";
	}

	// Server only options
	//--------------------

	if ($mode == Postfix::CONSTANT_MODE_SERVER) {

		try {
			$catchalldef = $postfix->GetCatchall();
			$messagesize = $postfix->GetMaxMessageSize();
			$smtpauth  = $postfix->GetSmtpAuthenticationState();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		// Message size
		//-------------

		$messagedd = array();
		$messagedd["1024000"] = "1 " . LOCALE_LANG_MEGABYTES;
		$messagedd["2048000"] = "2 " . LOCALE_LANG_MEGABYTES;
		$messagedd["5120000"] = "5 " . LOCALE_LANG_MEGABYTES;
		$messagedd["10240000"] = "10 " . LOCALE_LANG_MEGABYTES;
		$messagedd["20480000"] = "20 " . LOCALE_LANG_MEGABYTES;
		$messagedd["30720000"] = "30 " . LOCALE_LANG_MEGABYTES;
		$messagedd["40960000"] = "40 " . LOCALE_LANG_MEGABYTES;
		$messagedd["51200000"] = "50 " . LOCALE_LANG_MEGABYTES;
		$messagedd["102400000"] = "100 " . LOCALE_LANG_MEGABYTES;

		$smtpauthdd["on"] = LOCALE_LANG_ON;
		$smtpauthdd["off"] = LOCALE_LANG_OFF;
		if ($smtpauth)
			$smtpauth = "on";
		else
			$smtpauth = "off";

		$messagesize_dropdown = WebDropDownHash("messagesize", $messagesize, $messagedd);
		$smtpauth_dropdown = WebDropDownHash("smtpauth", $smtpauth, $smtpauthdd);

		// Catch-all user
		//---------------

		$user_dropdown = "";
		$catchall_exists = false;

		foreach ($userlist as $user) {
			if ($catchall == $user) {
				$catchall_exists = true;
				$selected = "selected";
			} else {
				$selected = "";
			}

			$user_dropdown .= "<option value='$user' $selected>$user</option>\n";
		}

		$selected = ($catchall_exists) ? "" : "selected";
		$user_dropdown .= "<option value='' $selected>" . WEB_LANG_RETURN_TO_SENDER . "</option>";


		// HTML
		//-----

		$serveronly_rows = "
		  <tr>
			<td class='mytablesubheader' nowrap>" . POSTFIX_LANG_MESSAGE_SIZE . "</td>
			<td>$messagesize_dropdown</td>
		  </tr>
		  <tr>
			<td class='mytablesubheader' nowrap>" . POSTFIX_LANG_CATCHALL . "</td>
			<td><select name='primarycatchall'>$user_dropdown</select></td>
		  </tr>
		";
	}

	// HTML output
	//------------

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE);
	echo "
	  <tr>
		<td colspan='2' class='mytableheader'>" . WEB_LANG_GENERAL_SETTINGS . "</td>
	  </tr>
	  <tr>
		<td width='250' class='mytablesubheader' nowrap>" . POSTFIX_LANG_PRIMARY_DOMAIN . "</td>
		<td>$domain &nbsp; &nbsp; " .  WebUrlJump("/admin/ldap.php", LOCALE_LANG_CONFIGURE) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . POSTFIX_LANG_HOSTNAME . "</td>
		<td><input type='text' name='hostname' size='20' value='$hostname'></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . POSTFIX_LANG_SMTP_AUTHENTICATION . "</td>
		<td>$smtpauth_dropdown</td>
	  </tr>
	  $serveronly_rows
	  <tr>
		<td class='mytablesubheader' nowrap>&#160;</td>
		<td>" . WebButtonUpdate("UpdateConfig") . "</td>
	  </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . POSTFIX_LANG_TRUSTED_NETWORKS . "</td>
	  </tr>
	  $trustednetwork
	  <tr>
		<td align='right' nowrap>
			<input class='textbox' size='20' type='text' name='trusted_network' value='$trusted_network' />
		</td>
		<td>" . WebButtonAdd("AddTrustedNetwork") . "</td>
	  </tr>
	  <tr>
		<td colspan='2' class='mytableheader'>" . POSTFIX_LANG_RELAY_HOSTS . "</td>
	  </tr>
	  $relayhostslist
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayForwarding()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayForwarding($forward_domain, $forward_server, $forward_port)
{
	global $postfix;

	$network = new Network(); // Locale

	try {
		$domain = $postfix->GetDomain();
		$forwarders = $postfix->GetForwarders();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Forward domains
	//----------------

	$forwarderslist = "";

	if ($forwarders) {
		foreach ($forwarders as $forwardinfo) {
			$forwarderslist .= "
			  <tr>
				<td align='right'>$forwardinfo[domain]</td>
				<td>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
				<td>$forwardinfo[server]</td>
				<td>$forwardinfo[port]</td>
				<td>" . WebButtonDelete("DeleteForwarder[$forwardinfo[domain]]") . "</td>
			  </tr>
			";
		}
	}

	$forward_port = (empty($forward_port)) ? "25" : $forward_port;

	// HTML output
	//------------

	WebFormOpen();
	WebTableOpen(WEB_LANG_FORWARD_TITLE);
	WebTableHeader(POSTFIX_LANG_DOMAIN . "||" . POSTFIX_LANG_FORWARD_SERVER . "|" . NETWORK_LANG_PORT  . "|");
	echo "
		$forwarderslist
		<tr>
			<td nowrap><input class='textbox' size='20' type='text' name='forward_domain' value='$forward_domain' /></td>
			<td nowrap>" . WEBCONFIG_ICON_ARROWRIGHT . "</td>
			<td><input class='textbox' size='20' type='text' name='forward_server' value='$forward_server' /></td>
			<td><input class='textbox' size='5' type='text' name='forward_port' value='$forward_port' style='width:50px;' /></td>
			<td>" . WebButtonAdd("AddForwarder") . "</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayDestinationDomains()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDestinationDomains($destination_domain)
{
	global $postfix;

	// Destinations
	//-------------

	try {
		$destinations = $postfix->GetDestinations();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if ($destinations) {
		$destinationslist = "";
		foreach ($destinations as $host) {

			if (preg_match("/\\$/", $host))
				continue;

			if (preg_match("/^localhost$/", $host))
				continue;

			$destinationslist .= "
			  <tr>
				<td align='right'>$host</td>
				<td>" . WebButtonDelete("DeleteDestination[$host]") . "</td>
			  </tr>
			";
		}
	}

	// HTML output
	//------------

	WebFormOpen();
	WebTableOpen(POSTFIX_LANG_DESTINATIONS);
	echo "
	  $destinationslist
	  <tr>
		<td width='250' align='right'><input type='text' name='destination_domain' size='20' value='$destination_domain' /></td>
		<td>" . WebButtonAdd("AddDestination") . "</td>
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
	global $postfix;
	global $firewall;

	try {
		$relayhosts = $postfix->GetRelayHosts();
		$allowedports = $firewall->GetAllowPorts();
		$firewallmode = $firewall->GetMode();
		$ispostfixrunning = $postfix->GetRunningState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// See bug #315 - Postfix changed the behavior of relayhost
	//---------------------------------------------------------

	if ($relayhosts && (count($relayhosts) > 1))
		WebDialogWarning(WEB_LANG_ERRMSG_ONE_RELAYHOST);

	// Check for firewall port
	//------------------------

	if ($ispostfixrunning) {
		$portok = false;

		if ($firewallmode == Firewall::CONSTANT_TRUSTEDSTANDALONE) {
			$portok = true;
		} else if ($allowedports) {
			foreach ($allowedports as $portinfo) {
				if ( ($portinfo["port"] == 25) && preg_match("/tcp/i", $portinfo["protocol"]) )
					$portok = true;
			}
		}

		if (!$portok) {
			$warning = WEB_LANG_FIREWALL_ENABLE;
			$warning .= "<br />" . WebButtonToggle("OpenFirewall", LOCALE_LANG_ENABLE);
			WebFormOpen();
			WebDialogWarning($warning);
			WebFormClose();
		}
	}
}

?>
