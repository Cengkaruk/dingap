<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2007 Point Clark Networks.
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
require_once("../../api/Bandwidth.class.php");
require_once("../../api/Firewall.class.php");
require_once("../../api/Network.class.php");
require_once("../../api/IfaceManager.class.php");
require_once('../../api/Firewall.list.php');
require_once("../../api/BandwidthMonitor.class.php");
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . BandwidthMonitor::CONSTANT_NAME . "'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Page helpers
//
///////////////////////////////////////////////////////////////////////////////

$services = array();
$services['all'] = array(
	'name' => WEB_LANG_ALL_PORTS,
	'port' => Firewall::CONSTANT_ALL_PORTS);
foreach ($PORTS as $port) {
	if (!strcasecmp($port[0], 'special')) continue;
	else if (!strcasecmp($port[3], 'ftp')) {
		$service = array();
		$service['name'] = $port[3];
		$service['port'] = '20:21';
		$services[strtolower($port[3])] = $service;
		continue;
	}
	else if (!strcasecmp($port[3], 'imap')) {
		$service = array();
		$service['name'] = $port[3] . '/S';;
		$service['port'] = '143:993';
		$services[strtolower($port[3])] = $service;
		continue;
	}
	else if (!strcasecmp($port[3], 'pop3')) {
		$service = array();
		$service['name'] = $port[3] . '/S';;
		$service['port'] = '110:995';
		$services[strtolower($port[3])] = $service;
		continue;
	}
	else if (!strcasecmp($port[3], 'http')) {
		$service = array();
		$service['name'] = $port[3] . '/S';;
		$service['port'] = '80:443';
		$services[strtolower($port[3])] = $service;
		continue;
	}
	else if (!strcasecmp($port[3], 'sip')) {
		$service = array();
		$service['name'] = 'VoIP (SIP)';
		$service['port'] = $port[2];
		$services[strtolower($port[3])] = $service;
		continue;
	}
	else if (!strcasecmp($port[3], 'rtp') ||
		!strcasecmp($port[3], 'passive ftp') ||
		!strcasecmp($port[3], 'bpalogin') ||
		!strcasecmp($port[3], 'gnutella') ||
		!strcasecmp($port[3], 'icq/aim') ||
		!strcasecmp($port[3], 'ident') ||
		!strcasecmp($port[3], 'irc') ||
		!strcasecmp($port[3], 'ntp') ||
		!strcasecmp($port[3], 'webmail') ||
		!strcasecmp($port[3], 'webmin') ||
		!strcasecmp($port[3], 'webconfig') ||
		!strcasecmp($port[3], 'kazaa/morpheus') ||
		!strcasecmp($port[3], 'msn') ||
		!strcasecmp($port[3], 'imaps') ||
		!strcasecmp($port[3], 'pop3s') ||
		!strcasecmp($port[3], 'https') ||
		(strcasecmp($port[1], 'tcp') &&
		strcasecmp($port[1], 'udp'))) continue;
	$service = array();
	$service['name'] = $port[3];
	$service['port'] = $port[2];
	$services[strtolower($port[3])] = $service;
}
ksort($services);

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-bandwidth.png", WEB_LANG_PAGE_INTRO);
WebServiceStatus(BandwidthMonitor::CONSTANT_NAME, "ClearSDN Remote Bandwidth Monitor");

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewall = new Firewall();
$bandwidth = new Bandwidth();
$net = new Network();
$iface_manager = new IfaceManager();
// For language tags
$iface = new Iface();

