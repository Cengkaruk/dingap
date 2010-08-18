<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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

require_once('../../gui/Webconfig.inc.php');
require_once('../../api/Daemon.class.php');
require_once('../../api/Layer7Filter.class.php');
require_once('../../api/FirewallLayer7Filter.class.php');
require_once('../../api/Firewall.class.php');
require_once("firewall-common.inc.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-protocol-filter.png', WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// F O R M  P O S T S
//
///////////////////////////////////////////////////////////////////////////////

$l7 = new Layer7Filter();

try {
	$l7->GetProtocols($groups, $patterns);

	if (isset($_POST['StartDaemon'])) {
		$patterns_exist = false;
		foreach ($patterns as $pattern) {
			if ($pattern['enabled']) {
				$patterns_exist = true;
				break;
			}
		}

		if ($patterns_exist) {
			$l7->SetRunningState(true);
			$l7->SetBootState(true);
			$firewall = new FirewallLayer7Filter();
			$firewall->SetProtocolFilterState(true);
			$firewall->Restart();
		} else {
			WebDialogWarning(LAYER7FILTER_LANG_NO_FILTERS);
		}
	} else if (isset($_POST['StopDaemon'])) {
		$l7->SetRunningState(false);
		$l7->SetBootState(false);
		$firewall = new FirewallLayer7Filter();
		$firewall->SetProtocolFilterState(false);
		$firewall->Restart();
	} else if (array_key_exists('l7_protocol', $_POST)) {
		$l7->ToggleProtocol($patterns, key($_POST['l7_protocol']));
		$l7->CommitChanges($patterns);
	} else if (array_key_exists('l7_block_all', $_POST)) {
		foreach ($patterns as $pattern) {
			if (strcasecmp($_POST['group'], 'all') &&
				!in_array($_POST['group'], $pattern['groups'])) continue;
			$l7->EnableProtocol($patterns, $pattern['name']);
		}
		$l7->CommitChanges($patterns);
	} else if (array_key_exists('l7_unblock_all', $_POST)) {
		foreach ($patterns as $pattern) {
			if (strcasecmp($_POST['group'], 'all') &&
				!in_array($_POST['group'], $pattern['groups'])) continue;
			$l7->DisableProtocol($patterns, $pattern['name']);
		}
		$l7->CommitChanges($patterns);
	} else if (array_key_exists('l7_bypass_add', $_POST)) {
		$firewall = new FirewallLayer7Filter();
		$firewall->AddException($_POST['l7_bypass_nickname'], $_POST['l7_bypass_ip']);
		$firewall->Restart();
	} else if (array_key_exists('l7_bypass_delete', $_POST)) {
		$firewall = new FirewallLayer7Filter();
		$firewall->DeleteException(key($_POST['l7_bypass_delete']));
		$firewall->Restart();
	} else if (array_key_exists('l7_bypass_toggle', $_POST)) {
		$firewall = new FirewallLayer7Filter();
		$firewall->ToggleException(key($_POST['l7_bypass_toggle']));
		$firewall->Restart();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
	WebFooter();
	return;
}

///////////////////////////////////////////////////////////////////////////////
//
// M A I N
//
///////////////////////////////////////////////////////////////////////////////

try {
	$firewalltest = new Firewall();
	$mode = $firewalltest->GetMode();
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

if (($mode == Firewall::CONSTANT_TRUSTEDSTANDALONE) || ($mode == Firewall::CONSTANT_STANDALONE))
	DisplayModeWarning();
else
	WebDialogDaemon('l7-filter', false);

DisplayTabView();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayTabView()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayTabView()
{
	$l7_active_tab = isset($_REQUEST['l7_active_tab']) ? $_REQUEST['l7_active_tab'] : 'protocols';

	$tabinfo['protocols']['title'] = WEB_LANG_PROTOCOLS;
	$tabinfo['protocols']['contents'] = GetProtocolsTab();
	$tabinfo['bypass']['title'] = WEB_LANG_BYPASS_TITLE;
	$tabinfo['bypass']['contents'] = GetExceptionsTab();

	echo "<div style='width: 100%'>";
	WebTab(WEB_LANG_PAGE_TITLE, $tabinfo, $l7_active_tab);
	echo "</div>";
}

///////////////////////////////////////////////////////////////////////////////
//
// GetExceptionsTab: Display hosts/addresses exempt from protocol filtering
//
///////////////////////////////////////////////////////////////////////////////

function GetExceptionsTab()
{
	ob_start();

	$firewall = new FirewallLayer7Filter();

	try {
		$exceptions = $firewall->GetExceptions();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	WebFormOpen();
	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";

	WebTableHeader('&nbsp;|' . 
		FIREWALL_LANG_NICKNAME . '|' .
		FIREWALL_LANG_DOMAIN_IP . '|');

	$index = 0;
	foreach ($exceptions as $rule) {
		$name = (strlen($rule['name'])) ? $rule['name'] : "-";
		if ($rule['enabled']) {
			$icon = 'iconenabled';
			$class = 'rowenabled';
			$toggle = LOCALE_LANG_DISABLE;
		} else {
			$icon = 'icondisabled';
			$class = 'rowdisabled';
			$toggle = LOCALE_LANG_ENABLE;
		}
		$class .= ($index % 2) ? 'alt' : '';

		printf('<tr class="%s"><td class="%s">&nbsp;</td><td nowrap>%s</td><td nowrap>%s</td><td nowrap>%s</td></tr>',
			$class, $icon, $name, $rule['ip'],
			WebButtonDelete("l7_bypass_delete[{$rule['ip']}]") .
			WebButtonToggle("l7_bypass_toggle[{$rule['ip']}]", $toggle));
		$index++;
	}

	if (!count($exceptions)) {
		printf("<tr><td colspan='4' align='center'>%s</td></tr>\n",
			FIREWALL_LANG_ERRMSG_RULES_NOT_DEFINED);
	}

	printf("<tr><td class='mytableheader' colspan='4'>%s</td></tr>\n", LOCALE_LANG_ADD);

	echo "
		<tr>
			<td colspan='2' nowrap>
				<input type='text' name='l7_bypass_nickname' />&#160;
			</td>
			<td nowrap>
				<input type='text' name='l7_bypass_ip' />
			</td>
			<td nowrap style='width: 1%'>" . WebButtonAdd('l7_bypass_add') . "</td>
		</tr>
	";
	echo "</table>\n";
	echo "<input type='hidden' name='l7_active_tab' value='bypass'>";
	WebFormClose();

	return ob_get_clean();
}

///////////////////////////////////////////////////////////////////////////////
//
// GetProtocolsTab: Display list of protocol filter patterns
//
///////////////////////////////////////////////////////////////////////////////

function GetProtocolsTab()
{
	global $groups;
	global $patterns;

	ob_start();

	if (!array_key_exists('group', $_POST))
		$_POST['group'] = 'all';

	WebFormOpen();
	echo "<table cellspacing='0' cellpadding='5' width='100%' border='0' class='tablebody'>\n";
	printf("<tr><td class='mytableheader' colspan='5'>%s: %s %s %s</td></tr>\n",
		WEB_LANG_FILTER_BY_GROUP,
		WebDropDownHash('group', $_POST['group'], $groups, 0, 'form.submit()'),
		WebButton('l7_block_all', WEB_LANG_BLOCK_ALL, WEBCONFIG_ICON_CHECKMARK),
		WebButton('l7_unblock_all', WEB_LANG_UNBLOCK_ALL, WEBCONFIG_ICON_XMARK));
	WebTableHeader('&nbsp;|' . 
		LAYER7FILTER_LANG_PROTOCOL_NAME . "|" .
		WEB_LANG_PROTOCOL_GROUPS . "|" .
		LOCALE_LANG_HELP . "|");

	$index = 0;

	foreach ($patterns as $pattern) {
		if (strcasecmp($_POST['group'], 'all'))
			if (!in_array($_POST['group'], $pattern['groups'])) continue;

		if ($pattern['enabled']) {
			$icon = 'iconenabled';
			$class = 'rowenabled';
			$toggle = WEB_LANG_UNBLOCK;
		} else {
			$icon = 'icondisabled';
			$class = 'rowdisabled';
			$toggle = WEB_LANG_BLOCK;
		}

		$class .= ($index % 2) ? 'alt' : '';

		$group_list = array();

		foreach ($pattern['groups'] as $group) {
			if (!array_key_exists($group, $groups)) continue;
			$group_list[] = $groups[$group];
		}

		if (count($group_list))
			$group_list = implode(', ', $group_list);
		else
			$group_list = WEB_LANG_GROUP_NONE;

		$desc = preg_replace('@(http://[A-z0-9:/_.-]*)@', '', $pattern['desc']);
		$wiki = '';

		if ($pattern['wiki'] != null) {
			$links = explode(' ', $pattern['wiki']);
			foreach ($links as $link) {
				$link = preg_replace("/.*\//", "", $link);
				$wiki .= sprintf('<a target="_blank" href="%s">%s</a>',
					$_SESSION['system_redirect'] . "/protocol-filter/pattern/" . $link,
					WEBCONFIG_ICON_EXTERNAL_LINK);
			}
		}

		printf('<tr class="%s"><td class="%s">&nbsp;</td><td>%s</td><td>%s</td><td nowrap>%s</td><td>',
			$class, $icon, $desc, $group_list, $wiki);

		echo WebButtonToggle("l7_protocol[{$pattern['name']}]", $toggle) . "</td></tr>\n";

		$index++;
	}

	echo "</table>\n";
	echo "<input type='hidden' name='l7_active_tab' value='protocols'>";
	WebFormClose();

	return ob_get_clean();
}

// vim: ts=4
?>
