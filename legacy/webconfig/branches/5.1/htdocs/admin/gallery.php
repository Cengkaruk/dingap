<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2007 Point Clark Networks.
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
require_once("../../api/Gallery.class.php");
require_once("../../api/Hostname.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-gallery.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$gallery = new Gallery();

try {
	if (isset($_POST['SetupMode']))
		$gallery->SetMode(Gallery::CONSTANT_MODE_SETUP);
	else if (isset($_POST['SecureMode']))
		$gallery->SetMode(Gallery::CONSTANT_MODE_SECURE);
} catch (Exception $e) {
	WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayGallery();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayGallery()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayGallery()
{
	global $gallery;

	try {
		$mode = $gallery->GetMode();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	if ($mode == Gallery::CONSTANT_MODE_SECURE)
		$submit = WebButton("SetupMode", WEB_LANG_CONFIGURE_GALLERY, WEBCONFIG_ICON_CONTINUE);
	else
		$submit = WebButton("SecureMode", WEB_LANG_SECURE_GALLERY, WEBCONFIG_ICON_CONTINUE);

	WebDialogInfo(WEB_LANG_GOTCHA);

	WebFormOpen();
	WebTableOpen(WEB_LANG_CONFIG_TITLE, "400");
	echo "
	 <tr>
	  <td class='mytableheader'>" . WEB_LANG_FIRST_TIME . "</td>
	 </tr>
	 <tr>
	  <td> " . WEB_LANG_STEP_ONE . "</td>
	 </tr>
	 <tr>
	  <td align='center'>$submit</td>
	 </tr>
	";
	WebTableClose("400");
	WebFormClose();
}

// vim: syntax=php ts=4
?>
