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
$class = "wizardcomplete";

foreach ($menuitems as $menuitem => $title) {
	if ($menuitem == $highlight)
		$class = "wizardcurrent";

	$menu .= "<div class='$class'><span class='wizardcommon'>$title</span></div>\n";
	//$menu .= "<div class='$class'><span class='wizardcommon'><a href='?step=$menuitem'>$title</a></span></div>\n";

	if ($menuitem == $highlight)
		$class = "wizardtodo";

	$count++;
}

echo "
	<table cellspacing='0' cellpadding='0' border='0' width='100%'>
		<tr>
			<td class='menu-header-left'>&nbsp; </td>
			<td class='menu-header-middle'>&nbsp;</td>
			<td class='menu-header-right'>&nbsp; </td>
		</tr>
	</table>
	<table cellspacing='0' cellpadding='0' width='200' border='0'>
		<tr>
			<td class='table-left' width='3'>&nbsp;</td>
			<td valign='top' width='194' class='menu-body-middle'>
				<div id='ccmenu' class='yuimenu'>
					<div id='menuwidth' class='bd'>
						$menu
					</div>
				</div>
				&nbsp;
			</td>
			<td class='table-right' width='3'>&nbsp; </td>
		</tr>
	</table>
	<table cellspacing='0' cellpadding='0' border='0' width='100%'>
		<tr>
			<td class='table-footer-left'> </td>
			<td class='table-footer-middle'>&nbsp; </td>
			<td class='table-footer-right'>&nbsp; </td>
		</tr>
	</table>
	<br>
";

// vim: syntax=php ts=4
?>
