<?php

/////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2006 Point Clark Networks.
// Created by: Michel Scherhage [techlab@dhd4all.com]
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Postfix.class.php");
require_once("../../gui/PostfixReport.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-postfix.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

$type = isset($_POST['type']) ? $_POST['type'] : PostfixReport::TIME_TODAY;
$maxrecords = isset($_POST['maxrecords']) ? $_POST['maxrecords'] : 10;

try {
	$postfix = new Postfix(); // For locale data
	$report = new PostfixReport($type);

	if (isset($_POST[PostfixReport::TYPE_DOMAIN_SUMMARY_DELIVERED])) {
		$report->GetDomainSummaryDelivered(100000);
	} else if (isset($_POST[PostfixReport::TYPE_DOMAIN_SUMMARY_RECEIVED])) {
		$report->GetDomainSummaryReceived(100000);
	} else if (isset($_POST[PostfixReport::TYPE_RECIPIENTS_BY_SIZE])) {
		$report->GetRecipientsBySize(100000);
	} else if (isset($_POST[PostfixReport::TYPE_RECIPIENTS])) {
		$report->GetRecipients(100000);
	} else if (isset($_POST[PostfixReport::TYPE_SENDERS])) {
		$report->GetSenders(100000);
	} else if (isset($_POST[PostfixReport::TYPE_SENDERS_BY_SIZE])) {
		$report->GetSendersBySize(100000);
	} else if (isset($_POST[PostfixReport::TYPE_BOUNCED])) {
		$report->GetMessageBounceDetail(100000);
	} else if (isset($_POST[PostfixReport::TYPE_REJECTED])) {
		$report->GetMessageRejectDetail(100000);
	} else if (isset($_POST[PostfixReport::TYPE_DISCARDED])) {
		$report->GetMessageDiscardDetail(100000);
	} else if (isset($_POST[PostfixReport::TYPE_DELIVERY_FAILURES])) {
		$report->GetSmtpDeliveryFailures(100000);
	} else if (isset($_POST[PostfixReport::TYPE_WARNING])) {
		$report->GetWarnings(100000);
	} else {
		DisplayReportSettings($type, $maxrecords);

		if ($report->IsDataAvailable()) {
			$report->GetDashboardSummary();
			if ($type == PostfixReport::TIME_MONTH)
				$report->GetFullReport();
			$report->GetDomainSummaryDelivered($maxrecords);
			$report->GetDomainSummaryReceived($maxrecords);
			$report->GetRecipients($maxrecords);
			$report->GetRecipientsBySize($maxrecords);
			$report->GetSenders($maxrecords);
			$report->GetSendersBySize($maxrecords);
			$report->GetMessageBounceDetail($maxrecords);
			$report->GetMessageRejectDetail($maxrecords);
			$report->GetMessageDiscardDetail($maxrecords);
			$report->GetSmtpDeliveryFailures($maxrecords);
			$report->GetWarnings($maxrecords);
		} else {
			WebDialogInfo(REPORT_LANG_NO_STATS);
		}
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

WebFooter();


///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayReportSettings()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayReportSettings($type, $maxrecords)
{
	global $report;

	$types = $report->GetTypes();

	$records = array();
	$records[5] = 5;
	$records[10] = 10;
	$records[20] = 20;
	$records[50] = 50;
	$records[100] = 100;
	$records[300] = 300;
	$records[100000] = LOCALE_LANG_ALL;

	WebFormOpen();
	WebTableOpen(REPORT_LANG_OVERVIEW, "300");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . REPORT_LANG_REPORT_PERIOD . "</td>
		<td nowrap>" . WebDropdownHash("type", $type, $types) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . REPORT_LANG_NUMBER_OF_RECORDS . "</td>
		<td nowrap>" . WebDropdownHash("maxrecords", $maxrecords, $records) . "</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader'>&#160; </td>
		<td nowrap>" . WebButtonGo("SelectReport") . "</td>
	  </tr>
	";
	WebTableClose("300");
	WebFormClose();
}

?>
