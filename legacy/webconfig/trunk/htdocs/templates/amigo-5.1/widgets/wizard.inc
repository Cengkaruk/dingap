<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2009 Point Clark Networks.
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

$menu = "";
$count = 1;
$class = "clearos_wizardcomplete";

$menu .= "<div class='clearos_wizardtitle'><span class='clearos_wizardcommon'>Installation Wizard</span></div>\n";

foreach ($menuitems as $menuitem => $title) {
	if ($menuitem == $highlight)
		$class = "clearos_wizardcurrent";

	$menu .= "<div class='$class'><span class='clearos_wizardcommon'>$title</span></div>\n";

	if ($menuitem == $highlight)
		$class = "clearos_wizardtodo";

	$count++;
}

echo "
	<div class='ui-widget ui-state-highlight ui-corner-all' id='clearos_wizardmenu'>
		$menu
    </div>
";

// vim: syntax=php ts=4
?>
