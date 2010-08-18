<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2009 Point Clark Networks.
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

require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// DisplayTime()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayTime()
{
	global $ntptime;
	global $ntpd;

	try {
		$timezones = $ntptime->GetTimeZoneList();
		$timezone = $ntptime->GetTimeZone();
		$ntp_state = $ntpd->GetRunningState();
		date_default_timezone_set($timezone);
	} catch (TimezoneNotSetException $e) {
		// Not the end of the world
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	// Date
	//-----

	$thedate = strftime("%b %e %Y");
	$thetime = strftime("%T %Z");

	// Time zone
	//----------

	$timezone_select = "";

	foreach ($timezones as $timeitem) {
		$selected = ($timezone == $timeitem['fullzone']) ? "selected" : "";
		$timeitem_html = preg_replace("/_/", " ", $timeitem['fullzone']);
		$timeitem_html = preg_replace("/\//", " - ", $timeitem_html);
		$timezone_select .= "<option $selected value='$timeitem[fullzone]'>$timeitem_html</option>\n";
	}

	// Time Server
	//------------

	$state_dropdown = WebDropDownEnabledDisabled("autosync", $ntp_state);

	// Time Synchronize
	//-----------------

	$buttonopts['onclick'] = 'setNtpTime()';
	$buttonopts['type'] = 'button';

	// HTML
	//-----

	WebFormOpen();
	WebTableOpen(WEB_LANG_TIME_CONFIG_TITLE, "100%");
	echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . TIME_LANG_DATE . " / " . TIME_LANG_TIME . "</td>
			<td nowrap>$thedate $thetime</td>
		</tr>
		<tr>
			<td class='mytablesubheader ' nowrap>" . TIME_LANG_TIMEZONE . "</td>
			<td nowrap><select name='timezone'>$timezone_select</select></td>
		</tr>
	";

    if (! WebIsSetup()) {
		echo "
		<tr>
			<td width='200' class='mytablesubheader' nowrap>" . NTPD_LANG_NTP_TIME_SERVER . "</td>
			<td>" . WebDropDownEnabledDisabled("autosync", $ntp_state) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&nbsp; </td>
			<td>" . 
				WebButtonUpdate("SetTime") . 
				WebButton("onclick", WEB_LANG_AUTOSYNC_NOW, WEBCONFIG_ICON_UPDATE, $buttonopts) . "&nbsp; 
				<span id='result'></span>
			</td>
		</tr>
		";
	}

	WebTableClose("100%");

	if (! WebIsSetup())
		WebFormClose();
}

// vim: ts=4
?>
