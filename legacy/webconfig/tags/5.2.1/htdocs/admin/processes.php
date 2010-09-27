<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2008 Point Clark Networks.
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
require_once("../../api/ProcessManager.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-processes.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$processes = new ProcessManager();

if (isset($_POST['DeleteID']))
{
	$pids = isset($_POST['id']) ? $_POST['id'] : array();

	if (!is_array($pids))
		$pids = array($pids);

	try {
		$processes->Kill($pids);
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayStatus();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayStatus
//
///////////////////////////////////////////////////////////////////////////////

function DisplayStatus()
{
	WebFormOpen();
	WebDialogInfo(
		WEB_LANG_KILL_HELP . '<br><br>' .
		WebButton("IdleButton", WEB_LANG_SHOW_IDLE, WEBCONFIG_ICON_TOGGLE,
			array('type' => 'button', 'onclick' => 'toggleIdle()')) .
		WebButton("CommandButton", WEB_LANG_FULL_CMD, WEBCONFIG_ICON_TOGGLE,
			array('type' => 'button', 'onclick' => 'toggleFcmd()')) .
		WebButton("PauseButton", WEB_LANG_CONTINUE, WEBCONFIG_ICON_CANCEL,
			array('type' => 'button', 'onclick' => 'loopIt()')) .
		WebButton("DeleteID", PROCESS_LANG_KILL, WEBCONFIG_ICON_DELETE)
	);
	echo "<div id='topdiv' align='center'>" . WEBCONFIG_ICON_LOADING . "</div>";
	WebFormClose();
}

// vim: syntax=php ts=4
?>
