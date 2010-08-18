<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2007 Point Clark Networks.
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
require_once("../../api/Locale.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/FirewallRule.class.php");
require_once("../../api/Network.class.php");
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-firewalladvanced.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new Firewall();
$rule = new FirewallRule();
$network = new Network(); // locale

if (isset($_POST['Cancel'])) {
	unset($_POST['ruletype']);
} else if (isset($_POST['SaveRule']) || isset($_POST['UpdateRule'])) {
	try {
		if (isset($_POST['UpdateRule'])) {
			$rule->SetRule($_POST['oldrule']);
			$firewall->DeleteRule($rule);
		}

		$rule->SetName($_POST['rulename']);

		if ($_POST['newrulegroup'])
			$rule->SetGroup($_POST['newrulegroup']);
		else if ($_POST['rulegroup'])
			$rule->SetGroup($_POST['rulegroup']);

		$rule->SetProtocol($_POST['protocol']);

		if ($_POST['sourceaddresstype'] == "MAC") {
			if (!$rule->IsValidMac($_POST['sourceaddress'])) {
				throw new ValidationException (FIREWALLRULE_LANG_ERRMSG_INVALID_MAC);
			} else {
				$_POST['ruletype'] |= FirewallRule::MAC_SOURCE;
				$rule->SetAddress($_POST['sourceaddress']);
			}
		} else {
			if (!$rule->IsValidTarget($_POST['sourceaddress']))
				throw new ValidationException (FIREWALLRULE_LANG_ERRMSG_INVALID_IPV4);
			else
				$rule->SetAddress($_POST['sourceaddress']);
		}

		$rule->SetFlags(FirewallRule::ENABLED | FirewallRule::CUSTOM | $_POST['ruletype']);

		$sportlow = isset($_POST['sourceportlow']) ? $_POST['sourceportlow'] : "";
		$sporthigh = isset($_POST['sourceporthigh']) ? $_POST['sourceporthigh'] : "";

		$dport = sprintf("{$_POST['destinationportlow']}%s%s",
			(strlen($_POST['destinationportlow']) && strlen($_POST['destinationporthigh'])) ? ":" : "",
			(strlen($_POST['destinationporthigh'])) ? $_POST['destinationporthigh'] : "");

		if (strlen($_POST['destinationaddress']) && $rule->IsValidMac($_POST['destinationaddress']))
			throw new ValidationException (FIREWALLRULE_LANG_ERRMSG_INVALID_IPV4);
		if (strlen($_POST['dport']) && !$rule->IsValidPort($_POST['dport']))
			throw new ValidationException (FIREWALLRULE_LANG_ERRMSG_INVALID_PORT);

		if (!empty($sporthigh))
			$rule->SetPortRange($sportlow, $sporthigh);
		else if (!empty($sportlow))
			$rule->SetPort($sportlow, $sporthigh);
		else
			$rule->SetPort(Firewall::CONSTANT_ALL_PORTS);

		$rule->SetParameter($_POST['destinationaddress'] . "_" . $dport);

		unset($_POST['ruletype']);

		$rule->GetRule();
		$firewall->AddRule($rule);
		$firewall->Restart();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());

		try {
			if (isset($_POST['UpdateRule'])) {
				$rule->SetRule($_POST['oldrule']);
				$firewall->AddRule($rule);
			}
		} catch (Exception $e) {
			//
		}
	}
} else if (isset($_POST['Delete'])) {
	try {
		$rule->SetRule(key($_POST['Delete']));
		$firewall->DeleteRule($rule);

		unset($_POST['ruletype']);
		$firewall->Restart();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['EnableToggle'])) {
	try {
		$rule->SetRule(key($_POST['EnableToggle']));
		$firewall->DeleteRule($rule);
		if ($rule->IsEnabled())
			$rule->Disable();
		else
			$rule->Enable();

		$firewall->AddRule($rule);

		unset($_POST['ruletype']);
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
	$mode = $firewall->GetMode();
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if ($mode == FirewallIncoming::CONSTANT_TRUSTEDSTANDALONE)
	DisplayModeWarning();

if (isset($_POST['Edit'])) {
	$rule->SetRule(key($_POST['Edit']));
	DisplayAddEditRule($rule);
} else if (isset($_POST['ruletype'])) {
	$rule->SetFlags((int)$_POST['ruletype']);
	DisplayAddEditRule($rule);
} else {
	DisplayRules();
	DisplayAddEditRule(false);
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// GetGroups()
//
///////////////////////////////////////////////////////////////////////////////

function GetGroups()
{
	global $firewall;

	$groups = array();

	try {
		$rules = $firewall->GetRules();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	foreach($rules as $rule) {
		if (!strlen($rule->GetGroup()))
			continue;

		$exists = false;

		foreach($groups as $group) {
			if ($rule->GetGroup() != $group)
				 continue;

			$exists = true; break;
		}

		if (!$exists)
			$groups[] = $rule->GetGroup();
	}

	sort($groups);

	return $groups;
}

///////////////////////////////////////////////////////////////////////////////
//
// RuleType()
//
///////////////////////////////////////////////////////////////////////////////

function RuleType($rule)
{
	$type = LOCALE_LANG_UNKNOWN;

	if ($rule->GetFlags() & FirewallRule::INCOMING_ALLOW)
		$type = FIREWALLRULE_LANG_TYPE_INCOMING_ALLOW;
	else if ($rule->GetFlags() & FirewallRule::INCOMING_BLOCK)
		$type = FIREWALLRULE_LANG_TYPE_INCOMING_BLOCK;
	else if ($rule->GetFlags() & FirewallRule::OUTGOING_BLOCK)
		$type = FIREWALLRULE_LANG_TYPE_OUTGOING_BLOCK;
	else if ($rule->GetFlags() & FirewallRule::FORWARD)
		$type = FIREWALLRULE_LANG_TYPE_PORT_FORWARD;
	else if ($rule->GetFlags() & FirewallRule::DMZ_PINHOLE)
		$type = FIREWALLRULE_LANG_TYPE_DMZ_PINHOLE;
	else if ($rule->GetFlags() & FirewallRule::DMZ_INCOMING)
		$type = FIREWALLRULE_LANG_TYPE_DMZ_INCOMING;
	else if ($rule->GetFlags() & FirewallRule::BANDWIDTH_MARK)
		continue;
	else if ($rule->GetFlags() & FirewallRule::ONE_TO_ONE)
		$type = FIREWALLRULE_LANG_TYPE_ONE_TO_ONE_NAT;
	else if ($rule->GetFlags() & FirewallRule::PPTP_FORWARD)
		continue;
	else if ($rule->GetFlags() & FirewallRule::MAC_FILTER)
		$type = FIREWALLRULE_LANG_TYPE_MAC_FILTER_ALLOW;

	if ($rule->GetFlags() & FirewallRule::WIFI)
		$type .= WEB_LANG_SUBTYPE_WIRELESS;

	return $type;
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayRules()
{
	global $firewall;

	try {
		$rules = $firewall->GetRules();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	foreach ($rules as $rule) {

		try {
			if (!($rule->GetFlags() & FirewallRule::CUSTOM))
				continue;

			$type = RuleType($rule);
			$name = $rule->GetName();
			$group = $rule->GetGroup();
			$ruleinfo = $rule->GetRule();
			$isenabled = $rule->IsEnabled();
		} catch (Exception $e) {
			WebDialogWarning($e->GetMessage());
			return;
		}

		if (!strlen($name)) $name = "";
		if (!strlen($group)) $group = "";

		if ($isenabled) {
			$toggle = LOCALE_LANG_DISABLE;
			$iconclass = "iconenabled";
			$rowclass = "rowenabled";
		} else {
			$toggle = LOCALE_LANG_ENABLE;
			$iconclass = "icondisabled";
			$rowclass = "rowdisabled";
		}

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$list .= "
			<tr class='$rowclass'>
				<td class='$iconclass'>&nbsp; </td>
				<td>$name</td>
				<td nowrap>$group</td>
				<td nowrap>$type</td>
				<td nowrap>" .
					WebButtonEdit("Edit[" . $ruleinfo . "]") .
					WebButtonDelete("Delete[" . $ruleinfo . "]") .
					WebButtonToggle("EnableToggle[" . $ruleinfo . "]", $toggle) . "
				</td>
			</tr>
		";
	}

	if (!$list)
		$list .= "<tr><td colspan='5' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>\n";

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "100%");
	WebTableHeader(
		"&nbsp;|" .
		FIREWALL_LANG_NICKNAME . "|" . 
		FIREWALL_LANG_GROUP . "|" . 
		FIREWALL_LANG_TYPE . "|" . 
		"&nbsp;"
	);
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayAddEditRule()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAddEditRule($rule)
{
	if (! $rule) {
		$types = "<option value='" . FirewallRule::INCOMING_ALLOW . "'>" . FIREWALLRULE_LANG_TYPE_INCOMING_ALLOW . "</option>\n";
		$types .= "<option value='" . FirewallRule::INCOMING_BLOCK . "'>" . FIREWALLRULE_LANG_TYPE_INCOMING_BLOCK . "</option>\n";
		$types .= "<option value='" . FirewallRule::OUTGOING_BLOCK . "'>" . FIREWALLRULE_LANG_TYPE_OUTGOING_BLOCK . "</option>\n";
		$types .= "<option value='" . FirewallRule::FORWARD . "'>" . FIREWALLRULE_LANG_TYPE_PORT_FORWARD . "</option>\n";

		WebFormOpen();
		WebTableOpen(WEB_LANG_ADD_RULE_TITLE, "75%");
		echo "
			<tr>
				<td width='30%' class='mytablesubheader' nowrap>" . WEB_LANG_ADD_RULE . "</td>
				<td><select name='ruletype'>$types</select> " . WebButtonAdd('AddRule') . "</td>
			</tr>
		";
		WebTableClose("75%");
		WebFormClose();

		return;
	}

	try {
		$groups = GetGroups();

		$flags = $rule->GetFlags();
		$port = $rule->GetPort();
		$group = $rule->GetGroup();
		$protocol = $rule->GetProtocol();
		$nickname = $rule->GetName();
		$sourceaddress = $rule->GetAddress();
		$destination = $rule->GetParameter();
		$ruleinfo = $rule->GetRule();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	// Protocol drop-down
	//-------------------

	$protolist = array();
	$protolist[FirewallRule::PROTO_AH] = "AH";
	$protolist[FirewallRule::PROTO_ESP] = "ESP";
	$protolist[FirewallRule::PROTO_GRE] = "GRE";
	$protolist[FirewallRule::PROTO_IP] = "IP";
	$protolist[FirewallRule::PROTO_TCP] = "TCP";
	$protolist[FirewallRule::PROTO_UDP] = "UDP";

	// Group information
	//------------------

	$rulegroup = isset($_POST['rulegroup']) ? $_POST['rulegroup'] : $group;
	$newrulegroup = isset($_POST['newrulegroup']) ? $_POST['newrulegroup'] : "";

	array_unshift($groups, WEB_LANG_NONE);

	// Port and address information
	//-----------------------------

	if (preg_match("/:/", $port)) {
		list($sourceportlow, $sourceporthigh) = explode(":", $port);
	} else {
		$sourceportlow = $port;
		$sourceporthigh = "";
	}

	if (preg_match("/_/", $destination)) {
		list($destinationaddress, $destinationports) = explode("_", $destination);
	} else {
		$destinationaddress = $destination;
		$destinationports = "";
	}

	if (preg_match("/:/", $destinationports)) {
		list($destinationportlow, $destinationporthigh) = explode(":", $destinationports);
	} else {
		$destinationportlow = $destinationports;
		$destinationporthigh = "";
	}

	$ipv4_checked = (!($flags & FirewallRule::MAC_SOURCE)) ? "checked" : "";
	$mac_checked = ($flags & FirewallRule::MAC_SOURCE) ? "checked" : "";

	// Action
	//-------

	if (!isset($_POST['Edit']))
		$button = WebButtonAdd('SaveRule');
	else
		$button = WebButtonUpdate('UpdateRule');

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_RULE_TITLE, "100%");
	echo "
		<tr>
			<td width='35%' class='mytablesubheader' nowrap>" . FIREWALL_LANG_TYPE . "</td>
			<td>" . RuleType($rule) . "</td>
		</tr>
		<tr>
			<td width='35%' class='mytablesubheader' nowrap>" . FIREWALL_LANG_NICKNAME . "</td>
			<td><input type='text' name='rulename' value='$nickname' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_GROUP . "</td>
			<td>
				<input type='text' name='newrulegroup' value='$newrulegroup' /> " .
				WebDropDownArray("rulegroup", $rulegroup, $groups) . "
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_PROTOCOL . "</td>
			<td>" . WebDropDownHash("protocol", $protocol, $protolist) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_SOURCE_ADDRESS . "</td>
			<td>
				<input type='text' name='sourceaddress' value='$sourceaddress' />
				<input type='radio' name='sourceaddresstype' value='IPv4' $ipv4_checked />" . NETWORK_LANG_IP . "
				<input type='radio' name='sourceaddresstype' value='MAC' $mac_checked />" . NETWORK_LANG_MAC_ADDRESS . "
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_SOURCE_PORT_RANGE . "</td>
			<td>
				<input type='text' name='sourceportlow' style='width:50px' value='$sourceportlow' />&nbsp;" . 
				WEBCONFIG_ICON_ARROWRIGHT . "&nbsp;
				<input type='text' name='sourceporthigh' style='width:50px' value='$sourceporthigh' />
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_DESTINATION_ADDRESS . "</td>
			<td colspan='3'><input type='text' name='destinationaddress' value='$destinationaddress' /></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_DESTINATION_PORT_RANGE . "</td>
			<td>
				<input type='text' name='destinationportlow' style='width:50px' value='$destinationportlow' />&nbsp; " .
				WEBCONFIG_ICON_ARROWRIGHT . "&nbsp;
				<input type='text' name='destinationporthigh' style='width:50px' value='$destinationporthigh' />
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader nowrap'><input type='hidden' name='ruletype' value='" . $rule->GetFlags() . "' /></td>
			<td></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp; </td>
			<td>" . 
				$button . " " . 
				WebButtonCancel('Cancel') . "
				<input type='hidden' name='oldrule' value='$ruleinfo' />
			</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: ts=4
?>
