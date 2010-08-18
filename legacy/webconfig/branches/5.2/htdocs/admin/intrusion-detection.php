<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2005-2009 Point Clark Networks.
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
require_once("../../api/Snort.class.php");
require_once("../../api/IntrusionProtectionUpdates.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . IntrusionProtectionUpdates::CONSTANT_NAME . "'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-intrusion-detection.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$snort = new Snort();

try {
	if (isset($_POST['EnableBoot'])) {
		$snort->SetBootState(true);
	} else if (isset($_POST['DisableBoot'])) {
		$snort->SetBootState(false);
	} else if (isset($_POST['StartDaemon'])) {
		$snort->SetRunningState(true);
	} else if (isset($_POST['StopDaemon'])) {
		$snort->SetRunningState(false);
	} else if (isset($_POST['Update']) && isset($_POST['files'])) {
		$snort->SetActiveRules($_POST['files']);
		$snort->Reset();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

WebServiceStatus(IntrusionProtectionUpdates::CONSTANT_NAME, "ClearSDN Intrusion Protection Updates");
WebDialogDaemon("snort");
DisplayRules();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayRules()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayRules()
{
	global $snort;

	try {
		$availablerules = $snort->GetAvailableRules();
		$activerules = $snort->GetActiveRules();
		$ruledetails = $snort->GetRuleDetails();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	$securityrows = array();
	$policyrows = array();

	foreach ($availablerules as $ruleinfo) {
		$filename = $ruleinfo['filename'];
		$nickname = preg_replace("/\.rules/", "", $filename);

		$ischecked = (in_array($filename, $activerules)) ? 'CHECKED' : '';
		$description = isset($ruledetails[$filename]['description']) ? $ruledetails[$filename]['description'] : $nickname;

		if (in_array($filename, $activerules)) {
			$ischecked = "checked";
        } else {
			$ischecked = "";
        }

		$tablerow = "
		  <tr>
		  	<td nowrap><input type='checkbox' name='files[]' value='" . $ruleinfo['filename'] . "' $ischecked /></td>
		    <td nowrap>" . $nickname . "</td>
		    <td nowrap>" . $description . "</td>
		    <td nowrap>" . $ruleinfo['count'] . "</td>
		  </tr>\n
		";

		if (isset($ruledetails[$filename]['type'])) {
			if ($ruledetails[$filename]['type'] == Snort::TYPE_SECURITY)
				$securityrows[$description] = $tablerow;
			elseif ($ruledetails[$filename]['type'] == Snort::TYPE_POLICY)
				$policyrows[$description] = $tablerow;
		}
	}

	$securityhtml = "";
	$policyhtml = "";

	ksort($securityrows);
	ksort($policyrows);

	foreach ($securityrows as $row)
		$securityhtml .= $row;

	foreach ($policyrows as $row)
		$policyhtml .= $row;

	WebFormOpen();
	WebTableOpen(WEB_LANG_SECURITY_RULES, "100%");
	WebTableHeader(LOCALE_LANG_ENABLED . "|" . SNORT_LANG_GROUP_NAME . "|" . LOCALE_LANG_DESCRIPTION . "|" . WEB_LANG_RULES_COUNT);
	echo $securityhtml;
	echo "<tr><td colspan='4' align='center'>" . WebButtonUpdate('Update') . "</td></tr>";
	WebTableClose("100%");

	WebTableOpen(WEB_LANG_POLICY_RULES, "100%");
	WebTableHeader(LOCALE_LANG_ENABLED . "|" . SNORT_LANG_GROUP_NAME . "|" . LOCALE_LANG_DESCRIPTION . "|" . WEB_LANG_RULES_COUNT);
	echo $policyhtml;
	echo "<tr><td colspan='4' align='center'>" . WebButtonUpdate('Update') . "</td></tr>";
	WebTableClose("100%");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