// Form variables for AddBandwidthRule
$bw_name = isset($_POST['bw_name']) ? $_POST['bw_name'] : "";
$bw_ifn = isset($_POST['bw_ifn']) ? $_POST['bw_ifn'] : "";
$bw_src_addr = isset($_POST['bw_src_addr']) ? $_POST['bw_src_addr'] : "";
$bw_src_port = isset($_POST['bw_src_port']) ? $_POST['bw_src_port'] : "";
$bw_ip_low = isset($_POST['bw_ip_low']) ? $_POST['bw_ip_low'] : "";
$bw_ip_high = isset($_POST['bw_ip_high']) ? $_POST['bw_ip_high'] : "";
$bw_port = isset($_POST['bw_port']) ? $_POST['bw_port'] : "";
$bw_priority = isset($_POST['bw_priority']) ? $_POST['bw_priority'] : "";
$bw_rate_dir = isset($_POST['bw_rate_dir']) ? $_POST['bw_rate_dir'] : "";
$bw_rate = isset($_POST['bw_rate']) ? $_POST['bw_rate'] : "";
$bw_ceil = isset($_POST['bw_ceil']) ? $_POST['bw_ceil'] : "";
$bw_mode = isset($_POST['bw_mode']) ? $_POST['bw_mode'] : 0;
$bw_service = isset($_POST['bw_service']) ? $_POST['bw_service'] : 'http';
$bw_dir = isset($_POST['bw_dir']) ? $_POST['bw_dir'] : 0;
$bw_speed = isset($_POST['bw_speed']) ? $_POST['bw_speed'] : 0;

