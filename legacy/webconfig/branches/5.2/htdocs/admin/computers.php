<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 Point Clark Networks.
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
//
// There is some logic in this page (e.g. printer handling, modes) that should
// probably get pushed into some other higher level class.
//
///////////////////////////////////////////////////////////////////////////////

require_once("../../gui/Webconfig.inc.php");
require_once("../../api/Samba.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-computers.png", WEB_LANG_PAGE_INTRO);
WebCheckUserDatabase();

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$samba = new Samba();

try {
	if (isset($_POST['Delete']))
		$samba->DeleteComputer(key($_POST['Delete']));
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['ConfirmDelete']))
	DisplayConfirm(key($_POST['ConfirmDelete']));
else
	DisplayComputers();

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayComputers()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayComputers()
{
	global $samba;

	try {
		$computers = $samba->GetComputers();
		$thisbox = $samba->GetNetbiosName() . "\$";
	} catch (SambaNotInitializedException $e) {
		WebDialogWarning(SAMBA_LANG_SAMBA_NOT_ACTIVE);
		return;
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	$computerhtml = "";

	if (count($computers) === 0) {
		$computerhtml = "<tr><td colspan='3' align='center'>" . SAMBA_LANG_NO_COMPUTERS . "</td></tr>";
	} else {
		$samba_server = $_SESSION['system_osname'] . " " . SAMBA_LANG_SERVER;

		ksort($computers);

		foreach ($computers as $computer => $detail) {
			$button = (strtolower($computer) == strtolower($thisbox)) ? $samba_server : WebButtonDelete("ConfirmDelete[$computer]");
			$computerhtml .= "
				<tr>
					<td>" . preg_replace('/\$$/', "", $computer) . "</td>
					<td>" . $detail['SID'] . "</td>
					<td>" . $button . "</td>
				</tr>
			";
		}
	}

	WebFormOpen();
	WebTableOpen(SAMBA_LANG_COMPUTERS);
	WebTableHeader(SAMBA_LANG_COMPUTER_NAME . "| SID - " . SAMBA_LANG_SECURITY_IDENTIFIER . "|");
	echo $computerhtml;
	WebTableClose();
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirm()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirm($computer)
{
	echo "<div style='width: 400; margin: 0 auto;'>";
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_CONFIRM);
	echo "
		<tr>
			<td align='center'>
			<br />
			<p>" . WEB_LANG_CONFIRM_DELETE . "<br>
			<b> $computer</b></p>
			<br />". WebButtonDelete("Delete[$computer]") . " " . WebButtonCancel("Cancel") . "
			</td>
		</tr>
	";
	WebTableClose();
	WebFormClose();
	echo "</div>";
}

?>
