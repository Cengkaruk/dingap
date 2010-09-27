<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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
require_once("../../api/Awstats.class.php");
require_once(GlobalGetLanguageTemplate(__FILE__));

require_once("../../gui/Report.class.php"); // TODO: for locale tag below

///////////////////////////////////////////////////////////////////////////////
//
// Header
//
///////////////////////////////////////////////////////////////////////////////

WebAuthenticate();
WebHeader(WEB_LANG_PAGE_TITLE);
WebDialogIntro(WEB_LANG_PAGE_TITLE, "/images/icon-httpd.png", WEB_LANG_PAGE_INTRO);

///////////////////////////////////////////////////////////////////////////////
//
// Handle Update
//
///////////////////////////////////////////////////////////////////////////////

$awstats = new Awstats();

try {
	if (isset($_POST['UpdatePassword']) && isset($_POST['password']))
		$awstats->SetPassword($_POST['password']);
} catch (Exception $e) {
	 WebDialogWarning($e->GetMessage());
}

///////////////////////////////////////////////////////////////////////////////
//
// Main
//
///////////////////////////////////////////////////////////////////////////////

DisplayPassword();
DisplayList();
WebFooter();

///////////////////////////////////////////////////////////////////////////////
// F U N C T I O N S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
//
// DisplayPassword
//
///////////////////////////////////////////////////////////////////////////////

function DisplayPassword()
{
	WebFormOpen();
	WebTableOpen(LOCALE_LANG_PASSWORD, "400");
	echo "
	  <tr>
		<td class='mytablesubheader' nowrap>" . LOCALE_LANG_USERNAME . "</td>
	    <td>awstats</td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>" . LOCALE_LANG_PASSWORD . "</td>
	    <td><input type='text' name='password' value='' /></td>
	  </tr>
	  <tr>
		<td class='mytablesubheader' nowrap>&#160; </td>
	    <td>" . WebButtonUpdate("UpdatePassword") . "</td>
	  </tr>
	";
	WebTableClose("400");
	WebFormClose();
}


///////////////////////////////////////////////////////////////////////////////
//
// DisplayList
//
///////////////////////////////////////////////////////////////////////////////

function DisplayList()
{
	global $awstats;

	try {
		$list = $awstats->GetDomainList();
	} catch (Exception $e) {
		 WebDialogWarning($e->GetMessage());
		 return;
	}

	$menu = "";

	foreach ($list as $reportname)
		$menu .= "<li><a target='_blank' href='/cgi/awstats.pl?config=$reportname'>$reportname</a><br> ";

	if (! $menu) {
		WebDialogWarning(REPORT_LANG_NO_STATS);
		return;
	}

	WebTableOpen(WEB_LANG_DOMAIN_REPORTS_TITLE, "400");
	echo "
		<tr>
			<td class='mytableheader'>" . WEB_LANG_DOMAIN . "</td>
		</tr>
		<tr>
			<td><br><ul>$menu</ul></td>
		</tr>
	";
	WebTableClose("400");
}

// vim: syntax=php ts=4
?>