try {
	if (array_key_exists('bw_add_rule', $_POST)) {
		$ip = $bw_ip_low;
		if (strlen($bw_ip_high)) $ip = "$bw_ip_low:$bw_ip_high";
		if ($bw_rate_dir == '0') {
			$bw_down_speed = 0;
			$bw_down_ceil = 0;
			$bw_up_speed = $bw_rate;
			$bw_up_ceil = $bw_ceil;
			if ($bw_src_addr) $bw_src_addr = 0;
			else $bw_src_addr = 1;
			if ($bw_src_port) $bw_src_port = 0;
			else $bw_src_port = 1;
		} else {
			$bw_up_speed = 0;
			$bw_up_ceil = 0;
			$bw_down_speed = $bw_rate;
			$bw_down_ceil = $bw_ceil;
		}
		if (!strcmp('all', $bw_ifn)) {
			$ext_iface = $iface_manager->GetExternalInterfaces();
			foreach ($ext_iface as $ifn) {
				$bandwidth->AddBandwidthRule(
					$bw_name, $ifn, $bw_src_addr, $bw_src_port, $ip, $bw_port, $bw_priority,
					$bw_up_speed, $bw_up_ceil, $bw_down_speed, $bw_down_ceil
				);
			}
		}
		else {
			$bandwidth->AddBandwidthRule(
				$bw_name, $bw_ifn, $bw_src_addr, $bw_src_port, $ip, $bw_port, $bw_priority,
				$bw_up_speed, $bw_up_ceil, $bw_down_speed, $bw_down_ceil
			);
		}
		$firewall->Restart();
	} else if (array_key_exists('bw_add_basic_rule', $_POST)) {
			$id = sprintf('bw_basic_%s_%c%c%c%c%c',
				$bw_service,
				97 + rand() % 26,
				65 + rand() % 26,
				48 + rand() % 10,
				48 + rand() % 10,
				65 + rand() % 26);
			$bandwidth->AddBasicBandwidthRule($id, $bw_mode,
				$services[$bw_service], $bw_dir, $bw_speed, $bw_priority);
		$firewall->Restart();
		unset($_POST);
	} else if (array_key_exists('bw_toggle_rule', $_POST)) {
		list(
			$enabled, $ifn, $src_addr, $src_port, $ip, $port, $priority,
			$up_speed, $up_ceil, $down_speed, $down_ceil
		) = explode('|', key($_POST['bw_toggle_rule']));
		$bandwidth->ToggleEnableBandwidthRule(
			($enabled) ? false : true,
			$ifn, $src_addr, $src_port, $ip, $port, $priority, $up_speed, $up_ceil, $down_speed, $down_ceil
		);
		$firewall->Restart();
	} else if (array_key_exists('bw_delete_rule', $_POST)) {
		list(
			$ifn, $src_addr, $src_port, $ip, $port, $priority, $up_speed, $up_ceil, $down_speed, $down_ceil
		) = explode('|', key($_POST['bw_delete_rule']));
		$bandwidth->DeleteBandwidthRule(
			$ifn, $src_addr, $src_port, $ip, $port, $priority, $up_speed, $up_ceil, $down_speed, $down_ceil
		);
		$firewall->Restart();
	} else if (array_key_exists('bw_toggle_basic_rule', $_POST)) {
		list($enabled, $id) = explode('|', key($_POST['bw_toggle_basic_rule']));
		$bandwidth->ToggleEnableBasicBandwidthRule(($enabled) ? false : true, $id);
		$firewall->Restart();
	} else if (array_key_exists('bw_delete_basic_rule', $_POST)) {
		$id = key($_POST['bw_delete_basic_rule']);
		$bandwidth->DeleteBasicBandwidthRule($id);
		$firewall->Restart();
	} else if (isset($_POST['UpdateInterface'])) {
		$ifn = key($_POST['UpdateInterface']);
		$upstream = isset($_POST['bw_ifn_upstream'][$ifn]) ? $_POST['bw_ifn_upstream'][$ifn] : "";
		$downstream = isset($_POST['bw_ifn_downstream'][$ifn]) ? $_POST['bw_ifn_downstream'][$ifn] : "";
		$bandwidth->UpdateInterface($ifn, $upstream, $downstream);
	} else if (isset($_POST['DisableBandwidth'])) {
		$bandwidth->Disable();
	} else if (isset($_POST['EnableBandwidth'])) {
		$bandwidth->Enable();
	}

    $errors = $bandwidth->GetValidationErrors(true);

    if (empty($errors)) {
		$bw_name = '';
		$bw_ifn = 'all';
		$bw_src_addr = '0';
		$bw_src_port = '0';
		$bw_ip_low = '';
		$bw_ip_high = '';
		$bw_port = '';
		$bw_priority = '0';
		$bw_rate_dir = '1';
		$bw_rate = '';
		$bw_ceil = '';
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

$priority_options['0'] = LOCALE_LANG_VERYHIGH;
$priority_options['2'] = LOCALE_LANG_HIGH;
$priority_options['3'] = LOCALE_LANG_MEDIUM;
$priority_options['4'] = LOCALE_LANG_LOW;
$priority_options['6'] = LOCALE_LANG_VERYLOW;

try {
	$mode = $firewall->GetMode();
	$initialized = $bandwidth->IsInitialized();
	$ifaces = $bandwidth->GetInterfaces(); // TODO: this is an expensive method call, do it once
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if (($mode == Firewall::CONSTANT_TRUSTEDSTANDALONE) || ($mode == Firewall::CONSTANT_STANDALONE))
	DisplayModeWarning();

if ($initialized) {
	DisplayStatus();
	DisplayInterfaces();
	DisplayTabView();
} else {
	WebDialogWarning(BANDWIDTH_LANG_ERRMSG_PLEASE_CONFIGURE_NETWORK_SPEEDS);
	DisplayInterfaces();
}


WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayStatus()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStatus()
{
	global $bandwidth;

	try {
		$qos_mode = $bandwidth->GetState();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

    // TODO: Merge with WebDialogDaemon

    if ($qos_mode) {
        $status_button = WebButtonToggle("DisableBandwidth", DAEMON_LANG_STOP);
        $status = "<span class='ok'><b>" . DAEMON_LANG_RUNNING . "</b></span>";
    } else {
        $status_button = WebButtonToggle("EnableBandwidth", DAEMON_LANG_START);
        $status = "<span class='alert'><b>" . DAEMON_LANG_STOPPED . "</b></span>";
    }

    $content = "
        <form action='' method='post'>
        <table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>
            <tr>
                <td nowrap align='right'><b>" . DAEMON_LANG_STATUS . " -</b>&#160; </td>
                <td nowrap><b>$status</b></td>
                <td width='10'>&#160; </td>
                <td width='100'>$status_button</td>
                <td width='10'>&#160; </td>
                <td rowspan='2'>" . DAEMON_LANG_WARNING_START . "</td>
            </tr>
        </table>
        </form>
    ";

    WebDialogBox("dialogdaemon", WEBCONFIG_LANG_SERVER_STATUS, WEBCONFIG_DIALOG_ICON_DAEMON, $content);
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayInterfaces()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayInterfaces()
{
	global $bandwidth;
	global $ifaces;

	$entrylist = '';
    $index = 0;

	foreach ($ifaces as $iface => $info) {
		if ($info['configured']) {
			$iconclass = 'iconenabled';
			$rowclass = 'rowenabled';
		} else {
			$iconclass = 'icondisabled';
			$rowclass = 'rowdisabled';
		}

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$entrylist .= "
		  <tr class='$rowclass'>
			<td nowrap class='$iconclass'>&nbsp; </td>
			<td nowrap>$iface</td>
			<td nowrap><input type='text' name='bw_ifn_upstream[$iface]' value='" . $info['upstream'] . "' style='width:45px' /> " . WEB_LANG_UNITS_KBIT . "</td>
			<td nowrap><input type='text' name='bw_ifn_downstream[$iface]' value='" . $info['downstream'] . "' style='width:45px' /> " . WEB_LANG_UNITS_KBIT . "</td>
			<td nowrap>" .
				WebButtonUpdate("UpdateInterface[$iface]") . "
			</td>
		  </tr>
		";
	}

	WebFormOpen();
	WebTableOpen(BANDWIDTH_LANG_UPLOADSPEED . " / " . BANDWIDTH_LANG_DOWNLOADSPEED, "100%");
	WebTableHeader(
		"&nbsp; |" .
		IFACE_LANG_INTERFACE . '|' . 
		BANDWIDTH_LANG_UPLOADSPEED . "|" .
		BANDWIDTH_LANG_DOWNLOADSPEED . "|" .
		'&nbsp; '
	);
	echo $entrylist;
	WebTableClose("100%");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayTabView()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayTabView()
{
	$bw_active_tab = isset($_REQUEST['bw_active_tab']) ? $_REQUEST['bw_active_tab'] : 'rules';

	$tabinfo['rules']['title'] = WEB_LANG_BANDWIDTH_TITLE;
	$tabinfo['rules']['contents'] = GetBandwidthRulesTab();
	$tabinfo['add_rule']['title'] = WEB_LANG_BANDWIDTH_ADD_RULE_TITLE;
	$tabinfo['add_rule']['contents'] = GetAddBandwidthRuleTab();
	$tabinfo['add_advanced_rule']['title'] = WEB_LANG_BANDWIDTH_ADD_ADVANCED_RULE_TITLE;
	$tabinfo['add_advanced_rule']['contents'] = GetAddAdvancedBandwidthRuleTab();

	echo "<div style='width: 100%'>";
	WebTab(WEB_LANG_PAGE_TITLE, $tabinfo, $bw_active_tab);
	echo "</div>";
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBandwidthRules()
//
///////////////////////////////////////////////////////////////////////////////

function GetBandwidthRulesTab()
{
	global $bandwidth;
	global $priority_options;
	global $services;
	global $ifaces;

	ob_start();

	try {
		$rules = $bandwidth->GetBandwidthRules();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return ob_get_clean();
	}

	$entries = array();

	foreach ($rules as $rule)
		$entries[$rule['wanif']][] = $rule;

	ksort($entries);

    $index = 0;
	$wanif = null;

	$keys = array();
	$basic = array();

	$directions = array();
	$directions[Bandwidth::DIR_ORIGINATING_LAN] = WEB_LANG_DIR_ORIGINATING_LAN;
	$directions[Bandwidth::DIR_DESTINED_LAN] = WEB_LANG_DIR_DESTINED_LAN;
	$directions[Bandwidth::DIR_ORIGINATING_GW] = WEB_LANG_DIR_ORIGINATING_GW;
	$directions[Bandwidth::DIR_DESTINED_GW] = WEB_LANG_DIR_DESTINED_GW;

	foreach($rules as $rule) {
		if ($rule['type'] != Bandwidth::TYPE_BASIC)
			continue;

		if (in_array($rule['name'], $keys))
			continue;

		$keys[] = $rule['name'];

		if ($rule['enabled']) {
			$toggle = LOCALE_LANG_DISABLE;
			$iconclass = 'iconenabled';
			$rowclass = 'rowenabled';
		} else {
			$toggle = LOCALE_LANG_ENABLE;
			$iconclass = 'icondisabled';
			$rowclass = 'rowdisabled';
		}

		$mode = '-';

		if ($rule['upstream'] == $rule['upstream_ceil'])
			$mode = WEB_LANG_MODE_LIMIT;
		else if ($rule['upstream_ceil'] == 0)
			$mode = WEB_LANG_MODE_RESERVE;

		$service = $rule['name'];
		if (preg_match('/^bw_basic_/', $rule['name'])) {
			list($a, $b, $key, $id) = explode('_', $rule['name']);
			if (array_key_exists($key, $services))
				$service = $services[$key]['name'];
		}

		$direction = $directions[$rule['direction']];

		$rowclass .= ($index % 2) ? "alt" : "";
		$index++;

		$basic[] = "
		  <tr class='$rowclass'>
			<td nowrap class='$iconclass'>&nbsp; </td>
			<td nowrap>$mode</td>
			<td nowrap>$service</td>
			<td nowrap>$direction</td>
			<td nowrap>$rule[upstream]</td>
			<td nowrap>" . $priority_options[$rule['priority']] . "</td>
			<td nowrap width='1%'>" .
				WebButtonDelete("bw_delete_basic_rule[$rule[name]]") .
				WebButtonToggle("bw_toggle_basic_rule[$rule[enabled]|$rule[name]]", $toggle) . "
			</td>
		  </tr>
		";
	}

	$advanced = array();

	foreach ($entries as $iface => $rules) {
		if (count($entries) > 1)
			$advanced[] = "<tr><td class='mytableheader' colspan='10'>" . IFACE_LANG_INTERFACE . ': ' . $iface . "</td><tr>";

		foreach($rules as $rule) {
			if ($rule['type'] == Bandwidth::TYPE_BASIC)
				continue;

			$rate = '-';
			$direction = '0';

			if ($rule['upstream'] > 0) {
				$rate = $rule['upstream'];
				if ($rule['upstream_ceil'] == 0) {
					if (isset($ifaces[$wanif]['upstream']))
						$rate .= ' / ' . $ifaces[$wanif]['upstream'];
				} else {
					$rate .= ' / ' . $rule['upstream_ceil'];
				}
			}
			else if ($rule['downstream'] > 0) {
				$rate = $rule['downstream'];
				$direction = '1';
				if ($rule['downstream_ceil'] == 0) {
					if (isset($ifaces[$wanif]['downstream']))
						$rate .= ' / ' . $ifaces[$wanif]['downstream'];
				} else {
					$rate .= ' / ' . $rule['downstream_ceil'];
				}
			}
	
			if ($rule['enabled']) {
				$toggle = LOCALE_LANG_DISABLE;
				$iconclass = 'iconenabled';
				$rowclass = 'rowenabled';
			} else {
				$toggle = LOCALE_LANG_ENABLE;
				$iconclass = 'icondisabled';
				$rowclass = 'rowdisabled';
			}
	
			$rowclass .= ($index % 2) ? "alt" : "";
			$index++;
	
			$key = sprintf("%s|%d|%d|%s|%d|%d|%d|%d|%d|%d",
				$rule['wanif'], $rule['src_addr'], $rule['src_port'], $rule['host'], $rule['port'], $rule['priority'],
				$rule['upstream'], $rule['upstream_ceil'], $rule['downstream'], $rule['downstream_ceil']);

			if ($direction == '1') {
				$src_addr_icon = ($rule['src_addr'] == 0 ? "<img src='/images/deprecated/icon-bandwidth-destination.gif' alt='destination' align='absbottom'>" : "<img src='/images/deprecated/icon-bandwidth-source.gif' alt='source' align='absbottom'>");
				$src_port_icon = ($rule['src_port'] == 0 ? "<img src='/images/deprecated/icon-bandwidth-destination.gif' alt='destination' align='absbottom'>" : "<img src='/images/deprecated/icon-bandwidth-source.gif' alt='source' align='absbottom'>");
			} else {
				$src_addr_icon = ($rule['src_addr'] != 0 ? "<img src='/images/deprecated/icon-bandwidth-destination.gif' alt='destination' align='absbottom'>" : "<img src='/images/deprecated/icon-bandwidth-source.gif' alt='source' align='absbottom'>");
				$src_port_icon = ($rule['src_port'] != 0 ? "<img src='/images/deprecated/icon-bandwidth-destination.gif' alt='destination' align='absbottom'>" : "<img src='/images/deprecated/icon-bandwidth-source.gif' alt='source' align='absbottom'>");
			}
			if (!strlen($rule['port'])) $src_port_icon = '';

			$advanced[] = "
			  <tr class='$rowclass'>
				<td nowrap class='$iconclass'>&nbsp; </td>
				<td nowrap>" . ((strlen($rule['name'])) ? $rule['name'] : '-') . "</td>
				<td nowrap>$src_addr_icon " .
					 ((strlen($rule['host'])) ? $rule['host'] : '-') . "
				</td>
				<td nowrap>$src_port_icon " .
					((strlen($rule['port'])) ? $rule['port'] : WEB_LANG_ALL_PORTS) . "
				</td>
				<td nowrap>" . $priority_options[$rule['priority']] . "</td>
				<td nowrap>" . (($direction == '0') ? BANDWIDTH_LANG_UPLOADSPEED : BANDWIDTH_LANG_DOWNLOADSPEED) . "</td>
				<td nowrap>$rate</td>
				<td nowrap width='1%'>" .
					WebButtonDelete("bw_delete_rule[$key]") .
					WebButtonToggle("bw_toggle_rule[$rule[enabled]|$key]", $toggle) . "
				</td>
			  </tr>
			";
		}
	}

	WebFormOpen();

	if (!count($basic) && !count($advanced)) {
		echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
		echo("<tr><td align='center'>" . FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED . "</td></tr>\n");
		echo('</table>');
	} else {
		if (count($basic)) {
			echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
			WebTableHeader(
				"&nbsp; |" .
				WEB_LANG_MODE . "|" .
				WEB_LANG_SERVICE . "|" .
				WEB_LANG_DIR . "|" .
				WEB_LANG_RATE . "|" .
				BANDWIDTH_LANG_GREED . "|" .
				"&nbsp; "
			);

			foreach ($basic as $entry) echo $entry;
			echo('</table>');
		}

		if (count($advanced)) {
			echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
			WebTableHeader(
				"&nbsp; |" .
				FIREWALL_LANG_NICKNAME . "|" . 
				NETWORK_LANG_IP . "|" .
				NETWORK_LANG_PORT . "|" .
				BANDWIDTH_LANG_GREED . "|" .
				WEB_LANG_DIR . "|" .
				WEB_LANG_RATE . " / " . WEB_LANG_CEILING . "|" .
				"&nbsp; "
			);

			foreach ($advanced as $entry) echo $entry;

			// TODO: these are not locale friendly icons, but ...
			echo "<tr><td class='mytablelegend' colspan='10'>";
			echo "<img src='/images/deprecated/icon-bandwidth-destination.gif' alt='destination' align='absbottom'> " . WEB_LANG_DESTINATION . "&nbsp; &nbsp;";
			echo "<img src='/images/deprecated/icon-bandwidth-source.gif' alt='source' align='absbottom'> " . WEB_LANG_SOURCE;
			echo "</td></tr>";

			echo('</table>');
		}
	}
	echo "<input type='hidden' name='bw_active_tab' value='rules'>";
	WebFormClose();

	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetAddBandwidthRuleTab()
//
///////////////////////////////////////////////////////////////////////////////

function GetAddBandwidthRuleTab()
{
	global $services;
	global $bw_priority;
	global $priority_options;

	if (empty($bw_priority))
		$bw_priority = 3;

	$protocols = array();

	foreach ($services as $key => $service)
		$protocols[$key] = $service['name'];

	$bw_dir = (empty($_POST['bw_dir'])) ? Bandwidth::DIR_DESTINED_LAN : $_POST['bw_dir'];
	$bw_mode = (empty($_POST['bw_mode'])) ? Bandwidth::MODE_RESERVE : $_POST['bw_mode'];
	$bw_speed = (empty($_POST['bw_speed'])) ? '' : $_POST['bw_speed'];
	$bw_service = (empty($_POST['bw_service'])) ? 'http' : $_POST['bw_service'];

	$mode = array();
	$mode[Bandwidth::MODE_LIMIT] = WEB_LANG_MODE_LIMIT;
	$mode[Bandwidth::MODE_RESERVE] = WEB_LANG_MODE_RESERVE;

	$direction = array();
	$direction[Bandwidth::DIR_ORIGINATING_LAN] = WEB_LANG_DIR_ORIGINATING_LAN;
	$direction[Bandwidth::DIR_DESTINED_LAN] = WEB_LANG_DIR_DESTINED_LAN;
	$direction[Bandwidth::DIR_ORIGINATING_GW] = WEB_LANG_DIR_ORIGINATING_GW;
	$direction[Bandwidth::DIR_DESTINED_GW] = WEB_LANG_DIR_DESTINED_GW;

	ob_start();
	WebFormOpen();
	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		WEB_LANG_MODE,
		WebDropDownHash('bw_mode', $bw_mode, $mode));
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		WEB_LANG_SERVICE,
		WebDropDownHash('bw_service', $bw_service, $protocols));
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		WEB_LANG_DIR,
		WebDropDownHash('bw_dir', $bw_dir, $direction));
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		WEB_LANG_RATE,
		"<input type='text' name='bw_speed' value='$bw_speed' style='width:45px' /> " .
		WEB_LANG_UNITS_KBIT);
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		BANDWIDTH_LANG_GREED,
		WebDropDownHash('bw_priority', $bw_priority, $priority_options));
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		'&nbsp;',
		WebButtonAdd('bw_add_basic_rule'));
	echo("</table>");
	echo("<input type='hidden' name='bw_active_tab' value='add_rule'>\n");
	WebFormClose();

	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetAddAdvancedBandwidthRuleTab()
//
///////////////////////////////////////////////////////////////////////////////

function GetAddAdvancedBandwidthRuleTab()
{
	global $bandwidth;
	global $iface_manager;
	global $bw_name;
	global $bw_ifn;
	global $bw_src_addr;
	global $bw_src_port;
	global $bw_ip_low;
	global $bw_ip_high;
	global $bw_port;
	global $bw_priority;
	global $bw_rate_dir;
	global $bw_rate;
	global $bw_ceil;
	global $priority_options;

	ob_start();

	try {
		$ext_iface = $iface_manager->GetExternalInterfaces();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return ob_get_clean();
	}

	WebFormOpen();
	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		FIREWALL_LANG_NICKNAME,
		"<input type='text' name='bw_name' value='$bw_name' style='width:75px' />");
	if (count($ext_iface) > 1) {
		$options = array();
		$options['all'] = LOCALE_LANG_ALL;
		foreach ($ext_iface as $iface) $options[$iface] = $iface;
		printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
			IFACE_LANG_INTERFACE,
			WebDropDownHash('bw_ifn', $bw_ifn, $options));
	} else echo "<input type='hidden' name='bw_ifn' value='all'>";

	$options = array();
	$options[0] = WEB_LANG_DESTINATION;
	$options[1] = WEB_LANG_SOURCE;
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		NETWORK_LANG_IP . ' / ' . NETWORK_LANG_IP_RANGE,
		WebDropDownHash('bw_src_addr', $bw_src_addr, $options) . "&nbsp;" .
		"<input type='text' name='bw_ip_low' value='$bw_ip_low' style='width:90px' /> : " .
		"<input type='text' name='bw_ip_high' value='$bw_ip_high' style='width:90px' />\n");
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		NETWORK_LANG_PORT,
		WebDropDownHash('bw_src_port', $bw_src_port, $options) . "&nbsp;" .
		"<input type='text' name='bw_port' value='$bw_port' style='width:30px' />\n");
	$options = array();
	$options[0] = BANDWIDTH_LANG_UPLOADSPEED;
	$options[1] = BANDWIDTH_LANG_DOWNLOADSPEED;
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		WEB_LANG_DIR,
		WebDropDownHash('bw_rate_dir', $bw_rate_dir, $options));
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		WEB_LANG_RATE,
		"<input type='text' name='bw_rate' value='$bw_rate' style='width:45px' /> " .
		WEB_LANG_UNITS_KBIT);
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		WEB_LANG_CEILING,
		"<input type='text' name='bw_ceil' value='$bw_ceil' style='width:45px' /> " .
		WEB_LANG_UNITS_KBIT);
	printf("<tr><td width='20%%' class='mytablesubheader' nowrap>%s</td><td>%s</td></tr>\n",
		BANDWIDTH_LANG_GREED,
		WebDropDownHash('bw_priority', $bw_priority, $priority_options));

	echo("<tr><td width='20%%' class='mytablesubheader' nowrap>&nbsp;<td>" .
		WebButtonAdd('bw_add_rule') . "</td></tr>\n");
	echo("<input type='hidden' name='bw_active_tab' value='add_advanced_rule'>\n");
	echo("</table>");
	WebFormClose();

	return ob_get_clean();
}

// vim: ts=4
?>
