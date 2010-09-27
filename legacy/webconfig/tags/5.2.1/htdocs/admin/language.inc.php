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
// DisplayLanguage()
//
///////////////////////////////////////////////////////////////////////////////

function DisplayLanguage()
{
	global $locale;

	try {
		$languages = $locale->GetLanguageInfo();
		$langcode = $locale->GetLanguageCode();
	} catch (Exception $e) {
		WebDialogWarning($e->GetMessage());
		return;
	}

	// Create dropdowns
	//-----------------

	$lang_options = "";

	foreach ($languages as $language) {
		if ($language["code"] == $langcode)
			$selected = "selected";
		else
			$selected = "";

		$lang_options .= "<option $selected value='" . $language["code"] . "'>" . $language["description"] . "</option>";
	}

	WebFormOpen();
	WebTableOpen(WEB_LANG_LANGUAGE_CONFIG_TITLE);
	echo "
		<tr>
			<td class='mytablesubheader' nowrap>" . LOCALE_LANG_LANGUAGE . "</td>
			<td nowrap><select name='langcode'>$lang_options</select></td>
		</tr>
	";
	if (! WebIsSetup()) {
		echo "
			<tr>
				<td class='mytablesubheader'>&#160; </td>
				<td nowrap>" . WebButtonUpdate("SetLocale") . "</td>
			</tr>
		";
	}

	WebTableClose();

	if (! WebIsSetup())
		WebFormClose();
}

// vim: syntax=php ts=4
?>
