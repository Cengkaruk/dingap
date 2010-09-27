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
require_once("../../api/Network.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/FirewallIncoming.class.php");
require_once("../../api/FirewallRule.class.php");
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-firewall-groups.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new Firewall();

if ($_POST['Confirm']) {
	try {
		$rule = new FirewallRule();
		$rule->SetRule(key($_POST['Confirm']));
		$firewall->DeleteRule($rule);

		// FIXME: setting an invalid name blows away the rule
		$rule->SetName($_POST['RuleName']);
		if (strlen($_POST['NewGroup']) && $rule->GetGroup() != $_POST['NewGroup'])
			$rule->SetGroup($_POST['NewGroup']);
		else if ($_POST['AssignGroup'] != "None" && $_POST['AssignGroup'] != "Remove")
			$rule->SetGroup($_POST['AssignGroup']);
		else if ($_POST['AssignGroup'] == "Remove")
			$rule->SetGroup("");

		if ($rule->CheckValidationErrors()) {
			WebDialogWarning($rule->GetValidationErrors(true));
		} else {
			$firewall->AddRule($rule);
			$firewall->Restart();
		}
	} catch (Exception $e) {
		  WebDialogWarning($e->GetMessage());
	 }
} else if ($_POST['EnableToggle']) {
	try {
		$rule = new FirewallRule();
		$rule->SetRule(key($_POST['EnableToggle']));
		$firewall->DeleteRule($rule);
		if ($rule->IsEnabled())
			$rule->Disable();
		else
			$rule->Enable();
		$firewall->AddRule($rule);
		$firewall->Restart();
	} catch (Exception $e) {
		  WebDialogWarning($e->GetMessage());
	 }
} else if ($_POST['EnableGroup']) {
	try {
		$rules = $firewall->GetRules();
		foreach($rules as $rule) {
			if ($rule->GetGroup() != key($_POST['EnableGroup']))
				continue;
			$firewall->DeleteRule($rule);
			$rule->Enable();
			$firewall->AddRule($rule);
		}
		$firewall->Restart();
	} catch (Exception $e) {
		  WebDialogWarning($e->GetMessage());
	 }
} else if (isset($_POST['DisableGroup'])) {
	try {
		$rules = $firewall->GetRules();

		foreach($rules as $rule) {
			if ($rule->GetGroup() != key($_POST['DisableGroup']))
				continue;

			$firewall->DeleteRule($rule);
			$rule->Disable();
			$firewall->AddRule($rule);
		}

		$firewall->Restart();
	} catch (Exception $e) {
		  WebDialogWarning($e->GetMessage());
	 }
} else if (isset($_POST['DeleteGroup'])) {
	try {
		$rules = $firewall->GetRules();
		foreach($rules as $rule) {
			if ($rule->GetGroup() != key($_POST['DeleteGroup']))
				continue;
			$firewall->DeleteRule($rule);
		}
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
	DisplayEditRule(key($_POST['Edit']));
} else {
	$groups = GetGroups();
	DisplayRules();

	if (sizeof($groups)) {
		DisplayGroups($groups);
		WebDialogIntro(WEB_LANG_EXTRA_TITLE, "/images/icon-firewallhelp.png", WEB_LANG_EXTRA_INTRO);
		DisplayGroupManager($groups);
	}
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

			$exists = true;
			break;
		}

		if (!$exists)
			$groups[] = $rule->GetGroup();
	}

	sort($groups);

	return $groups;
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
		if (strlen($rule->GetGroup()))
			continue;

		$type = $rule->GetTypeText();

		// TODO: there should be a GetType method instead of doing a compare on text strings
		// Do not show bandwidth rules
		if ($type == FIREWALLRULE_LANG_TYPE_BANDWIDTH)
			continue;

		$name = $rule->GetName();
		$isenabled = $rule->IsEnabled();

		if (!strlen($name)) {
			if ($rule->GetProtocol())
				$name = $rule->GetProtocol();
			elseif (strlen($rule->GetAddress()))
				$name = $rule->GetAddress();
			else
				$name = "-";
		}

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
				<td nowrap>" . $name . "</td>
				<td nowrap>" . $type . "</td>
				<td nowrap>" . 
					WebButtonEdit("Edit[" . $rule->GetRule() . "]") .
					WebButtonToggle("EnableToggle[" . $rule->GetRule() . "]", $toggle) . "
				</td>
			</tr>
		";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "100%");
	WebTableHeader("|" . FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_TYPE . "|");
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayGroups()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayGroups($groups)
{
	global $firewall;

	try {
		$rules = $firewall->GetRules();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	foreach ($groups as $group) {
		$list = "";
		$index = 0;

		foreach($rules as $rule) {
			if ($rule->GetGroup() != $group)
				continue;

			$type = $rule->GetTypeText();
			$name = $rule->GetName();
			$isenabled = $rule->IsEnabled();

			if (!strlen($name)) {
				if ($rule->GetProtocol())
					$name = $rule->GetProtocol();
				elseif (strlen($rule->GetAddress()))
					$name = $rule->GetAddress();
				else
					$name = "-";
			}

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
					<td nowrap>" . $name . "</td>
					<td nowrap>" . $type . "</td>
					<td nowrap>" . 
						WebButtonEdit("Edit[" . $rule->GetRule() . "]") .
						WebButtonToggle("EnableToggle[" . $rule->GetRule() . "]", $toggle) . "
					</td>
				</tr>
			";
		}

		WebFormOpen();
		WebTableOpen(FIREWALL_LANG_GROUP . " - " . $group, "100%");
		WebTableHeader("|" . FIREWALL_LANG_NICKNAME . "|" . FIREWALL_LANG_TYPE . "|");
		echo $list;
		WebTableClose("100%");
		WebFormClose();
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayGroupManager()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayGroupManager($groups)
{
	$list = "";

	foreach ($groups as $group) {
		$list .= "
			<tr>
				<td nowrap>" . $group . "</td>
				<td nowrap>" . 
				WebButtonDelete("DeleteGroup[$group]") .
				WebButtonToggle("EnableGroup[$group]", "Enable") . 
				WebButtonToggle("DisableGroup[$group]", "Disable") . 
				"</td>
			</tr>
		";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_MANAGER_TITLE, "100%");
	WebTableHeader(FIREWALL_LANG_GROUP . "|");
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditRule()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditRule($rule)
{
	$oldrule = new FirewallRule();
	$network = new Network(); // for locale

	try {
		$oldrule->SetRule($rule);
		$groups = GetGroups();
		$port = $oldrule->GetPort();
		$name = $oldrule->GetName();
		$group = $oldrule->GetGroup();
		$address = $oldrule->GetAddress();
		$protocol = $oldrule->GetProtocol();
		$typetext = $oldrule->GetTypeText();
		$parameter = $oldrule->GetParameter();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	switch ($protocol) {
		case FirewallRule::PROTO_TCP:
			$protocoltext = "TCP";
			break;
		case FirewallRule::PROTO_UDP:
			$protocoltext = "UDP";
			break;
		case FirewallRule::PROTO_GRE:
			$protocoltext = "GRE";
			break;
		case FirewallRule::PROTO_ESP:
			$protocoltext = "ESP";
			break;
		case FirewallRule::PROTO_AH:
			$protocoltext = "AH";
			break;
		case FirewallRule::PROTO_IP:
			$protocoltext = "IP";
			break;
		default:
			$protocoltext = LOCALE_LANG_UNKNOWN;
			break;
	}

	if (count($groups) == 0) {
		$grouplist = "";
	} else {
		$firstitem = empty($group) ? "" : "<option value='Remove'>" . WEB_LANG_REMOVE_FROM_GROUP . "</option>";

		foreach ($groups as $entry)
			$grouplist .= sprintf("\n<option%s>$entry</option>\n", ($group == $entry) ? " selected" : "");

		$grouplist = "
			<select name='AssignGroup'>
				$firstitem
				$grouplist
			</select>";
	}

	if (! empty($address)) {
		$addresshtml = "
			<tr>
				<td class='mytablesubheader' nowrap>" . NETWORK_LANG_IP . "</td>
				<td nowrap>" . $address . "</td>
			</tr>
		";
	}

	if (! empty($parameter)) {
		$parameterhtml = "
			<tr>
				<td class='mytablesubheader' nowrap>" . WEB_LANG_PARAMETER . "</td>
				<td nowrap>" . $parameter . "</td>
			</tr>
		";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_EDIT_RULE_TITLE, "100%");
	echo "
		<tr>
			<td class='mytableheader' colspan='2'>" . FIREWALL_LANG_RULE . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_TYPE . "</td>
			<td nowrap>" . $typetext . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_PROTOCOL . "</td>
			<td nowrap>" . $protocoltext . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . NETWORK_LANG_PORT . "</td>
			<td nowrap>" . $port . "</td>
		</tr>
		$addresshtml
		$parameterhtml
		<tr>
			<td class='mytableheader' colspan='2'>" . FIREWALL_LANG_RULE . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_NICKNAME . "</td>
			<td nowrap><input type='text' name='RuleName' value='$name'></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . FIREWALL_LANG_GROUP . "</td>
			<td nowrap>$grouplist <input type='text' name='NewGroup' value=''></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp;</td>
			<td nowrap>" . WebButtonConfirm("Confirm[$rule]") . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: ts=4
?>
