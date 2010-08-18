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
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallRule.class.php");
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/FirewallWifi.class.php");
require_once("../../api/Hostname.class.php");
require_once("../../api/HostnameChecker.class.php");
require_once("../../api/Iface.class.php");
require_once("../../api/IfaceManager.class.php");
require_once("../../api/Network.class.php");
require_once("../../api/Resolver.class.php");
require_once("../../api/Routes.class.php");
require_once("../../api/Syswatch.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// DisplayVirtual()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayVirtual($eth, $ip, $netmask)
{
	$interfaces = new IfaceManager();

	if ($eth) {
		try {
			$iface = new Iface($eth);
			$info = $iface->GetInterfaceInfo();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}

		if (!$ip)
			$ip = isset($info['ifcfg']['ipaddr']) ? $info['ifcfg']['ipaddr'] : "";

		if (!$netmask)
			$netmask = isset($info['ifcfg']['netmask']) ? $info['ifcfg']['netmask'] : "";

		$eth = "<input type='text' name='eth' value='$eth' readonly class='readonly' />";
	} else {
		try {
			$iface = new Iface("not used"); // locale only
			$ethlist = $interfaces->GetInterfaceDetails();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
		}

		$realifaces = array();

		foreach ($ethlist as $eth => $info) {
			if ($info['configured'] && !$info['virtual'])
				$realifaces[] = $eth;
		}

		if (count($realifaces) == 1) {
			$eth = "<input type='text' name='eth' value='$realifaces[0]' readonly class='readonly' />";
		} else if (count($realifaces) > 1) {
			$eth = WebDropDownArray("eth", "", $realifaces);
		} else {
			return;
		}
	}

	WebFormOpen();
	WebTableOpen(IFACE_LANG_VIRTUAL, "350");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . IFACE_LANG_INTERFACE . "</td>
		<td>" . $eth . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . NETWORK_LANG_IP . "</td>
		<td><input type='text' name='ip' value='$ip' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . NETWORK_LANG_NETMASK . "</td>
		<td><input type='text' name='netmask' value='$netmask' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap> &#160; </td>
		<td>" . WebButtonUpdate("SaveVirtual") . "</td>
	  </tr>
	";
	WebTableClose("350");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayNetwork
//
///////////////////////////////////////////////////////////////////////////////

function DisplayNetwork($ethlist)
{
	$firewall = new Firewall();
	$hostname = new Hostname();
	$resolver = new Resolver();
	$interfaces = new IfaceManager();

	try {
		$syswatch = new Syswatch();
		$working_wif = $syswatch->GetWorkingExternalInterfaces();
	} catch (SyswatchUnknownStateException $e) {
		$working_wif = array();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	try {
		$mode = $firewall->GetMode();
		$nslist = $resolver->GetNameservers();
		$realhostname = $hostname->Get();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	// Firewall mode
	//
	// Do not show all the firewall modes (too confusing).
	// Instead, we create our own mini-list.
	//----------------------------------------------------

	$niccount = 0;
	$peerdns = true;

	foreach ($ethlist as $eth => $info) {
		// Skip interfaces used 'indirectly' (e.g. PPPoE, bonded interfaces)
		if (isset($info['master']))
			continue;

		// Skip 1-to-1 NAT interfaces
		if (isset($info['one-to-one-nat']) && $info['one-to-one-nat'])
			continue;

		// Skip non-configurable interfaces
		if (! $info['configurable'])
			continue;

		$niccount++;
	}

	if ($niccount >= 2) {
		$modeinfo["constant"] = Firewall::CONSTANT_GATEWAY;
		$modeinfo["description"] = FIREWALL_LANG_MODE_GATEWAY;
		$modelist[] = $modeinfo;
	}

	$modeinfo["constant"] = Firewall::CONSTANT_STANDALONE;
	$modeinfo["description"] = FIREWALL_LANG_MODE_STANDALONE;
	$modelist[] = $modeinfo;

	$modeinfo["constant"] = Firewall::CONSTANT_TRUSTEDSTANDALONE;
	$modeinfo["description"] = FIREWALL_LANG_MODE_TRUSTEDSTANDALONE;
	$modelist[] = $modeinfo;

	// DNS servers
	//------------

	// TODO: move this to InterfaceManager
	// Decide whether or not to show DNS servers read-only

	$peerdns = array();

	foreach ($ethlist as $eth => $info) {
		if (($info['ifcfg']['bootproto'] == Iface::BOOTPROTO_DHCP) || ($info['ifcfg']['bootproto'] == Iface::BOOTPROTO_PPPOE)) {
			if (isset($info['ifcfg']['peerdns'])) {
				if (preg_match("/yes/i", $info['ifcfg']['peerdns']))
					$peerdns[] = $eth;
			} else {
				$peerdns[] = $eth;
			}
		}
	}

	$ns_readonly = (count($peerdns) == 0) ? "" : "class='readonly' readonly ";
	$ns_html = "";

	for ($ns_index = 0; $ns_index < count($nslist); $ns_index++) {
		// FIXME finish ajax implementation
		//	$test_result = WEBCONFIG_ICON_LOADING;
		$test_result = "";
		$count_html = $ns_index + 1;
		$ns_ip = $nslist[$ns_index];

		$ns_html .= "
		  <tr>
			<td class='mytablesubheader' nowrap>" . RESOLVER_LANG_NAMESERVER . " #$count_html</td>
			<td><input type='text' name='ns[$ns_index]' size='20' value='$ns_ip' $ns_readonly/>
				<span id='ns_$ns_ip'>$test_result</span></td>
		  </tr>
		";
	}

	if (count($peerdns) == 0) {
		// Show at least two DNS servers
		while ($count_html < 2) {
			$count_html++;
			$ns_html .= "
			  <tr>
				<td class='mytablesubheader' nowrap>" . RESOLVER_LANG_NAMESERVER . " #$count_html</td>
				<td><input type='text' name='ns[$ns_index]' size='20' value='$ns[$ns_index]' /></td>
			  </tr>
			";
		}
	}

	// Drop-downs
	//-----------

	$mode_dropdown = "";

	foreach ($modelist as $modeinfo) {
		if ($modeinfo["constant"] == $mode)
			$mode_dropdown .= "<option selected value='$modeinfo[constant]'>$modeinfo[description]</option>";
		else
			$mode_dropdown .= "<option value='$modeinfo[constant]'>$modeinfo[description]</option>";
	}

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(NETWORK_LANG_NETWORK, "100%");

	echo "
		<tr>
			<td class='mytablesubheader' nowrap width='200'>" . FIREWALL_LANG_MODE . "</td>
			<td><select name='mode'>$mode_dropdown</select></td>
		</tr>
	";

	if (! WebIsSetup()) {
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>" . HOSTNAME_LANG_HOSTNAME . "</td>
				<td><input type='text' name='realhostname' size='30' value='$realhostname' /></td>
			</tr>
		";
	}

	echo $ns_html;

	if (! WebIsSetup()) {
		echo "
			<tr>
				<td class='mytablesubheader' nowrap>&#160; </td>
				<td>" . WebButtonUpdate("UpdateConfig") . "</td>
			</tr>
		";
	}

	WebTableClose("100%");
	if (! WebIsSetup())
		WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayInterfaces
//
///////////////////////////////////////////////////////////////////////////////

function DisplayInterfaces($ethlist)
{
	$ethsummary = "";

	foreach ($ethlist as $eth => $info) {
		// Skip interfaces used 'indirectly' (e.g. PPPoE, bonded interfaces)
		if (isset($info['master']))
			continue;

		// Skip 1-to-1 NAT interfaces
		if (isset($info['one-to-one-nat']) && $info['one-to-one-nat'])
			continue;

		// Skip non-configurable interfaces
		if (! $info['configurable'])
			continue;

		// Create summary
		//---------------

		if (empty($info['address']) && $info['configured']) {
			$ipicon = WEBCONFIG_ICON_LOADING;
		} else {
			$ipicon = '';
		}

		$ip = empty($info['address']) ? '' : $info['address'];
		$speed = (isset($info['speed']) && $info['speed'] > 0) ? $info['speed'] . " " . LOCALE_LANG_MEGABITS : "";
		$role = isset($info['role']) ? $info['role'] : "";
		$roletext = isset($info['roletext']) ? $info['roletext'] : "";
		$bootproto = isset($info['ifcfg']['bootprototext']) ? $info['ifcfg']['bootprototext'] : "";

		if (isset($info['link'])) {
			if ($info['link'] == -1)
				$link = "";
			else if ($info['link'] == 0)
				$link = LOCALE_LANG_NO;
			else
				$link = LOCALE_LANG_YES;
		} else {
			$link = "";
		}

		if ($info['configured']) {
			if ($info["type"] == Iface::TYPE_VIRTUAL) {
				$ethoption = WebButtonDelete("ConfirmDeleteVirtual[$eth]");
				$editoption = WebButtonEdit("EditVirtual[$eth]");
			} else if ($info["type"] == Iface::TYPE_PPPOE) {
				// Never ending PPPoE crap
				$ethoption = WebButtonDelete("ConfirmDeleteInterface[$eth]");
				$editoption = WebButtonEdit("EditPppoe[$eth]");
			} else {
				$on_network = (isset($info['address']) && ($info['address'] == $_SERVER['SERVER_ADDR'])) ? true : false;
				$ethoption = ($on_network) ? "" : WebButtonDelete("ConfirmDeleteInterface[$eth]");
				$editoption = WebButtonEdit("DisplayEdit[$eth]");
			}
		} else {
			$ethoption = "";
			$editoption = WebButtonEdit("DisplayEdit[$eth]");
		}

		// Hack to squeeze everything neatly onto 80 column console screen (use two rows)
		if (WEBCONFIG_CONSOLE) {
			$ethsummary .= "
			  <tr>
				<td>$eth&#160;</td>
				<td>$roletext <input type='hidden' name='role' value='$role' />&#160;</td>
				<td>$bootproto&#160;</td>
				<td>$ip&#160;</td>
				<td>$link&#160;</td>
				<td>$speed&#160;</td>
				<td>$editoption $ethoption</td>
			  </tr>
			";
		} else {
			$ethsummary .= "
			  <tr>
				<td nowrap>$eth</td>
				<td nowrap><span id='${eth}_role'>$roletext</span> <input type='hidden' name='role' value='$role' /></td>
				<td nowrap><span id='${eth}_bootproto'>$bootproto</span></td>
				<td nowrap>
					<span id='${eth}_ipicon'>$ipicon</span>
					<span id='${eth}_iplog'></span>
					<span id='${eth}_ip'>$ip</span>
				</td>
				<td nowrap><span id='${eth}_link'>$link</span></td>
				<td nowrap><span id='${eth}_speed'>$speed</span></td>
				<td nowrap>$editoption $ethoption</td>
			  </tr>
			";
		}
	}

	WebFormOpen();

	if ($ethsummary) {
		if (WEBCONFIG_CONSOLE) {
			WebTableOpen(IFACE_LANG_INTERFACE . " - <a href='network.php'>(" . strtolower(LOCALE_LANG_REFRESH) . ")</a>");
			WebTableHeader("|" . FIREWALL_LANG_ROLE . "&#160;|" . IFACE_LANG_TYPE .
				"&#160;|" . NETWORK_LANG_IP .
				"&#160;|" . IFACE_LANG_LINK . "&#160;|" . IFACE_LANG_SPEED . "&#160;|");
		} else {
			WebTableOpen(IFACE_LANG_INTERFACE, "100%");
			WebTableHeader(IFACE_LANG_INTERFACE . "|" . FIREWALL_LANG_ROLE . "|" . IFACE_LANG_TYPE .
				"|" . NETWORK_LANG_IP .
				"|" . IFACE_LANG_LINK . "|" . IFACE_LANG_SPEED . "|");
		}
		echo $ethsummary;
		WebTableClose("100%");
	}

	WebFormClose();

	if (WebIsSetup())
		DisplayNetwork($ethlist);

	echo "<span id='ifaces_ready'>&nbsp; </span>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAddVirtual
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAddVirtual()
{
	WebFormOpen();
	WebDialogInfo(
		WEB_LANG_FOLLOW_LINK_TO_ADD_VIRTUAL . " &#160; " .
		WebButtonContinue("AddVirtual")
	);
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditPppoe
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditPppoe($eth, $role, $pppoe_peerdns, $username, $password, $mtu)
{
	// PPPOEKLUDGE
	// Changing a PPPoE to a non-PPPoE interface is a bit nasty.  For now,
	// a user can tweak PPPoE connection settings, or completely delete it.

	try {
		$iface = new Iface($eth);
		$info = $iface->GetInterfaceInfo();

		$firewall = new Firewall();
		$defaultrole = $firewall->GetInterfaceRole($eth);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if (empty($username) && isset($info['ifcfg']['user']))
		$username = $info['ifcfg']['user'];

	if (empty($mtu) && isset($info['ifcfg']['mtu']))
		$mtu = $info['ifcfg']['mtu'];

	if (empty($pppoe_peerdns))
		$pppoe_peerdns = isset($info['ifcfg']['peerdns']) && preg_match("/no/i", $info['ifcfg']['peerdns']) ? "" : "checked";

	if (empty($role))
		$role = $defaultrole;

	WebFormOpen();
	WebTableOpen(IFACE_LANG_PPPOE);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_USERNAME . "</td>
			<td><input type='text' name='username' value='$username' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input type='text' name='password' value='$password' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_MTU . "</td>
			<td><input type='text' name='mtu' value='$mtu' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . IFACE_LANG_PEERDNS . "</td>
			<td><input type='checkbox' name='pppoe_peerdns' $pppoe_peerdns/></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap> &nbsp; </td>
			<td>" . WebButtonConfirm("SaveNetworkInterface") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose();
	echo "<input type='hidden' name='eth' value='$eth' />";
	echo "<input type='hidden' name='role' value='$role' />";
	echo "<input type='hidden' name='bootproto' value='" . Iface::BOOTPROTO_PPPOE . "' />";
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEdit
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEdit($eth, $role, $bootproto, $ip, $netmask, $gateway, $dhcp_hostname, 
	$peerdns, $username, $password, $mtu)
{
	$network = new Network();
	$firewall = new Firewall();
	$interfaces = new IfaceManager();

	try {
		$interface = new Iface($eth);
		$info = $interface->GetInterfaceInfo();
		$numinterfaces = $interfaces->GetInterfaceCount();
		$type = $interface->GetType();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	// Initialize form variables
	//--------------------------

	if (empty($role))
		$role = $firewall->GetInterfaceRole($eth);

	if (empty($bootproto))
		$bootproto = isset($info['ifcfg']['bootproto']) ? $info['ifcfg']['bootproto'] : "";

	if (empty($ip))
		$ip = isset($info['ifcfg']['ipaddr']) ? $info['ifcfg']['ipaddr'] : "";

	if (empty($netmask))
		$netmask = isset($info['ifcfg']['netmask']) ? $info['ifcfg']['netmask'] : "";
	
	if (empty($gateway))
		$gateway = isset($info['ifcfg']['gateway']) ? $info['ifcfg']['gateway'] : "";

	if (empty($dhcp_hostname))
		$dhcp_hostname = isset($info['ifcfg']['dhcp_hostname']) ? $info['ifcfg']['dhcp_hostname'] : "";

	if (empty($mtu))
		$mtu = isset($info['ifcfg']['mtu']) ? $info['ifcfg']['mtu'] : "";

	if (empty($username))
		$username = isset($info['ifcfg']['user']) ? $info['ifcfg']['user'] : "";

	if (empty($password))
		$password = isset($info['ifcfg']['password']) ? $info['ifcfg']['password'] : "";

	if (empty($peerdns))
		$peerdns = (isset($info['ifcfg']['peerdns']) && preg_match("/no/i", $info['ifcfg']['peerdns'])) ? "" : "checked";

	if (empty($pppoe_peerdns))
		$pppoe_peerdns = (isset($info['ifcfg']['peerdns']) && preg_match("/no/i", $info['ifcfg']['peerdns'])) ? "" : "checked";

	// If not defined, figure out some appropriate defaults

	if (empty($ip))
		$ip = $interface->GetLiveIp();

	if (empty($netmask)) {
		$netmask = $interface->GetLiveNetmask();
		if (!$netmask)
			$netmask = "255.255.255.0";
	}

	if (empty($bootproto)) {
		if ($numinterfaces == 1)
			$bootproto =  Iface::BOOTPROTO_DHCP;
		else
			$bootproto =  Iface::BOOTPROTO_STATIC;
	}


	// Vendor/model
	//-------------------------------------------------------------------

	$vendor = LOCALE_LANG_UNKNOWN;

	try {
		$details = $interfaces->GetVendorDetails($eth);

		if ($details['sub_device'] == null) {
			$vendor =  $details['vendor'] . " " . $details['device'] . " " . $details['bus'];
		} else {
			if ($details['device'] == null)
				$vendor = $details['vendor'] . " " . $details['bus'];
			else
				$vendor = $details['vendor'] . " " . $details['sub_device'] . " " . $details['bus'];
		}

		if (strlen($vendor) > 64)
			$vendor = substr($vendor, 0, 64) . '...';
	} catch (Exception $e) {
		// Not fatal
	}

	// Link
	//-------------------------------------------------------------------

	if (isset($info['link'])) {
		if ($info['link'] == -1)
			$link = "";
		else if ($info['link'] == 0)
			$link = WEBCONFIG_ICON_XMARK;
		else
			$link = WEBCONFIG_ICON_CHECKMARK;
	} else {
		$link = "";
	}

	// Role
	//-------------------------------------------------------------------

	if ($numinterfaces == 1) {
		$role = Firewall::CONSTANT_EXTERNAL;
        $role_out = "<input type='hidden' name='role' value='$role' />" . FIREWALL_LANG_EXTERNAL;
	} else {
		$role_dmz = ($role == Firewall::CONSTANT_DMZ) ? "selected" : "";
		$role_hotlan = ($role == Firewall::CONSTANT_HOT_LAN) ? "selected" : "";
		$role_internal = ($role == Firewall::CONSTANT_LAN) ? "selected" : "";
		$role_external = ($role == Firewall::CONSTANT_EXTERNAL) ? "selected" : "";

		$role_out = "
			<select name='role' onchange='toggleNetworkRole()' id='role'>
			<option value='" . Firewall::CONSTANT_LAN . "' $role_internal>" . FIREWALL_LANG_LAN . "</option>
			<option value='" . Firewall::CONSTANT_HOT_LAN . "' $role_hotlan>" . FIREWALL_LANG_HOT_LAN . "</option>
			<option value='" . Firewall::CONSTANT_EXTERNAL . "' $role_external>" . FIREWALL_LANG_EXTERNAL . "</option>
			<option value='" . Firewall::CONSTANT_DMZ . "' $role_dmz>" . FIREWALL_LANG_DMZ . "</option></select>
		";
	}

	// Type
	//-------------------------------------------------------------------

	$selected = array();
	$selected['dhcp'] = "";
	$selected['static'] = "";
	$selected['pppoe'] = "";

	if ($bootproto == Iface::BOOTPROTO_DHCP) {
		$selected['dhcp'] = "selected";
	} else if ($bootproto == Iface::BOOTPROTO_PPPOE) {
		$selected['pppoe'] = "selected";
	} else {
		$selected['static'] = "selected";
	}

	$bootproto_out = "
		<select name='bootproto' onchange='toggleNetworkType()' id='networktype'>
			<option value='" . Iface::BOOTPROTO_STATIC . "' " . $selected['static'] . ">" . IFACE_LANG_STATIC . "</option>
			<option value='" . Iface::BOOTPROTO_DHCP . "' " . $selected['dhcp'] . ">" . IFACE_LANG_DHCP . "</option>
			<option value='" . Iface::BOOTPROTO_PPPOE . "' " . $selected['pppoe'] . ">" . IFACE_LANG_PPPOE . "</option>
		</select>
	";

	// Details...
	//-------------------------------------------------------------------

	// Wireless settings if it is a wireless interface
	//------------------------------------------------

	if ($type == Iface::TYPE_WIRELESS) {

		if (empty($mode))
			$mode = isset($info['ifcfg']['mode']) ? $info['ifcfg']['mode'] : "";

		if (empty($rate))
			$rate = isset($info['ifcfg']['rate']) ? $info['ifcfg']['rate'] : "";

		if (empty($key))
			$key = isset($info['ifcfg']['key']) ? $info['ifcfg']['key'] : "s:";

		if (empty($essid))
			$essid = isset($info['ifcfg']['essid']) ? $info['ifcfg']['essid'] : "";

		$modelist = array("Ad-Hoc", "Managed", "Master", "Repeater", "Secondary", "auto");
		$ratelist = array("auto", "54M", "22M", "11M", "2M", "1M");

		$mode_dropdown = WebDropDownArray("mode", $mode, $modelist);
		$rate_dropdown = WebDropDownArray("rate", $rate, $ratelist);

		if (preg_match("/^ath/", $eth))
			$mode_dropdown = WebDropDownArray("mode", "Master", array("Master"));

		$wireless_extras = "
			<tr>
				<td class='mytablesubheader' nowrap>" . IFACE_LANG_ESSID . "</td>
				<td><input type='text' name='essid' value='$essid' /></td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . IFACE_LANG_KEY . "</td>
				<td><input type='text' name='key' value='$key' /></td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . IFACE_LANG_MODE . "</td>
				<td>$mode_dropdown</td>
			</tr>
			<tr>
				<td class='mytablesubheader' nowrap>" . IFACE_LANG_BIT_RATE . "</td>
				<td>$rate_dropdown</td>
			</tr>
		";
	} else {
		$wireless_extras = "";
	}

	// Static IP form
	//---------------

	ob_start();

	echo "<div id='static'>";
	WebTableOpen(IFACE_LANG_STATIC);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_IP . "</td>
			<td><input type='text' name='ip' value='$ip' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_NETMASK . "</td>
			<td><input type='text' name='netmask' value='$netmask' /></td>
		</tr>
		<tr id='gateway'>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_GATEWAY . "</td>
			<td><input type='text' name='gateway' value='$gateway' /></td>
		</tr>
		$wireless_extras
		<tr>
			<td class='mytablesubheader' nowrap> &nbsp; </td>
			<td>" . WebButtonConfirm("SaveNetworkInterface") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose();
	echo "</div>";

	$static_form = ob_get_clean();

	// DHCP form
	//----------

	ob_start();

	echo "<div id='dhcp' style='display: none'>";
	WebTableOpen(IFACE_LANG_DHCP);
	echo "
		$wireless_extras
		<tr>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_HOSTNAME . "</td>
			<td><input type='text' name='dhcp_hostname' value='$dhcp_hostname' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . IFACE_LANG_PEERDNS . "</td>
			<td><input type='checkbox' name='peerdns' $peerdns/></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap> &nbsp; </td>
			<td>" . WebButtonConfirm("SaveNetworkInterface") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose();
	echo "</div>";

	$dhcp_form = ob_get_clean();

	// PPPoE form
	//-----------

	ob_start();

	if (($bootproto == Iface::BOOTPROTO_PPPOE) && ($role != Firewall::CONSTANT_EXTERNAL))
		WebDialogWarning(WEB_LANG_PPPOE_ON_LAN_INTERFACE_WARNING);

	echo "<div id='pppoe' style='display: none'>";
	WebTableOpen(IFACE_LANG_PPPOE);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_USERNAME . "</td>
			<td><input type='text' name='username' value='$username' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
			<td><input type='text' name='password' value='$password' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_MTU . "</td>
			<td><input type='text' name='mtu' value='$mtu' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . IFACE_LANG_PEERDNS . "</td>
			<td><input type='checkbox' name='pppoe_peerdns' $pppoe_peerdns/></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap> &nbsp; </td>
			<td>" . WebButtonUpdate("SaveNetworkInterface") . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose();
	echo "</div>";

	$pppoe_form = ob_get_clean();

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(IFACE_LANG_INTERFACE);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_VENDOR_MODEL . "</td>
			<td nowrap>$vendor</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . IFACE_LANG_INTERFACE . "</td>
			<td>$eth <input type='hidden' name='eth' value='$eth' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . IFACE_LANG_LINK . "</td>
			<td>$link</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_ROLE . "</td>
			<td>$role_out</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . IFACE_LANG_TYPE . "</td>
			<td>$bootproto_out</td>
		</tr>
	";
	WebTableClose();

	echo $static_form;
	echo $dhcp_form;
	echo $pppoe_form;

	WebFormClose();

	echo "<script type='text/javascript'>toggleNetworkType();</script>";
	echo "<script type='text/javascript'>toggleNetworkRole();</script>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirmDelete()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirmDelete($function, $eth)
{
	WebFormOpen();
	WebDialogWarning(
		WEB_LANG_CONFIRM_DELETE_INTERFACE . " &nbsp; " . $eth . "<br><br>" .
		WebButtonDelete($function . "[" . $eth . "]")
	);
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// SanityCheck()
//
///////////////////////////////////////////////////////////////////////////////

function SanityCheck($ethlist)
{
	$interfaces = new IfaceManager();

	// External interface must be defined
	//-----------------------------------

	$external = false;
	$weird_gateways = "";

	foreach ($ethlist as $eth => $info) {
		if ($info['role'] == Firewall::CONSTANT_EXTERNAL) {
			$external = true;
			if (($info['type'] == Iface::TYPE_ETHERNET) && ($info['ifcfg']['bootproto'] == Iface::BOOTPROTO_STATIC)) {
				$network = new Network();
				if (! $network->IsValidIpOnNetwork($info['ifcfg']['ipaddr'], $info['ifcfg']['netmask'], $info['ifcfg']['gateway']))
					$weird_gateways .= "
						<tr>
							<td>$eth</td>
							<td>" . $info['ifcfg']['ipaddr'] . "</td>
							<td>" . $info['ifcfg']['netmask'] . "</td>
							<td>" . $info['ifcfg']['gateway'] . "</td>
						</tr>
					";
			}
		}
	}

	if (! empty($weird_gateways)) {
		WebDialogInfo(NETWORK_LANG_ERRMSG_UNUSUAL_GATEWAY_SETTING . "<br><br>
			<table width='100%' border='0' cellspacing='0' cellpadding='2' align='center'>
				<tr>
					<td><b>" . IFACE_LANG_INTERFACE . "</b></td>
					<td><b>" . NETWORK_LANG_IP . "</b></td>
					<td><b>" . NETWORK_LANG_NETMASK . "</b></td>
					<td><b>" . NETWORK_LANG_GATEWAY . "</b></td>
				</tr>
				$weird_gateways
			</table>");
	}

	if (! $external)
		WebDialogWarning(WEB_LANG_EXTERNAL_INTERFACE_REQUIRED);

	// Check firewall mode, if set to DMZ with MultiWAN and no source-based
	// routes for DMZ networks found, display warning...
	//------------------------------------------------------------------------

	if (file_exists('../../api/FirewallMultiWan.class.php')) {
		require_once('../../api/FirewallMultiWan.class.php');
		$multiwan = new FirewallMultiWan();
		$multiwan->SanityCheckDmz();
	}
}

// vim: ts=4
?>
