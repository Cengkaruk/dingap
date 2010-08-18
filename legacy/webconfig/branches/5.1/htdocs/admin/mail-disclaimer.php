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
require_once("../../api/Altermime.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-mail-disclaimer.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$disclaimer = new Altermime();

$text = isset($_POST['text']) ? $_POST['text'] : "";
$state = isset($_POST['state']) ? true : false;

try {
	if (isset($_POST['Update'])){
		$disclaimer->SetDisclaimerPlaintext($text);
		$disclaimer->SetDisclaimerState($state);
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
	$errors = $disclaimer->GetValidationErrors(true);

	if (empty($errors)) {
		$state = $disclaimer->GetDisclaimerState();
		$text = $disclaimer->GetDisclaimerPlaintext();
	} else {
		WebDialogWarning($errors);
	}
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}


DisplayDisclaimer($state, $text);
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// Display Disclaimer
//
///////////////////////////////////////////////////////////////////////////////

function DisplayDisclaimer($state, $text)
{
	global $disclaimer;

	$checked = ($state) ? "checked" : "";

	$state_checkbox = "<input type='checkbox' name='state' $checked>";

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIGURE_DISCLAIMER, "100%");
	echo "
		<tr>
			<td width='20%' class='mytablesubheader' nowrap>" . LOCALE_LANG_ENABLED . "</td>
			<td nowrap>" . $state_checkbox . "</td>
		</tr>
		<tr>
			<td valign='top' class='mytablesubheader' nowrap>" . ALTERMIME_LANG_TEXT_DISCLAIMER . "</td>
			<td><textarea rows='10' cols='50' name='text'>$text</textarea></td>
		</tr>
		<tr>
			<td class='mytablesubheader' nowrap>&nbsp; </td>
			<td>" . WebButtonUpdate("Update") . "</td>
		</tr>
	";
	WebTableClose("100%");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
