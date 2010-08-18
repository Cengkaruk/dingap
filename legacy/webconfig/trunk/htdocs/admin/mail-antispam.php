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
require_once("../../api/Amavis.class.php");
require_once("../../api/Postfix.class.php");
require_once("../../api/Postgrey.class.php");
require_once("../../api/SpamAssassin.class.php");
require_once("../../api/AntispamUpdates.class.php");
require_once("mailfilter.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Subscription information
//
///////////////////////////////////////////////////////////////////////////////

// TODO: implement this better
require_once("clearcenter-status.inc.php");
$header = "<script type='text/javascript' src='/admin/clearcenter-status.js.php?service=" . AntispamUpdates::CONSTANT_NAME ."'></script>\n";

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE, "default", $header);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-antispam.png", WEB_LANG_PAGE_INTRO);
WebServiceStatus(AntispamUpdates::CONSTANT_NAME, "ClearSDN Antispam Updates");

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$amavis = new Amavis();
$postfix = new Postfix();
$postgrey = new Postgrey();
$spam = new Spamassassin();

try {
	if (isset($_POST['StartDaemon'])) {
		$amavis->SetAntispamState(true);
		$amavis->SetBootState(true);

		if ($amavis->GetRunningState())
			$amavis->Reset();
		else
			$amavis->SetRunningState(true);

	} else if (isset($_POST['StopDaemon'])) {
		$amavis->SetAntispamState(false);

		if (! $amavis->GetAntivirusState()) {
			$amavis->SetBootState(false);
			$amavis->SetRunningState(false);
		} else {
			$amavis->Reset();
		}

		$spam->SetBootState(false);
		$spam->SetRunningState(false);

	} else if (isset($_POST['UpdateConfig'])) {
		$amavis->SetSubjectTagState((bool)$_POST['subject_tag_state']);

        if (isset($_POST['subject_tag']))
            $amavis->SetSubjectTag($_POST['subject_tag']);

        if (isset($_POST['subject_tag_level']))
            $amavis->SetSubjectTagLevel($_POST['subject_tag_level']);

		$discard = (bool)$_POST['discard_state'];
		$discard_level = isset($_POST['discard_level']) ? $_POST['discard_level'] : -1;
		$quarantine = (bool)$_POST['quarantine_state'];
		$quarantine_level = isset($_POST['quarantine_level']) ? $_POST['quarantine_level'] : -1;

		$amavis->SetAntispamDiscardAndQuarantine($discard, $discard_level, $quarantine, $quarantine_level);
		$amavis->SetImageProcessingState((bool)$_POST['image_processing_state']);
		$amavis->Reset();
	} else if (isset($_POST['UpdateWhiteList'])) {
		$spam->SetWhiteList($_POST['list']);
		$amavis->Reset();
	} else if (isset($_POST['UpdateBlackList'])) {
		$spam->SetBlackList($_POST['list']);
		$amavis->Reset();
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['EditWhiteList'])) {
	DisplayList("whitelist");
} else if (isset($_POST['EditBlackList'])) {
	DisplayList("blacklist");
} else {
	WebDialogMailFilter("spamassassin");
	DisplayConfig();
	DisplayBlacklists();
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

function DisplayConfig()
{
	global $spam;
	global $amavis;

	try {
		$state = $amavis->GetAntispamState();
		$subject_tag = $amavis->GetSubjectTag();
		$subject_tag_level = $amavis->GetSubjectTagLevel();
		$subject_tag_state = $amavis->GetSubjectTagState();
		$image_processing_state = $amavis->GetImageProcessingState();
		$spaminfo = $amavis->GetAntispamDiscardAndQuarantine();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$discard_state = $spaminfo['discard'];
	$discard_level = $spaminfo['discard_level'];
	$quarantine_state = $spaminfo['quarantine'];
	$quarantine_level = $spaminfo['quarantine_level'];

	$subject_tag_level_options['2.0'] = '2.0';
	$subject_tag_level_options['2.5'] = '2.5';
	$subject_tag_level_options['3.0'] = '3.0 - ' . LOCALE_LANG_LOW;
	$subject_tag_level_options['3.5'] = '3.5';
	$subject_tag_level_options['4.0'] = '4.0';
	$subject_tag_level_options['4.5'] = '4.5';
	$subject_tag_level_options['5.0'] = '5.0 - ' . LOCALE_LANG_MEDIUM;
	$subject_tag_level_options['5.5'] = '5.5';
	$subject_tag_level_options['6.0'] = '6.0';
	$subject_tag_level_options['6.5'] = '6.5';
	$subject_tag_level_options['7.0'] = '7.0 - ' . LOCALE_LANG_HIGH;
	$subject_tag_level_options['7.5'] = '7.5';
	$subject_tag_level_options['8.0'] = '8.0';
	$subject_tag_level_options['8.5'] = '8.5';
	$subject_tag_level_options['9.0'] = '9.0 - ' . LOCALE_LANG_VERYHIGH;
	$subject_tag_level_options['9.5'] = '9.5';
	$subject_tag_level_options['10.0'] = '10.0';
	$subject_tag_level_options['15.0'] = '15.0';
	$subject_tag_level_options['20.0'] = '20.0';

	$discard_level_options['5'] = '5 - ' . LOCALE_LANG_VERYLOW;
	$discard_level_options['6'] = '6';
	$discard_level_options['7'] = '7';
	$discard_level_options['8'] = '8';
	$discard_level_options['9'] = '9';
	$discard_level_options['10'] = '10 - ' . LOCALE_LANG_LOW;
	$discard_level_options['11'] = '11';
	$discard_level_options['12'] = '12';
	$discard_level_options['13'] = '13';
	$discard_level_options['14'] = '14';
	$discard_level_options['15'] = '15 - ' . LOCALE_LANG_MEDIUM;
	$discard_level_options['16'] = '16';
	$discard_level_options['17'] = '17';
	$discard_level_options['18'] = '18';
	$discard_level_options['19'] = '19';
	$discard_level_options['20'] = '20 - ' . LOCALE_LANG_HIGH;
	$discard_level_options['21'] = '21';
	$discard_level_options['22'] = '22';
	$discard_level_options['23'] = '23';
	$discard_level_options['24'] = '24';
	$discard_level_options['25'] = '25 - ' . LOCALE_LANG_VERYHIGH;

	$quarantine_level_options['5'] = '5 - ' . LOCALE_LANG_VERYLOW;
	$quarantine_level_options['6'] = '6';
	$quarantine_level_options['7'] = '7';
	$quarantine_level_options['8'] = '8';
	$quarantine_level_options['9'] = '9';
	$quarantine_level_options['10'] = '10 - ' . LOCALE_LANG_LOW;
	$quarantine_level_options['11'] = '11';
	$quarantine_level_options['12'] = '12';
	$quarantine_level_options['13'] = '13';
	$quarantine_level_options['14'] = '14';
	$quarantine_level_options['15'] = '15 - ' . LOCALE_LANG_MEDIUM;
	$quarantine_level_options['16'] = '16';
	$quarantine_level_options['17'] = '17';
	$quarantine_level_options['18'] = '18';
	$quarantine_level_options['19'] = '19';
	$quarantine_level_options['20'] = '20 - ' . LOCALE_LANG_HIGH;
	$quarantine_level_options['21'] = '21';
	$quarantine_level_options['22'] = '22';
	$quarantine_level_options['23'] = '23';
	$quarantine_level_options['24'] = '24';
	$quarantine_level_options['25'] = '25 - ' . LOCALE_LANG_VERYHIGH;

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "450");
	echo "
		<tr>
			<td class='mytableheader' colspan='2'>" . WEB_LANG_DISCARD_POLICY . "</td>
		</tr>
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
			<td>" . WebDropDownEnabledDisabled("discard_state", $discard_state, 0, "togglediscardlevel();", "discard_state") . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_THRESHOLD . "</td>
			<td>" . WebDropDownHash("discard_level", $discard_level, $discard_level_options, 0, null, "discard_level") . "</td>
		</tr>
		<tr>
			<td class='mytableheader' colspan='2'>" . AMAVIS_LANG_QUARANTINE_POLICY . "</td>
		</tr>
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
			<td>" . WebDropDownEnabledDisabled("quarantine_state", $quarantine_state, 0, "togglequarantinelevel();", "quarantine_state") . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_THRESHOLD . "</td>
			<td>" . WebDropDownHash("quarantine_level", $quarantine_level, $quarantine_level_options, 0, null, "quarantine_level") . "</td>
		</tr>
		<tr>
			<td class='mytableheader' colspan='2'>" . AMAVIS_LANG_SUBJECT_TAG . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
			<td>" . WebDropDownEnabledDisabled("subject_tag_state", $subject_tag_state, 0, "togglesubjecttag();", "subject_tag_state") . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_THRESHOLD . "</td>
			<td>" . WebDropDownHash("subject_tag_level", $subject_tag_level, $subject_tag_level_options, 0, null, "subject_tag_level") . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . AMAVIS_LANG_SUBJECT_TAG . "</td>
			<td><input type='text' id='subject_tag' name='subject_tag' value='$subject_tag' /></td>
		</tr>
		<tr>
			<td class='mytableheader' colspan='2'>" . AMAVIS_LANG_IMAGE_PROCESSING . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_STATUS . "</td>
			<td>" . WebDropDownEnabledDisabled("image_processing_state", $image_processing_state) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&#160; </td>
			<td>" . WebButtonUpdate("UpdateConfig") . "</td>
		</tr>
	";
	WebTableClose("450");
	WebFormClose();

    echo "<script type='text/javascript'>togglediscardlevel()</script>";
    echo "<script type='text/javascript'>togglequarantinelevel()</script>";
    echo "<script type='text/javascript'>togglesubjecttag()</script>";
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayList()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayList($listtype)
{
	global $spam;

	if ($listtype == "whitelist") {
		$title = SPAMASSASSIN_LANG_WHITELIST;
		$action = "UpdateWhiteList";
		$list = $spam->GetWhiteList();
	} else {
		$title = SPAMASSASSIN_LANG_BLACKLIST;
		$action = "UpdateBlackList";
		$list = $spam->GetBlackList();
	}

	WebFormOpen();
	WebTableOpen($title, "300");
	echo "
		<tr>
			<td><textarea name='list' cols='60' rows='15'>$list</textarea></td>
		</tr>
		<tr>
			<td align='center'>" . WebButtonUpdate($action) . " " . WebButtonCancel("Cancel") . "</td>
		</tr>
	";
	WebTableClose("300");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayBlacklists
//
///////////////////////////////////////////////////////////////////////////////

function DisplayBlacklists()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIGURE_LISTS, "450");
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . SPAMASSASSIN_LANG_WHITELIST . "</td>
			<td>" . WebButtonEdit("EditWhiteList") . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>" . SPAMASSASSIN_LANG_BLACKLIST . "</td>
			<td>" . WebButtonEdit("EditBlackList") . "</td>
		</tr>
	";
	WebTableClose("450");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
