<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2006 Point Clark Networks.
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
require_once("../../api/System.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-shutdown.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$system = new System();

try {
	if (isset($_POST['ShutdownConfirm']) && isset($_POST['shutdowntype'])) {
		if ($_POST['shutdowntype'] == "shutdown")
			$system->Shutdown();
		else if ($_POST['shutdowntype'] == "restart")
			$system->Restart();
	}
} catch (Exception $e) {
    WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

if (isset($_POST['ShutdownRestart']) && isset($_POST['shutdowntype'])) {
    DisplayConfirm($_POST['shutdowntype']);
} else if (isset($_POST['ShutdownConfirm']) && isset($_POST['shutdowntype'])) {
	WebDialogWarning(WEB_LANG_GOING_OFFLINE);
} else {
    DisplayShutdown();
}

WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayShutdown()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayShutdown()
{
	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "350");
	echo "
		<tr>
			<td class='mytablesubheader'>" . WEB_LANG_SYSTEM . "</td>
			<td>
				<select name='shutdowntype'>
				<option value='restart'>" . SYSTEM_LANG_RESTART . "</option>
				<option value='shutdown'>" . SYSTEM_LANG_SHUTDOWN . "</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&nbsp; </td>
			<td>" . WebButtonUpdate("ShutdownRestart") . "</td>
		</tr>
	";
	WebTableClose("350");
	WebFormClose();
}

///////////////////////////////////////////////////////////////////////////////
//
// DisplayConfirm()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayConfirm($type)
{
	if ($type == "shutdown")
		$display = WEB_LANG_SHUTDOWN_CONFIRM;
	else if ($type == "restart")
		$display = WEB_LANG_RESTART_CONFIRM;

	WebFormOpen();
	WebDialogWarning(
	     "<p>" . $display . "</p>" .
	     "<p>" . WebButtonContinue("ShutdownConfirm") . " " . WebButtonCancel("Cancel") . "<br />
	      <input type='hidden' name='shutdowntype' value='$type' /></p>
	");
	WebFormClose();
}

// vim: ts=4
?>
