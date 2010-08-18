<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2002-2007 Point Clark Networks.
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
require_once('../../api/Webconfig.class.php');
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();

$webconfig = new Webconfig();

// Handle template update before sending out header
$template_error = "";

if (isset($_POST['SetTemplate'])) {
	try {
		$webconfig->SetTemplate($_POST['template']);
		$_SESSION['system_template'] = $_POST['template'];
	} catch (Exception $e) {
		$template_error = $e->GetMessage();
	}
}

WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-settings.png", WEB_LANG_PAGE_INTRO);

if ($template_error)
	WebDialogWarning($template_error);

DisplayTemplate();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayTemplate()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayTemplate()
{
	global $webconfig;

	try {
		$template = $webconfig->GetTemplate();
		$templatelist = $webconfig->GetTemplateList();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_PAGE_TITLE, "450");

	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . WEB_LANG_THEME . "</td>
			<td nowrap>" . WebDropDownHash("template", $template, $templatelist) . "</td>
		</tr>
		<tr>
			<td class='mytablesubheader'>&nbsp; </td>
			<td nowrap>" . WebButtonUpdate("SetTemplate") . "</td>
		</tr>
	";
	WebTableClose("450");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
