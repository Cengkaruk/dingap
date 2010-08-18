<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2006-2009 Point Clark Networks.
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
require_once("../../api/FirewallCustom.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, '/images/icon-firewall-custom.png', WEB_LANG_PAGE_INTRO, true);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$firewallcustom = new FirewallCustom();

if (isset($_POST['FirewallRestart'])) {
	$firewall = new Firewall();
	$firewall->Restart();
} else if (isset($_POST['Add'])) {
} else if (isset($_POST['ToggleRule'])) {
	try {
		$firewallcustom->ToggleRule(key($_POST['ToggleRule']), (current($_POST['ToggleRule']) == LOCALE_LANG_ENABLE) ? 1 : 0);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpRule'])) {
	try {
		$firewallcustom->SetRulePriority(key($_POST['UpRule']), FirewallCustom::MOVE_UP);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DownRule'])) {
	try {
		$firewallcustom->SetRulePriority(key($_POST['DownRule']), FirewallCustom::MOVE_DOWN);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['AddRule'])) {
	try {
		// TODO using 1/-1 as true/false.  Change to standard.
		$enabled = ($_POST['enabled'] == "-1") ? false : true;
		$firewallcustom->AddRule($_POST['entry'], $_POST['description'], $enabled, $_POST['priority']);
		unset($_POST);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['DeleteRule'])) {
	try {
		$firewallcustom->DeleteRule(key($_POST['DeleteRule']));
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['UpdateRule'])) {
	try {
		$firewallcustom->UpdateRule(key($_POST['UpdateRule']), $_POST['entry'], $_POST['description'], $_POST['enabled']);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
} else if (isset($_POST['EditRule'])) {
	DisplayEditRule(key($_POST['EditRule']));
}

try {
	if ($firewallcustom->IsFirewallRestartRequired()) {
		WebFormOpen();
		WebDialogWarning(WEB_LANG_FIREWALL_RESTART_REQUIRED .
			"<div style='text-align: center; padding: 10 10 10 10;'>".  WebButton("FirewallRestart", WEB_LANG_RESTART_FIREWALL) . "</div>"
		);
		WebFormClose();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (!isset($_POST['EditRule']))
	DisplayAddRule();

DisplaySummary();

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S 
/////////////////////////////////////////////////////////////////////////////// 

///////////////////////////////////////////////////////////////////////////////
//
// DisplaySummary()
//
///////////////////////////////////////////////////////////////////////////////

function DisplaySummary()
{
	global $firewallcustom;

	try {
		$rules = $firewallcustom->GetRules();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();

	WebTableOpen(WEB_LANG_SUMMARY, "100%");
	$rowcount = 0;
	foreach ($rules as $rule) {
		if ($rule['enabled']) {
			$toggle = LOCALE_LANG_DISABLE;
			$iconclass = "iconenabled";
			$rowclass = "rowenabled";
		} else {
			$toggle = LOCALE_LANG_ENABLE;
			$iconclass = "icondisabled";
			$rowclass = "rowdisabled";
		}
		$rowclass .= ($rowcount % 2) ? 'alt' : '';

		// TODO Will be nice to have a web-app framework to display a JS tooltip widget rather than use title
		echo "
			<tr class='$rowclass' title='" . $rule['description'] . "'>
				<td class='$iconclass'>&#160; </td>
				<td nowrap>" . $rule['entry'] . "</td>
				<td nowrap>" . 
					($rowcount == 0 ? "" : WebButton("UpRule[" . $rule['line'] . "]", "&uarr;")) . 
					($rowcount == count($rules) -1 ? "" : WebButton("DownRule[" . $rule['line'] . "]", "&darr;")) . 
				"</td> 
				<td nowrap align='right'>" . 
					WebButtonToggle("ToggleRule[" . $rule['line'] . "]", $toggle) . 
					WebButtonEdit("EditRule[" . $rule['line'] . "]") . 
					WebButtonDelete("DeleteRule[" . $rule['line'] . "]") . "
				</td>
			</tr>
		";
		$rowcount++;
	}

	if (empty($rules))
		echo "<tr><td colspan='4' align='center'>" . WEB_LANG_NO_CUSTOM_RULES . "</td></tr>";
	WebTableClose("100%");
	WebFormClose();

}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayAddRule()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayAddRule()
{
	global $firewallcustom;

	$enable_options = "<option value='1'" . ((!isset($_POST['enabled']) || $_POST['enabled']) ? " SELECTED" : "") . ">" . LOCALE_LANG_YES . "</option>" .
			   "<option value='-1'" . ($_POST['enabled'] < 0 ? " SELECTED" : "") . ">" . LOCALE_LANG_NO . "</option>";
	$priority_options = "<option value='1'" . ((!isset($_POST['priority']) || $_POST['priority']) ? " SELECTED" : "") . ">" . WEB_LANG_TOP . "</option>" .
			   "<option value='-1'" . ($_POST['priority'] < 0 ? " SELECTED" : "") . ">" . WEB_LANG_BOTTOM . "</option>";

	WebFormOpen();
	WebTableOpen(LOCALE_LANG_ADD, "100%");
	echo "<tr>";
	echo "<td class='mytablesubheader' width='20%'>" . WEB_LANG_RULE . "</td>";
	echo "<td width='80%'><input type='text' name='entry' value='" . $_POST['entry'] . "' style='width:100%;'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>" . WEB_LANG_DESCRIPTION . "</td>";
	echo "<td><input type='text' name='description' value='" . $_POST['description'] . "' style='width:100%;'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>" . WEB_LANG_ENABLE . "</td>";
	echo "<td><select name='enabled'>$enable_options</select></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>" . WEB_LANG_PRIORITY . "</td>";
	echo "<td><select name='priority'>$priority_options</select></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>&#160;</td>";
	echo "<td>" . WebButtonSave("AddRule") . "</td>";
	echo "</tr>";
	WebTableClose("100%");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayEditRule()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayEditRule($index)
{
	global $firewallcustom;

	$rule = $firewallcustom->GetRule($index);
	
	$enable_options = "<option value='1'" . ($rule['enabled'] ? " SELECTED" : "") . ">" . LOCALE_LANG_YES . "</option>" .
			   "<option value='0'" . (!$rule['enabled'] ? " SELECTED" : "") . ">" . LOCALE_LANG_NO . "</option>";
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_EDIT, "100%");
	echo "<tr>";
	echo "<td class='mytablesubheader' width='20%'>" . WEB_LANG_RULE . "</td>";
	echo "<td width='80%'><input type='text' name='entry' value='" . $rule['entry'] . "' style='width:100%;'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>" . WEB_LANG_DESCRIPTION . "</td>";
	echo "<td><input type='text' name='description' value='" . $rule['description'] . "' style='width:100%;'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>" . WEB_LANG_ENABLE . "</td>";
	echo "<td><select name='enabled'>$enable_options</select></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='mytablesubheader'>&#160;</td>";
	echo "<td>" . WebButtonSave("UpdateRule[$index]") . "</td>";
	echo "</tr>";
	WebTableClose("100%");
	WebFormClose();
}


// vi: syntax=php ts=4
?>
