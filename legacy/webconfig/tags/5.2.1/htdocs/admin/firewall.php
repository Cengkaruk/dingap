<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
require_once("../../api/FirewallIncoming.class.php");
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
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-firewall.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new FirewallIncoming();

$port_nickname = isset($_POST['port_nickname']) ? $_POST['port_nickname'] : "";
$port_protocol = isset($_POST['port_protocol']) ? $_POST['port_protocol'] : "";
$port = isset($_POST['port']) ? $_POST['port'] : "";
$range_nickname = isset($_POST['range_nickname']) ? $_POST['range_nickname'] : "";
$range_protocol = isset($_POST['range_protocol']) ? $_POST['range_protocol'] : "";
$range_from = isset($_POST['range_from']) ? $_POST['range_from'] : "";
$range_to = isset($_POST['range_to']) ? $_POST['range_to'] : "";
$block_nickname = isset($_POST['block_nickname']) ? $_POST['block_nickname'] : "";
$block_host = isset($_POST['block_host']) ? $_POST['block_host'] : "";

try {
	if (isset($_POST['AddAllowStandardService'])) {
		$firewall->AddAllowStandardService($_POST['service']);
		$firewall->Restart();
	} else if (isset($_POST['AddAllowPort'])) {
		$firewall->AddAllowPort($port_nickname, $port_protocol, $port);
		$firewall->Restart();
	} else if (isset($_POST['AddAllowPortRange'])) {
		$firewall->AddAllowPortRange($range_nickname, $range_protocol, $range_from, $range_to);
		$firewall->Restart();
	} else if (isset($_POST['DeleteAllowPort'])) {
		list($protocol, $port) = explode("|", key($_POST['DeleteAllowPort']));
		$firewall->DeleteAllowPort($protocol, $port);
		$firewall->Restart();
	} else if (isset($_POST['DeleteAllowPortRange'])) {
		list($protocol, $from, $to) = explode("|", key($_POST['DeleteAllowPortRange']));
		$firewall->DeleteAllowPortRange($protocol, $from, $to);
		$firewall->Restart();
	} else if (isset($_POST['DeleteIpsecRule'])) {
		$firewall->SetIpsecServerState(false);
		$firewall->Restart();
	} else if (isset($_POST['DeletePptpRule'])) {
		$firewall->SetPptpServerState(false);
		$firewall->Restart();
	} else if (isset($_POST['AddBlockHost'])) {
		$firewall->AddBlockHost($block_nickname, $block_host);
		$firewall->Restart();
	} else if (isset($_POST['DeleteBlockHost'])) {
		$firewall->DeleteBlockHost(key($_POST['DeleteBlockHost']));
		$firewall->Restart();
	} else if (isset($_POST['ToggleAllowPort'])) {
		list($enabled, $protocol, $port) = explode("|", key($_POST['ToggleAllowPort']));
		$firewall->ToggleEnableAllowPort(($enabled) ? false : true, $protocol, $port);
		$firewall->Restart();
	} else if (isset($_POST['ToggleAllowPortRange'])) {
		list($enabled, $protocol, $from, $to) = explode("|", key($_POST['ToggleAllowPortRange']));
		$firewall->ToggleEnableAllowPortRange(($enabled) ? false : true, $protocol, $from, $to);
		$firewall->Restart();
	} else if (isset($_POST['ToggleBlockHost'])) {
		list($enabled, $host) = explode("|", key($_POST['ToggleBlockHost']));
		$firewall->ToggleEnableBlockHost(($enabled) ? false : true, $host);
		$firewall->Restart();
	}

	$errors = $firewall->GetValidationErrors(true);

	if (empty($errors)) {
		$port_nickname = "";
		$port_protocol = "";
		$port = "";
		$range_nickname = "";
		$range_protocol = "";
		$range_from = "";
		$range_to = "";
		$block_nickname = "";
		$block_host = "";
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

try {
	$mode = $firewall->GetMode();
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if ($mode == FirewallIncoming::CONSTANT_TRUSTEDSTANDALONE)
	DisplayModeWarning();

DisplayIncoming();
DisplayAdd($port_nickname, $port_protocol, $port, $range_nickname, $range_protocol, $range_from, $range_to);
DisplayIncomingBlock($block_nickname, $block_host);

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayIncoming()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayIncoming()
{
	global $firewall;

	try {
		$ports = $firewall->GetAllowPorts();
		$ranges = $firewall->GetAllowPortRanges();
		$ipsec = $firewall->GetIpsecServerState();
		$pptp = $firewall->GetPptpServerState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
    $index = 0;

	if ($ports) {
		foreach ($ports as $rule) {
			$name = (strlen($rule['name'])) ? $rule['name'] : "-";

			if ($rule['enabled']) {
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
			  <td nowrap>$name</td>
			  <td nowrap>" . $rule['service']. "</td>
			  <td nowrap>" . $rule['protocol']. "</td>
			  <td nowrap>" . $rule['port']. "</td>
			  <td nowrap>" .
			  WebButtonDelete("DeleteAllowPort[$rule[protocol]|$rule[port]]") .
			  WebButtonToggle("ToggleAllowPort[$rule[enabled]|$rule[protocol]|$rule[port]]", $toggle) . "
			  </td>
			 </tr>\n
			";
		}
	}

	if ($ranges) {
		foreach ($ranges as $range) {
			$name = (strlen($range['name'])) ? $range['name'] : "-";

			if ($range['enabled']) {
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
			  <td nowrap>$name</td>
			  <td nowrap>$range[service]</td>
			  <td nowrap>$range[protocol]</td>
			  <td nowrap>$range[from]&#160;:&#160;$range[to]</td>
			  <td nowrap>" .
			  WebButtonDelete("DeleteAllowPortRange[$range[protocol]|$range[from]|$range[to]]") . 
			  WebButtonToggle("ToggleAllowPortRange[$range[enabled]|$range[protocol]|$range[from]|$range[to]]", $toggle) . "
			  </td>
			 </tr>\n
			";
		}
	}

	if ($ipsec) {
		$rowclass = "rowenabled";
		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$list .= "
		 <tr class='$rowclass'>
		  <td class='iconenabled'>&nbsp; </td>
		  <td nowrap>&nbsp; </td>
		  <td nowrap>IPsec</td>
		  <td nowrap>ESP/AH + UDP</td>
		  <td nowrap>500</td>
		  <td nowrap>" . WebButtonDelete("DeleteIpsecRule") . "</td>
		 </tr>\n
		";
	}

	if ($pptp) {
		$rowclass = "rowenabled";
		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$list .= "
		 <tr class='$rowclass'>
		  <td class='iconenabled'>&nbsp; </td>
		  <td nowrap>&nbsp; </td>
		  <td nowrap>PPTP</td>
		  <td nowrap>GRE + TCP</td>
		  <td nowrap>1723</td>
		  <td nowrap>" . WebButtonDelete("DeletePptpRule") . "</td>
		 </tr>\n
		";
	}

	if (!$list)
		$list .= "<tr><td colspan='5' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_DELETE_RULE_TITLE, "100%");
	WebTableHeader("|" . 
		FIREWALL_LANG_NICKNAME . "|" . 
		FIREWALL_LANG_SERVICE . "|" . 
		FIREWALL_LANG_PROTOCOL . "|" .
		FIREWALL_LANG_PORT . "|"
	);
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayAdd()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAdd($port_nickname, $port_protocol, $port, $range_nickname, $range_protocol, $range_from, $range_to)
{
	global $firewall;

	try {
		$servicelist = $firewall->GetStandardServiceList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$protocols = array('TCP', 'UDP');

	$standard_dropdown = WebDropDownArray("service", '', $servicelist);
	$port_protocol_dropdown = WebDropDownArray("port_protocol", $port_protocol, $protocols);
	$range_protocol_dropdown = WebDropDownArray("range_protocol", $range_protocol, $protocols);

	WebFormOpen();
	WebTableOpen(WEB_LANG_ADD_RULE_TITLE, "100%");
	echo "
	 <tr>
	  <td width='25%' nowrap class='mytablesubheader'>" . FIREWALL_LANG_STANDARD_SERVICES . "</td>
	  <td width='55%'>$standard_dropdown</td>
	  <td width='20%' nowrap>" . WebButtonAdd('AddAllowStandardService') . "</td>
	 </tr>
	 <tr>
	  <td nowrap class='mytablesubheader'>" . FIREWALL_LANG_NICKNAME . " / " . FIREWALL_LANG_PORT . "</td>
	  <td>
		<input type='text' name='port_nickname' value='$port_nickname' />&#160;
		$port_protocol_dropdown
		<input type='text' name='port' value='$port' style='width:40px' />
	   </td>
	   <td nowrap>" . WebButtonAdd('AddAllowPort') . "</td>
	 </tr>
	 <tr>
	  <td nowrap class='mytablesubheader'>" . FIREWALL_LANG_NICKNAME . " / " . FIREWALL_LANG_PORT_RANGE . "</td>
	  <td nowrap>
		<input type='text' name='range_nickname' value='$range_nickname' />&#160;
		$range_protocol_dropdown
		<input type='text' name='range_from' value='$range_from' style='width:40px' /> :
		<input type='text' name='range_to' value='$range_to' style='width:40px' />
	  </td>
	  <td>" . WebButtonAdd('AddAllowPortRange') . "</td>
	 </tr>
	";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayIncomingBlock()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayIncomingBlock($block_nickname, $block_host)
{
	global $firewall;

	$network = new Network(); // For locale

	try {
		$hosts = $firewall->GetBlockHosts();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$list = "";
	$index = 0;

	foreach ($hosts as $rule) {
		$name = (strlen($rule['name'])) ? $rule['name'] : "-";

		if ($rule['enabled']) {
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
		  <td nowrap>$name</td>
		  <td nowrap>$rule[host]</td>
		  <td nowrap>" .
		  WebButtonDelete("DeleteBlockHost[$rule[host]]") .
		  WebButtonToggle("ToggleBlockHost[$rule[enabled]|$rule[host]]", $toggle) . "
		  </td>
		 </tr>\n
		";
	}

	if (!$list)
		$list = "<tr><td colspan='3' align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>";

	$list .= "
		<tr>
		  <td>&nbsp; </td>
		  <td nowrap><input type='text' name='block_nickname' value='$block_nickname' /></td>
		  <td nowrap><input type='text' name='block_host' value='$block_host' /></td>
		  <td nowrap>" . WebButtonAdd('AddBlockHost') . "</td>
		</tr>
	";

	WebFormOpen();
	WebTableOpen(WEB_LANG_BLOCK_HOST_TITLE, "100%");
	WebTableHeader("|" . FIREWALL_LANG_NICKNAME . "|" . NETWORK_LANG_IP . "|");
	echo $list;
	WebTableClose("100%");
	WebFormClose();
}

// vi: ts=4
?>
